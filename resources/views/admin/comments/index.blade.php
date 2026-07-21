@extends('layouts.admin')

@section('title', 'Comment Moderation')

@section('content')
<x-page-header title="Comment Moderation" subtitle="Review listener comments across the archive (M26 · FR-ENG-01/03/05)" />

<div class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-300">
    <x-icon name="info" class="size-5 shrink-0 text-primary-600 dark:text-primary-400" />
    <p>Moderation mode is configurable (pre-moderation, post-moderation or off) and an automated Bangla/English profanity filter pre-screens submissions before they reach this queue.</p>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Comment</th><th>Author</th><th>On</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($comments as $comment)
                    <tr>
                        <td class="max-w-md">
                            @if ($comment->rating)
                                <span class="mb-1 inline-flex items-center gap-0.5" title="{{ $comment->rating }} / 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-icon name="star" class="size-3.5 {{ $i <= $comment->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-600' }}" style="{{ $i <= $comment->rating ? 'fill: currentColor' : '' }}" />
                                    @endfor
                                </span>
                            @endif
                            <p class="text-sm text-slate-700 dark:text-slate-200">{{ Str::limit($comment->body, 160) }}</p>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $comment->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td class="text-sm">{{ $comment->user?->name ?? 'Unknown' }}</td>
                        <td class="text-sm">
                            @if ($comment->commentable_type)
                                <span class="badge-slate">{{ ucwords(str_replace('_', ' ', $comment->commentable_type)) }}</span>
                                @php $target = data_get($comment->commentable, 'title') ?? data_get($comment->commentable, 'name') ?? data_get($comment->commentable, 'archive_no'); @endphp
                                @if ($target)<p class="mt-1 max-w-[16rem] truncate text-xs text-slate-500 dark:text-slate-400">{{ $target }}</p>@endif
                            @else — @endif
                        </td>
                        <td><x-status-badge :status="$comment->status" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('moderation.manage')
                                    @if ($comment->status !== 'approved')
                                        <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button class="btn-ghost btn-sm text-emerald-600 dark:text-emerald-400" title="Approve"><x-icon name="check-badge" class="size-4" /></button>
                                        </form>
                                    @endif
                                    @if ($comment->status !== 'hidden')
                                        <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="action" value="hide">
                                            <button class="btn-ghost btn-sm text-amber-600 dark:text-amber-400" title="Hide"><x-icon name="eye" class="size-4" /></button>
                                        </form>
                                    @endif
                                    @if ($comment->status !== 'removed')
                                        <form method="POST" action="{{ route('admin.comments.moderate', $comment) }}" class="inline"
                                              x-data @submit.prevent="if (confirm('Remove this comment?')) $el.submit()">
                                            @csrf
                                            <input type="hidden" name="action" value="remove">
                                            <button class="btn-ghost btn-sm text-rose-600 dark:text-rose-400" title="Remove"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="chat" title="No comments to moderate" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($comments->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $comments->links() }}</div>
    @endif
</div>
@endsection
