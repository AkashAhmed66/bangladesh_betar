@extends('layouts.admin')

@section('title', $show ? 'Edit Watch Show' : 'New Watch Show')

@section('content')
<x-page-header :title="$show ? 'Edit: '.$show->title : 'Create Watch Show'" subtitle="Build the show and episodes, complete editorial approval, then publish it to Watch.">
    @if ($show)
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.watch-shows.episodes.create', $show) }}" class="btn-secondary"><x-icon name="plus" class="size-4" /> {{ __('Add Episode') }}</a>
            @if (! $show->is_published && in_array($show->approval_status, ['draft', 'rejected', 'changes_requested'], true))
                <form method="POST" action="{{ route('admin.watch-shows.submit', $show) }}">@csrf<button class="btn-accent"><x-icon name="upload" class="size-4" /> {{ __('Submit for Approval') }}</button></form>
            @endif
            @can('watch.publish')
                @if ($show->is_published)
                    <form method="POST" action="{{ route('admin.watch-shows.unpublish', $show) }}">@csrf<button class="btn-secondary"><x-icon name="eye" class="size-4" /> {{ __('Unpublish') }}</button></form>
                @elseif ($show->approval_status === 'approved')
                    <form method="POST" action="{{ route('admin.watch-shows.publish', $show) }}">@csrf<button class="btn-primary"><x-icon name="check-badge" class="size-4" /> {{ __('Publish') }}</button></form>
                @endif
            @endcan
        </div>
    @endif
</x-page-header>

@if ($show)
    @php $activeApproval = $show->approvals->sortByDesc('submitted_at')->first(); @endphp
    <div class="mb-5 max-w-4xl rounded-xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Watch publication workflow') }}</p><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __($activeApproval?->currentStage?->name ?? ($show->approval_status === 'approved' ? 'Approval complete — ready to publish' : 'Add at least one active video episode, then submit the show.')) }}</p></div>
            <div class="flex items-center gap-2"><x-status-badge :status="$show->approval_status" /><span class="{{ $show->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ __($show->is_published ? 'Public' : 'Not public') }}</span></div>
        </div>
    </div>
@endif

<form method="POST" action="{{ $show ? route('admin.watch-shows.update', $show) : route('admin.watch-shows.store') }}" enctype="multipart/form-data" class="max-w-4xl">
    @csrf
    @if ($show) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input :label="__('Show title (English)')" name="title" :value="$show?->title" required />
            <x-form.input :label="__('Show title (Bangla)')" name="title_bn" :value="$show?->title_bn" />
            <x-form.input :label="__('URL slug')" name="slug" :value="$show?->slug" required />
            <x-form.select :label="__('Category')" name="category" :value="$show?->category" required :options="$categories" help="Managed from Watch → Categories and reflected in the public portal." />
            <x-form.input :label="__('Eyebrow (English)')" name="eyebrow" :value="$show?->eyebrow" placeholder="New original drama" />
            <x-form.input :label="__('Eyebrow (Bangla)')" name="eyebrow_bn" :value="$show?->eyebrow_bn" />
            <x-form.textarea :label="__('Description (English)')" name="description" :value="$show?->description" rows="4" required />
            <x-form.textarea :label="__('Description (Bangla)')" name="description_bn" :value="$show?->description_bn" rows="4" />
            <x-form.artwork-upload class="sm:col-span-2" :current-path="$show?->image_path" label="Show hero image" :wide="true" help="JPG, PNG or WebP, up to 8 MB. Use a cinematic 16:9 image." />
            <x-form.input label="Year" name="year" type="number" :value="$show?->year ?? date('Y')" />
            <x-form.input label="Audience rating" name="rating" :value="$show?->rating ?? 'G'" placeholder="G, PG…" />
            <x-form.input label="Display position" name="position" type="number" :value="$show?->position ?? 0" required />
            <x-form.toggle label="Featured hero" name="is_featured" :checked="(bool) $show?->is_featured" help="Featured shows are prioritised in the Watch hero." />
            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-500 dark:bg-slate-800/70 dark:text-slate-400">Publishing is separate from editing. Changing show details or any episode unpublishes the show and requires fresh approval.</p>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.watch-shows.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __($show ? 'Save as Draft' : 'Create Draft') }}</button>
        </div>
    </div>
</form>

@if ($show)
    <section class="mt-8 max-w-4xl">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div><h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Episodes') }}</h2><p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Upload videos or manage the episode list and order.') }}</p></div>
            <a href="{{ route('admin.watch-shows.episodes.create', $show) }}" class="btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> {{ __('Add') }}</a>
        </div>
        <div class="card overflow-hidden">
            <div class="table-shell">
                <table class="table-app">
                    <thead><tr><th>#</th><th>{{ __('Episode') }}</th><th>{{ __('Length') }}</th><th>{{ __('Video') }}</th><th>{{ __('Status') }}</th><th class="text-right">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @forelse ($show->episodes as $episode)
                            <tr>
                                <td class="text-slate-400">{{ $episode->position }}</td>
                                <td><p class="font-medium text-slate-800 dark:text-slate-100">{{ $episode->title }}</p><p class="mt-0.5 max-w-md line-clamp-1 text-xs text-slate-400">{{ $episode->description }}</p></td>
                                <td class="whitespace-nowrap text-sm">{{ $episode->duration_minutes }} min</td>
                                <td><span class="{{ $episode->video_path ? 'badge-emerald' : 'badge-slate' }}">{{ __($episode->video_path ? 'Uploaded' : 'Not uploaded') }}</span></td>
                                <td><span class="{{ $episode->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ __($episode->is_published ? 'Published' : 'Draft') }}</span></td>
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
