@extends('layouts.admin')

@section('title', 'Stories')

@section('content')
<x-page-header title="Stories" subtitle="Individually addressable stories inside episodes (FR-EVT-03)">
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
@endsection
