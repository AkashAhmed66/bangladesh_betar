<?php

namespace App\Providers;

use App\Models\AdCampaign;
use App\Models\Advertiser;
use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\AudioVersion;
use App\Models\Banner;
use App\Models\BroadcastChannel;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Department;
use App\Models\EditSession;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Language;
use App\Models\MediaItem;
use App\Models\Mood;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use App\Models\PromoCode;
use App\Models\RightsHolder;
use App\Models\RightsRecord;
use App\Models\Setting;
use App\Models\Song;
use App\Models\SpeechConversion;
use App\Models\Station;
use App\Models\Subscription;
use App\Models\Transcript;
use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchEpisode;
use App\Models\WatchShow;
use App\Models\Workflow;
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

        // HLS segments: ~6/min is real-time playback for 10s chunks; 90/min
        // leaves room for buffering and seeks while making bulk ripping of
        // long recordings impractically slow (download-protection policy).
        RateLimiter::for('hls-seg', fn (Request $request) => Limit::perMinute(90)->by($request->ip()));

        // Stable aliases for polymorphic relations — keeps DB values and
        // public API type strings decoupled from PHP class names.
        Relation::enforceMorphMap([
            'user' => User::class,
            'audio_asset' => AudioAsset::class,
            'audio_version' => AudioVersion::class,
            'song' => Song::class,
            'album' => Album::class,
            'artist' => Artist::class,
            'programme' => Programme::class,
            'episode' => Episode::class,
            'podcast_channel' => PodcastChannel::class,
            'podcast_episode' => PodcastEpisode::class,
            'playlist' => Playlist::class,
            'comment' => Comment::class,
            'edit_session' => EditSession::class,
            'media_item' => MediaItem::class,
            'rights_record' => RightsRecord::class,
            'station' => Station::class,
            'plan' => Plan::class,
            'payment' => Payment::class,
            'subscription' => Subscription::class,
            'setting' => Setting::class,
            'banner' => Banner::class,

            // The rest of the Auditable models — every one needs an entry or
            // creating the very first real (non-seeded) row throws
            // ClassMorphViolationException from the audit-log hook, since
            // Auditable calls getMorphClass() on every create/update/delete.
            'ad_campaign' => AdCampaign::class,
            'advertiser' => Advertiser::class,
            'category' => Category::class,
            'department' => Department::class,
            'genre' => Genre::class,
            'language' => Language::class,
            'mood' => Mood::class,
            'promo_code' => PromoCode::class,
            'rights_holder' => RightsHolder::class,
            'transcript' => Transcript::class,
            'workflow' => Workflow::class,
            'broadcast_channel' => BroadcastChannel::class,
            'speech_conversion' => SpeechConversion::class,
            'audio_book' => AudioBook::class,
            'news_article' => NewsArticle::class,
            'news_category' => NewsCategory::class,
            'watch_show' => WatchShow::class,
            'watch_episode' => WatchEpisode::class,
            'watch_category' => WatchCategory::class,
        ]);
    }
}
