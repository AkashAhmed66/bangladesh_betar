@extends('layouts.admin')

@section('title', 'Review Approval')

@php
    $actionMeta = [
        'submitted' => ['label' => 'Submitted', 'icon' => 'clipboard-check', 'color' => 'blue'],
        'resubmitted' => ['label' => 'Resubmitted', 'icon' => 'arrow-path', 'color' => 'blue'],
        'approved' => ['label' => 'Approved', 'icon' => 'check-badge', 'color' => 'green'],
        'rejected' => ['label' => 'Rejected', 'icon' => 'x', 'color' => 'red'],
        'correction_requested' => ['label' => 'Changes requested', 'icon' => 'arrow-path', 'color' => 'amber'],
        'escalated' => ['label' => 'Escalated', 'icon' => 'exclamation', 'color' => 'amber'],
    ];
    $canAct = in_array($approval->status, ['pending', 'changes_requested'], true);
@endphp

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $approval->approvable?->title ?? 'Item #'.$approval->approvable_id }}</h2>
            <x-status-badge :status="$approval->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ ucfirst(str_replace('_', ' ', $approval->approvable_type)) }} · {{ $approval->workflow?->name ?? 'No workflow' }}
        </p>
    </div>
    <a href="{{ route('admin.approvals.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Queue</a>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        {{-- Summary --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Submission</h3></div>
            <div class="card-body grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Submitted by</p>
                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $approval->submitter?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Submitted</p>
                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $approval->submitted_at?->diffForHumans() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Current stage</p>
                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $approval->currentStage?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Completed</p>
                    <p class="font-medium text-slate-800 dark:text-slate-100">{{ $approval->completed_at?->diffForHumans() ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Action form --}}
        @can('approvals.act')
            @if ($canAct)
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Your Decision</h3></div>
                    <form method="POST" action="{{ route('admin.approvals.act', $approval) }}">
                        @csrf
                        <div class="card-body space-y-4">
                            <div x-data="{ rating: {{ old('rating', 0) }}, hover: 0 }">
                                <label class="form-label">Content rating <span class="font-normal text-slate-400">(optional)</span></label>
                                <div class="flex items-center gap-1">
                                    <template x-for="star in [1,2,3,4,5]" :key="star">
                                        <button type="button" @click="rating = (rating === star ? 0 : star)"
                                                @mouseenter="hover = star" @mouseleave="hover = 0"
                                                class="p-0.5" :aria-label="`Rate ${star} star${star > 1 ? 's' : ''}`">
                                            {{-- Dynamic color/fill live on this plain <span> wrapper, not on
                                                 <x-icon> itself — Blade's own `:attr="expr"` component-prop
                                                 syntax would otherwise try to compile the Alpine JS as PHP. --}}
                                            <span class="inline-block transition"
                                                  :class="(hover || rating) >= star ? 'text-amber-500 [&>svg]:fill-current' : 'text-slate-300 dark:text-slate-600'">
                                                <x-icon name="star" class="size-5" />
                                            </span>
                                        </button>
                                    </template>
                                    <button type="button" x-show="rating" x-cloak @click="rating = 0"
                                            class="ml-1 text-xs font-medium text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">Clear</button>
                                </div>
                                <input type="hidden" name="rating" x-model="rating">
                            </div>
                            <x-form.textarea label="Comments" name="comments" rows="3"
                                             help="Required when rejecting or requesting changes (FR-WRK-03)." />
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <button type="submit" name="action" value="request_changes" class="btn-secondary"><x-icon name="arrow-path" class="size-4" /> Request Changes</button>
                            <button type="submit" name="action" value="reject" class="btn-danger"><x-icon name="x" class="size-4" /> Reject</button>
                            <button type="submit" name="action" value="approve" class="btn-primary"><x-icon name="check-badge" class="size-4" /> Approve</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card"><div class="card-body text-sm text-slate-500 dark:text-slate-400">This approval is closed — no further action is required.</div></div>
            @endif
        @endcan

        {{-- History timeline --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Action History</h3></div>
            <div class="card-body">
                @forelse ($approval->actions as $action)
                    @php $meta = $actionMeta[$action->action] ?? ['label' => ucfirst(str_replace('_', ' ', $action->action)), 'icon' => 'info', 'color' => 'slate']; @endphp
                    <div class="flex gap-3 @if (! $loop->last) pb-4 @endif">
                        <div class="flex flex-col items-center">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                <x-icon :name="$meta['icon']" class="size-4" />
                            </span>
                            @unless ($loop->last)<span class="mt-1 w-px flex-1 bg-slate-200 dark:bg-slate-700"></span>@endunless
                        </div>
                        <div class="pb-1">
                            <p class="flex flex-wrap items-center gap-2 text-sm text-slate-800 dark:text-slate-100">
                                <span class="badge-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                                <span>by {{ $action->user?->name ?? 'System' }}</span>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $action->created_at?->diffForHumans() }}</p>
                            @if ($action->rating)
                                <div class="mt-1 flex items-center gap-0.5" title="{{ $action->rating }} / 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-icon name="star" class="size-3.5 {{ $i <= $action->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-600' }}"
                                                style="{{ $i <= $action->rating ? 'fill: currentColor' : '' }}" />
                                    @endfor
                                </div>
                            @endif
                            @if ($action->comments)
                                <p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">{{ $action->comments }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No actions recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Stage progression --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Workflow Stages</h3></div>
            <div class="card-body">
                <ol class="space-y-3">
                    @forelse ($approval->workflow?->stages ?? [] as $stage)
                        @php $isCurrent = $stage->id === $approval->current_stage_id; @endphp
                        <li class="flex items-center gap-3">
                            <span @class([
                                'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-primary-600 text-white' => $isCurrent,
                                'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => ! $isCurrent,
                            ])>{{ $stage->sequence }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $stage->name }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $stage->approver_role }}</p>
                            </div>
                            @if ($isCurrent)<span class="badge-amber">Current</span>@endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">This workflow has no stages.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
