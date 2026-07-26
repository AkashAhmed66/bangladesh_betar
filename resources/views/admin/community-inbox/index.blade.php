@extends('layouts.admin')

@section('title', 'Community Inbox')

@section('content')
<x-page-header title="Community Inbox"
               subtitle="Abuse reports, content issues & listener feedback in one queue (M26 · FR-ENG-04/07/09)" />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="type" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Type</th><th>Submission</th><th>From</th><th>About</th><th>Status</th><th class="w-80">Set status</th></tr></thead>
            <tbody>
                @forelse ($submissions as $item)
                    <tr>
                        <td>
                            <span class="badge-slate whitespace-nowrap">{{ $types[$item->type] ?? ucwords(str_replace('_', ' ', $item->type)) }}</span>
                        </td>
                        <td class="max-w-sm">
                            @if ($item->category)
                                <span class="badge-amber">{{ ucwords(str_replace('_', ' ', $item->category)) }}</span>
                            @endif
                            @if ($item->subject_line)<p class="mt-1 font-medium text-slate-800 dark:text-slate-100">{{ $item->subject_line }}</p>@endif
                            @if ($item->message)<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($item->message, 160) }}</p>@endif
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $item->created_at?->format('d M Y H:i') }}</p>
                        </td>
                        <td class="text-sm">{{ $item->user?->name ?? 'Anonymous' }}</td>
                        <td class="text-sm">
                            @if ($item->subject_type)
                                <span class="badge-slate">{{ ucwords(str_replace('_', ' ', $item->subject_type)) }}</span>
                                @php $target = data_get($item->subject, 'title') ?? data_get($item->subject, 'name') ?? data_get($item->subject, 'body'); @endphp
                                @if ($item->subject_type === 'audio_asset')
                                    <p class="mt-1 max-w-[16rem] truncate text-xs">
                                        <a href="{{ route('admin.assets.show', $item->subject_id) }}" class="text-primary-700 hover:underline dark:text-primary-300">
                                            {{ Str::limit($target ?? 'Audio asset #'.$item->subject_id, 60) }}
                                        </a>
                                    </p>
                                @elseif ($target)
                                    <p class="mt-1 max-w-[16rem] truncate text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($target, 60) }}</p>
                                @endif
                            @else
                                <span class="text-slate-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                        <td>
                            <x-status-badge :status="$item->status" />
                            @if ($item->handler)<p class="mt-1 text-xs text-slate-400 dark:text-slate-500">by {{ $item->handler->name }}</p>@endif
                        </td>
                        <td>
                            @can('moderation.manage')
                                <form method="POST" action="{{ route('admin.community-inbox.update-status', $item) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="resolution_notes" rows="2" class="form-input text-sm" placeholder="Resolution notes (optional)">{{ $item->resolution_notes }}</textarea>
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($item->status !== 'in_progress')<button name="status" value="in_progress" class="btn-secondary btn-sm">In progress</button>@endif
                                        @if ($item->status !== 'resolved')<button name="status" value="resolved" class="btn-primary btn-sm">Resolve</button>@endif
                                        @if ($item->status !== 'dismissed')<button name="status" value="dismissed" class="btn-ghost btn-sm">Dismiss</button>@endif
                                        @if ($item->status !== 'new')<button name="status" value="new" class="btn-ghost btn-sm">Reopen</button>@endif
                                    </div>
                                </form>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $item->resolution_notes ?: '—' }}</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="inbox" title="Nothing in the inbox" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($submissions->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $submissions->links() }}</div>
    @endif
</div>
@endsection
