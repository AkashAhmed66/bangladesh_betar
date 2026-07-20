@extends('layouts.admin')

@section('title', 'Edit Sessions')

@section('content')
<x-page-header title="Edit Sessions" subtitle="Non-destructive editing register (M12)" />

{{-- FR-EDT-01: edits never touch the preservation master --}}
<div class="mb-5 flex items-start gap-3 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">
    <x-icon name="info" class="size-5 shrink-0" />
    <p>All edits run non-destructively on working proxies via an edit decision list (EDL). The preservation master is never modified — every session can be reverted to source (FR-EDT-01).</p>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Session title…" class="form-input w-56">
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $sessions->total() }} sessions</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Session</th><th>Source Asset</th><th>Editor</th><th>Operations</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    <x-icon name="scissors" class="size-4" />
                                </span>
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $session->title ?? 'Untitled session #'.$session->id }}</p>
                            </div>
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $session->audioAsset?->title ?? '—' }}</td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $session->editor?->name ?? '—' }}</td>
                        <td class="text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ count($session->edl ?? []) }} ops</td>
                        <td><x-status-badge :status="$session->status" /></td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $session->updated_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="scissors" title="No edit sessions" message="Editing sessions started by producers will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($sessions->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $sessions->links() }}</div>
    @endif
</div>
@endsection
