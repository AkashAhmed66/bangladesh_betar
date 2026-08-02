@extends('layouts.admin')

@section('title', $asset->title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $asset->title }}</h2>
            <x-status-badge :status="$asset->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ $asset->archive_no }} · Uploaded by {{ $asset->uploader?->name ?? '—' }}
            @if ($asset->title_bn) · {{ $asset->title_bn }} @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.assets.show', $asset) }}" class="btn-secondary"><x-icon name="archive" class="size-4" /> Full Asset Record</a>
        @can('ai-moderation.view')
            <a href="{{ route('admin.ai-moderation.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Queue</a>
        @endcan
    </div>
</div>

{{-- Listen inline — review what you decide on, without leaving the page --}}
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

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        @if (! $job)
            <div class="card"><div class="card-body"><x-empty-state icon="shield" title="No analysis on record" message="This asset has no AI analysis job associated with it." /></div></div>
        @else
            @if ($job->status === 'error')
                <div class="flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                    <x-icon name="exclamation" class="mt-0.5 size-4 shrink-0" />
                    <p>AI analysis could not run{{ $job->error ? ' — '.$job->error : '' }}. Review the recording manually before deciding.</p>
                </div>
            @endif

            {{-- Duplicate detection --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Duplicate Detection</h3>
                    @if ($job->is_duplicate)<span class="badge-amber">Possible duplicate</span>@else<span class="badge-green">No match</span>@endif
                </div>
                <div class="card-body">
                    @if ($job->is_duplicate)
                        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Audio match</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->audio_match_pct !== null ? number_format($job->audio_match_pct, 1).'%' : '—' }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Content match</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->content_match_pct !== null ? number_format($job->content_match_pct, 1).'%' : '—' }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Matched upload</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->matched_filename ?? '—' }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Uploaded</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->matched_uploaded_at?->format('j M Y H:i') ?? '—' }}</p></div>
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">No acoustic or content match against the existing corpus.</p>
                    @endif
                </div>
            </div>

            {{-- Content safety --}}
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Content Safety Analysis</h3></div>
                <div class="card-body space-y-5">
                    @if ($job->summary)
                        <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">{{ $job->summary }}</p>
                    @endif

                    <div>
                        <div class="mb-1.5 flex items-center gap-2">
                            @if ($job->violence_detected)<span class="badge-red">Violence detected</span>@else<span class="badge-green">No violence detected</span>@endif
                            @if ($job->violence_confidence !== null)<span class="text-xs text-slate-400">confidence {{ number_format($job->violence_confidence * 100) }}%</span>@endif
                        </div>
                        @foreach (($job->violence_evidence ?? []) as $quote)
                            <p class="font-bangla mt-1 rounded-lg border-l-2 border-rose-300 bg-rose-50/60 px-3 py-1.5 text-sm text-slate-600 dark:border-rose-500/40 dark:bg-rose-500/5 dark:text-slate-300">&ldquo;{{ $quote }}&rdquo;</p>
                        @endforeach
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center gap-2">
                            @if ($job->anti_government_detected)<span class="badge-red">Anti-government content detected</span>@else<span class="badge-green">No anti-government content detected</span>@endif
                            @if ($job->anti_government_confidence !== null)<span class="text-xs text-slate-400">confidence {{ number_format($job->anti_government_confidence * 100) }}%</span>@endif
                        </div>
                        @foreach (($job->anti_government_evidence ?? []) as $quote)
                            <p class="font-bangla mt-1 rounded-lg border-l-2 border-rose-300 bg-rose-50/60 px-3 py-1.5 text-sm text-slate-600 dark:border-rose-500/40 dark:bg-rose-500/5 dark:text-slate-300">&ldquo;{{ $quote }}&rdquo;</p>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Transcript (FR-AIF-06 — editable during moderation) --}}
            @php
                $transcriptModel = $asset->transcripts->firstWhere('transcript_type', 'transcript');
                $transcriptText = $transcriptModel?->full_text ?: ($job->transcript_readable ?: $job->transcript);
            @endphp
            @if ($transcriptText)
                <div class="card" x-data="{ editingTranscript: {{ $errors->has('full_text') ? 'true' : 'false' }} }">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Transcript</h3>
                            @if ($transcriptModel?->is_verified)<span class="badge-green">Verified</span>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">{{ $job->language ? ucfirst($job->language) : 'Language unknown' }}@if ($job->duration_sec) · {{ gmdate('i:s', (int) $job->duration_sec) }} @endif</span>
                            @can('ai-moderation.review')
                                <button type="button" @click="editingTranscript = ! editingTranscript" class="btn-secondary btn-sm">
                                    <x-icon name="pencil" class="size-3.5" /> <span x-text="editingTranscript ? 'Cancel' : 'Edit'"></span>
                                </button>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body" x-show="! editingTranscript">
                        <p class="font-bangla whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $transcriptText }}</p>
                    </div>
                    @can('ai-moderation.review')
                        <form method="POST" action="{{ route('admin.ai-moderation.transcript', $asset) }}" x-show="editingTranscript" x-cloak>
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <textarea name="full_text" rows="14" required
                                          class="form-input font-bangla w-full text-sm leading-relaxed">{{ old('full_text', $transcriptText) }}</textarea>
                                <p class="form-help">Corrections are saved as the asset's verified transcript — used by search and everywhere the transcript is shown.</p>
                                @error('full_text')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                                <button type="button" @click="editingTranscript = false" class="btn-secondary">Cancel</button>
                                <button type="submit" class="btn-primary"><x-icon name="check-badge" class="size-4" /> Save Transcript</button>
                            </div>
                        </form>
                    @endcan
                </div>
            @endif
        @endif

        {{-- Decision --}}
        @can('ai-moderation.review')
            @if (in_array($asset->status, ['ai_flagged', 'ai_review'], true))
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Reviewer Decision</h3></div>
                    <form method="POST" action="{{ route('admin.ai-moderation.review', $asset) }}">
                        @csrf
                        <div class="card-body">
                            <x-form.textarea label="Remarks" name="comments" rows="3" :required="true"
                                             help="Required for every decision — explain why this asset is accepted or rejected." />
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <button type="submit" name="action" value="reject" class="btn-danger"
                                    onclick="return confirm('Reject this asset? It can never be submitted for approval or published.');">
                                <x-icon name="x" class="size-4" /> Reject
                            </button>
                            <button type="submit" name="action" value="approve" class="btn-primary"><x-icon name="check-badge" class="size-4" /> Approve &amp; Release to Pipeline</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card"><div class="card-body text-sm text-slate-500 dark:text-slate-400">
                    This asset has already been reviewed
                    @if ($job?->reviewer) by <span class="font-medium text-slate-700 dark:text-slate-200">{{ $job->reviewer->name }}</span> @endif
                    @if ($job?->reviewed_at) ({{ $job->reviewed_at->diffForHumans() }}) @endif.
                    @if ($job?->review_comments)
                        <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">{{ $job->review_comments }}</p>
                    @endif
                </div></div>
            @endif
        @endcan
    </div>

    {{-- Right rail — full catalogue context, like the approval review page --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Catalogue Details</h3></div>
            <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                @foreach ([
                    'Archive no' => $asset->archive_no,
                    'Content type' => ucfirst(str_replace('_', ' ', $asset->content_type)),
                    'Station' => $asset->station?->name,
                    'Department' => $asset->department?->name,
                    'Programme' => $asset->programme?->title,
                    'Category' => $asset->category?->name,
                    'Language' => $asset->language?->name,
                    'Duration' => $asset->duration_seconds ? gmdate($asset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', (int) $asset->duration_seconds) : null,
                    'Format' => $asset->format ? strtoupper($asset->format).($asset->sample_rate ? ' · '.($asset->sample_rate / 1000).' kHz' : '') : null,
                    'Uploaded by' => $asset->uploader?->name,
                    'Uploaded' => $asset->created_at?->format('j M Y H:i'),
                    'Source' => $asset->source ? ucfirst(str_replace('_', ' ', $asset->source)) : null,
                    'Analysis completed' => $job?->completed_at?->diffForHumans(),
                ] as $label => $value)
                    @if ($value)
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
    </div>
</div>
@endsection
