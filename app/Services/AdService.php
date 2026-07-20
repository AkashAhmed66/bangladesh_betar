<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdAsset;
use App\Models\AdImpression;
use App\Models\AudioAsset;
use App\Models\User;

/**
 * M27 — server-side ad selection for free-tier playback (FR-ADV-03/05).
 * Falls back to house announcements / PSAs when no campaign is eligible.
 */
class AdService
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    /**
     * Pick an ad to insert before/around the given asset, or null if the
     * listener should not receive one.
     *
     * @return array{id:int, title:string, type:string, duration_seconds:int, slot:string}|null
     */
    public function selectFor(?User $user, AudioAsset $asset, string $slot = 'pre_roll'): ?array
    {
        if (! $this->entitlements->shouldServeAds($user, (bool) $asset->is_public_service)) {
            return null;
        }

        // Prefer an active commercial spot; otherwise a house/PSA fallback.
        $ad = AdAsset::query()->servable()->where('ad_type', 'commercial')
                ->whereHas('campaign', fn ($q) => $q->running())
                ->inRandomOrder()->first()
            ?? AdAsset::query()->servable()->whereIn('ad_type', ['house', 'psa'])
                ->inRandomOrder()->first();

        if ($ad === null) {
            return null;
        }

        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'type' => $ad->ad_type,
            'duration_seconds' => $ad->duration_seconds,
            'slot' => $slot,
            'audio_url' => route('api.v1.ads.audio', ['adAsset' => $ad->id]),
        ];
    }

    public function logImpression(int $adAssetId, ?User $user, ?string $anonymousId, string $slot, string $platform, bool $completed): void
    {
        $ad = AdAsset::query()->find($adAssetId);
        if ($ad === null) {
            return;
        }

        AdImpression::query()->create([
            'ad_asset_id' => $ad->id,
            'ad_campaign_id' => $ad->ad_campaign_id,
            'user_id' => $user?->id,
            'anonymous_id' => $anonymousId,
            'slot' => $slot,
            'platform' => $platform,
            'completed' => $completed,
            'created_at' => now(),
        ]);
    }
}
