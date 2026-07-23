<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitAudioForAiAnalysis;
use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\AudioAsset;
use App\Models\AuditLog;
use App\Models\AudioVersion;
use App\Models\Category;
use App\Models\Language;
use App\Models\Programme;
use App\Models\Station;
use App\Models\Workflow;
use App\Services\AudioProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AudioAssetController extends Controller
{
    private const CONTENT_TYPES = [
        'song', 'programme', 'podcast', 'story', 'news', 'interview', 'drama',
        'speech', 'jingle', 'psa', 'advert', 'voice_over', 'historical',
    ];

    public function index(Request $request): View
    {
        $assets = AudioAsset::query()
            ->with(['station', 'category', 'uploader'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('title_bn', 'like', '%'.$request->string('q').'%')
                ->orWhere('archive_no', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('content_type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('station'), fn ($q) => $q->where('station_id', $request->integer('station')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.assets.index', [
            'assets' => $assets,
            'stations' => Station::query()->orderBy('name')->pluck('name', 'id'),
            'contentTypes' => self::CONTENT_TYPES,
            'statuses' => [
                'analyzing', 'ai_flagged', 'ai_rejected', 'draft', 'pending_qc', 'qc_failed',
                'in_review', 'pending_approval', 'approved', 'published', 'rejected', 'unpublished', 'archived',
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.assets.form', ['asset' => null] + $this->formOptions());
    }

    public function store(Request $request, AudioProcessor $processor): RedirectResponse
    {
        if ($error = $this->uploadError($request)) {
            return back()->withInput()->withErrors(['audio_file' => $error]);
        }

        $data = $this->validated($request);

        $asset = AudioAsset::query()->create($data + [
            'archive_no' => AudioAsset::nextArchiveNo(),
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'uploaded_by' => $request->user()->id,
            // FR-ING / M16 — held here until the AI postmortem (duplicate /
            // violence / anti-government / transcription) reports back.
            'status' => 'analyzing',
            'source' => $data['source'] ?? 'upload',
        ]);

        $this->ingestFile($request, $asset, $processor);
        $this->triggerAiAnalysis($asset);

        return redirect()->route('admin.assets.show', $asset)
            ->with('success', "Asset {$asset->archive_no} ingested and is being analyzed by the AI safety check. This page will show the transcript and the outcome shortly.");
    }

    /**
     * FR-ING-01 — ingest / replace the audio for an existing asset (one file
     * at a time). Stores the file, runs technical analysis (M02) and
     * (re)registers the preservation master + a derived online version.
     */
    public function uploadMaster(Request $request, AudioAsset $asset, AudioProcessor $processor): RedirectResponse
    {
        $this->authorize('assets.upload');

        if ($error = $this->uploadError($request)) {
            return back()->with('error', $error);
        }

        $this->ingestFile($request, $asset, $processor, replace: true);

        // Replaced bytes are new content — re-run the AI safety check on them
        // exactly as on first upload.
        $asset->update(['status' => 'analyzing']);
        $this->triggerAiAnalysis($asset);

        return back()->with('success', 'Audio ingested and is being re-analyzed by the AI safety check.');
    }

    /** Dispatch the async duplicate/violence/anti-government/transcription check (M16). */
    private function triggerAiAnalysis(AudioAsset $asset): void
    {
        SubmitAudioForAiAnalysis::dispatch($asset->id);
    }

    /**
     * Validate the uploaded audio and return a clear, actionable error message
     * (or null if valid). Avoids the fragile content-sniffing `mimes` rule —
     * audio formats (m4a→audio/mp4, etc.) frequently mis-detect — and turns
     * PHP's opaque "failed to upload" into a real explanation.
     */
    private function uploadError(Request $request): ?string
    {
        if (! $request->hasFile('audio_file')) {
            $limit = ini_get('upload_max_filesize');
            $post = ini_get('post_max_size');

            return "No file was received. The file may exceed the server upload limit "
                ."(upload_max_filesize={$limit}, post_max_size={$post}). Increase these limits or upload a smaller file.";
        }

        $file = $request->file('audio_file');

        if (! $file->isValid()) {
            return match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'The file exceeds the server upload limit (upload_max_filesize='.ini_get('upload_max_filesize').'). '
                    .'Increase upload_max_filesize/post_max_size (already set to 512M in the Docker image).',
                UPLOAD_ERR_PARTIAL => 'The upload was interrupted — please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'The server has no writable temp directory for uploads.',
                UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file to disk.',
                default => 'The file failed to upload (error code '.$file->getError().').',
            };
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, config('audio.accepted_formats'), true)) {
            return 'Unsupported format ".'.$ext.'". Allowed: '.implode(', ', config('audio.accepted_formats')).'.';
        }

        $maxMb = (int) config('audio.max_upload_mb', 512);
        if ($file->getSize() > $maxMb * 1024 * 1024) {
            return 'The file is '.round($file->getSize() / 1048576, 1).' MB — larger than the '.$maxMb.' MB limit.';
        }

        return null;
    }

    /**
     * Store the uploaded file on the archive disk, analyse it, and (re)build
     * the version family: an immutable preservation master + a default online
     * streaming version. The master file is never overwritten in place — a
     * replacement is stored under a new versioned filename (FR-REP-03).
     */
    private function ingestFile(Request $request, AudioAsset $asset, AudioProcessor $processor, bool $replace = false): void
    {
        $file = $request->file('audio_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'wav');
        $disk = Storage::disk('local');

        $version = (int) $asset->versions()->max('id') + 1;
        $masterPath = "masters/{$asset->archive_no}-m{$version}.{$ext}";
        $onlinePath = "streams/{$asset->archive_no}-{$version}.{$ext}";

        $disk->putFileAs('masters', $file, basename($masterPath));
        // Without a transcoder we serve the same bytes as the "online" proxy;
        // with ffmpeg present the ingest pipeline would down-encode here.
        $disk->copy($masterPath, $onlinePath);

        $absolute = $disk->path($masterPath);
        $meta = $processor->analyze($absolute);
        $checksum = hash_file('sha256', $absolute);
        $sizeBytes = $file->getSize();

        // Update the asset's technical metadata (FR-ING-09).
        $asset->update(array_filter([
            'original_filename' => $file->getClientOriginalName(),
            'format' => $ext,
            'duration_seconds' => $meta['duration_seconds'] ?: $asset->duration_seconds,
            'sample_rate' => $meta['sample_rate'],
            'bit_depth' => $meta['bit_depth'],
            'channels' => $meta['channels'],
            'bitrate_kbps' => $meta['bitrate_kbps'],
            'loudness_lufs' => $meta['loudness_lufs'],
            'peak_db' => $meta['peak_db'],
            'silence_percent' => $meta['silence_percent'],
            'size_bytes' => $sizeBytes,
            'checksum_sha256' => $checksum,
            'waveform_peaks' => $meta['waveform_peaks'],
        ], fn ($v) => $v !== null));

        if ($replace) {
            // Keep prior masters immutable; just add the new version family.
            $asset->versions()->where('is_default', true)->update(['is_default' => false]);
        }

        $master = AudioVersion::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'preservation_master',
            'label' => 'Preservation Master ('.strtoupper($ext).')',
            'file_path' => $masterPath,
            'format' => $ext,
            'bitrate_kbps' => $meta['bitrate_kbps'],
            'duration_seconds' => $meta['duration_seconds'],
            'size_bytes' => $sizeBytes,
            'checksum_sha256' => $checksum,
            'is_default' => false,
            'created_by' => $request->user()->id,
        ]);

        AudioVersion::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'online',
            'label' => 'Online Streaming',
            'file_path' => $onlinePath,
            'format' => $ext,
            'bitrate_kbps' => min(320, $meta['bitrate_kbps'] ?? 192),
            'duration_seconds' => $meta['duration_seconds'],
            'size_bytes' => $disk->size($onlinePath),
            'checksum_sha256' => hash_file('sha256', $disk->path($onlinePath)),
            'is_default' => true,
            'derived_from_id' => $master->id,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record('asset_ingested', $asset, null, [
            'file' => $file->getClientOriginalName(), 'analyzer' => $meta['analyzer'],
        ], "Audio ingested for {$asset->archive_no} ({$meta['analyzer']})");
    }

    /** Persist browser-computed waveform peaks (used when no server ffmpeg). */
    public function storePeaks(Request $request, AudioAsset $asset): \Illuminate\Http\JsonResponse
    {
        $this->authorize('assets.edit');
        $data = $request->validate([
            'peaks' => ['required', 'array', 'min:8', 'max:2000'],
            'peaks.*' => ['numeric', 'between:0,1'],
        ]);
        $asset->update(['waveform_peaks' => array_map(fn ($p) => round((float) $p, 3), $data['peaks'])]);

        return response()->json(['message' => 'Waveform saved.']);
    }

    public function show(AudioAsset $asset): View
    {
        $asset->load([
            'station', 'department', 'programme', 'category', 'language', 'uploader',
            'versions.creator', 'tags', 'artists', 'rightsRecords.rightsHolder',
            'qcReports.reviewer', 'transcripts', 'aiSuggestions', 'song.album',
            'approvals.currentStage', 'approvals.actions.user', 'editSessions.editor',
            'latestAiAnalysisJob.reviewer',
        ]);

        $stats = $asset->dailyStats()->orderByDesc('stat_date')->take(14)->get()->reverse()->values();

        return view('admin.assets.show', compact('asset', 'stats'));
    }

    public function edit(AudioAsset $asset): View
    {
        return view('admin.assets.form', ['asset' => $asset] + $this->formOptions());
    }

    public function update(Request $request, AudioAsset $asset): RedirectResponse
    {
        $asset->update($this->validated($request));

        return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset metadata updated.');
    }

    public function destroy(Request $request, AudioAsset $asset): RedirectResponse
    {
        // FR-REP-03 — masters are protected: only Super Administrators delete assets.
        if (! $request->user()->hasRole('Super Administrator')) {
            return back()->with('error', 'Only a Super Administrator may delete archive assets (FR-REP-03).');
        }

        $asset->delete();
        AuditLog::record('asset_deleted', $asset, null, null, "Asset {$asset->archive_no} deleted");

        return redirect()->route('admin.assets.index')->with('success', 'Asset removed (soft-deleted; master retained).');
    }

    /** Submit into the configured approval workflow (M13). */
    public function submitForApproval(Request $request, AudioAsset $asset): RedirectResponse
    {
        if (in_array($asset->status, ['analyzing', 'ai_flagged'], true)) {
            return back()->with('error', 'This asset is still being checked by the AI safety analysis.');
        }

        if ($asset->status === 'ai_rejected') {
            return back()->with('error', 'This asset was rejected by the AI Reviewer and cannot be published (M16).');
        }

        if ($asset->approvals()->pending()->exists()) {
            return back()->with('error', 'This asset already has a pending approval.');
        }

        $workflow = Workflow::forContentType($asset->content_type);
        if (! $workflow) {
            return back()->with('error', 'No active workflow is configured for this content type.');
        }

        $stage = $workflow->stages()->first();

        $approval = Approval::query()->create([
            'approvable_type' => 'audio_asset',
            'approvable_id' => $asset->id,
            'workflow_id' => $workflow->id,
            'current_stage_id' => $stage?->id,
            'status' => 'pending',
            'submitted_by' => $request->user()->id,
            'submitted_at' => now(),
        ]);

        ApprovalAction::query()->create([
            'approval_id' => $approval->id,
            'workflow_stage_id' => $stage?->id,
            'user_id' => $request->user()->id,
            'action' => 'submitted',
            'comments' => $request->string('comments') ?: 'Submitted for approval.',
        ]);

        $asset->update(['status' => 'in_review']);

        return back()->with('success', "Submitted into workflow: {$workflow->name}.");
    }

    /** FR-CPR-04/05 — publication requires cleared rights + approval. */
    public function publish(AudioAsset $asset): RedirectResponse
    {
        if ($asset->rights_status !== 'cleared') {
            return back()->with('error', 'Publication blocked: rights are not cleared (FR-CPR-05).');
        }

        if (! in_array($asset->status, ['approved', 'unpublished'], true)) {
            return back()->with('error', 'Only fully approved assets can be published (FR-WRK-07).');
        }

        if (! $asset->versions()->where('version_type', 'online')->exists()) {
            return back()->with('error', 'No online streaming version exists for this asset.');
        }

        $asset->update([
            'status' => 'published',
            'access_level' => 'public',
            'published_at' => $asset->published_at ?? now(),
        ]);

        AuditLog::record('asset_published', $asset, null, null, "Asset {$asset->archive_no} published");

        return back()->with('success', 'Asset is now live on the public platform.');
    }

    public function unpublish(AudioAsset $asset): RedirectResponse
    {
        $asset->update(['status' => 'unpublished', 'access_level' => 'internal']);
        AuditLog::record('asset_unpublished', $asset, null, null, "Asset {$asset->archive_no} unpublished");

        return back()->with('success', 'Asset removed from the public platform.');
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'content_type' => ['required', Rule::in(self::CONTENT_TYPES)],
            'station_id' => ['nullable', 'exists:stations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'source' => ['nullable', Rule::in(['upload', 'bulk', 'ftp', 'studio', 'live_recording', 'digitization', 'migration'])],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'format' => ['nullable', Rule::in(['wav', 'bwf', 'flac', 'mp3', 'aac', 'm4a', 'ogg', 'aiff'])],
            'recorded_on' => ['nullable', 'date'],
            'first_broadcast_on' => ['nullable', 'date'],
            'access_level' => ['required', Rule::in(['public', 'internal', 'restricted'])],
            'is_premium' => ['boolean'],
            'is_public_service' => ['boolean'],
            'allow_comments' => ['boolean'],
            'content_warning' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'stations' => Station::query()->orderBy('name')->pluck('name', 'id'),
            'programmes' => Programme::query()->orderBy('title')->pluck('title', 'id'),
            'categories' => Category::query()->where('type', 'content')->orderBy('name')->pluck('name', 'id'),
            'languages' => Language::query()->orderBy('name')->pluck('name', 'id'),
            'contentTypes' => self::CONTENT_TYPES,
        ];
    }

    private function fakeWaveform(): array
    {
        $peaks = [];
        for ($i = 0; $i < 160; $i++) {
            $peaks[] = round(min(1, max(0.05, 0.4 + 0.3 * sin($i / 8) + mt_rand(-20, 20) / 100)), 3);
        }

        return $peaks;
    }
}
