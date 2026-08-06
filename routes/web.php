<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

/*
|--------------------------------------------------------------------------
| Admin Web Portal (back office)
|--------------------------------------------------------------------------
| Every action is permission-gated (Spatie). Permissions are seeded in
| RolePermissionSeeder; see that file for the full matrix.
*/

Route::prefix('admin')->name('admin.')->group(function (): void {

    // ---- Authentication ----
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->name('login.attempt');
    });

    Route::middleware(['auth', 'staff'])->group(function (): void {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

        // ---- Self-service profile (every portal user, incl. artists) ----
        // Not permission-gated: a user may always manage their own account.
        Route::get('profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

        Route::get('/', [Admin\DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')->name('dashboard');

        // ---- M01: Users & Roles ----
        Route::resource('users', Admin\UserController::class)->except('show')
            ->middleware('permission:users.view');
        Route::resource('roles', Admin\RoleController::class)->except('show')
            ->middleware('permission:roles.view');

        // ---- M04: Stations & hierarchy ----
        Route::resource('stations', Admin\StationController::class)->except('show')
            ->middleware('permission:stations.view');
        Route::resource('programmes', Admin\ProgrammeController::class)->except('show')
            ->middleware('permission:programmes.view');

        // ---- M05: Vocabularies (categories, genres, moods, languages, tags) ----
        Route::prefix('vocabularies/{type}')->whereIn('type', ['categories', 'genres', 'moods', 'languages', 'tags'])
            ->middleware('permission:taxonomies.view')->name('vocabularies.')->group(function (): void {
                Route::get('/', [Admin\VocabularyController::class, 'index'])->name('index');
                Route::post('/', [Admin\VocabularyController::class, 'store'])->name('store');
                Route::put('{id}', [Admin\VocabularyController::class, 'update'])->name('update');
                Route::delete('{id}', [Admin\VocabularyController::class, 'destroy'])->name('destroy');
            });

        Route::resource('artists', Admin\ArtistController::class)->except('show')
            ->middleware('permission:artists.view');

        // ---- M02/M04: Audio assets & versions ----
        Route::middleware('permission:assets.view')->group(function (): void {
            Route::get('assets', [Admin\AudioAssetController::class, 'index'])->name('assets.index');
            Route::get('assets/create', [Admin\AudioAssetController::class, 'create'])
                ->middleware('permission:assets.upload')->name('assets.create');
            Route::post('assets', [Admin\AudioAssetController::class, 'store'])
                ->middleware('permission:assets.upload')->name('assets.store');
            Route::get('assets/{asset}', [Admin\AudioAssetController::class, 'show'])->name('assets.show');
            Route::get('assets/{asset}/edit', [Admin\AudioAssetController::class, 'edit'])
                ->middleware('permission:assets.edit')->name('assets.edit');
            Route::put('assets/{asset}', [Admin\AudioAssetController::class, 'update'])
                ->middleware('permission:assets.edit')->name('assets.update');
            Route::delete('assets/{asset}', [Admin\AudioAssetController::class, 'destroy'])
                ->middleware('permission:assets.delete')->name('assets.destroy');
            Route::post('assets/{asset}/publish', [Admin\AudioAssetController::class, 'publish'])
                ->middleware('permission:assets.publish')->name('assets.publish');
            Route::post('assets/{asset}/unpublish', [Admin\AudioAssetController::class, 'unpublish'])
                ->middleware('permission:assets.publish')->name('assets.unpublish');
            // Choose which version the public streams (before or after publishing).
            Route::post('assets/{asset}/versions/{version}/streaming', [Admin\AudioAssetController::class, 'setStreamingVersion'])
                ->middleware('permission:assets.publish')->name('assets.versions.streaming');
            Route::post('assets/{asset}/submit', [Admin\AudioAssetController::class, 'submitForApproval'])
                ->middleware('permission:assets.edit')->name('assets.submit');
            // FR-CPR-01/02 — submitter files the copyright documents after approval.
            Route::post('assets/{asset}/submit-rights', [Admin\AudioAssetController::class, 'submitForRights'])
                ->middleware('permission:assets.edit')->name('assets.submit-rights');

            // M02 — (re)ingest audio for an existing asset (one file at a time)
            Route::post('assets/{asset}/upload', [Admin\AudioAssetController::class, 'uploadMaster'])
                ->middleware('permission:assets.upload')->name('assets.upload');

            // Requirements §11 — per-asset listener analytics & heat map
            Route::get('assets/{asset}/analytics', [Admin\AssetAnalyticsController::class, 'show'])->name('assets.analytics');

            // Audio Studio — visualization + non-destructive editing
            Route::get('assets/{asset}/studio', [Admin\AudioStudioController::class, 'show'])->name('assets.studio');
            Route::get('assets/{asset}/versions/{version}/audio', [Admin\AudioStudioController::class, 'streamVersion'])->name('assets.stream');
            // EBU R128 loudness analysis (integrated LUFS, LRA, true-peak, curve)
            Route::post('assets/{asset}/loudness', [Admin\AudioStudioController::class, 'loudness'])->name('assets.loudness');
            // Signal statistics (astats): RMS, peak, DC offset, crest factor, noise floor, clipping
            Route::post('assets/{asset}/astats', [Admin\AudioStudioController::class, 'astats'])->name('assets.astats');
            Route::post('assets/{asset}/peaks', [Admin\AudioStudioController::class, 'savePeaks'])
                ->middleware('permission:assets.edit')->name('assets.peaks');
            Route::get('assets/{asset}/markers', [Admin\AudioStudioController::class, 'markers'])->name('assets.markers');
            Route::post('assets/{asset}/markers', [Admin\AudioStudioController::class, 'storeMarker'])
                ->middleware('permission:assets.edit')->name('assets.markers.store');
            // Bulk-save chapters (Studio "Save" button) — persists only on save.
            Route::post('assets/{asset}/markers/sync', [Admin\AudioStudioController::class, 'syncMarkers'])
                ->middleware('permission:assets.edit')->name('assets.markers.sync');
            Route::patch('assets/{asset}/markers/{marker}', [Admin\AudioStudioController::class, 'updateMarker'])
                ->middleware('permission:assets.edit')->name('assets.markers.update');
            Route::delete('assets/{asset}/markers/{marker}', [Admin\AudioStudioController::class, 'deleteMarker'])
                ->middleware('permission:assets.edit')->name('assets.markers.destroy');
            Route::post('assets/{asset}/edit-session', [Admin\AudioStudioController::class, 'saveEdit'])
                ->middleware('permission:editing.use')->name('assets.edit-session');

            // M12 — render an edit/enhancement chain into a new version (ffmpeg)
            Route::post('assets/{asset}/render', [Admin\AudioStudioController::class, 'submitRender'])
                ->middleware('permission:editing.use')->name('assets.render');
            Route::get('assets/{asset}/render/{editSession}/status', [Admin\AudioStudioController::class, 'renderStatus'])
                ->name('assets.render.status');

            // Fast auto-preview render (sound effects over a short window)
            Route::post('assets/{asset}/preview', [Admin\AudioStudioController::class, 'previewRender'])
                ->middleware('permission:editing.use')->name('assets.preview');
            Route::get('assets/{asset}/preview-audio', [Admin\AudioStudioController::class, 'previewAudio'])
                ->middleware('permission:editing.use')->name('assets.preview-audio');
        });

        // ---- M31: Audio Books (PDF/text → dual-voice narration → approval → premium public) ----
        Route::redirect('speech', 'audiobooks');   // old PDF-to-Speech links keep working
        Route::middleware('permission:audiobooks.use')->group(function (): void {
            Route::get('audiobooks', [Admin\AudioBookController::class, 'index'])->name('audiobooks.index');
            Route::get('audiobooks/create', [Admin\AudioBookController::class, 'create'])->name('audiobooks.create');
            Route::post('audiobooks', [Admin\AudioBookController::class, 'store'])->name('audiobooks.store');
            Route::get('audiobooks/{audiobook}', [Admin\AudioBookController::class, 'show'])->name('audiobooks.show');
            Route::post('audiobooks/{audiobook}/submit', [Admin\AudioBookController::class, 'submit'])->name('audiobooks.submit');
            Route::post('audiobooks/{audiobook}/text', [Admin\AudioBookController::class, 'updateText'])->name('audiobooks.update-text');
            Route::post('audiobooks/{audiobook}/review', [Admin\AudioBookController::class, 'review'])
                ->middleware('permission:audiobooks.approve')->name('audiobooks.review');
            Route::post('audiobooks/{audiobook}/unpublish', [Admin\AudioBookController::class, 'unpublish'])
                ->middleware('permission:audiobooks.approve')->name('audiobooks.unpublish');
            Route::get('audiobooks/{audiobook}/audio/{voice}', [Admin\AudioBookController::class, 'audio'])->name('audiobooks.audio');
            Route::delete('audiobooks/{audiobook}', [Admin\AudioBookController::class, 'destroy'])->name('audiobooks.destroy');
        });

        // ---- M03: Digitization ----
        Route::resource('media-items', Admin\MediaItemController::class)->except('show')
            ->middleware('permission:digitization.view');

        // ---- M08: Music library ----
        Route::resource('albums', Admin\AlbumController::class)->except('show')
            ->middleware('permission:albums.view');
        Route::resource('songs', Admin\SongController::class)->except('show')
            ->middleware('permission:songs.view');
        Route::resource('playlists', Admin\PlaylistController::class)->only(['index', 'show'])
            ->middleware('permission:playlists.view');

        // ---- M09: Podcasts ----
        Route::resource('podcast-channels', Admin\PodcastChannelController::class)->except('show')
            ->middleware('permission:podcasts.view');
        Route::resource('podcast-episodes', Admin\PodcastEpisodeController::class)->except('show')
            ->middleware('permission:podcasts.view');

        // ---- M27: Live broadcasting ----
        // Custom routes are declared before the resource so /studio, /status etc.
        // are matched ahead of the {broadcast_channel} wildcard.
        Route::get('broadcast-channels/{broadcastChannel}/studio', [Admin\BroadcastChannelController::class, 'studio'])
            ->middleware('permission:broadcasts.broadcast')->name('broadcast-channels.studio');
        Route::get('broadcast-channels/{broadcastChannel}/status', [Admin\BroadcastChannelController::class, 'status'])
            ->middleware('permission:broadcasts.view')->name('broadcast-channels.status');
        Route::post('broadcast-channels/{broadcastChannel}/go-live', [Admin\BroadcastChannelController::class, 'goLive'])
            ->middleware('permission:broadcasts.broadcast')->name('broadcast-channels.go-live');
        Route::post('broadcast-channels/{broadcastChannel}/stop', [Admin\BroadcastChannelController::class, 'stop'])
            ->middleware('permission:broadcasts.broadcast')->name('broadcast-channels.stop');
        Route::get('broadcast-channels/{broadcastChannel}/participants', [Admin\BroadcastChannelController::class, 'participants'])
            ->middleware('permission:broadcasts.view')->name('broadcast-channels.participants');
        Route::post('broadcast-channels/{broadcastChannel}/grant-speak', [Admin\BroadcastChannelController::class, 'grantSpeak'])
            ->middleware('permission:broadcasts.broadcast')->name('broadcast-channels.grant-speak');
        Route::post('broadcast-channels/{broadcastChannel}/revoke-speak', [Admin\BroadcastChannelController::class, 'revokeSpeak'])
            ->middleware('permission:broadcasts.broadcast')->name('broadcast-channels.revoke-speak');
        Route::resource('broadcast-channels', Admin\BroadcastChannelController::class)->except('show')
            ->middleware('permission:broadcasts.view');

        // ---- M10: Event programmes (Bhoot FM) ----
        Route::resource('episodes', Admin\EpisodeController::class)->except('show')
            ->middleware('permission:episodes.view');

        // ---- M12: Edit sessions ----
        Route::get('edit-sessions', [Admin\EditSessionController::class, 'index'])
            ->middleware('permission:editing.view')->name('edit-sessions.index');

        // ---- M13: Workflow & approvals ----
        Route::resource('workflows', Admin\WorkflowController::class)->except('show')
            ->middleware('permission:workflows.view');
        Route::get('approvals', [Admin\ApprovalController::class, 'index'])
            ->middleware('permission:approvals.view')->name('approvals.index');
        Route::get('approvals/{approval}', [Admin\ApprovalController::class, 'show'])
            ->middleware('permission:approvals.view')->name('approvals.show');
        Route::post('approvals/{approval}/act', [Admin\ApprovalController::class, 'act'])
            ->middleware('permission:approvals.act')->name('approvals.act');

        // ---- M30: Notifications (approval / moderation / rights events) ----
        Route::get('notifications', [Admin\NotificationController::class, 'index'])
            ->middleware('permission:notifications.view')->name('notifications.index');
        Route::get('notifications/poll', [Admin\NotificationController::class, 'poll'])->name('notifications.poll');
        Route::get('notifications/{id}/open', [Admin\NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/read-all', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');

        // ---- M14: Rights ----
        Route::resource('rights-holders', Admin\RightsHolderController::class)->except('show')
            ->middleware('permission:rights.view');
        Route::resource('rights-records', Admin\RightsRecordController::class)->except('show')
            ->middleware('permission:rights.view');
        // Copyright document download — gated in-controller: rights team OR the
        // submitter of the asset (record visibility), so no rights.view middleware.
        Route::get('rights-records/{rightsRecord}/documents/{index}', [Admin\RightsRecordController::class, 'document'])
            ->whereNumber('index')->name('rights-records.document');
        // Review page (full details + approve/reject) — visible to the rights
        // team AND the submitter (gated in-controller); acting needs rights.manage.
        Route::get('rights-records/{rightsRecord}', [Admin\RightsRecordController::class, 'show'])
            ->name('rights-records.show');
        Route::post('rights-records/{rightsRecord}/review', [Admin\RightsRecordController::class, 'review'])
            ->middleware('permission:rights.manage')->name('rights-records.review');

        // ---- M16: AI content moderation (duplicate / violence / anti-government + transcription) ----
        Route::get('ai-moderation', [Admin\AiModerationController::class, 'index'])
            ->middleware('permission:ai-moderation.view')->name('ai-moderation.index');
        // Review page — visible to AI reviewers AND the uploader (gated in-controller).
        Route::get('ai-moderation/{asset}', [Admin\AiModerationController::class, 'show'])->name('ai-moderation.show');
        Route::post('ai-moderation/{asset}/review', [Admin\AiModerationController::class, 'review'])
            ->middleware('permission:ai-moderation.review')->name('ai-moderation.review');
        // FR-AIF-06 — correct the AI transcript during moderation.
        Route::put('ai-moderation/{asset}/transcript', [Admin\AiModerationController::class, 'updateTranscript'])
            ->middleware('permission:ai-moderation.review')->name('ai-moderation.transcript');

        // ---- M24: Curation ----
        Route::resource('banners', Admin\BannerController::class)->except('show')
            ->middleware('permission:curation.view');

        // ---- M26: Moderation & feedback ----
        // Comments moderation + the unified Community Inbox (reports, content
        // issues, feedback) both sit under the `moderation` permission.
        Route::middleware('permission:moderation.view')->group(function (): void {
            Route::get('comments', [Admin\CommentModerationController::class, 'index'])->name('comments.index');
            Route::post('comments/{comment}/moderate', [Admin\CommentModerationController::class, 'moderate'])
                ->middleware('permission:moderation.manage')->name('comments.moderate');
            Route::get('community-inbox', [Admin\CommunityInboxController::class, 'index'])->name('community-inbox.index');
            Route::post('community-inbox/{submission}/status', [Admin\CommunityInboxController::class, 'updateStatus'])
                ->middleware('permission:moderation.manage')->name('community-inbox.update-status');
        });

        // ---- M18: Plans, subscriptions, payments ----
        Route::resource('plans', Admin\PlanController::class)->only(['index', 'edit', 'update'])
            ->middleware('permission:plans.view');
        Route::resource('promo-codes', Admin\PromoCodeController::class)->except('show')
            ->middleware('permission:plans.view');
        Route::get('subscriptions', [Admin\SubscriptionController::class, 'index'])
            ->middleware('permission:subscriptions.view')->name('subscriptions.index');
        Route::post('subscriptions/{subscription}/cancel', [Admin\SubscriptionController::class, 'cancel'])
            ->middleware('permission:subscriptions.manage')->name('subscriptions.cancel');
        Route::get('payments', [Admin\PaymentController::class, 'index'])
            ->middleware('permission:payments.view')->name('payments.index');
        Route::post('payments/{payment}/refund', [Admin\PaymentController::class, 'refund'])
            ->middleware('permission:payments.refund')->name('payments.refund');

        // ---- M27: Advertisements ----
        Route::middleware('permission:ads.view')->group(function (): void {
            Route::resource('advertisers', Admin\AdvertiserController::class)->except('show');
            Route::resource('ad-campaigns', Admin\AdCampaignController::class)->except('show');
        });

        // ---- M21/M22: Audit & preservation ----
        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')->name('audit-logs.index');
        Route::get('backups', [Admin\BackupController::class, 'index'])
            ->middleware('permission:backups.view')->name('backups.index');

        // ---- Settings (incl. theme) ----
        Route::get('settings', [Admin\SettingController::class, 'edit'])
            ->middleware('permission:settings.view')->name('settings.edit');
        Route::put('settings', [Admin\SettingController::class, 'update'])
            ->middleware('permission:settings.manage')->name('settings.update');
    });
});
