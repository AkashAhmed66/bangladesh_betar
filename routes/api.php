<?php

use App\Http\Controllers\Api\LiveKitWebhookController;
use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Portal API — v1
|--------------------------------------------------------------------------
| Powers the Spotify-style public web + mobile apps (M17). Token auth via
| Laravel Sanctum. Full reference: docs/API_DOCUMENTATION.md
|
| Middleware:
|   - (none)          public / guest-accessible (FR-USR-11 guest mode)
|   - auth:sanctum    requires a listener bearer token
|   - optional.auth   attaches the user if a token is present, else guest
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    // ---- Health ----
    Route::get('ping', fn () => response()->json(['status' => 'ok', 'service' => 'Bangladesh Betar Audio Archive API', 'version' => '1.0']))->name('ping');

    // ---- Authentication (M01) ----
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [V1\AuthController::class, 'register'])->name('register');
        Route::post('login', [V1\AuthController::class, 'login'])->name('login');
        Route::post('otp/request', [V1\AuthController::class, 'requestOtp'])->name('otp.request');
        Route::post('otp/verify', [V1\AuthController::class, 'verifyOtp'])->name('otp.verify');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [V1\AuthController::class, 'logout'])->name('logout');
            Route::get('me', [V1\AuthController::class, 'me'])->name('me');
            Route::put('profile', [V1\AuthController::class, 'updateProfile'])->name('profile.update');
        });
    });

    /*
     |--------------------------------------------------------------------
     | Public / guest-accessible (optional auth — FR-USR-11 guest mode)
     |--------------------------------------------------------------------
     | 'optional.auth' attaches request->user() when a token is present so
     | responses can include is_favorited / is_following flags for members.
     */
    Route::middleware('optional.auth')->group(function (): void {

        // Discovery & home (M17/M24)
        Route::get('home', [V1\BrowseController::class, 'home'])->name('home');
        Route::get('categories', [V1\BrowseController::class, 'categories'])->name('categories');
        Route::get('genres', [V1\BrowseController::class, 'genres'])->name('genres');
        Route::get('trending', [V1\BrowseController::class, 'trending'])->name('trending');
        Route::get('top-played', [V1\BrowseController::class, 'topPlayed'])->name('top-played');
        Route::get('new-releases', [V1\BrowseController::class, 'newReleases'])->name('new-releases');
        Route::get('on-this-day', [V1\BrowseController::class, 'onThisDay'])->name('on-this-day');
        Route::get('featured-artists', [V1\BrowseController::class, 'featuredArtists'])->name('featured-artists');
        Route::get('radio', [V1\BrowseController::class, 'radio'])->name('radio');

        // Search (M06)
        Route::get('search', [V1\SearchController::class, 'search'])->name('search');
        Route::get('search/suggest', [V1\SearchController::class, 'suggest'])->name('search.suggest');
        Route::get('search/semantic', [V1\SearchController::class, 'semantic'])->name('search.semantic');

        // Catalogue (M17) — songs, albums, artists, programmes, podcasts, stories
        Route::get('songs', [V1\CatalogueController::class, 'songs'])->name('songs.index');
        Route::get('songs/{song}', [V1\CatalogueController::class, 'song'])->name('songs.show');
        Route::get('albums', [V1\CatalogueController::class, 'albums'])->name('albums.index');
        Route::get('albums/{album}', [V1\CatalogueController::class, 'album'])->name('albums.show');
        Route::get('artists', [V1\CatalogueController::class, 'artists'])->name('artists.index');
        Route::get('artists/{artist}', [V1\CatalogueController::class, 'artist'])->name('artists.show');
        Route::get('programmes', [V1\CatalogueController::class, 'programmes'])->name('programmes.index');
        Route::get('programmes/{programme}', [V1\CatalogueController::class, 'programme'])->name('programmes.show');
        Route::get('episodes/{episode}', [V1\CatalogueController::class, 'episode'])->name('episodes.show');
        Route::get('podcasts', [V1\CatalogueController::class, 'podcasts'])->name('podcasts.index');
        Route::get('podcasts/{podcastChannel}', [V1\CatalogueController::class, 'podcast'])->name('podcasts.show');

        // Live broadcasting (M27) — what's on air + subscribe-only listener tokens
        Route::get('live-channels', [V1\LiveController::class, 'index'])->name('live-channels.index');
        Route::get('live-channels/{broadcastChannel}', [V1\LiveController::class, 'show'])->name('live-channels.show');
        Route::post('live-channels/{broadcastChannel}/token', [V1\LiveController::class, 'token'])->name('live-channels.token');
        Route::post('live-channels/{broadcastChannel}/raise-hand', [V1\LiveController::class, 'raiseHand'])->name('live-channels.raise-hand');
        Route::post('live-channels/{broadcastChannel}/lower-hand', [V1\LiveController::class, 'lowerHand'])->name('live-channels.lower-hand');
        Route::get('podcast-episodes/{podcastEpisode}', [V1\CatalogueController::class, 'podcastEpisode'])->name('podcast-episodes.show');
        Route::get('assets/{asset}', [V1\CatalogueController::class, 'asset'])->name('assets.show');
        Route::get('playlists/{playlist}', [V1\CatalogueController::class, 'playlist'])->name('playlists.public.show');

        // Playback & streaming (M07) — entitlement enforced inside
        Route::get('assets/{asset}/stream', [V1\PlaybackController::class, 'stream'])->name('stream');
        Route::post('assets/{asset}/events', [V1\PlaybackController::class, 'event'])->name('events');
        Route::post('ads/impression', [V1\PlaybackController::class, 'adImpression'])->name('ads.impression');

        // Comments read + recommendations fallback (M25/M26)
        Route::get('assets/{asset}/comments', [V1\EngagementController::class, 'comments'])->name('comments.index');
        Route::get('recommendations/for-you', [V1\RecommendationController::class, 'forYou'])->name('recommendations.for-you');
        Route::get('assets/{asset}/similar', [V1\RecommendationController::class, 'similar'])->name('recommendations.similar');

        // Subscriptions — plans are public (M18)
        Route::get('plans', [V1\SubscriptionController::class, 'plans'])->name('plans');

        // Issue reports & feedback can be sent by guests (M26)
        Route::post('issue-reports', [V1\EngagementController::class, 'reportIssue'])->name('issue-reports.store');
        Route::post('feedback', [V1\EngagementController::class, 'feedback'])->name('feedback.store');
    });

    // Signed streaming endpoint (temporary URL issued by StreamingService)
    Route::get('stream/{asset}/play/{version}', [V1\PlaybackController::class, 'play'])
        ->name('stream.play')->middleware('signed');

    // Ad creative audio for pre-roll slots (public — referenced by audio_url
    // in the ad descriptor returned by GET /assets/{asset}/stream)
    Route::get('ads/{adCampaign}/audio', [V1\PlaybackController::class, 'adAudio'])->name('ads.audio');

    /*
     |--------------------------------------------------------------------
     | Authenticated listener only (auth:sanctum)
     |--------------------------------------------------------------------
     */
    Route::middleware('auth:sanctum')->group(function (): void {

        // Library — playlists, favourites, follows, history, queue (M17)
        Route::get('me/playlists', [V1\LibraryController::class, 'playlists'])->name('playlists.index');
        Route::post('me/playlists', [V1\LibraryController::class, 'createPlaylist'])->name('playlists.store');
        Route::get('me/playlists/{playlist}', [V1\LibraryController::class, 'showPlaylist'])->name('playlists.show');
        Route::put('me/playlists/{playlist}', [V1\LibraryController::class, 'updatePlaylist'])->name('playlists.update');
        Route::delete('me/playlists/{playlist}', [V1\LibraryController::class, 'deletePlaylist'])->name('playlists.destroy');
        Route::post('me/playlists/{playlist}/items', [V1\LibraryController::class, 'addPlaylistItem'])->name('playlists.items.store');
        Route::delete('me/playlists/{playlist}/items/{item}', [V1\LibraryController::class, 'removePlaylistItem'])->name('playlists.items.destroy');
        Route::put('me/playlists/{playlist}/reorder', [V1\LibraryController::class, 'reorderPlaylist'])->name('playlists.reorder');

        Route::get('me/favorites', [V1\LibraryController::class, 'favorites'])->name('favorites.index');
        Route::post('me/favorites/toggle', [V1\LibraryController::class, 'toggleFavorite'])->name('favorites.toggle');

        // Premium offline download — returns the full audio file (entitlement enforced).
        Route::get('assets/{asset}/download', [V1\PlaybackController::class, 'download'])->name('assets.download');

        Route::get('me/follows', [V1\LibraryController::class, 'follows'])->name('follows.index');
        Route::post('me/follows/toggle', [V1\LibraryController::class, 'toggleFollow'])->name('follows.toggle');

        Route::get('me/history', [V1\LibraryController::class, 'history'])->name('history.index');
        Route::get('me/continue-listening', [V1\LibraryController::class, 'continueListening'])->name('continue-listening');

        Route::get('me/queue', [V1\LibraryController::class, 'getQueue'])->name('queue.show');
        Route::put('me/queue', [V1\LibraryController::class, 'saveQueue'])->name('queue.save');

        // Engagement — comments, ratings, reports (M26)
        Route::post('assets/{asset}/comments', [V1\EngagementController::class, 'postComment'])->name('comments.store');
        Route::delete('comments/{comment}', [V1\EngagementController::class, 'deleteComment'])->name('comments.destroy');
        Route::post('assets/{asset}/rate', [V1\EngagementController::class, 'rate'])->name('rate');
        Route::post('reports', [V1\EngagementController::class, 'report'])->name('reports.store');

        // The listener's own Community Inbox — track status of what they sent (M26)
        Route::get('me/submissions', [V1\EngagementController::class, 'mySubmissions'])->name('submissions.index');

        // Recommendations personalization (M25)
        Route::post('me/personalization/opt-out', [V1\RecommendationController::class, 'optOut'])->name('recommendations.opt-out');

        // Subscription management (M18)
        Route::get('me/subscription', [V1\SubscriptionController::class, 'status'])->name('subscription.status');
        Route::post('me/subscription/subscribe', [V1\SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
        Route::post('me/subscription/cancel', [V1\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::get('me/payments', [V1\SubscriptionController::class, 'payments'])->name('payments.index');
    });
});

// LiveKit server webhooks (M27) — outside /v1, authenticated by signed JWT in
// the Authorization header (verified in the controller), not Sanctum.
Route::post('livekit/webhook', [LiveKitWebhookController::class, 'handle'])->name('api.livekit.webhook');
