<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin tables share one polished, dark-mode-aware paginator (numbered
        // pages + ellipsis + result summary). Applies to every ->links() call.
        Paginator::defaultView('pagination.admin');

        // FR-API-06 — public API is rate-limited per authenticated user or IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        // Tighter limit on auth endpoints to slow credential stuffing.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Stable aliases for polymorphic relations — keeps DB values and
        // public API type strings decoupled from PHP class names.
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'audio_asset' => \App\Models\AudioAsset::class,
            'audio_version' => \App\Models\AudioVersion::class,
            'song' => \App\Models\Song::class,
            'album' => \App\Models\Album::class,
            'artist' => \App\Models\Artist::class,
            'programme' => \App\Models\Programme::class,
            'episode' => \App\Models\Episode::class,
            'podcast_channel' => \App\Models\PodcastChannel::class,
            'podcast_episode' => \App\Models\PodcastEpisode::class,
            'playlist' => \App\Models\Playlist::class,
            'comment' => \App\Models\Comment::class,
            'edit_session' => \App\Models\EditSession::class,
            'media_item' => \App\Models\MediaItem::class,
            'rights_record' => \App\Models\RightsRecord::class,
            'station' => \App\Models\Station::class,
            'plan' => \App\Models\Plan::class,
            'payment' => \App\Models\Payment::class,
            'subscription' => \App\Models\Subscription::class,
            'setting' => \App\Models\Setting::class,
            'banner' => \App\Models\Banner::class,

            // The rest of the Auditable models — every one needs an entry or
            // creating the very first real (non-seeded) row throws
            // ClassMorphViolationException from the audit-log hook, since
            // Auditable calls getMorphClass() on every create/update/delete.
            'ad_campaign' => \App\Models\AdCampaign::class,
            'advertiser' => \App\Models\Advertiser::class,
            'category' => \App\Models\Category::class,
            'department' => \App\Models\Department::class,
            'genre' => \App\Models\Genre::class,
            'language' => \App\Models\Language::class,
            'mood' => \App\Models\Mood::class,
            'promo_code' => \App\Models\PromoCode::class,
            'rights_holder' => \App\Models\RightsHolder::class,
            'transcript' => \App\Models\Transcript::class,
            'workflow' => \App\Models\Workflow::class,
            'broadcast_channel' => \App\Models\BroadcastChannel::class,
        ]);
    }
}
