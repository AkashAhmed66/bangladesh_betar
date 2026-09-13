@extends('layouts.admin')

@section('title', 'News Articles')

@section('content')
<x-page-header title="News Articles" subtitle="Manage the reporting shown in the public News portal.">
    @can('news.manage')
        <a href="{{ route('admin.news-articles.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New Article') }}</a>
    @endcan
</x-page-header>

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="w-full sm:max-w-sm">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search title or category…') }}" class="form-input w-full">
    </form>
    <span class="text-sm text-slate-500 dark:text-slate-400">{{ __(':count articles', ['count' => $articles->total()]) }}</span>
</div>

<div class="card overflow-hidden">
    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th>{{ __('Article') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Published') }}</th>
                    <th>{{ __('Approval') }}</th>
                    <th>{{ __('Public status') }}</th>
                    <th class="text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr>
                        <td>
                            <div class="flex min-w-[18rem] items-center gap-3">
                                <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                                    @if ($article->image_path)
                                        <img src="{{ asset('storage/'.$article->image_path) }}" alt="" class="size-full object-cover">
                                    @else
                                        <div class="grid size-full place-items-center text-slate-400"><x-icon name="document-text" class="size-6" /></div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="line-clamp-2 font-medium text-slate-800 dark:text-slate-100">{{ $article->title }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ __(':count min read', ['count' => $article->read_time_minutes]) }} @if($article->is_featured) · {{ __('Featured') }} @endif</p>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge-slate">{{ $article->category }}</span></td>
                        <td class="whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ $article->published_at?->format('d M Y, H:i') ?? '—' }}</td>
                        <td><x-status-badge :status="$article->approval_status ?? 'draft'" /></td>
                        <td><span class="{{ $article->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ __($article->is_published ? 'Published' : 'Not public') }}</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('news.manage')
                                    <a href="{{ route('admin.news-articles.edit', $article) }}" class="btn-ghost btn-sm" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                    @if (! $article->is_published && in_array($article->approval_status, ['draft', 'rejected', 'changes_requested'], true))
                                        <form method="POST" action="{{ route('admin.news-articles.submit', $article) }}">@csrf<button class="btn-secondary btn-sm" title="{{ __('Submit for approval') }}"><x-icon name="upload" class="size-4" /><span class="sr-only">{{ __('Submit for approval') }}</span></button></form>
                                    @endif
                                    @can('news.publish')
                                        @if ($article->is_published)
                                            <form method="POST" action="{{ route('admin.news-articles.unpublish', $article) }}">@csrf<button class="btn-secondary btn-sm" title="{{ __('Unpublish') }}"><x-icon name="eye" class="size-4" /><span class="sr-only">{{ __('Unpublish') }}</span></button></form>
                                        @elseif ($article->approval_status === 'approved')
                                            <form method="POST" action="{{ route('admin.news-articles.publish', $article) }}">@csrf<button class="btn-primary btn-sm" title="{{ __('Publish') }}"><x-icon name="check-badge" class="size-4" /><span class="sr-only">{{ __('Publish') }}</span></button></form>
                                        @endif
                                    @endcan
                                    <x-confirm-delete :action="route('admin.news-articles.destroy', $article)" confirm="Delete this news article?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="document-text" title="No news articles yet" message="Create the first story for the public News portal." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($articles->hasPages())
    <div class="mt-6">{{ $articles->links() }}</div>
@endif
@endsection
