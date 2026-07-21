@php
    /** Sidebar map: section => [ [permission, route-name, icon, label, active-prefixes] ] */
    $nav = [
        '' => [
            ['dashboard.view', 'admin.dashboard', 'home', 'Dashboard', ['admin.dashboard']],
            ['approvals.view', 'admin.approvals.index', 'clipboard-check', 'My Approvals', ['admin.approvals.']],
        ],
        'Archive' => [
            ['assets.view', 'admin.assets.index', 'archive', 'Audio Assets', ['admin.assets.']],
            ['digitization.view', 'admin.media-items.index', 'disc', 'Digitization', ['admin.media-items.']],
            ['stations.view', 'admin.stations.index', 'radio', 'Stations', ['admin.stations.']],
            ['programmes.view', 'admin.programmes.index', 'squares', 'Programmes', ['admin.programmes.']],
            ['editing.view', 'admin.edit-sessions.index', 'scissors', 'Edit Sessions', ['admin.edit-sessions.']],
        ],
        'Catalogue' => [
            ['songs.view', 'admin.songs.index', 'music', 'Songs', ['admin.songs.']],
            ['albums.view', 'admin.albums.index', 'disc', 'Albums', ['admin.albums.']],
            ['artists.view', 'admin.artists.index', 'user-circle', 'Artists', ['admin.artists.']],
            ['playlists.view', 'admin.playlists.index', 'queue', 'Playlists', ['admin.playlists.']],
            ['taxonomies.view', 'admin.vocabularies.index', 'funnel', 'Vocabularies', ['admin.vocabularies.'], ['type' => 'categories']],
        ],
        'Content' => [
            ['podcasts.view', 'admin.podcast-channels.index', 'microphone', 'Podcast Channels', ['admin.podcast-channels.']],
            ['podcasts.view', 'admin.podcast-episodes.index', 'play', 'Podcast Episodes', ['admin.podcast-episodes.']],
            ['episodes.view', 'admin.episodes.index', 'ghost', 'Event Episodes', ['admin.episodes.']],
            ['stories.view', 'admin.stories.index', 'chat', 'Stories', ['admin.stories.']],
            ['submissions.view', 'admin.story-submissions.index', 'inbox', 'Story Submissions', ['admin.story-submissions.']],
        ],
        'Governance' => [
            ['workflows.view', 'admin.workflows.index', 'workflow', 'Workflows', ['admin.workflows.']],
            ['rights.view', 'admin.rights-records.index', 'scale', 'Rights Records', ['admin.rights-records.']],
            ['rights.view', 'admin.rights-holders.index', 'users', 'Rights Holders', ['admin.rights-holders.']],
            ['qc.view', 'admin.qc-reports.index', 'shield-check', 'Quality Control', ['admin.qc-reports.']],
            ['transcripts.view', 'admin.transcripts.index', 'document-text', 'Transcripts', ['admin.transcripts.']],
            ['ai-suggestions.view', 'admin.ai-suggestions.index', 'sparkles', 'AI Review', ['admin.ai-suggestions.']],
        ],
        'Community' => [
            ['moderation.view', 'admin.comments.index', 'chat', 'Comments', ['admin.comments.']],
            ['moderation.view', 'admin.content-reports.index', 'flag', 'Reported Content', ['admin.content-reports.']],
            ['issues.view', 'admin.issue-reports.index', 'exclamation', 'Issue Reports', ['admin.issue-reports.']],
            ['takedowns.view', 'admin.takedown-requests.index', 'shield', 'Takedowns', ['admin.takedown-requests.']],
            ['feedback.view', 'admin.feedback.index', 'inbox', 'Feedback', ['admin.feedback.']],
        ],
        'Curation' => [
            ['curation.view', 'admin.home-sections.index', 'squares', 'Home Sections', ['admin.home-sections.']],
            ['curation.view', 'admin.banners.index', 'flag', 'Banners', ['admin.banners.']],
        ],
        'Business' => [
            ['plans.view', 'admin.plans.index', 'star', 'Plans', ['admin.plans.']],
            ['plans.view', 'admin.promo-codes.index', 'star', 'Promo Codes', ['admin.promo-codes.']],
            ['subscriptions.view', 'admin.subscriptions.index', 'credit-card', 'Subscriptions', ['admin.subscriptions.']],
            ['payments.view', 'admin.payments.index', 'banknotes', 'Payments', ['admin.payments.']],
            ['ads.view', 'admin.ad-campaigns.index', 'megaphone', 'Ad Campaigns', ['admin.ad-campaigns.']],
            ['ads.view', 'admin.ad-assets.index', 'play', 'Ad Assets', ['admin.ad-assets.']],
            ['ads.view', 'admin.advertisers.index', 'users', 'Advertisers', ['admin.advertisers.']],
        ],
        'Insights' => [
            ['audit.view', 'admin.audit-logs.index', 'clock', 'Audit Trail', ['admin.audit-logs.']],
            ['backups.view', 'admin.backups.index', 'server', 'Backups', ['admin.backups.']],
        ],
        'System' => [
            ['users.view', 'admin.users.index', 'users', 'Users', ['admin.users.']],
            ['roles.view', 'admin.roles.index', 'shield', 'Roles & Permissions', ['admin.roles.']],
            ['settings.view', 'admin.settings.edit', 'cog', 'Settings', ['admin.settings.']],
        ],
    ];
@endphp

@foreach ($nav as $section => $items)
    @php
        $visible = array_filter($items, fn ($item) => auth()->user()->can($item[0]));
    @endphp
    @if ($visible !== [])
        @if ($section !== '')
            <p class="nav-section">{{ $section }}</p>
        @else
            <div class="pt-3"></div>
        @endif

        <div class="space-y-0.5">
            @foreach ($visible as $item)
                @php
                    [$permission, $route, $icon, $label, $prefixes] = $item;
                    $params = $item[5] ?? ($route === 'admin.vocabularies.index' ? ['type' => 'categories'] : []);
                    $active = collect($prefixes)->contains(fn ($p) => request()->routeIs($p.'*') || request()->routeIs(rtrim($p, '.')));
                @endphp
                <a href="{{ route($route, $params) }}" class="nav-item {{ $active ? 'active' : '' }}" title="{{ $label }}">
                    <x-icon :name="$icon" class="size-[18px] shrink-0" />
                    <span class="nav-label truncate">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endforeach
