@extends('layouts.admin')

@section('title', 'Playlists')

@section('content')
<x-page-header title="Editorial Playlists & Collections" subtitle="Curated collections publishable to the public app (FR-CUR-02)">
    @can('playlists.manage')
        <a href="{{ route('admin.playlists.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Collection</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Collection</th><th>Tracks</th><th>Followers</th><th>Type</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($playlists as $playlist)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $playlist->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $playlist->title_bn ?: Str::limit($playlist->description, 50) }}</p>
                        </td>
                        <td class="text-sm tabular-nums">{{ $playlist->items_count }}</td>
                        <td class="text-sm tabular-nums">{{ number_format($playlist->followers_count) }}</td>
                        <td><span class="badge-{{ $playlist->is_editorial ? 'primary' : 'blue' }}">{{ $playlist->is_editorial ? 'Editorial' : 'Listener' }}</span></td>
                        <td><x-status-badge :status="$playlist->is_published ? 'published' : 'draft'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('playlists.manage')
                                    <a href="{{ route('admin.playlists.edit', $playlist) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.playlists.destroy', $playlist)" confirm="Delete collection {{ $playlist->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="queue" title="No collections yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($playlists->hasPages())<div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $playlists->links() }}</div>@endif
</div>
@endsection
