@extends('layouts.admin')

@section('title', 'Audit Log')

@section('content')
<x-page-header title="Audit Log" subtitle="Append-only, immutable trail of every privileged action (M21 · FR-AUD-02)" />

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search description…" class="form-input w-56">
            <select name="action" class="form-input w-40">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                @endforeach
            </select>
            <select name="user_id" class="form-input w-44">
                <option value="">All users</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('user_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input w-40" title="From date">
            <input type="date" name="to" value="{{ request('to') }}" class="form-input w-40" title="To date">
            <button class="btn-secondary btn-sm">Filter</button>
            @if (request()->hasAny(['q', 'action', 'user_id', 'from', 'to']))
                <a href="{{ route('admin.audit-logs.index') }}" class="btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
            <tbody>
                @php
                    $actionColor = fn ($a) => [
                        'created' => 'green', 'updated' => 'blue', 'deleted' => 'red', 'restored' => 'green',
                        'login' => 'slate', 'logout' => 'slate', 'approved' => 'green', 'rejected' => 'red',
                        'published' => 'green', 'unpublished' => 'slate', 'refund_issued' => 'amber',
                    ][$a] ?? 'slate';
                @endphp
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-sm tabular-nums text-slate-500 dark:text-slate-400">{{ $log->created_at?->format('j M Y H:i:s') }}</td>
                        <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="badge-{{ $actionColor($log->action) }}">{{ str_replace('_', ' ', $log->action) }}</span></td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $log->description ?? '—' }}</td>
                        <td class="text-xs font-mono text-slate-400">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="clipboard-check" title="No audit entries" message="No actions match the current filters." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
