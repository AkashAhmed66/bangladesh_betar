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
            <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Description</th><th>IP</th><th class="text-right">Changes</th></tr></thead>
            <tbody x-data="{ open: null }">
                @php
                    $actionColor = fn ($a) => [
                        'created' => 'green', 'updated' => 'blue', 'deleted' => 'red', 'restored' => 'green',
                        'login' => 'slate', 'logout' => 'slate', 'approved' => 'green', 'rejected' => 'red',
                        'published' => 'green', 'unpublished' => 'slate', 'refund_issued' => 'amber',
                    ][$a] ?? 'slate';
                    // Stringify a stored value for display.
                    $show = fn ($v) => $v === null ? '—' : (is_bool($v) ? ($v ? 'true' : 'false') : (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v));
                @endphp
                @forelse ($logs as $log)
                    @php
                        $old = $log->old_values ?? [];
                        $new = $log->new_values ?? [];
                        // Keys recorded on either side — changed ones matter most,
                        // but context keys (e.g. remarks) are kept too.
                        $keys = collect(array_unique(array_merge(array_keys($old), array_keys($new))));
                        $hasChanges = $keys->isNotEmpty();
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap text-sm tabular-nums text-slate-500 dark:text-slate-400">{{ $log->created_at?->format('j M Y H:i:s') }}</td>
                        <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="badge-{{ $actionColor($log->action) }}">{{ str_replace('_', ' ', $log->action) }}</span></td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $log->description ?? '—' }}</td>
                        <td class="text-xs font-mono text-slate-400">{{ $log->ip_address ?? '—' }}</td>
                        <td class="text-right">
                            @if ($hasChanges)
                                <button type="button" @click="open = open === {{ $log->id }} ? null : {{ $log->id }}"
                                        class="btn-secondary btn-sm">
                                    <x-icon name="eye" class="size-3.5" />
                                    <span x-text="open === {{ $log->id }} ? 'Hide' : 'View'">View</span>
                                </button>
                            @else
                                <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                            @endif
                        </td>
                    </tr>
                    @if ($hasChanges)
                        <tr x-show="open === {{ $log->id }}" x-cloak>
                            <td colspan="6" class="bg-slate-50/70 px-5 py-4 dark:bg-slate-800/40">
                                <div class="space-y-3">
                                    @foreach ($keys as $key)
                                        @php
                                            $before = $old[$key] ?? null;
                                            $after = $new[$key] ?? null;
                                            $changed = $before !== $after;
                                        @endphp
                                        <div>
                                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                {{ str_replace('_', ' ', $key) }} @unless ($changed)<span class="font-normal normal-case text-slate-400">(unchanged)</span>@endunless
                                            </p>
                                            @if ($changed && array_key_exists($key, $old))
                                                <pre class="max-h-48 overflow-y-auto whitespace-pre-wrap rounded-lg border-l-2 border-rose-300 bg-rose-50/60 px-3 py-2 font-sans text-sm text-slate-600 dark:border-rose-500/40 dark:bg-rose-500/5 dark:text-slate-300">{{ $show($before) }}</pre>
                                            @endif
                                            <pre class="{{ $changed ? 'mt-1 border-l-2 border-green-400 bg-green-50/60 dark:border-green-500/40 dark:bg-green-500/5' : 'border-l-2 border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900/40' }} max-h-48 overflow-y-auto whitespace-pre-wrap rounded-lg px-3 py-2 font-sans text-sm text-slate-700 dark:text-slate-200">{{ $show($after ?? $before) }}</pre>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6"><x-empty-state icon="clipboard-check" title="No audit entries" message="No actions match the current filters." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
