@extends('layouts.admin')

@section('title', 'Rights Review')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $asset?->title ?? 'Rights Record #'.$record->id }}</h2>
            <x-status-badge :status="$record->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Rights submission · {{ $record->rightsHolder?->name ?? 'Unknown holder' }}
            @if ($asset) · {{ $asset->archive_no }} @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($asset)
            @can('assets.view')
                <a href="{{ route('admin.assets.show', $asset) }}" class="btn-accent"><x-icon name="archive" class="size-4" /> Open Asset Record</a>
            @endcan
        @endif
        @can('rights.manage')
            <a href="{{ route('admin.rights-records.edit', $record) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Edit Full Record</a>
        @endcan
        @can('rights.view')
            <a href="{{ route('admin.rights-records.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Rights</a>
        @endcan
    </div>
</div>

{{-- Listen inline — review the recording the rights apply to --}}
@if ($asset)
    @php $playVersion = $asset->versions->firstWhere('is_default', true) ?? $asset->versions->first(); @endphp
    <div class="card mb-6 overflow-hidden">
        <div class="bg-slate-900 p-5 dark:bg-slate-950"
             x-data="{
                playing: false, cur: 0, dur: {{ $asset->duration_seconds ?: 0 }},
                fmt(s){ if(!isFinite(s)) return '0:00'; const m=Math.floor(s/60), sec=Math.floor(s%60); return m+':'+String(sec).padStart(2,'0'); },
                toggle(){ const a=$refs.audio; a.paused ? a.play() : a.pause(); },
                seek(e){ const a=$refs.audio; const r=$refs.wave.getBoundingClientRect(); const f=(e.clientX-r.left)/r.width; if(a.duration) a.currentTime=f*a.duration; }
             }">
        @if ($playVersion)
            <audio x-ref="audio" preload="none" class="hidden"
                   src="{{ route('admin.assets.stream', ['asset' => $asset->id, 'version' => $playVersion->id], false) }}"
                   @play="playing=true" @pause="playing=false" @ended="playing=false"
                   @timeupdate="cur=$refs.audio.currentTime"
                   @loadedmetadata="if($refs.audio.duration) dur=$refs.audio.duration"></audio>
        @endif
            <div x-ref="wave" class="relative cursor-pointer" @click="seek($event)">
                <x-waveform :peaks="$asset->waveform_peaks ?? []" :height="56" class="text-primary-400" />
                <div class="pointer-events-none absolute inset-y-0 left-0 bg-white/10" :style="`width: ${dur ? (cur/dur*100) : 0}%`"></div>
            </div>
            <div class="mt-3 flex items-center gap-4">
                <button @click="toggle()" @if (! $playVersion) disabled @endif
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white transition hover:bg-primary-500 disabled:opacity-40">
                    <span x-show="!playing"><x-icon name="play" class="size-5 translate-x-0.5" /></span>
                    <span x-show="playing" x-cloak><x-icon name="pause" class="size-5" /></span>
                </button>
                <div class="text-sm tabular-nums text-slate-300">
                    <span x-text="fmt(cur)">0:00</span> / <span x-text="fmt(dur)">{{ gmdate($asset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', (int) $asset->duration_seconds) }}</span>
                </div>
                <div class="ml-auto text-xs text-slate-400">{{ $asset->archive_no }} · {{ strtoupper($asset->format ?? '—') }}</div>
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        {{-- Submission details --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Rights Submission</h3></div>
            <div class="card-body grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Rights holder</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->rightsHolder?->name ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Submitted by</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->creator?->name ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Submitted</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->created_at?->diffForHumans() ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Rights granted</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ implode(', ', array_map('ucfirst', $record->rights_types ?? [])) ?: '—' }}</p></div>
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Territory</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->territory }}</p></div>
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Validity</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->valid_from?->format('j M Y') ?? '—' }} → {{ $record->valid_until?->format('j M Y') ?? 'perpetual' }}</p></div>
                <div><p class="text-xs text-slate-500 dark:text-slate-400">Royalty</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->royalty_required ? 'Required' : 'Not required' }}</p></div>
                @if ($record->rightsHolder?->email)
                    <div><p class="text-xs text-slate-500 dark:text-slate-400">Holder e-mail</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $record->rightsHolder->email }}</p></div>
                @endif
            </div>
            @if ($record->royalty_notes || $record->notes)
                <div class="space-y-2 border-t border-slate-100 px-5 py-4 text-sm dark:border-slate-800">
                    @if ($record->royalty_notes)
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Royalty notes</p>
                        <p class="whitespace-pre-line text-slate-600 dark:text-slate-300">{{ $record->royalty_notes }}</p>
                    @endif
                    @if ($record->notes)
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notes & decision history</p>
                        <p class="whitespace-pre-line text-slate-600 dark:text-slate-300">{{ $record->notes }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Copyright documents --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Copyright Documents</h3></div>
            <div class="card-body">
                @if (! empty($record->documents))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($record->documents as $i => $doc)
                            <a href="{{ route('admin.rights-records.document', [$record, $i]) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-primary-400 hover:text-primary-700 dark:border-slate-700 dark:text-slate-200 dark:hover:text-primary-400">
                                <x-icon name="document-text" class="size-4" /> {{ $doc['name'] ?? 'Document '.($i + 1) }}
                            </a>
                        @endforeach
                    </div>
                    <p class="form-help mt-2">Review every document before deciding.</p>
                @else
                    <p class="text-sm text-slate-500 dark:text-slate-400">No documents were attached to this submission.</p>
                @endif
            </div>
        </div>

        {{-- AI Safety Analysis (M16) — what the AI gate found for this recording --}}
        @if ($asset && ($aiJob = $asset->latestAiAnalysisJob))
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Safety Analysis</h3>
                    @if ($aiJob->status === 'error')
                        <span class="badge-red">Analysis failed</span>
                    @elseif ($aiJob->isFlagged())
                        <span class="badge-amber">Flagged for review</span>
                    @else
                        <span class="badge-green">Cleared</span>
                    @endif
                </div>
                <div class="card-body space-y-3">
                    <div class="flex flex-wrap gap-1.5">
                        @if ($aiJob->is_duplicate)<span class="badge-amber">Duplicate</span>@endif
                        @if ($aiJob->violence_detected)<span class="badge-red">Violence</span>@endif
                        @if ($aiJob->anti_government_detected)<span class="badge-red">Anti-government</span>@endif
                        @if ($aiJob->status === 'done' && ! $aiJob->isFlagged())<span class="badge-slate">No issues found</span>@endif
                    </div>
                    @if ($aiJob->summary)<p class="text-sm text-slate-600 dark:text-slate-300">{{ $aiJob->summary }}</p>@endif
                    @if ($aiJob->review_status !== 'pending' && $aiJob->review_status !== 'not_required')
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                            AI Reviewer decision: <span class="font-medium">{{ ucfirst($aiJob->review_status) }}</span>
                            @if ($aiJob->reviewer) by {{ $aiJob->reviewer->name }} @endif
                            @if ($aiJob->reviewed_at) ({{ $aiJob->reviewed_at->diffForHumans() }}) @endif
                            @if ($aiJob->review_comments) — “{{ $aiJob->review_comments }}” @endif
                        </p>
                    @endif
                    @can('ai-moderation.view')
                        <a href="{{ route('admin.ai-moderation.show', $asset) }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Full AI moderation record →</a>
                    @endcan
                </div>
            </div>
        @endif

        {{-- Approval workflow history (M13) — how the recording got approved --}}
        @if ($asset && $asset->approvals->isNotEmpty())
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Approval History</h3></div>
                <div class="card-body space-y-4">
                    @foreach ($asset->approvals as $approval)
                        <div class="flex items-start gap-3">
                            <x-icon name="workflow" class="mt-0.5 size-5 shrink-0 text-primary-600" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $approval->workflow?->name }}</span>
                                    <x-status-badge :status="$approval->status" />
                                    @if ($approval->currentStage && in_array($approval->status, ['pending', 'changes_requested'], true))
                                        <span class="text-xs text-slate-500">Stage: {{ $approval->currentStage->name }}</span>
                                    @endif
                                    @can('approvals.view')
                                        <a href="{{ route('admin.approvals.show', $approval) }}" class="ml-auto text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Full record →</a>
                                    @endcan
                                </div>
                                <ul class="mt-2 space-y-2 text-xs text-slate-500 dark:text-slate-400">
                                    @foreach ($approval->actions as $action)
                                        <li>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span>{{ $action->created_at->format('j M Y H:i') }} —</span>
                                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $action->user?->name }}</span>
                                                <span>{{ str_replace('_', ' ', $action->action) }}</span>
                                                @if ($action->rating)
                                                    <span class="flex items-center gap-0.5" title="{{ $action->rating }} / 5">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <x-icon name="star" class="size-3 {{ $i <= $action->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-600' }}"
                                                                    style="{{ $i <= $action->rating ? 'fill: currentColor' : '' }}" />
                                                        @endfor
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($action->comments)
                                                <p class="mt-0.5 text-slate-600 dark:text-slate-300">&ldquo;{{ $action->comments }}&rdquo;</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Decision --}}
        @can('rights.manage')
            @if ($record->status === 'pending')
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Your Decision</h3></div>
                    <form method="POST" action="{{ route('admin.rights-records.review', $record) }}">
                        @csrf
                        <div class="card-body">
                            <x-form.textarea label="Remarks" name="comments" rows="3" :required="true"
                                             help="Required for every decision — recorded on the rights record and in the audit trail." />
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <button type="submit" name="action" value="reject" class="btn-danger"
                                    onclick="return confirm('Reject this rights submission? The asset cannot be published until rights are approved.');">
                                <x-icon name="x" class="size-4" /> Reject
                            </button>
                            <button type="submit" name="action" value="approve" class="btn-primary">
                                <x-icon name="check-badge" class="size-4" /> Approve — Unlock Publishing
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card"><div class="card-body text-sm text-slate-500 dark:text-slate-400">
                    This rights record has been decided — status: <x-status-badge :status="$record->status" />.
                    Use “Edit Full Record” to change terms or status.
                </div></div>
            @endif
        @endcan
    </div>

    {{-- Right rail — asset context --}}
    <div class="space-y-6">
        @if ($asset)
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Catalogue Details</h3></div>
                <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @foreach ([
                        'Archive no' => $asset->archive_no,
                        'Content type' => ucfirst(str_replace('_', ' ', $asset->content_type)),
                        'Status' => null,
                        'Station' => $asset->station?->name,
                        'Programme' => $asset->programme?->title,
                        'Category' => $asset->category?->name,
                        'Language' => $asset->language?->name,
                        'Duration' => $asset->duration_seconds ? gmdate($asset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', (int) $asset->duration_seconds) : null,
                        'Uploaded by' => $asset->uploader?->name,
                        'Uploaded' => $asset->created_at?->format('j M Y H:i'),
                    ] as $label => $value)
                        @if ($label === 'Status')
                            <div class="flex justify-between gap-3 px-5 py-2.5">
                                <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                                <dd class="text-right"><x-status-badge :status="$asset->status" /></dd>
                            </div>
                        @elseif ($value)
                            <div class="flex justify-between gap-3 px-5 py-2.5">
                                <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
                @if ($asset->artists->isNotEmpty() || $asset->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 border-t border-slate-100 px-5 py-3 dark:border-slate-800">
                        @foreach ($asset->artists as $artist)
                            <span class="badge-purple">{{ $artist->name }}{{ $artist->pivot->role ? ' · '.$artist->pivot->role : '' }}</span>
                        @endforeach
                        @foreach ($asset->tags as $tag)<span class="badge-slate">{{ $tag->name }}</span>@endforeach
                    </div>
                @endif
            </div>

            @if ($asset->description)
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Description</h3></div>
                    <div class="card-body"><p class="whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $asset->description }}</p></div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
