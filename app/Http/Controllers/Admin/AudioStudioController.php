<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RenderEditSession;
use App\Models\AudioAsset;
use App\Models\AudioMarker;
use App\Models\AudioVersion;
use App\Models\AuditLog;
use App\Models\EditSession;
use App\Services\AudioRenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Audio Studio — the professional visual interface (Audio Visualization spec):
 * interactive waveform, spectrogram, real-time spectrum, loudness meter,
 * playback heatmap, silence/noise & speaker/segment overlays, non-destructive
 * editing (EDL), AI/manual content markers and original-vs-edited compare.
 *
 * Masters are preserved; edits create new derived versions (FR-EDT-01/05/06).
 */
class AudioStudioController extends Controller
{
    public function show(Request $request, AudioAsset $asset): View
    {
        $this->authorizeRecordVisibility($asset);

        $asset->load(['versions' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id'), 'song', 'programme']);

        // The version to load into the Studio — ?version=<id> lets the operator
        // open any version of the family (master, online, edited, restored…).
        $selected = ($request->integer('version') ? $asset->versions->firstWhere('id', $request->integer('version')) : null)
            ?? $asset->versions->firstWhere('is_default', true)
            ?? $asset->versions->first();

        // Speaker/segment strips from verified & draft transcripts (FR-AIF).
        $segments = $asset->transcripts()->get()->flatMap(function ($t) {
            return collect($t->lines ?? [])->map(fn ($line) => [
                'start' => (float) ($line['start'] ?? 0),
                'end' => (float) ($line['end'] ?? 0),
                'speaker' => $line['speaker'] ?? 'Speaker',
                'text' => $line['text'] ?? '',
            ]);
        })->values();

        // Chapter markers from podcast episodes, if any.
        $chapters = optional($asset->programme)->exists
            ? collect()
            : collect(\App\Models\PodcastEpisode::query()->where('audio_asset_id', $asset->id)->value('chapters') ?? []);

        $markers = $asset->markers()->with('creator')->get();

        // Latest 14-day heat-map (second-by-second listening density, M19).
        $heatmap = $asset->dailyStats()->latest('stat_date')->value('heatmap') ?? [];

        $editSessions = $asset->editSessions()->with('editor')->latest()->get();

        return view('admin.assets.studio', [
            'asset' => $asset,
            'versions' => $asset->versions,
            'defaultVersion' => $selected,
            'selectedVersionId' => $selected?->id,
            'segments' => $segments,
            'chapters' => $chapters,
            'markers' => $markers,
            'markersData' => $markers->map($this->markerArray())->values(),
            // Only persist browser-computed peaks back to the asset when viewing
            // its primary version (avoid overwriting with a derived version's shape).
            'needsPeaks' => empty($asset->waveform_peaks) && ($selected?->is_default ?? false),
            'heatmap' => array_values($heatmap ?: []),
            'editSessions' => $editSessions,
            'markerTypes' => array_keys(AudioMarker::COLORS),
            'markerColors' => AudioMarker::COLORS,
        ]);
    }

    /**
     * Stream any version's bytes to authorized staff for professional review —
     * including the preservation master (QC/restoration). Range-capable so the
     * waveform/spectrogram and seeking work. Never exposed to the public.
     */
    public function streamVersion(Request $request, AudioAsset $asset, AudioVersion $version): BinaryFileResponse
    {
        $this->authorizeRecordVisibility($asset);
        abort_unless($version->audio_asset_id === $asset->id, 404);

        $disk = Storage::disk('local');

        // Prefer the real uploaded file; otherwise fall back to demo media so
        // the Studio is always playable (seeded assets have no real bytes).
        $relative = $version->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : $this->demoTrackFor($asset->id, $disk);

        abort_unless($relative && $disk->exists($relative), 404, 'Media file not available.');

        $mime = match (strtolower(pathinfo($relative, PATHINFO_EXTENSION))) {
            'wav' => 'audio/wav', 'mp3' => 'audio/mpeg', 'm4a', 'aac', 'mp4' => 'audio/mp4',
            'ogg', 'oga', 'opus' => 'audio/ogg', 'flac' => 'audio/flac', default => 'application/octet-stream',
        };

        return response()->file($disk->path($relative), [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    /**
     * EBU R128 loudness analysis of the loaded version: integrated LUFS, LRA,
     * true-peak and a short-term loudness-over-time curve (read-only).
     */
    public function loudness(Request $request, AudioAsset $asset, AudioRenderService $renderer): JsonResponse
    {
        $versionId = (int) $request->input('version_id');
        $version = ($versionId ? $asset->versions()->find($versionId) : null)
            ?? $asset->versions()->where('is_default', true)->first()
            ?? $asset->versions()->first();

        $disk = Storage::disk('local');
        $relative = $version && $version->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : $this->demoTrackFor($asset->id, $disk);

        if (! $relative || ! $disk->exists($relative)) {
            return response()->json(['ok' => false, 'error' => 'Media file not available.'], 404);
        }

        $res = $renderer->measureLoudness($disk->path($relative));

        return response()->json($res, ($res['ok'] ?? false) ? 200 : 422);
    }

    /** Signal statistics (ffmpeg astats) for the Studio "Signal Analysis" panel. */
    public function astats(Request $request, AudioAsset $asset, AudioRenderService $renderer): JsonResponse
    {
        $versionId = (int) $request->input('version_id');
        $version = ($versionId ? $asset->versions()->find($versionId) : null)
            ?? $asset->versions()->where('is_default', true)->first()
            ?? $asset->versions()->first();

        $disk = Storage::disk('local');
        $relative = $version && $version->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : $this->demoTrackFor($asset->id, $disk);

        if (! $relative || ! $disk->exists($relative)) {
            return response()->json(['ok' => false, 'error' => 'Media file not available.'], 404);
        }

        $res = $renderer->measureAstats($disk->path($relative));

        return response()->json($res, ($res['ok'] ?? false) ? 200 : 422);
    }

    /**
     * Resolve a demo track for an asset, generating the demo set on demand if
     * the container has none yet (so streaming never 404s).
     */
    private function demoTrackFor(int $assetId, $disk): ?string
    {
        $preferred = sprintf('demo-audio/track-%02d.wav', ($assetId % 12) + 1);
        if ($disk->exists($preferred)) {
            return $preferred;
        }

        $existing = collect($disk->files('demo-audio'))->first(fn ($f) => str_ends_with($f, '.wav'));
        if ($existing) {
            return $existing;
        }

        // None present — synthesize the demo set once, then use it.
        try {
            \Artisan::call('demo:audio');
        } catch (\Throwable) {
            return null;
        }

        return $disk->exists($preferred) ? $preferred
            : collect($disk->files('demo-audio'))->first(fn ($f) => str_ends_with($f, '.wav'));
    }

    /** Persist waveform peaks computed in the browser (no server ffmpeg). */
    public function savePeaks(Request $request, AudioAsset $asset): JsonResponse
    {
        $this->authorize('assets.edit');
        $data = $request->validate([
            'peaks' => ['required', 'array', 'min:8', 'max:4000'],
            'peaks.*' => ['numeric', 'between:0,1'],
        ]);
        $asset->update(['waveform_peaks' => array_map(fn ($p) => round((float) $p, 3), $data['peaks'])]);

        return response()->json(['message' => 'Waveform saved.']);
    }

    /* ------------------------------- markers -------------------------- */

    public function markers(AudioAsset $asset): JsonResponse
    {
        return response()->json(['data' => $asset->markers()->get()->map($this->markerArray())]);
    }

    public function storeMarker(Request $request, AudioAsset $asset): JsonResponse
    {
        $this->authorize('assets.edit');
        $data = $request->validate([
            'marker_type' => ['required', Rule::in(array_keys(AudioMarker::COLORS))],
            'label' => ['required', 'string', 'max:255'],
            'start_seconds' => ['required', 'numeric', 'min:0'],
            'end_seconds' => ['nullable', 'numeric', 'gte:start_seconds'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $marker = $asset->markers()->create($data + ['is_ai' => false, 'created_by' => $request->user()->id]);

        return response()->json(['data' => ($this->markerArray())($marker)], 201);
    }

    public function updateMarker(Request $request, AudioAsset $asset, AudioMarker $marker): JsonResponse
    {
        $this->authorize('assets.edit');
        abort_unless($marker->audio_asset_id === $asset->id, 404);

        $data = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'start_seconds' => ['sometimes', 'numeric', 'min:0'],
        ]);
        $marker->update($data);

        return response()->json(['data' => ($this->markerArray())($marker)]);
    }

    public function deleteMarker(AudioAsset $asset, AudioMarker $marker): JsonResponse
    {
        $this->authorize('assets.edit');
        abort_unless($marker->audio_asset_id === $asset->id, 404);
        $marker->delete();

        return response()->json(['message' => 'Marker removed.']);
    }

    /**
     * Bulk-replace this asset's chapter markers with the provided set. The Studio
     * builds chapters locally and only persists them when the editor presses
     * "Save", so adds/renames/deletes don't touch the database until then. Only
     * `chapter` markers are affected — AI/other marker types are left untouched.
     */
    public function syncMarkers(Request $request, AudioAsset $asset): JsonResponse
    {
        $this->authorize('assets.edit');

        $data = $request->validate([
            'chapters' => ['present', 'array'],
            'chapters.*.label' => ['required', 'string', 'max:255'],
            'chapters.*.start_seconds' => ['required', 'numeric', 'min:0'],
        ]);

        $userId = $request->user()->id;

        DB::transaction(function () use ($asset, $data, $userId): void {
            $asset->markers()->where('marker_type', 'chapter')->delete();

            foreach ($data['chapters'] as $c) {
                $asset->markers()->create([
                    'marker_type' => 'chapter',
                    'label' => $c['label'],
                    'start_seconds' => $c['start_seconds'],
                    'end_seconds' => null,
                    'is_ai' => false,
                    'created_by' => $userId,
                ]);
            }
        });

        $chapters = $asset->markers()->where('marker_type', 'chapter')
            ->orderBy('start_seconds')->get()->map($this->markerArray())->values();

        return response()->json(['data' => $chapters]);
    }

    /* ----------------------------- editing ---------------------------- */

    /**
     * Save a non-destructive edit session as an Edit Decision List and create
     * an edited derived version — the master is never modified (FR-EDT-01/04/05).
     */
    public function saveEdit(Request $request, AudioAsset $asset): JsonResponse
    {
        $this->authorize('assets.edit');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'edl' => ['required', 'array', 'min:1'],
            'edl.*.op' => ['required', Rule::in(['trim', 'cut', 'split', 'fade_in', 'fade_out', 'gain', 'marker'])],
            'edl.*.start' => ['required', 'numeric', 'min:0'],
            'edl.*.end' => ['nullable', 'numeric'],
            'edl.*.params' => ['nullable', 'array'],
        ]);

        $sourceVersion = $asset->versions()->where('is_default', true)->first()
            ?? $asset->versions()->first();

        $session = EditSession::query()->create([
            'audio_asset_id' => $asset->id,
            'source_version_id' => $sourceVersion?->id,
            'editor_id' => $request->user()->id,
            'title' => $data['title'] ?? 'Studio edit '.now()->format('Y-m-d H:i'),
            'edl' => $data['edl'],
            'status' => 'submitted',
            'notes' => count($data['edl']).' operations from the Audio Studio.',
        ]);

        // Register the edited output as a new "clip" version derived from the
        // source. (With ffmpeg the EDL would be rendered into real bytes here.)
        $output = AudioVersion::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'clip',
            'label' => $session->title,
            'file_path' => "edits/{$asset->archive_no}-edit{$session->id}.".($sourceVersion->format ?? 'wav'),
            'format' => $sourceVersion->format ?? 'wav',
            'duration_seconds' => $this->estimateEditedDuration($asset->duration_seconds, $data['edl']),
            'derived_from_id' => $sourceVersion?->id,
            'created_by' => $request->user()->id,
            'notes' => 'Rendered from Studio EDL (pending transcode).',
        ]);

        $session->update(['output_version_id' => $output->id]);

        AuditLog::record('edit_session_saved', $asset, null, ['operations' => count($data['edl'])],
            "Studio edit #{$session->id} on {$asset->archive_no}");

        return response()->json([
            'message' => 'Edit saved as a new version. The preservation master is unchanged.',
            'edit_session_id' => $session->id,
            'output_version_id' => $output->id,
        ], 201);
    }

    /** Allowed operations for the render pipeline (M12 editing & restoration). */
    private const RENDER_OPS = [
        'trim', 'cut', 'silence', 'reverse', 'gain', 'normalize', 'fade', 'eq', 'denoise',
        'dehum', 'declick', 'declip', 'compress', 'limit', 'exciter', 'pitch',
        'tempo', 'silence_remove', 'channels', 'resample', 'export',
    ];

    /**
     * Submit an edit/enhancement chain to be rendered by ffmpeg into a new
     * derived version (queued). The source/master are never modified.
     */
    public function submitRender(Request $request, AudioAsset $asset, AudioRenderService $renderer): JsonResponse
    {
        $this->authorize('editing.use');

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'source_version_id' => ['nullable', 'integer'],
            'target' => ['nullable', Rule::in(['new', 'current'])],
            'ops' => ['required', 'array', 'min:1'],
            'ops.*.op' => ['required', Rule::in(self::RENDER_OPS)],
        ]);

        // Use the RAW ops (validate() strips keys without rules). Every value is
        // cast, clamped and escapeshellarg'd inside AudioRenderService, and each
        // op name is allow-listed above, so raw params are safe to render.
        $ops = array_values($request->input('ops', []));
        $sourceVersionId = $request->integer('source_version_id') ?: null;

        $source = $asset->versions()
            ->when($sourceVersionId, fn ($q) => $q->where('id', $sourceVersionId))
            ->when(! $sourceVersionId, fn ($q) => $q->orderByDesc('is_default'))
            ->first();

        // Never overwrite the immutable master — fall back to a new version.
        $target = $request->input('target') === 'current'
            && $source && $source->version_type !== 'preservation_master'
                ? 'current' : 'new';

        $session = EditSession::query()->create([
            'audio_asset_id' => $asset->id,
            'source_version_id' => $source?->id,
            'editor_id' => $request->user()->id,
            'title' => $request->string('title')->toString() ?: 'Studio render '.now()->format('Y-m-d H:i'),
            'edl' => $ops,
            'status' => 'in_progress',
            'render_status' => 'queued',
            'progress' => 0,
        ]);

        RenderEditSession::dispatch($session->id, $target);

        return response()->json([
            'message' => 'Render queued.',
            'edit_session_id' => $session->id,
            'target' => $target,
            'ffmpeg_available' => $renderer->available(),
        ], 202);
    }

