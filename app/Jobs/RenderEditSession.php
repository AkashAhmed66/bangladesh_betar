<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\AudioVersion;
use App\Models\EditSession;
use App\Services\AudioProcessor;
use App\Services\AudioRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * M12 — render a non-destructive edit/enhancement session with ffmpeg, writing
 * a NEW derived version. The source (and master) are never modified.
 */
class RenderEditSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    /** @param string $target 'new' (create a new version) | 'current' (overwrite the source version) */
    public function __construct(private readonly int $editSessionId, private readonly string $target = 'new') {}

    public function handle(AudioRenderService $renderer, AudioProcessor $processor): void
    {
        $session = EditSession::query()->with(['audioAsset', 'sourceVersion'])->find($this->editSessionId);
        if (! $session) {
            return;
        }

        $asset = $session->audioAsset;
        $ops = $session->edl ?? [];
        $disk = Storage::disk('local');

        $session->update(['render_status' => 'rendering', 'progress' => 15, 'error' => null]);

        // Resolve the source file (real upload preferred; demo fallback so the
        // pipeline is testable for seeded assets in Docker).
        // Prefer the real source file; otherwise the SAME per-asset demo track the
        // Studio waveform/preview use (track-{asset%12+1}), so the rendered result
        // matches what the operator saw — not a different, shorter demo file.
        $src = $session->sourceVersion;
        $demo = sprintf('demo-audio/track-%02d.wav', ($asset->id % 12) + 1);
        $inputRel = $src && $src->file_path && $disk->exists($src->file_path)
            ? $src->file_path
            : ($disk->exists($demo) ? $demo : collect($disk->files('demo-audio'))->first(fn ($f) => str_ends_with($f, '.wav')));

        if (! $inputRel || ! $disk->exists($inputRel)) {
            $this->fail($session, 'No source audio available to render.');

            return;
        }

        $format = $this->exportFormat($ops, $src?->format ?? 'wav');
        $outputRel = "edits/{$asset->archive_no}-edit{$session->id}.{$format}";
        $inputAbs = $disk->path($inputRel);
        $outputAbs = $disk->path($outputRel);

        $result = $renderer->render($inputAbs, $outputAbs, $ops, [
            'sample_rate' => $asset->sample_rate ?: 44100,
            'duration' => $asset->duration_seconds ?: 0,
        ]);

        $session->update(['ffmpeg_cmd' => $result['cmd'] ?? null, 'progress' => 80]);

        if (! $result['ok']) {
            $this->fail($session, $result['error'] ?? 'Render failed.');

            return;
        }

        // Overwrite the source version in place only when explicitly requested
        // AND it is not the immutable preservation master (FR-REP-03).
        $overwrite = $this->target === 'current'
            && $src !== null
            && $src->version_type !== 'preservation_master'
            && $src->file_path;

        if ($overwrite) {
            // Move the rendered output onto the source version's path (new ext
            // if the format changed), replacing its bytes and metadata.
            $newPath = preg_replace('/\.[^.]+$/', '', $src->file_path).'.'.$format;
            if ($newPath !== $outputRel) {
                $disk->delete($newPath);
                $disk->move($outputRel, $newPath);
            }
            if ($src->file_path !== $newPath && $disk->exists($src->file_path)) {
                $disk->delete($src->file_path);
            }
            $finalAbs = $disk->path($newPath);
            $meta = $processor->analyze($finalAbs);

            $src->update([
                'label' => $session->title ?: $src->label,
                'file_path' => $newPath,
                'format' => $format,
                'bitrate_kbps' => $meta['bitrate_kbps'],
                'duration_seconds' => $meta['duration_seconds'] ?: $src->duration_seconds,
                'size_bytes' => $disk->size($newPath),
                'checksum_sha256' => hash_file('sha256', $finalAbs),
                'notes' => count($ops).' operations applied in place from the Studio.',
            ]);
            $version = $src;
        } else {
            // Analyse the rendered output and register it as a NEW version.
            $meta = $processor->analyze($outputAbs);
            $version = AudioVersion::query()->create([
                'audio_asset_id' => $asset->id,
                'version_type' => $this->versionType($ops),
                'label' => $session->title ?: 'Studio render',
                'file_path' => $outputRel,
                'format' => $format,
                'bitrate_kbps' => $meta['bitrate_kbps'],
                'duration_seconds' => $meta['duration_seconds'] ?: $asset->duration_seconds,
                'size_bytes' => $disk->size($outputRel),
                'checksum_sha256' => hash_file('sha256', $outputAbs),
                'is_default' => false,
                'derived_from_id' => $src?->id,
                'created_by' => $session->editor_id,
                'notes' => count($ops).' operations rendered from the Audio Studio.',
            ]);
        }

        $session->update([
            'output_version_id' => $version->id,
            'render_status' => 'done',
            'progress' => 100,
            'status' => 'submitted',
        ]);

        // Persist waveform peaks for the new version's asset thumbnail if the
        // source had none (keeps list views accurate).
        if ($meta['waveform_peaks'] && empty($asset->waveform_peaks)) {
            $asset->update(['waveform_peaks' => $meta['waveform_peaks']]);
        }

        AuditLog::record('edit_rendered', $asset, null, [
            'edit_session' => $session->id, 'version' => $version->id, 'ops' => count($ops),
        ], "Rendered edit #{$session->id} → version #{$version->id} ({$asset->archive_no})");
    }

    public function failed(\Throwable $e): void
    {
        EditSession::query()->where('id', $this->editSessionId)
            ->update(['render_status' => 'failed', 'error' => $e->getMessage()]);
    }

    private function fail(EditSession $session, string $message): void
    {
        $session->update(['render_status' => 'failed', 'error' => $message, 'progress' => 100]);
    }

    private function exportFormat(array $ops, string $default): string
    {
        foreach ($ops as $op) {
            if (($op['op'] ?? '') === 'export' && ! empty($op['format'])) {
                return strtolower($op['format']);
            }
        }

        return in_array(strtolower($default), ['wav', 'flac', 'mp3', 'aac', 'm4a', 'ogg'], true) ? strtolower($default) : 'wav';
    }

    private function versionType(array $ops): string
    {
        $types = array_column($ops, 'op');
        if (array_intersect($types, ['denoise', 'dehum', 'declick', 'declip'])) {
            return 'restored';
        }
        if (array_intersect($types, ['eq', 'normalize', 'compress', 'limit', 'pitch', 'tempo', 'gain', 'fade', 'exciter'])) {
            return 'enhanced';
        }

        return 'edited';
    }
}
