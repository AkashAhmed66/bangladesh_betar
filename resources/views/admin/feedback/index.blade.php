@extends('layouts.admin')

@section('title', 'Feedback')

@section('content')
<x-page-header title="Listener Feedback" subtitle="General feedback, suggestions & complaints channel (M26 · FR-ENG-09)" />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="category" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </select>
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
            <thead><tr><th>Feedback</th><th>Category</th><th>From</th><th>Status</th><th class="text-right">Set status</th></tr></thead>
            <tbody>
                @forelse ($feedback as $item)
                    <tr>
                        <td class="max-w-md">
                            @if ($item->subject)<p class="font-medium text-slate-800 dark:text-slate-100">{{ $item->subject }}</p>@endif
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ Str::limit($item->message, 180) }}</p>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $item->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td><span class="badge-slate">{{ ucfirst($item->category) }}</span></td>
                        <td class="text-sm">{{ $item->user?->name ?? 'Anonymous' }}</td>
                        <td>
                            <x-status-badge :status="$item->status" />
                            @if ($item->handler)<p class="mt-1 text-xs text-slate-400 dark:text-slate-500">by {{ $item->handler->name }}</p>@endif
                        </td>
                        <td>
                            @can('feedback.manage')
                                <div class="flex flex-wrap items-center justify-end gap-1">
                                    @foreach ($statuses as $status)
                                        @if ($item->status !== $status)
                                            <form method="POST" action="{{ route('admin.feedback.update-status', $item) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button class="btn-ghost btn-sm">{{ ucfirst($status) }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            @else — @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="inbox" title="No feedback yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($feedback->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $feedback->links() }}</div>
    @endif
</div>
@endsection
