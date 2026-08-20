@extends('layouts.admin')

@section('title', $show ? 'Edit Watch Show' : 'New Watch Show')

@section('content')
<x-page-header :title="$show ? 'Edit: '.$show->title : 'Create Watch Show'" subtitle="Show details power the hero, shelves and detail pages in the public Watch portal.">
    @if ($show)
        <a href="{{ route('admin.watch-shows.episodes.create', $show) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Add Episode</a>
    @endif
</x-page-header>

<form method="POST" action="{{ $show ? route('admin.watch-shows.update', $show) : route('admin.watch-shows.store') }}" enctype="multipart/form-data" class="max-w-4xl">
    @csrf
    @if ($show) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Show title" name="title" :value="$show?->title" required />
            <x-form.input label="URL slug" name="slug" :value="$show?->slug" required />
            <x-form.input label="Eyebrow" name="eyebrow" :value="$show?->eyebrow" placeholder="New original drama" />
            <x-form.select label="Category" name="category" :value="$show?->category ?? 'Documentary'" required :options="$categories" help="This controls which Watch category page displays the show." />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$show?->description" rows="4" required /></div>
            <x-form.artwork-upload class="sm:col-span-2" :current-path="$show?->image_path" label="Show hero image" :wide="true" help="JPG, PNG or WebP, up to 8 MB. Use a cinematic 16:9 image." />
            <x-form.input label="Year" name="year" type="number" :value="$show?->year ?? date('Y')" />
            <x-form.input label="Audience rating" name="rating" :value="$show?->rating ?? 'G'" placeholder="G, PG…" />
            <x-form.input label="Display position" name="position" type="number" :value="$show?->position ?? 0" required />
            <x-form.input label="Publish date and time" name="published_at" type="datetime-local" :value="$show?->published_at?->setTimezone(config('portal.admin_timezone'))->format('Y-m-d\TH:i')" help="Bangladesh time. Leave empty to publish immediately." />
            <x-form.toggle label="Featured hero" name="is_featured" :checked="(bool) $show?->is_featured" help="Featured shows are prioritised in the Watch hero." />
            <x-form.toggle label="Published" name="is_published" :checked="(bool) $show?->is_published" help="Draft shows and their episodes stay hidden from the public API." />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.watch-shows.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $show ? 'Save Changes' : 'Create Show' }}</button>
        </div>
    </div>
</form>

@if ($show)
    <section class="mt-8 max-w-4xl">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div><h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Episodes</h2><p class="text-sm text-slate-500 dark:text-slate-400">Upload videos or manage the episode list and order.</p></div>
            <a href="{{ route('admin.watch-shows.episodes.create', $show) }}" class="btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> Add</a>
        </div>
        <div class="card overflow-hidden">
            <div class="table-shell">
                <table class="table-app">
                    <thead><tr><th>#</th><th>Episode</th><th>Length</th><th>Video</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($show->episodes as $episode)
                            <tr>
                                <td class="text-slate-400">{{ $episode->position }}</td>
                                <td><p class="font-medium text-slate-800 dark:text-slate-100">{{ $episode->title }}</p><p class="mt-0.5 max-w-md line-clamp-1 text-xs text-slate-400">{{ $episode->description }}</p></td>
                                <td class="whitespace-nowrap text-sm">{{ $episode->duration_minutes }} min</td>
                                <td><span class="{{ $episode->video_path ? 'badge-emerald' : 'badge-slate' }}">{{ $episode->video_path ? 'Uploaded' : 'Not uploaded' }}</span></td>
                                <td><span class="{{ $episode->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ $episode->is_published ? 'Published' : 'Draft' }}</span></td>
                                <td><div class="flex items-center justify-end gap-1"><a href="{{ route('admin.watch-episodes.edit', $episode) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a><x-confirm-delete :action="route('admin.watch-episodes.destroy', $episode)" confirm="Delete this episode?" /></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state icon="play" title="No episodes yet" message="Add the first episode to this show." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif
@endsection
