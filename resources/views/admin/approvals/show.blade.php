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
@php $reviewAsset = $approval->approvable instanceof \App\Models\AudioAsset ? $approval->approvable : null; @endphp
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
    <div class="flex flex-wrap gap-2">
        @if ($reviewAsset)
            @can('assets.view')
                <a href="{{ route('admin.assets.show', $reviewAsset) }}" class="btn-accent"><x-icon name="archive" class="size-4" /> Open Asset Record</a>
            @endcan
        @endif
        <a href="{{ route('admin.approvals.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Queue</a>
    </div>
</div>

{{-- Listen inline — same stream the asset page uses (FR-WRK-03: review what you approve) --}}
@if ($reviewAsset)
    @php $playVersion = $reviewAsset->versions->firstWhere('is_default', true) ?? $reviewAsset->versions->first(); @endphp
    <div class="card mb-6 overflow-hidden">
        <div class="bg-slate-900 p-5 dark:bg-slate-950"
             x-data="{
                playing: false, cur: 0, dur: {{ $reviewAsset->duration_seconds ?: 0 }},
                fmt(s){ if(!isFinite(s)) return '0:00'; const m=Math.floor(s/60), sec=Math.floor(s%60); return m+':'+String(sec).padStart(2,'0'); },
                toggle(){ const a=$refs.audio; a.paused ? a.play() : a.pause(); },
                seek(e){ const a=$refs.audio; const r=$refs.wave.getBoundingClientRect(); const f=(e.clientX-r.left)/r.width; if(a.duration) a.currentTime=f*a.duration; }
             }">
        @if ($playVersion)
            <audio x-ref="audio" preload="none" class="hidden"
                   data-hls="{{ \App\Support\Hls::adminAssetHls($reviewAsset, $playVersion) ?? '' }}"
                   data-fallback="{{ route('admin.assets.stream', ['asset' => $reviewAsset->id, 'version' => $playVersion->id], false) }}"
                   x-init="window.betarHls($el)"
                   @play="playing=true" @pause="playing=false" @ended="playing=false"
                   @timeupdate="cur=$refs.audio.currentTime"
                   @loadedmetadata="if($refs.audio.duration) dur=$refs.audio.duration"></audio>
        @endif

            <div x-ref="wave" class="relative cursor-pointer" @click="seek($event)">
                <x-waveform :peaks="$reviewAsset->waveform_peaks ?? []" :height="56" class="text-primary-400" />
                <div class="pointer-events-none absolute inset-y-0 left-0 bg-white/10"
                     :style="`width: ${dur ? (cur/dur*100) : 0}%`"></div>
            </div>

            <div class="mt-3 flex items-center gap-4">
                <button @click="toggle()" @if (! $playVersion) disabled @endif
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white transition hover:bg-primary-500 disabled:opacity-40">
                    <span x-show="!playing"><x-icon name="play" class="size-5 translate-x-0.5" /></span>
                    <span x-show="playing" x-cloak><x-icon name="pause" class="size-5" /></span>
                </button>
                <div class="text-sm tabular-nums text-slate-300">
                    <span x-text="fmt(cur)">0:00</span> / <span x-text="fmt(dur)">{{ gmdate($reviewAsset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', (int) $reviewAsset->duration_seconds) }}</span>
                </div>
                <div class="ml-auto text-xs text-slate-400">
                    {{ $reviewAsset->archive_no }} · {{ strtoupper($reviewAsset->format ?? '—') }}
                </div>
            </div>
            @unless ($playVersion)
                <p class="mt-2 text-xs text-amber-400">No playable version yet.</p>
            @endunless
        </div>
    </div>
@endif

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

        {{-- ---- Full asset record inline: everything a reviewer needs ---- --}}
        @if ($reviewAsset)
            {{-- Description --}}
            @if ($reviewAsset->description)
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Description</h3></div>
                    <div class="card-body"><p class="whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $reviewAsset->description }}</p></div>
                </div>
            @endif

            {{-- AI Safety Analysis (M16) --}}
            @if ($aiJob = $reviewAsset->latestAiAnalysisJob)
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
                        @if ($aiJob->review_comments)
                            <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                                AI Reviewer{{ $aiJob->reviewer ? ' '.$aiJob->reviewer->name : '' }}: “{{ $aiJob->review_comments }}”
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Transcripts (M16) --}}
            @if ($reviewAsset->transcripts->isNotEmpty())
                <div class="card" x-data="{ fullTranscript: false }">
                    <div class="card-header">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Transcripts & Lyrics</h3>
                        <button type="button" @click="fullTranscript = ! fullTranscript" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">
                            <span x-text="fullTranscript ? 'Show less' : 'Show full'"></span>
                        </button>
                    </div>
                    <div class="card-body space-y-4">
                        @foreach ($reviewAsset->transcripts as $transcript)
                            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="badge-slate">{{ ucfirst($transcript->transcript_type) }}</span>
                                    @if ($transcript->is_ai_generated)<span class="badge-purple">AI generated</span>@endif
                                    @if ($transcript->is_verified)<span class="badge-green">Verified</span>@else<span class="badge-amber">Unverified draft</span>@endif
                                </div>
                                <div class="text-sm text-slate-600 dark:text-slate-300">
                                    @if ($transcript->full_text)
                                        <p x-show="! fullTranscript" class="font-bangla whitespace-pre-wrap leading-relaxed">{{ Str::limit($transcript->full_text, 500) }}</p>
                                        <p x-show="fullTranscript" x-cloak class="font-bangla max-h-96 overflow-y-auto whitespace-pre-wrap leading-relaxed">{{ $transcript->full_text }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

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

    {{-- Stage progression + asset context rail --}}
    <div class="space-y-6">
        @if ($reviewAsset)
            {{-- Catalogue details — same facts as the asset record page --}}
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Catalogue Details</h3></div>
                <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @foreach ([
                        'Archive no' => $reviewAsset->archive_no,
                        'Content type' => ucfirst(str_replace('_', ' ', $reviewAsset->content_type)),
                        'Station' => $reviewAsset->station?->name,
                        'Department' => $reviewAsset->department?->name,
                        'Programme' => $reviewAsset->programme?->title,
                        'Category' => $reviewAsset->category?->name,
                        'Language' => $reviewAsset->language?->name,
                        'Duration' => $reviewAsset->duration_seconds ? gmdate($reviewAsset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', (int) $reviewAsset->duration_seconds) : null,
                        'Format' => $reviewAsset->format ? strtoupper($reviewAsset->format).($reviewAsset->sample_rate ? ' · '.($reviewAsset->sample_rate / 1000).' kHz' : '').($reviewAsset->bit_depth ? ' · '.$reviewAsset->bit_depth.' bit' : '') : null,
                        'Loudness' => $reviewAsset->loudness_lufs !== null ? $reviewAsset->loudness_lufs.' LUFS · peak '.$reviewAsset->peak_db.' dB' : null,
                        'Recorded' => $reviewAsset->recorded_on?->format('j M Y'),
                        'First broadcast' => $reviewAsset->first_broadcast_on?->format('j M Y'),
                        'Uploaded by' => $reviewAsset->uploader?->name,
                        'Uploaded' => $reviewAsset->created_at?->format('j M Y H:i'),
                        'Source' => ucfirst(str_replace('_', ' ', $reviewAsset->source)),
                        'Size' => $reviewAsset->size_bytes ? round($reviewAsset->size_bytes / 1048576, 1).' MB' : null,
                    ] as $label => $value)
                        @if ($value)
                            <div class="flex justify-between gap-3 px-5 py-2.5">
                                <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
                @if ($reviewAsset->artists->isNotEmpty() || $reviewAsset->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 border-t border-slate-100 px-5 py-3 dark:border-slate-800">
                        @foreach ($reviewAsset->artists as $artist)
                            <span class="badge-purple">{{ $artist->name }}{{ $artist->pivot->role ? ' · '.$artist->pivot->role : '' }}</span>
                        @endforeach
                        @foreach ($reviewAsset->tags as $tag)<span class="badge-slate">{{ $tag->name }}</span>@endforeach
                    </div>
                @endif
            </div>

            {{-- Rights (M14) --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Rights</h3>
                    <x-status-badge :status="$reviewAsset->rights_status" />
                </div>
                @forelse ($reviewAsset->rightsRecords as $record)
                    <div class="border-t border-slate-100 px-5 py-3 text-sm first:border-0 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $record->rightsHolder?->name ?? 'Unknown holder' }}</p>
                            <x-status-badge :status="$record->status" />
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ implode(', ', array_map('ucfirst', $record->rights_types ?? [])) }} · {{ $record->territory }}
                        </p>
                    </div>
                @empty
                    <p class="px-5 py-3 text-sm text-slate-500 dark:text-slate-400">No rights submission yet — it follows once the approval completes.</p>
                @endforelse
            </div>

            {{-- Version family --}}
            @if ($reviewAsset->versions->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Version Family</h3></div>
                    <ul class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        @foreach ($reviewAsset->versions as $version)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-slate-700 dark:text-slate-200">{{ $version->label ?? ucfirst(str_replace('_', ' ', $version->version_type)) }}</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ strtoupper($version->format ?? '—') }}{{ $version->duration_seconds ? ' · '.gmdate('i:s', (int) $version->duration_seconds) : '' }}</span>
                                </span>
                                @if ($version->is_default)<span class="badge-green">Streaming</span>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

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
