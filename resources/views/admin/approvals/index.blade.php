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

<div x-data="{ section: 'workflow' }">
    {{-- Section switcher: one review stream at a time --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ([
            'workflow' => ['clipboard-check', 'Workflow Approvals', $approvals->total()],
            'ai' => ['shield', 'AI Moderation', $aiItems->count()],
            'books' => ['megaphone', 'Audio Books', $bookItems->count()],
            'rights' => ['scale', 'Rights Records', $rightsItems->count()],
        ] as $key => [$icon, $label, $count])
            <button type="button" @click="section = '{{ $key }}'"
                    :class="section === '{{ $key }}' ? 'bg-primary-600 text-white' : 'bg-white text-slate-600 hover:text-slate-900 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-white'"
                    class="flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2 text-sm font-medium transition dark:border-slate-700">
                <x-icon :name="$icon" class="size-4" /> {{ $label }}
                <span :class="section === '{{ $key }}' ? 'bg-white/20' : 'bg-slate-100 dark:bg-slate-800'"
                      class="rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums">{{ $count }}</span>
            </button>
        @endforeach
    </div>

<div class="card" x-show="section === 'workflow'">
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
                                @elseif ($a->rights_status === 'approved')
                                    <p class="mt-1 text-[11px] font-semibold text-sky-600 dark:text-sky-400">Rights approved — ready to publish</p>
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

{{-- ---- AI moderation reviews ---- --}}
<div x-show="section === 'ai'" x-cloak>
@if ($aiItems->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="shield" class="size-4.5 text-primary-600" /> AI Moderation — awaiting review
            </h3>
            @can('ai-moderation.view')
                <a href="{{ route('admin.ai-moderation.index') }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Open module →</a>
            @endcan
        </div>
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Recording</th><th>Analysis</th><th>Uploaded by</th><th>Waiting</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($aiItems as $item)
                        @php $aiJob = $item->latestAiAnalysisJob; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.ai-moderation.show', $item) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $item->title }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item->archive_no }}</p>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @if ($aiJob?->is_duplicate)<span class="badge-amber">Duplicate</span>@endif
                                    @if ($aiJob?->violence_detected)<span class="badge-red">Violence</span>@endif
                                    @if ($aiJob?->anti_government_detected)<span class="badge-red">Anti-government</span>@endif
                                    @if ($aiJob && $aiJob->status === 'error')<span class="badge-slate">Analysis failed</span>@endif
                                    @if ($aiJob && $aiJob->status === 'done' && ! $aiJob->isFlagged())<span class="badge-green">Clean</span>@endif
                                </div>
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">{{ $item->uploader?->name ?? '—' }}</td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $item->updated_at?->diffForHumans() }}</td>
                            <td>
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.ai-moderation.show', $item) }}" class="btn-secondary btn-sm">
                                        <x-icon name="eye" class="size-4" /> {{ auth()->user()->can('ai-moderation.review') ? 'Review' : 'View' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card"><div class="px-5 py-10"><x-empty-state icon="shield" title="Nothing awaiting AI review" message="New uploads pending AI moderation will appear here." /></div></div>
@endif
</div>

{{-- ---- Audio books ---- --}}
<div x-show="section === 'books'" x-cloak>
@if ($bookItems->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="megaphone" class="size-4.5 text-primary-600" /> Audio Books — awaiting approval
            </h3>
            @can('audiobooks.use')
                <a href="{{ route('admin.audiobooks.index') }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Open module →</a>
            @endcan
        </div>
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Audio book</th><th>Language</th><th>Narrations</th><th>Created by</th><th>Waiting</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($bookItems as $book)
                        <tr>
                            <td>
                                <a href="{{ route('admin.audiobooks.show', $book) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $book->title }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($book->characters) }} chars{{ $book->used_ocr ? ' · OCR' : '' }}</p>
                            </td>
                            <td class="text-sm">{{ $book->language === 'bn' ? 'Bangla' : 'English' }}</td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">
                                {{ implode(' · ', array_filter([
                                    $book->audio_male_path ? '♂ '.gmdate('i:s', $book->duration_male) : null,
                                    $book->audio_female_path ? '♀ '.gmdate('i:s', $book->duration_female) : null,
                                    $book->audio_enhanced_path ? '✦ '.gmdate('i:s', $book->duration_enhanced) : null,
                                ])) ?: '—' }}
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">{{ $book->user?->name ?? '—' }}</td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $book->submitted_at?->diffForHumans() }}</td>
                            <td>
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.audiobooks.show', $book) }}" class="btn-secondary btn-sm">
                                        <x-icon name="eye" class="size-4" /> {{ auth()->user()->can('audiobooks.approve') ? 'Review' : 'View' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card"><div class="px-5 py-10"><x-empty-state icon="megaphone" title="No audio books awaiting approval" message="Submitted audio books will appear here for review." /></div></div>
@endif
</div>

{{-- ---- Rights submissions ---- --}}
<div x-show="section === 'rights'" x-cloak>
@if ($rightsItems->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="scale" class="size-4.5 text-primary-600" /> Rights Submissions — awaiting approval
            </h3>
            @can('rights.view')
                <a href="{{ route('admin.rights-records.index') }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Open module →</a>
            @endcan
        </div>
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Recording</th><th>Rights holder</th><th>Documents</th><th>Submitted by</th><th>Waiting</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($rightsItems as $record)
                        <tr>
                            <td>
                                <a href="{{ route('admin.rights-records.show', $record) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $record->audioAsset?->title ?? 'Record #'.$record->id }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $record->audioAsset?->archive_no }}</p>
                            </td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">{{ $record->rightsHolder?->name ?? '—' }}</td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">{{ count($record->documents ?? []) }} file{{ count($record->documents ?? []) === 1 ? '' : 's' }}</td>
                            <td class="text-sm text-slate-600 dark:text-slate-300">{{ $record->creator?->name ?? '—' }}</td>
                            <td class="text-sm text-slate-500 dark:text-slate-400">{{ $record->created_at?->diffForHumans() }}</td>
                            <td>
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.rights-records.show', $record) }}" class="btn-secondary btn-sm">
                                        <x-icon name="eye" class="size-4" /> {{ auth()->user()->can('rights.manage') ? 'Review' : 'View' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card"><div class="px-5 py-10"><x-empty-state icon="scale" title="No rights submissions awaiting approval" message="Filed copyright submissions will appear here for review." /></div></div>
@endif
</div>

</div>
@endsection
