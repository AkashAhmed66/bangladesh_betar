@extends('layouts.admin')

@section('title', 'Artists')

@section('content')
<x-page-header title="Artists & People" subtitle="Reusable person records — singers, composers, presenters, voice artists (FR-MET-04)">
    @can('artists.manage')
        <a href="{{ route('admin.artists.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Artist</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search artists…" class="form-input w-56">
            <select name="type" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $artists->total() }} artists</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Artist</th><th>Type</th><th>Songs</th><th>Followers</th><th>Featured</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($artists as $artist)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-100 text-sm font-semibold text-accent-800 dark:bg-accent-500/20 dark:text-accent-300">
                                    {{ strtoupper(substr($artist->name, 0, 1)) }}
                                </span>
                                <div>
                                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $artist->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $artist->name_bn }}</p>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $artist->artist_type)) }}</span></td>
                        <td class="text-sm tabular-nums">{{ $artist->songs_count }}</td>
                        <td class="text-sm tabular-nums">{{ number_format($artist->followers_count) }}</td>
                        <td>@if ($artist->is_featured)<span class="badge-amber">Featured</span>@endif</td>
                        <td><x-status-badge :status="$artist->is_published ? 'published' : 'draft'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('artists.manage')
                                    <a href="{{ route('admin.artists.edit', $artist) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.artists.destroy', $artist)" confirm="Remove artist {{ $artist->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="user-circle" title="No artists found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($artists->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $artists->links() }}</div>
    @endif
</div>
@endsection
