@extends('layouts.admin')

@section('title', 'Albums')

@section('content')
<x-page-header title="Albums" subtitle="First-class browsable album entities with artwork and track lists (FR-SNG-09)">
    @can('albums.manage')
        <a href="{{ route('admin.albums.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Album</a>
    @endcan
</x-page-header>

<div class="mb-5 flex items-center justify-between">
    <form method="GET"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search albums…" class="form-input w-64"></form>
    <span class="text-sm text-slate-500 dark:text-slate-400">{{ $albums->total() }} albums</span>
</div>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
    @forelse ($albums as $album)
        <div class="card overflow-hidden">
            <div class="flex aspect-square items-center justify-center bg-gradient-to-br from-primary-600 to-primary-900">
                <x-icon name="disc" class="size-14 text-white/80" />
            </div>
            <div class="p-3.5">
                <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $album->title }}</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                    {{ $album->artists->pluck('name')->take(2)->implode(', ') ?: 'Various' }} · {{ $album->year }}
                </p>
                <div class="mt-2.5 flex items-center justify-between">
                    <span class="text-xs text-slate-400">{{ $album->songs_count }} tracks</span>
                    <div class="flex gap-1">
                        @if ($album->is_featured)<span class="badge-amber">★</span>@endif
                        @can('albums.manage')
                            <a href="{{ route('admin.albums.edit', $album) }}" class="btn-ghost btn-sm p-1"><x-icon name="pencil" class="size-3.5" /></a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full"><x-empty-state icon="disc" title="No albums yet" /></div>
    @endforelse
</div>

@if ($albums->hasPages())
    <div class="mt-6">{{ $albums->links() }}</div>
@endif
@endsection
