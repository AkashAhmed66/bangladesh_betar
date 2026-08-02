@extends('layouts.admin')

@section('title', 'Approvals')

@section('content')
<x-page-header title="Approvals"
               subtitle="My Queue — your submissions awaiting others' sign-off · My Approvals — records that need your approval (FR-WRK-03/05)">
    <div class="flex rounded-lg border border-slate-200 p-0.5 dark:border-slate-700">
        <a href="{{ route('admin.approvals.index') }}"
           @class([
               'rounded-md px-3 py-1.5 text-sm font-medium transition',
               'bg-primary-600 text-white' => $scope === 'queue',
               'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' => $scope !== 'queue',
           ])>My Queue</a>
        <a href="{{ route('admin.approvals.index', ['scope' => 'approvals']) }}"
           @class([
               'rounded-md px-3 py-1.5 text-sm font-medium transition',
               'bg-primary-600 text-white' => $scope === 'approvals',
               'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' => $scope !== 'approvals',
           ])>My Approvals</a>
        @can('records.view-all')
            <a href="{{ route('admin.approvals.index', ['scope' => 'all']) }}"
               @class([
                   'rounded-md px-3 py-1.5 text-sm font-medium transition',
                   'bg-primary-600 text-white' => $scope === 'all',
                   'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' => $scope !== 'all',
               ])>All Records</a>
        @endcan
    </div>
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Item</th><th>Workflow / Stage</th><th>Awaiting</th><th>Submitted By</th><th>Age</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($approvals as $approval)
                    @php
                        $inFlight = in_array($approval->status, ['pending', 'changes_requested'], true);
                        $overdue = $inFlight && $approval->submitted_at && $approval->workflow
                            && $approval->submitted_at->copy()->addHours($approval->workflow->escalation_hours)->isPast();
                    @endphp
                    <tr>
                        <td>
                            @if ($approval->approvable instanceof \App\Models\AudioAsset)
                                <a href="{{ route('admin.assets.show', $approval->approvable) }}"
                                   class="font-medium text-primary-700 hover:underline dark:text-primary-300"
                                   title="Open the asset record to listen and inspect">{{ $approval->approvable->title }}</a>
                            @else
                                <p class="font-medium text-slate-800 dark:text-slate-100">{{ $approval->approvable?->title ?? 'Item #'.$approval->approvable_id }}</p>
                            @endif
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $approval->approvable_type)) }}</p>
                        </td>
                        <td class="text-sm">
                            <p class="text-slate-700 dark:text-slate-200">{{ $approval->workflow?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                @if ($inFlight)
                                    Stage: {{ $approval->currentStage?->name ?? 'No active stage' }}
                                @else
                                    Completed{{ $approval->completed_at ? ' · '.$approval->completed_at->diffForHumans() : '' }}
                                @endif
                            </p>
                        </td>
                        <td class="text-sm">
                            @if ($inFlight && $approval->currentStage)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    <x-icon name="shield-check" class="size-3.5" /> {{ $approval->currentStage->approver_role }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                            @endif
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
                        <td>
                            <x-status-badge :status="$approval->status" />
                            @if ($approval->isActionableBy(auth()->user()))
                                <p class="mt-1 text-[11px] font-semibold text-primary-600 dark:text-primary-400">Needs your action</p>
                            @endif
                            {{-- Post-approval pipeline: rights submission → clearance → publish --}}
                            @if ($approval->status === 'approved' && $approval->approvable instanceof \App\Models\AudioAsset)
                                @php
                                    $a = $approval->approvable;
                                    $rightsPending = $a->rightsRecords->where('status', 'pending')->isNotEmpty();
                                @endphp
                                @if ($a->status === 'published')
                                    <p class="mt-1 text-[11px] font-semibold text-green-600 dark:text-green-400">Published</p>
                                @elseif ($a->rights_status === 'cleared')
                                    <p class="mt-1 text-[11px] font-semibold text-sky-600 dark:text-sky-400">Rights cleared — ready to publish</p>
                                @elseif ($rightsPending)
                                    <p class="mt-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400">Rights review pending</p>
                                @else
                                    <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">Awaiting rights submission</p>
                                @endif
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.approvals.show', $approval) }}" class="btn-secondary btn-sm">
                                    <x-icon name="eye" class="size-4" /> {{ $inFlight ? 'Review' : 'History' }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        @if ($scope === 'queue')
                            <x-empty-state icon="clipboard-check" title="No submissions yet" message="You haven't submitted anything for approval. Submit an asset from its detail page to start the review workflow." />
                        @elseif ($scope === 'approvals')
                            <x-empty-state icon="clipboard-check" title="Nothing needs your approval" message="No records are waiting at a stage your role signs off." />
                        @else
                            <x-empty-state icon="clipboard-check" title="Queue is clear" message="Nothing is moving through review right now." />
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($approvals->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $approvals->links() }}</div>
    @endif
</div>
@endsection
