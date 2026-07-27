<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;

/**
 * M18 — resolves what a listener is entitled to, enforcing the freemium
 * rules (FR-SUB-06/07). Guests and free users get the Free plan features.
 */
class EntitlementService
{
    public function planFor(?User $user): Plan
    {
        if ($user !== null && $user->isPremium()) {
            return Plan::query()->where('code', 'premium')->firstOrFail();
        }

        return Plan::query()->where('code', 'free')->firstOrFail();
    }

    /** @return array<string, mixed> */
    public function entitlements(?User $user): array
    {
        $plan = $this->planFor($user);
        $isPremium = $plan->code === 'premium';

        return [
            'plan' => $plan->code,
            'is_premium' => $isPremium,
            'is_authenticated' => $user !== null,
            'ads_enabled' => (bool) $plan->feature('ads', true),
            'max_quality_kbps' => (int) $plan->feature('max_quality_kbps', 128),
            'skips_per_hour' => $plan->feature('skips_per_hour'),
            // Free tier: how many tracks a listener may actively choose/queue per
            // day (admin-configurable). Premium is unlimited (null). After the
            // budget is spent the client falls back to a random radio mix.
            'daily_picks' => $isPremium ? null : max(0, (int) Setting::get('free_daily_picks', 10)),
            'offline_downloads' => (bool) $plan->feature('offline_downloads', false),
            'equalizer' => (bool) $plan->feature('equalizer', false),
            'premium_content_access' => $plan->feature('premium_content', 'preview'),
            'preview_seconds' => (int) $plan->feature('preview_seconds', 90),
        ];
    }

    /**
     * Whether the listener may hear the full-length version of an asset.
     * Premium-flagged content is preview-only for free/guest listeners.
     */
    public function canPlayFullLength(?User $user, bool $isPremiumContent): bool
    {
        if (! $isPremiumContent) {
            return true;
        }

        return $this->planFor($user)->feature('premium_content', 'preview') === 'full';
    }

    public function canDownloadOffline(?User $user): bool
    {
        return (bool) $this->planFor($user)->feature('offline_downloads', false);
    }

    public function shouldServeAds(?User $user, bool $isPublicService): bool
    {
        // Public-service content is always ad-free (FR-ADV-07 / FR-SUB-11).
        if ($isPublicService) {
            return false;
        }

        return (bool) $this->planFor($user)->feature('ads', true);
    }
}