    /** Poll the status of a render (for the Studio progress UI). */
    public function renderStatus(AudioAsset $asset, EditSession $editSession): JsonResponse
    {
        abort_unless($editSession->audio_asset_id === $asset->id, 404);
        $editSession->load('outputVersion');
        $version = $editSession->outputVersion;

        return response()->json([
            'render_status' => $editSession->render_status,
            'progress' => $editSession->progress,
            'error' => $editSession->error,
            'version' => $version ? [
                'id' => $version->id,
                'label' => $version->label,
                'type' => $version->version_type,
                'duration' => $version->duration_seconds,
                'stream_url' => route('admin.assets.stream', ['asset' => $asset->id, 'version' => $version->id], false),
            ] : null,
        ]);
    }

    /**
     * Fast synchronous PREVIEW render: applies the sound-shaping effects to a
     * short window (from the playhead) and returns a URL the editor plays
     * immediately — so the operator hears denoise/pitch/etc. live without saving.
     */
    public function previewRender(Request $request, AudioAsset $asset, AudioRenderService $renderer): JsonResponse
    {
        $this->authorize('editing.use');

        $request->validate([
            'ops' => ['required', 'array', 'min:1'],
            'ops.*.op' => ['required', Rule::in(self::RENDER_OPS)],
            'start' => ['nullable', 'numeric', 'min:0'],
            'source_version_id' => ['nullable', 'integer'],
        ]);

        $ops = array_values($request->input('ops', []));
        $start = (float) $request->input('start', 0);
        $vid = $request->integer('source_version_id') ?: null;

        $source = $asset->versions()
            ->when($vid, fn ($q) => $q->where('id', $vid))
            ->when(! $vid, fn ($q) => $q->orderByDesc('is_default'))
            ->first();

        $disk = Storage::disk('local');
        $inputRel = $source && $source->file_path && $disk->exists($source->file_path)
            ? $source->file_path
            : $this->demoTrackFor($asset->id, $disk);

        if (! $inputRel || ! $disk->exists($inputRel)) {
            return response()->json(['error' => 'No source audio available for preview.'], 422);
        }

        $outRel = "previews/pv-{$asset->id}-{$request->user()->id}.mp3";
        // Full-file render (duration 0) so the previewed waveform stays aligned
        // with markers/segments; only duration-preserving effects are sent.
        $res = $renderer->renderPreview(
            $disk->path($inputRel), $disk->path($outRel), $ops,
            ['sample_rate' => $asset->sample_rate ?: 44100, 'duration' => $asset->duration_seconds ?: 0],
        );

        if (! $res['ok']) {
            return response()->json(['error' => $res['error']], 422);
        }

        return response()->json([
            'url' => route('admin.assets.preview-audio', $asset, false).'?t='.now()->timestamp,
        ]);
    }

    /** Serve the caller's latest preview clip for this asset. */
    public function previewAudio(Request $request, AudioAsset $asset): BinaryFileResponse
    {
        $this->authorize('editing.use');
        $disk = Storage::disk('local');
        $rel = "previews/pv-{$asset->id}-{$request->user()->id}.mp3";
        abort_unless($disk->exists($rel), 404, 'No preview available yet.');

        return response()->file($disk->path($rel), [
            'Content-Type' => 'audio/mpeg',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function estimateEditedDuration(int $original, array $edl): int
    {
        $removed = 0;
        foreach ($edl as $op) {
            if (($op['op'] ?? '') === 'cut' && isset($op['end'])) {
                $removed += max(0, (float) $op['end'] - (float) $op['start']);
            }
        }

        return max(0, (int) round($original - $removed));
    }

    private function markerArray(): callable
    {
        return fn (AudioMarker $m) => [
            'id' => $m->id,
            'type' => $m->marker_type,
            'label' => $m->label,
            'start' => (float) $m->start_seconds,
            'end' => $m->end_seconds !== null ? (float) $m->end_seconds : null,
            'color' => $m->resolvedColor(),
            'is_ai' => (bool) $m->is_ai,
        ];
    }
}
