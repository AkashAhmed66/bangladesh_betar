<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\PlayEvent;
use App\Models\PlayHistory;
use App\Services\AdService;
use App\Services\StreamingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * M07/M27 — playback: resolve a signed streaming URL respecting plan
 * entitlements, insert ads for free-tier listeners, and record events.
 */
class PlaybackController extends Controller
{
    public function __construct(
        private readonly StreamingService $streaming,
        private readonly AdService $ads,
    ) {}

    /**
     * Return a playable descriptor: the (preview or full) stream URL plus
     * any pre-roll ad for free listeners.
     */
    public function stream(Request $request, AudioAsset $asset): JsonResponse
    {
        abort_unless($asset->isPublished(), 404, 'Content not available.');

        $user = $request->user();
        $descriptor = $this->streaming->resolve($asset, $user);

        if ($descriptor === null) {
            return response()->json(['message' => 'No streamable version is available for this content.'], 422);
        }

        // The client paces ads to one per N songs and sets ?ad=1 when it's time.
        $ad = $this->ads->selectFor($user, $asset, $request->boolean('ad'));

        return response()->json([
            'asset_id' => $asset->id,
            'title' => $asset->title,
            'stream' => $descriptor,
            'ad' => $ad,          // null for premium / public-service / not-yet-due
            'ad_every_n_songs' => max(1, (int) \App\Models\Setting::get('ad_every_n_songs', 2)),
            // Free-tier daily pick budget (null = unlimited for premium). Sent on
            // every stream so guests — who never call /auth/me — also learn the
            // admin-configured limit.
            'daily_picks' => ($user && $user->isPremium()) ? null : max(0, (int) \App\Models\Setting::get('free_daily_picks', 10)),
            'requires_login_for_full' => $descriptor['is_preview'] && $user === null && $asset->is_premium,
        ]);
    }

    /**
     * Premium offline download — returns the full derived audio file (never a
     * master or preview) as an attachment for signed-in listeners whose plan
     * allows offline downloads. The public app stores the bytes locally
     * (IndexedDB) for offline playback.
     */
    public function download(Request $request, AudioAsset $asset): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($asset->isPublished(), 404, 'Content not available.');

        $user = $request->user();
        abort_if($user === null, 401, 'Sign in to download.');
        abort_unless(
            app(\App\Services\EntitlementService::class)->canDownloadOffline($user),
            403,
            'Offline downloads are a Premium feature.',
        );

        // Same rule as the public stream: the default derived version, never a master.
        $version = $asset->versions()
            ->where('is_default', true)
            ->where('version_type', '!=', 'preservation_master')
            ->first();

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $relative = $version?->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : sprintf('demo-audio/track-%02d.wav', ($asset->id % 12) + 1);

