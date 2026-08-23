@extends('layouts.admin')

@section('title', 'Watch Shows')

@section('content')
<x-page-header title="Watch Shows" subtitle="Manage shows, episode metadata and video uploads for the public Watch portal.">
    @can('watch.manage')
        <a href="{{ route('admin.watch-shows.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Show</a>
    @endcan
</x-page-header>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="w-full sm:max-w-sm"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search shows or categories…" class="form-input w-full"></form>
    <span class="text-sm text-slate-500 dark:text-slate-400">{{ $shows->total() }} shows</span>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($shows as $show)
        <article class="card overflow-hidden">
            <div class="relative aspect-video bg-slate-900">
                @if ($show->image_path)
                    <img src="{{ asset('storage/'.$show->image_path) }}" alt="" class="size-full object-cover">
                @else
                    <div class="grid size-full place-items-center text-slate-500"><x-icon name="play" class="size-12" /></div>
                @endif
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-4 pt-10 text-white">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">{{ $show->eyebrow ?: $show->category }}</p>
                    <h2 class="mt-1 font-semibold">{{ $show->title }}</h2>
                </div>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ $show->category }}</span><span>·</span><span>{{ $show->year ?: 'Year unset' }}</span><span>·</span><span>{{ $show->episodes_count }} episodes</span>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="flex gap-1.5">
                        <span class="{{ $show->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ $show->is_published ? 'Published' : 'Not public' }}</span>
                        <x-status-badge :status="$show->approval_status ?? 'draft'" />
                        @if ($show->is_featured)<span class="badge-amber">Featured</span>@endif
                    </div>
                    @can('watch.manage')
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.watch-shows.edit', $show) }}" class="btn-ghost btn-sm" title="Edit and manage episodes"><x-icon name="pencil" class="size-4" /></a>
                            @if (! $show->is_published && in_array($show->approval_status, ['draft', 'rejected', 'changes_requested'], true))
                                <form method="POST" action="{{ route('admin.watch-shows.submit', $show) }}">@csrf<button class="btn-secondary btn-sm" title="Submit for approval"><x-icon name="upload" class="size-4" /><span class="sr-only">Submit for approval</span></button></form>
                            @endif
                            @can('watch.publish')
                                @if ($show->is_published)
                                    <form method="POST" action="{{ route('admin.watch-shows.unpublish', $show) }}">@csrf<button class="btn-secondary btn-sm" title="Unpublish"><x-icon name="eye" class="size-4" /><span class="sr-only">Unpublish</span></button></form>
                                @elseif ($show->approval_status === 'approved')
                                    <form method="POST" action="{{ route('admin.watch-shows.publish', $show) }}">@csrf<button class="btn-primary btn-sm" title="Publish"><x-icon name="check-badge" class="size-4" /><span class="sr-only">Publish</span></button></form>
                                @endif
                            @endcan
                            <x-confirm-delete :action="route('admin.watch-shows.destroy', $show)" confirm="Delete this show and all its episodes?" />
                        </div>
                    @endcan
                </div>
            </div>
        </article>
    @empty
        <div class="sm:col-span-2 xl:col-span-3"><x-empty-state icon="play" title="No Watch shows yet" message="Create a show, upload its image, then add episodes." /></div>
    @endforelse
</div>

@if ($shows->hasPages())
    <div class="mt-6">{{ $shows->links() }}</div>
@endif
@endsection
