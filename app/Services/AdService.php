<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\AudioAsset;
use App\Models\User;

/**
 * M27 — server-side ad selection for free-tier playback (FR-ADV-03/05).
 * A campaign's creative is an existing audio asset from the library; the
 * highest-priority running campaign with a linked creative is served.
 */
class AdService
{
    /** Every ad plays for exactly this long — enforced here and in the player. */
    public const AD_LENGTH_SECONDS = 10;

    public function __construct(private readonly EntitlementService $entitlements) {}

    /**
     * Pick an ad to insert before/around the given asset, or null if the
     * listener should not receive one.
     *
     * @return array{id:int, title:string, audio_asset_id:int, duration_seconds:int, slot:string, audio_url:string}|null
     */
    public function selectFor(?User $user, AudioAsset $asset, string $slot = 'pre_roll'): ?array
    {
        if (! $this->entitlements->shouldServeAds($user, (bool) $asset->is_public_service)) {
            return null;
        }

        // A running campaign whose linked creative is a published, streamable
        // audio asset. Higher priority (lower number) wins; ties break randomly.
        $campaign = AdCampaign::query()->running()
            ->whereNotNull('audio_asset_id')
            ->whereHas('audioAsset', fn ($q) => $q->where('status', 'published'))
            ->with('audioAsset')
            ->orderBy('priority')
            ->inRandomOrder()
            ->first();

        if ($campaign === null || $campaign->audioAsset === null) {
            return null;
        }

        $creative = $campaign->audioAsset;

        return [
            'id' => $campaign->id,
            'title' => $campaign->name,
            'audio_asset_id' => $creative->id,
            // Fixed ad length — the creative is cut to exactly this many seconds
            // by the player, regardless of the underlying recording's length.
            'duration_seconds' => self::AD_LENGTH_SECONDS,
            'slot' => $slot,
            'audio_url' => route('api.v1.ads.audio', ['adCampaign' => $campaign->id]),
        ];
    }

    public function logImpression(int $adCampaignId, ?User $user, ?string $anonymousId, string $slot, string $platform, bool $completed): void
    {
        $campaign = AdCampaign::query()->find($adCampaignId);
        if ($campaign === null) {
            return;
        }

        AdImpression::query()->create([
            'ad_campaign_id' => $campaign->id,
            'audio_asset_id' => $campaign->audio_asset_id,
            'user_id' => $user?->id,
            'anonymous_id' => $anonymousId,
            'slot' => $slot,
            'platform' => $platform,
            'completed' => $completed,
            'created_at' => now(),
        ]);
    }
}