        abort_unless($disk->exists($relative), 404, 'Media file not available.');

        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION)) ?: 'wav';
        $filename = \Illuminate\Support\Str::slug($asset->title ?: 'recording').'.'.$ext;

        return $this->mediaResponse($disk->path($relative))
            ->setContentDisposition('attachment', $filename);
    }

    /**
     * FR-ANL-01 — record a playback event. Also updates Continue Listening
     * for signed-in users (FR-PLY-09) and increments play counts.
     */
    public function event(Request $request, AudioAsset $asset): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['required', Rule::in(['play', 'pause', 'seek', 'replay', 'skip', 'progress', 'complete'])],
            'position_seconds' => ['nullable', 'integer', 'min:0'],
            'platform' => ['nullable', Rule::in(['web', 'android', 'ios'])],
            'anonymous_id' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();

        PlayEvent::query()->create([
            'audio_asset_id' => $asset->id,
            'user_id' => $user?->id,
            'anonymous_id' => $user ? null : ($data['anonymous_id'] ?? null),
            'event_type' => $data['event_type'],
            'position_seconds' => $data['position_seconds'] ?? 0,
            'platform' => $data['platform'] ?? 'web',
            'region' => null,
            'created_at' => now(),
        ]);

        if (in_array($data['event_type'], ['play', 'replay'], true)) {
            $asset->increment('play_count');
        }

        // Continue Listening / resume position, synced across devices.
        if ($user !== null && in_array($data['event_type'], ['progress', 'pause', 'complete'], true)) {
            PlayHistory::query()->updateOrCreate(
                ['user_id' => $user->id, 'playable_type' => 'audio_asset', 'playable_id' => $asset->id],
                [
                    'audio_asset_id' => $asset->id,
                    'progress_seconds' => $data['position_seconds'] ?? 0,
                    'completed' => $data['event_type'] === 'complete',
                    'last_played_at' => now(),
                ],
            );
        }

        return response()->json(['message' => 'Event recorded.'], 202);
    }

    /** FR-ADV-06 — record an ad impression/completion. */
    public function adImpression(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ad_campaign_id' => ['required', 'exists:ad_campaigns,id'],
            'slot' => ['nullable', Rule::in(['pre_roll', 'between'])],
            'platform' => ['nullable', Rule::in(['web', 'android', 'ios'])],
            'completed' => ['boolean'],
            'anonymous_id' => ['nullable', 'string', 'max:64'],
        ]);

        $this->ads->logImpression(
            $data['ad_campaign_id'],
            $request->user(),
            $request->user() ? null : ($data['anonymous_id'] ?? null),
            $data['slot'] ?? 'pre_roll',
            $data['platform'] ?? 'web',
            (bool) ($data['completed'] ?? false),
        );

        return response()->json(['message' => 'Impression recorded.'], 202);
    }

    /**
     * Signed streaming endpoint — streams the derived media bytes with HTTP
     * Range support so browsers can seek. Masters are never reachable here.
     * When the referenced archive file is absent (local/demo environments),
     * a deterministic synthesized demo track is streamed instead so the
     * public portal remains fully functional (run `php artisan demo:audio`).
     */
    public function play(Request $request, AudioAsset $asset, \App\Models\AudioVersion $version): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Expired or invalid streaming URL.');
        abort_if($version->version_type === 'preservation_master', 403, 'Master files are never publicly streamable.');
        abort_unless($version->audio_asset_id === $asset->id, 404);

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        $relative = $version->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : sprintf('demo-audio/track-%02d.wav', ($asset->id % 12) + 1);

        abort_unless($disk->exists($relative), 404, 'Media file not available. Run `php artisan demo:audio` to generate demo media.');

        return $this->mediaResponse($disk->path($relative));
    }

    /**
     * Public ad audio for pre-roll slots resolved by GET /assets/{asset}/stream.
     * Streams the campaign's linked audio-asset creative (its default online
     * version), falling back to a synthesized demo track when the file is absent.
     */
    public function adAudio(\App\Models\AdCampaign $adCampaign): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($adCampaign->status === 'active', 404);

        $asset = $adCampaign->audioAsset;
        abort_unless($asset !== null, 404, 'This campaign has no creative.');

        // The streamable version = the default, non-master version (same rule
        // the public StreamingService uses); never a preservation master.
        $version = $asset->versions()
            ->where('is_default', true)
            ->where('version_type', '!=', 'preservation_master')
            ->first();

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        $relative = $version?->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : sprintf('demo-audio/track-%02d.wav', ($asset->id % 12) + 1);

        abort_unless($disk->exists($relative), 404, 'Ad media not available. Run `php artisan demo:audio` to generate demo media.');

        return $this->mediaResponse($disk->path($relative));
    }

    /** Range-capable file response with the right audio content type. */
    private function mediaResponse(string $absolutePath): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'm4a', 'aac', 'mp4' => 'audio/mp4',
            'ogg', 'opus' => 'audio/ogg',
            'flac' => 'audio/flac',
            default => 'application/octet-stream',
        };

        // BinaryFileResponse honours Range/If-Range requests, enabling seek.
        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=1800',
        ]);
    }
}
