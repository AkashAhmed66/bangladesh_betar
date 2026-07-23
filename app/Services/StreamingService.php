<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AudioAsset;
use App\Models\AudioVersion;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Produces signed, expiring streaming URLs for public playback (NFR-01).
 * Only derived online/preview versions are ever exposed — never masters
 * (FR-PLY-10 / FR-PUB-03).
 */
class StreamingService
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    /**
     * Build a playback descriptor for an asset given the listener's plan.
     *
     * @return array{version: string, url: string, expires_at: string, duration_seconds: int, is_preview: bool, bitrate_kbps: int}|null
     */
    public function resolve(AudioAsset $asset, ?User $user): ?array
    {
        if (! $asset->isPublished()) {
            return null;
        }

        $fullLength = $this->entitlements->canPlayFullLength($user, (bool) $asset->is_premium);

        // The chosen "streaming" version = whichever version is flagged default
        // (admins pick it in the Version Family table), never the master.
        $streaming = fn () => $asset->versions()
            ->where('is_default', true)
            ->where('version_type', '!=', 'preservation_master')
            ->first();

        $version = $fullLength
            ? $streaming()
            : ($asset->versions()->where('version_type', 'preview')->first() ?? $streaming());

        // Never fall back to a master.
        if ($version === null || $version->version_type === 'preservation_master') {
            return null;
        }

        $maxQuality = $this->entitlements->entitlements($user)['max_quality_kbps'];
        $ttl = (int) \App\Models\Setting::get('stream_url_ttl_minutes', 30);

        return [
            'version' => $version->version_type,
            'url' => $this->signedUrl($asset, $version, $ttl),
            'expires_at' => now()->addMinutes($ttl)->toIso8601String(),
            'duration_seconds' => $version->duration_seconds ?: $asset->duration_seconds,
            'is_preview' => $version->version_type === 'preview',
            'bitrate_kbps' => min($version->bitrate_kbps ?: 128, $maxQuality),
        ];
    }

    private function signedUrl(AudioAsset $asset, AudioVersion $version, int $ttlMinutes): string
    {
        // In production this points at the media/CDN edge; here we sign a
        // route that would stream the derived file.
        return URL::temporarySignedRoute(
            'api.v1.stream.play',
            now()->addMinutes($ttlMinutes),
            ['asset' => $asset->id, 'version' => $version->id],
        );
    }
}
