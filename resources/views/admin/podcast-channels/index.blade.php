@extends('layouts.admin')

@section('title', 'Podcast Channels')

@section('content')
<x-page-header title="Podcast Channels" subtitle="RSS-capable podcast feeds (M09 / FR-POD-01)">
    @can('podcasts.manage')
        <a href="{{ route('admin.podcast-channels.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Channel</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search channels…" class="form-input w-64">
            <button class="btn-secondary btn-sm">Search</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $channels->total() }} channels</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Channel</th><th>Category</th><th>Owner</th><th>Episodes</th><th>Followers</th><th>RSS</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($channels as $channel)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $channel->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $channel->title_bn ?? $channel->language?->name }}</p>
                        </td>
                        <td class="text-sm">{{ $channel->category?->name ?? '—' }}</td>
                        <td class="text-sm">{{ $channel->owner?->name ?? '—' }}</td>
                        <td class="text-sm tabular-nums">{{ $channel->episodes_count }}</td>
                        <td class="text-sm tabular-nums">{{ number_format((int) $channel->followers_count) }}</td>
                        <td>
                            @if ($channel->rss_enabled)
                                <span class="badge-green">Enabled</span>
                                @if ($channel->rss_include_premium)<span class="badge-amber">+ Premium</span>@endif
                            @else
                                <span class="badge-slate">Off</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$channel->is_published ? 'published' : 'draft'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('podcasts.manage')
                                    <a href="{{ route('admin.podcast-channels.edit', $channel) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.podcast-channels.destroy', $channel)" confirm="Delete channel {{ $channel->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="microphone" title="No podcast channels yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($channels->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $channels->links() }}</div>
    @endif
</div>
@endsection
