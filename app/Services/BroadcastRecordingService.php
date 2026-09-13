<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BroadcastRecording;
use App\Models\BroadcastSession;
use App\Support\Hls;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BroadcastRecordingService
{
    public function __construct(private readonly LiveKitService $liveKit) {}

    /** Start (or resume) the single recording belonging to a live session. */
    public function start(BroadcastSession $session): BroadcastRecording
    {
        $disk = (string) config('services.livekit.recording_disk', 'broadcast_recordings');
        $relativePath = sprintf(
            'channel-%d/session-%d-%s.ogg',
            $session->broadcast_channel_id,
            $session->id,
            now()->format('Ymd-His'),
        );

        $recording = $session->recording()->firstOrCreate([], [
            'status' => 'pending',
            'disk' => $disk,
            'file_path' => $relativePath,
            'format' => 'ogg',
            'mime_type' => 'audio/ogg',
        ]);

        if (in_array($recording->status, ['starting', 'active', 'finalizing', 'complete'], true)) {
            return $recording;
        }

        if (! config('services.livekit.recording_enabled', true)) {
            $recording->update([
                'status' => 'failed',
                'error' => 'Automatic broadcast recording is disabled.',
            ]);

            return $recording->refresh();
        }

        $recording->update(['status' => 'starting', 'error' => null]);

        try {
            if (! $this->liveKit->ensureRoom($session->room_name)) {
                throw new \RuntimeException('LiveKit could not create the room required for recording.');
            }

            $outputRoot = rtrim((string) config('services.livekit.recording_output_directory', '/out'), '/');
            $egress = $this->liveKit->startRoomRecording(
                $session->room_name,
                $outputRoot.'/'.$recording->file_path,
            );

            $recording->update([
                'egress_id' => $egress['egress_id'],
                'status' => 'active',
                'started_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Broadcast recording failed to start.', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            $recording->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }

        return $recording->refresh();
    }

    /** Ask Egress to finalize before the LiveKit room is deleted. */
    public function stop(BroadcastSession $session): bool
    {
        $recording = $session->recording;
        if (! $recording || in_array($recording->status, ['complete', 'failed', 'aborted'], true)) {
            return true;
        }

        if (! $recording->egress_id) {
            $recording->update([
                'status' => 'failed',
                'error' => 'The recording never received a LiveKit Egress ID.',
                'ended_at' => now(),
            ]);

            return false;
        }

        $recording->update(['status' => 'finalizing']);
        $stopping = $this->liveKit->stopRoomRecording($recording->egress_id);

        if (! $stopping) {
            $recording->update([
                'error' => 'LiveKit did not acknowledge the recording stop request. Room closure will retry finalization.',
            ]);
        }

        return $stopping;
    }

    /** Apply signed LiveKit egress webhook progress to the recording row. */
    public function handleWebhook(string $event, array $info): void
    {
        $egressId = (string) ($info['egressId'] ?? $info['egress_id'] ?? '');
        $roomName = (string) ($info['roomName'] ?? $info['room_name'] ?? '');

        $recording = $egressId !== ''
            ? BroadcastRecording::query()->where('egress_id', $egressId)->first()
            : null;

        if (! $recording && $roomName !== '') {
            $recording = BroadcastRecording::query()
                ->whereHas('session', fn ($query) => $query->where('room_name', $roomName))
                ->latest('id')
                ->first();
        }

        if (! $recording) {
            Log::warning('LiveKit sent an egress event for an unknown recording.', compact('event', 'egressId', 'roomName'));

            return;
        }

        if ($egressId !== '' && ! $recording->egress_id) {
            $recording->egress_id = $egressId;
        }

        if ($event === 'egress_started') {
            $recording->fill([
                'status' => 'active',
                'started_at' => $this->timestamp($info['startedAt'] ?? $info['started_at'] ?? null) ?? now(),
                'error' => null,
            ])->save();

            return;
        }

        if ($event === 'egress_updated') {
            if ($recording->status === 'starting') {
                $recording->status = 'active';
            }
            $recording->save();

            return;
        }

        if ($event !== 'egress_ended') {
            return;
        }

        $file = (array) (($info['fileResults'] ?? $info['file_results'] ?? [])[0] ?? []);
        $rawStatus = $info['status'] ?? null;
        $error = trim((string) ($info['error'] ?? $file['error'] ?? ''));
        $complete = in_array($rawStatus, [3, 6, '3', '6', 'EGRESS_COMPLETE', 'EGRESS_LIMIT_REACHED'], true)
            || ($rawStatus === null && $error === '');

        $durationNs = (int) ($file['duration'] ?? $info['duration'] ?? 0);
        $duration = $durationNs > 0 ? (int) ceil($durationNs / 1_000_000_000) : null;
        $size = (int) ($file['size'] ?? 0) ?: null;

        if ($complete && $recording->file_path) {
            $storage = Storage::disk($recording->disk);
            if ($size === null && $storage->exists($recording->file_path)) {
                $size = $storage->size($recording->file_path);
            }
        }

        $recording->fill([
            'status' => $complete ? 'complete' : (in_array($rawStatus, [5, '5', 'EGRESS_ABORTED'], true) ? 'aborted' : 'failed'),
            'duration_seconds' => $duration,
            'file_size' => $size,
            'published_at' => $complete && $recording->is_published
                ? ($recording->published_at ?? now())
                : $recording->published_at,
            'ended_at' => $this->timestamp($info['endedAt'] ?? $info['ended_at'] ?? null) ?? now(),
            'error' => $error !== '' ? $error : null,
        ])->save();

        if ($complete && $recording->is_published) {
            Hls::ensureQueued('broadcast', $recording->id, 'main');
        }
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        $timestamp = (int) $value;
        if ($timestamp > 10_000_000_000) {
            $timestamp = (int) floor($timestamp / 1_000_000_000);
        }

        return CarbonImmutable::createFromTimestamp($timestamp);
    }
}
