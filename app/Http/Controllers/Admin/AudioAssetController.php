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
use App\Models\RightsHolder;
use App\Models\RightsRecord;
use App\Models\Station;
use App\Models\Workflow;
use App\Services\AudioProcessor;
use App\Support\Notify;
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

    /**
     * Dropdown labels explaining where each content type leads: song /
     * programme / podcast flow into the public catalogue via their module;
     * the rest are archive classifications (attachable to programme episodes).
     */
    private const CONTENT_TYPE_OPTIONS = [
        'song' => 'Song — publishes via Songs (music library)',
        'programme' => 'Programme — publishes via Programme Episodes',
        'podcast' => 'Podcast — publishes via Podcast Episodes',
        'story' => 'Story — archive classification',
        'news' => 'News — archive classification',
        'interview' => 'Interview — archive classification',
        'drama' => 'Drama — archive classification',
        'speech' => 'Speech — archive classification',
        'jingle' => 'Jingle — archive classification',
        'psa' => 'PSA — archive classification',
        'advert' => 'Advert — ad campaign audio',
        'voice_over' => 'Voice over — archive classification',
        'historical' => 'Historical — archive classification',
    ];

    public function index(Request $request): View
    {
        $assets = AudioAsset::query()
            ->visibleTo($request->user())
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
                'analyzing', 'ai_review', 'ai_flagged', 'ai_rejected', 'draft',
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
        $this->authorizeRecordVisibility($asset);

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
        $this->authorizeRecordVisibility($asset);
        $data = $request->validate([
            'peaks' => ['required', 'array', 'min:8', 'max:2000'],
            'peaks.*' => ['numeric', 'between:0,1'],
        ]);
        $asset->update(['waveform_peaks' => array_map(fn ($p) => round((float) $p, 3), $data['peaks'])]);

        return response()->json(['message' => 'Waveform saved.']);
    }

    public function show(AudioAsset $asset): View
    {
        $this->authorizeRecordVisibility($asset);

        $asset->load([
            'station', 'department', 'programme', 'category', 'language', 'uploader',
            'versions.creator', 'tags', 'artists', 'rightsRecords.rightsHolder',
            'transcripts', 'aiSuggestions', 'song.album',
            'approvals.currentStage', 'approvals.actions.user', 'editSessions.editor',
            'latestAiAnalysisJob.reviewer',
        ]);

        $stats = $asset->dailyStats()->orderByDesc('stat_date')->take(14)->get()->reverse()->values();

        return view('admin.assets.show', compact('asset', 'stats'));
    }

    public function edit(AudioAsset $asset): View
    {
        $this->authorizeRecordVisibility($asset);

        return view('admin.assets.form', ['asset' => $asset] + $this->formOptions());
    }

    public function update(Request $request, AudioAsset $asset): RedirectResponse
    {
        $this->authorizeRecordVisibility($asset);
        $asset->update($this->validated($request));

        return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset metadata updated.');
    }

    public function destroy(Request $request, AudioAsset $asset): RedirectResponse
    {
        $this->authorizeRecordVisibility($asset);

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
        $this->authorizeRecordVisibility($asset);

        if (in_array($asset->status, ['analyzing', 'ai_review', 'ai_flagged'], true)) {
            return back()->with('error', 'This asset is awaiting AI moderation approval — every upload must be cleared there first.');
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

        // Notify the approvers whose role owns the first stage.
        if ($stage) {
            Notify::role($stage->approver_role, 'needs_approval',
                'Needs your approval',
                "{$request->user()->name} submitted “{$asset->title}” — stage: {$stage->name}.",
                route('admin.approvals.show', $approval),
                except: $request->user()->id);
        }

        return back()->with('success', "Submitted into workflow: {$workflow->name}.");
    }

    /**
     * FR-CPR-01/02 — after the approval workflow completes, the submitter
     * files the copyright documents + rights details ("Submit for Rights").
     * Creates the pending rights record the rights team reviews and clears —
     * which is what unlocks the Publish button.
     */
    public function submitForRights(Request $request, AudioAsset $asset): RedirectResponse
    {
        $this->authorizeRecordVisibility($asset);

        if ($asset->status !== 'approved') {
            return back()->with('error', 'Complete the approval workflow first — rights are submitted once the asset is approved.');
        }

        if ($asset->rights_status === 'approved') {
            return back()->with('error', 'Rights are already approved for this asset.');
        }

        if ($asset->rightsRecords()->whereIn('status', ['pending', 'approved'])->exists()) {
            return back()->with('error', 'A rights submission is already under review for this asset.');
        }

        $data = $request->validate([
            'rights_holder_id' => ['nullable', 'exists:rights_holders,id'],
            'holder_name' => ['nullable', 'required_without:rights_holder_id', 'string', 'max:255'],
            'holder_email' => ['nullable', 'email', 'max:255'],
            'rights_types' => ['required', 'array', 'min:1'],
            'rights_types.*' => [Rule::in(['broadcast', 'streaming', 'download', 'commercial'])],
            'territory' => ['required', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'royalty_required' => ['boolean'],
            'royalty_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ]);

        // Resolve the rights holder: an existing one, or register the named one.
        $holderId = $data['rights_holder_id'] ?? null;
        if (! $holderId) {
            $holderId = RightsHolder::query()->firstOrCreate(
                ['name' => $data['holder_name']],
                ['holder_type' => 'person', 'email' => $data['holder_email'] ?? null],
            )->id;
        }

        // Copyright documents live on the private disk — served only through
        // the gated download route.
        $documents = [];
        foreach ($request->file('documents', []) as $file) {
            $documents[] = [
                'path' => $file->store("rights-docs/{$asset->id}", 'local'),
                'name' => $file->getClientOriginalName(),
            ];
        }

        $record = RightsRecord::query()->create([
            'audio_asset_id' => $asset->id,
            'rights_holder_id' => $holderId,
            'rights_types' => $data['rights_types'],
            'territory' => $data['territory'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'royalty_required' => (bool) ($data['royalty_required'] ?? false),
            'royalty_notes' => $data['royalty_notes'] ?? null,
            'notes' => $data['notes'] ?? null,
            'documents' => $documents,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        $asset->update(['rights_status' => 'pending']);

        AuditLog::record('rights_submitted', $asset, null, [
            'rights_record_id' => $record->id, 'documents' => count($documents),
        ], "Rights submission for {$asset->archive_no} ({$record->id})");

        // Tell the rights team there is a submission to review.
        Notify::permission('rights.manage', 'rights_submitted',
            'Rights submission needs review',
            "{$request->user()->name} filed copyright documents for “{$asset->title}” (".count($documents).' file'.(count($documents) === 1 ? '' : 's').').',
            route('admin.rights-records.show', $record),
            except: $request->user()->id);

        return back()->with('success', 'Copyright documents submitted. The rights team will review and approve them — publishing unlocks once approved.');
    }

    /** FR-CPR-04/05 — publication requires approved rights + a completed workflow. */
    public function publish(AudioAsset $asset): RedirectResponse
    {
        $this->authorizeRecordVisibility($asset);

        if ($asset->rights_status !== 'approved') {
            return back()->with('error', 'Publication blocked: rights are not approved. Complete the rights record in Rights Records and set it to Approved first (FR-CPR-05).');
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
        $this->authorizeRecordVisibility($asset);
        $asset->update(['status' => 'unpublished', 'access_level' => 'internal']);
        AuditLog::record('asset_unpublished', $asset, null, null, "Asset {$asset->archive_no} unpublished");

        return back()->with('success', 'Asset removed from the public platform.');
    }

    /**
     * Choose which version of the family is streamed to the public (the
     * "Streaming" version). Works before publishing (selects what will go
     * live) and after (swaps the live audio instantly). The preservation
     * master is immutable and can never be the streaming version.
     */
    public function setStreamingVersion(AudioAsset $asset, AudioVersion $version): RedirectResponse
    {
        $this->authorize('assets.publish');
        $this->authorizeRecordVisibility($asset);

        abort_unless($version->audio_asset_id === $asset->id, 404);

        if ($version->isMaster() || $version->version_type === 'preview') {
            return back()->with('error', 'The master and the short preview clip cannot be the streaming version — choose a full online or edited version.');
        }

        // Promote this version; demote the rest of the family.
        $asset->versions()->update(['is_default' => false]);
        $version->update(['is_default' => true]);

        AuditLog::record('asset_streaming_version_set', $asset, null, [
            'version_id' => $version->id, 'version_type' => $version->version_type,
        ], "Streaming version for {$asset->archive_no} set to #{$version->id}");

        $label = $version->label ?: ucfirst(str_replace('_', ' ', $version->version_type));
        $message = $asset->isPublished()
            ? "Now streaming “{$label}” to the public."
            : "“{$label}” selected — it will go live when you publish.";

        return back()->with('success', $message);
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
            'contentTypes' => self::CONTENT_TYPE_OPTIONS,
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
