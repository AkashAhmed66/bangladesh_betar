@extends('layouts.admin')

@section('title', 'Playlists')

@section('content')
<x-page-header title="Playlists" subtitle="Playlists created by listeners in the public app, and the tracks they added." />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by title or owner…" class="form-input w-64">
            <button class="btn-secondary btn-sm">Search</button>
            @if (request('q'))<a href="{{ route('admin.playlists.index') }}" class="btn-ghost btn-sm">Clear</a>@endif
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $playlists->total() }} playlists</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th>Playlist</th>
                    <th>Owner</th>
                    <th>Tracks</th>
                    <th>Visibility</th>
                    <th>Created</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($playlists as $playlist)
                    <tr>
                        <td>
                            <a href="{{ route('admin.playlists.show', $playlist) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $playlist->title }}</a>
                            @if ($playlist->title_bn)<p class="text-xs text-slate-500 dark:text-slate-400" style="font-family:'Noto Sans Bengali',sans-serif;">{{ $playlist->title_bn }}</p>@endif
                        </td>
                        <td class="text-sm">{{ $playlist->user?->name ?? '—' }}</td>
                        <td class="text-sm tabular-nums">{{ $playlist->items_count }}</td>
                        <td>
                            @if ($playlist->is_public)<span class="badge-green">Public</span>@else<span class="badge-slate">Private</span>@endif
                        </td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $playlist->created_at?->diffForHumans() }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.playlists.show', $playlist) }}" class="btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="queue" title="No playlists yet" message="Playlists created by listeners in the public app will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($playlists->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $playlists->links() }}</div>
    @endif
</div>
@endsection
