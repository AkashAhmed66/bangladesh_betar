@extends('layouts.admin')

@section('title', 'Songs')

@section('content')
<x-page-header title="Music Library" subtitle="Songs with version families, mood/genre and popularity rankings (M08)">
    @can('songs.manage')
        <a href="{{ route('admin.songs.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Song</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search songs…" class="form-input w-56">
            <select name="genre" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All genres</option>
                @foreach ($genres as $id => $name)<option value="{{ $id }}" @selected(request('genre') == $id)>{{ $name }}</option>@endforeach
            </select>
            <select name="album" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All albums</option>
                @foreach ($albums as $id => $name)<option value="{{ $id }}" @selected(request('album') == $id)>{{ $name }}</option>@endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $songs->total() }} songs</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Song</th><th>Singer</th><th>Album</th><th>Genre</th><th>Version</th><th>Verified</th><th>Plays</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($songs as $song)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $song->audioAsset?->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $song->audioAsset?->title_bn }}</p>
                        </td>
                        <td class="text-sm">{{ $song->artists->where('pivot.role', 'singer')->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td class="text-sm">{{ $song->album?->title ?? '—' }}</td>
                        <td class="text-sm">{{ $song->genre?->name ?? '—' }}</td>
                        <td><span class="badge-slate">{{ ucfirst($song->version_type) }}</span></td>
                        <td>@if ($song->mood_genre_verified)<span class="badge-green">Verified</span>@else<span class="badge-amber">Draft</span>@endif</td>
                        <td class="text-sm tabular-nums">{{ number_format($song->audioAsset?->play_count ?? 0) }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @if ($song->audioAsset)<a href="{{ route('admin.assets.show', $song->audioAsset) }}" class="btn-ghost btn-sm"><x-icon name="eye" class="size-4" /></a>@endif
                                @can('songs.manage')
                                    <a href="{{ route('admin.songs.edit', $song) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.songs.destroy', $song)" confirm="Remove this song?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="music" title="No songs found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($songs->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $songs->links() }}</div>
    @endif
</div>
@endsection
