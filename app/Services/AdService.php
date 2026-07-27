<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\AudioAsset;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * M27 — server-side ad selection for free-tier playback (FR-ADV-03/05).
 * A campaign's creative is an existing audio asset from the library. Every
 * eligible campaign (active + within its flight dates + a published creative)
 * is served in a simple ROUND-ROBIN — no priority — so all ads rotate equally,
 * one after another. The client paces how often an ad appears (one per N songs,
 * configurable via the `ad_every_n_songs` setting).
 */
class AdService
{
    /** Every ad plays for exactly this long — enforced here and in the player. */
    public const AD_LENGTH_SECONDS = 10;

    /** Cache key holding the last campaign id served, for round-robin rotation. */
    private const RR_KEY = 'ads:rr:last_campaign_id';

    public function __construct(private readonly EntitlementService $entitlements) {}

    /**
     * Pick the next ad to insert before the given asset, or null if the listener
     * should not receive one right now. `$wantAd` is the client's pacing signal
     * (it only asks for an ad once N songs have passed).
     *
     * @return array{id:int, title:string, audio_asset_id:int, duration_seconds:int, slot:string, audio_url:string}|null
     */
    public function selectFor(?User $user, AudioAsset $asset, bool $wantAd = true): ?array
    {
        if (! $wantAd) {
            return null; // not time for an ad yet (client paces one per N songs)
        }
        if (! $this->entitlements->shouldServeAds($user, (bool) $asset->is_public_service)) {
            return null;
        }
        if (! (bool) Setting::get('preroll_enabled', true)) {
            return null;
        }

        // Every eligible campaign — active, in flight dates, published creative —
        // in a stable order, so we can walk them round-robin (no priority).
        $campaigns = AdCampaign::query()->running()
            ->whereNotNull('audio_asset_id')
            ->whereHas('audioAsset', fn ($q) => $q->where('status', 'published'))
            ->with('audioAsset')
            ->orderBy('id')
            ->get();

        if ($campaigns->isEmpty()) {
            return null;
        }

        // Round-robin: serve the first campaign after the last one served, wrapping
        // back to the start — so every ad is shown one after another, in turn.
        $lastId = (int) Cache::get(self::RR_KEY, 0);
        $campaign = $campaigns->first(fn ($c) => $c->id > $lastId) ?? $campaigns->first();
        Cache::put(self::RR_KEY, $campaign->id, now()->addDays(7));

        $creative = $campaign->audioAsset;

        return [
            'id' => $campaign->id,
            'title' => $campaign->name,
            'audio_asset_id' => $creative->id,
            // Fixed ad length — the creative is cut to exactly this many seconds
            // by the player, regardless of the underlying recording's length.
            'duration_seconds' => self::AD_LENGTH_SECONDS,
            'slot' => 'pre_roll',
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
