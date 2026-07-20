@extends('layouts.admin')

@section('title', 'Approvals')

@section('content')
<x-page-header title="Approval Queue" subtitle="Review items awaiting sign-off in your workflows (FR-WRK-03/05)">
    <div class="flex rounded-lg border border-slate-200 p-0.5 dark:border-slate-700">
        <a href="{{ route('admin.approvals.index') }}"
           @class([
               'rounded-md px-3 py-1.5 text-sm font-medium transition',
               'bg-primary-600 text-white' => $scope === 'mine',
               'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' => $scope !== 'mine',
           ])>My Queue</a>
        <a href="{{ route('admin.approvals.index', ['scope' => 'all']) }}"
           @class([
               'rounded-md px-3 py-1.5 text-sm font-medium transition',
               'bg-primary-600 text-white' => $scope === 'all',
               'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' => $scope !== 'all',
           ])>All Pending</a>
    </div>
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Item</th><th>Workflow / Stage</th><th>Submitted By</th><th>Age</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($approvals as $approval)
                    @php
                        $overdue = $approval->submitted_at && $approval->workflow
                            && $approval->submitted_at->copy()->addHours($approval->workflow->escalation_hours)->isPast();
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $approval->approvable?->title ?? 'Item #'.$approval->approvable_id }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $approval->approvable_type)) }}</p>
                        </td>
                        <td class="text-sm">
                            <p class="text-slate-700 dark:text-slate-200">{{ $approval->workflow?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $approval->currentStage?->name ?? 'No active stage' }}</p>
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $approval->submitter?->name ?? '—' }}</td>
                        <td>
                            <span @class([
                                'inline-flex items-center gap-1 text-sm',
                                'font-medium text-amber-700 dark:text-amber-400' => $overdue,
                                'text-slate-500 dark:text-slate-400' => ! $overdue,
                            ])>
                                @if ($overdue)<x-icon name="clock" class="size-3.5" />@endif
                                {{ $approval->submitted_at?->diffForHumans() ?? '—' }}
                            </span>
                            @if ($overdue)<p class="text-[11px] text-amber-600 dark:text-amber-400">Overdue</p>@endif
                        </td>
                        <td><x-status-badge :status="$approval->status" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.approvals.show', $approval) }}" class="btn-secondary btn-sm">
                                    <x-icon name="eye" class="size-4" /> Review
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="clipboard-check" title="Queue is clear" message="No approvals are waiting on you right now." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($approvals->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $approvals->links() }}</div>
    @endif
</div>
@endsection
