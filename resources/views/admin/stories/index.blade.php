@extends('layouts.admin')

@section('title', 'Stories & Submissions')

@section('content')
<x-page-header title="Stories & Submissions" subtitle="Individually addressable stories inside episodes, and the listener submission queue that feeds them (FR-EVT-03 / FR-EVT-05)">
    @can('stories.manage')
        <a href="{{ route('admin.stories.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Story</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Title or storyteller…" class="form-input w-64">
            <button class="btn-secondary btn-sm">Search</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $stories->total() }} stories</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Story</th><th>Episode</th><th>District</th><th>Storyteller</th><th>Category</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($stories as $story)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $story->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $story->title_bn }}</p>
                        </td>
                        <td class="text-sm">
                            {{ $story->episode?->title ?? '—' }}
                            @if ($story->episode?->programme)
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $story->episode->programme->title }}</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $story->district ?? '—' }}</td>
                        <td class="text-sm">
                            {{ $story->publicStorytellerName() }}
                            @if ($story->is_anonymous)<x-icon name="ghost" class="inline size-4 text-slate-400" />@endif
                        </td>
                        <td class="text-sm">{{ $story->category?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$story->is_published ? 'published' : 'draft'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('stories.manage')
                                    <a href="{{ route('admin.stories.edit', $story) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.stories.destroy', $story)" confirm="Delete story {{ $story->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="chat" title="No stories yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($stories->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $stories->links() }}</div>
    @endif
</div>

@can('submissions.view')
    <div class="card mt-6">
        <div class="card-header">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <select name="submission_status" class="form-input w-44" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'in_review', 'accepted', 'rejected'] as $status)
                        <option value="{{ $status }}" @selected(request('submission_status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
                <button class="btn-secondary btn-sm">Filter</button>
            </form>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $submissions->total() }} submissions</span>
        </div>

        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Submitter</th><th>Type</th><th>Consent</th><th>Status</th><th>Reviewed By</th><th class="text-right">Actions</th></tr></thead>
                @forelse ($submissions as $submission)
                    <tbody x-data="{ open: false }" class="align-top">
                        <tr>
                            <td>
                                <p class="font-medium text-slate-800 dark:text-slate-100">
                                    {{ $submission->is_anonymous ? 'Anonymous' : ($submission->submitter_name ?? $submission->user?->name ?? 'Unknown') }}
                                </p>
                                @unless ($submission->is_anonymous)
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $submission->contact }}</p>
                                @endunless
                            </td>
                            <td><span class="badge-slate">{{ ucfirst($submission->submission_type) }}</span></td>
                            <td>
                                @if ($submission->consent_given)
                                    <span class="badge-green">Consent given</span>
                                @else
                                    <span class="badge-red">No consent</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$submission->status" /></td>
                            <td class="text-sm">{{ $submission->reviewer?->name ?? '—' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Review action only for users permitted to act on submissions --}}
                                    @can('submissions.review')
                                        <button type="button" class="btn-secondary btn-sm" @click="open = !open">
                                            <x-icon name="clipboard-check" class="size-4" /> Review
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @can('submissions.review')
                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="bg-slate-50 dark:bg-slate-800/40">
                                    @if ($submission->content_text)
                                        <p class="mb-3 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $submission->content_text }}</p>
                                    @endif
                                    <form method="POST" action="{{ route('admin.story-submissions.review', $submission) }}"
                                          class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                        @csrf
                                        <div class="w-full sm:w-44">
                                            <x-form.select label="Decision" name="status"
                                                           :value="in_array($submission->status, ['in_review', 'accepted', 'rejected'], true) ? $submission->status : 'in_review'"
                                                           required
                                                           :options="['in_review' => 'In review', 'accepted' => 'Accepted', 'rejected' => 'Rejected']" />
                                        </div>
                                        <div class="flex-1">
                                            <x-form.input label="Review notes" name="review_notes" :value="$submission->review_notes" />
                                        </div>
                                        <button type="submit" class="btn-primary">Save Review</button>
                                    </form>
                                </td>
                            </tr>
                        @endcan
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="6"><x-empty-state icon="inbox" title="No submissions yet" /></td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        @if ($submissions->hasPages())
            <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $submissions->links() }}</div>
        @endif
    </div>
@endcan
@endsection
