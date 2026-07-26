@extends('layouts.admin')

@section('title', $asset->title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $asset->title }}</h2>
            <x-status-badge :status="$asset->status" />
            @if ($asset->is_premium)<span class="badge-amber">Premium</span>@endif
            @if ($asset->is_public_service)<span class="badge-green">Public Service</span>@endif
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ $asset->archive_no }} · {{ ucfirst(str_replace('_', ' ', $asset->content_type)) }}
            @if ($asset->title_bn) · {{ $asset->title_bn }} @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('assets.view')
            <a href="{{ route('admin.assets.studio', $asset) }}" class="btn-accent"><x-icon name="wave" class="size-4" /> Open Studio</a>
        @endcan
        @can('assets.edit')
            <a href="{{ route('admin.assets.edit', $asset) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
            @if (in_array($asset->status, ['draft', 'qc_failed', 'rejected'], true))
                <form method="POST" action="{{ route('admin.assets.submit', $asset) }}">@csrf
                    <button class="btn-accent"><x-icon name="clipboard-check" class="size-4" /> Submit for Approval</button>
                </form>
            @endif
        @endcan
        @can('assets.publish')
            @if (in_array($asset->status, ['approved', 'unpublished'], true))
                <form method="POST" action="{{ route('admin.assets.publish', $asset) }}">@csrf
                    <button class="btn-primary"><x-icon name="globe" class="size-4" /> Publish</button>
                </form>
            @elseif ($asset->status === 'published')
                <form method="POST" action="{{ route('admin.assets.unpublish', $asset) }}">@csrf
                    <button class="btn-danger"><x-icon name="x" class="size-4" /> Unpublish</button>
                </form>
            @endif
        @endcan
    </div>
</div>

{{-- Player / waveform (M07: interactive waveform, professional monitoring) --}}
@php $playVersion = $asset->versions->firstWhere('is_default', true) ?? $asset->versions->first(); @endphp
<div class="card mb-6 overflow-hidden">
    <div class="bg-slate-900 p-6 dark:bg-slate-950"
         x-data="{
            playing: false, cur: 0, dur: {{ $asset->duration_seconds ?: 0 }}, ready: false,
            fmt(s){ if(!isFinite(s)) return '0:00'; const m=Math.floor(s/60), sec=Math.floor(s%60); return m+':'+String(sec).padStart(2,'0'); },
            toggle(){ const a=$refs.audio; a.paused ? a.play() : a.pause(); },
            seek(e){ const a=$refs.audio; const r=$refs.wave.getBoundingClientRect(); const f=(e.clientX-r.left)/r.width; if(a.duration) a.currentTime=f*a.duration; }
         }">
        @if ($playVersion)
            <audio x-ref="audio" preload="none" class="hidden"
                   src="{{ route('admin.assets.stream', ['asset' => $asset->id, 'version' => $playVersion->id], false) }}"
                   @play="playing=true" @pause="playing=false" @ended="playing=false"
                   @timeupdate="cur=$refs.audio.currentTime"
                   @loadedmetadata="ready=true; if($refs.audio.duration) dur=$refs.audio.duration"></audio>
        @endif

        <div x-ref="wave" class="relative cursor-pointer" @click="seek($event)">
            <x-waveform :peaks="$asset->waveform_peaks ?? []" :height="72" class="text-primary-400" />
            {{-- progress overlay --}}
            <div class="pointer-events-none absolute inset-y-0 left-0 bg-white/10"
                 :style="`width: ${dur ? (cur/dur*100) : 0}%`"></div>
        </div>

        <div class="mt-4 flex items-center gap-4">
            <button @click="toggle()" @if (! $playVersion) disabled @endif
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white transition hover:bg-primary-500 disabled:opacity-40">
                <span x-show="!playing"><x-icon name="play" class="size-5 translate-x-0.5" /></span>
                <span x-show="playing" x-cloak><x-icon name="pause" class="size-5" /></span>
            </button>
            <div class="text-sm tabular-nums text-slate-300">
                <span x-text="fmt(cur)">0:00</span> / <span x-text="fmt(dur)">{{ gmdate($asset->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $asset->duration_seconds) }}</span>
            </div>
            <div class="ml-auto flex items-center gap-4 text-xs text-slate-400">
                <span>{{ strtoupper($asset->format ?? '—') }} · {{ $asset->sample_rate ? ($asset->sample_rate / 1000).' kHz' : '—' }} · {{ $asset->bit_depth }} bit</span>
                <span>{{ $asset->loudness_lufs }} LUFS · Peak {{ $asset->peak_db }} dB</span>
                @can('assets.view')
                    <a href="{{ route('admin.assets.studio', $asset) }}" class="hidden text-primary-300 hover:underline sm:inline">Open Studio →</a>
                @endcan
            </div>
        </div>
        @unless ($playVersion)
            <p class="mt-3 text-xs text-amber-400">No playable version yet — upload audio to enable playback.</p>
        @endunless
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        {{-- Versions (M04) --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Version Family</h3>
                <span class="text-xs text-slate-400">Pick the version the public streams — change it anytime. Master is immutable.</span>
            </div>
            <div class="table-shell">
                <table class="table-app">
                    <thead><tr><th>Version</th><th>Format</th><th>Bitrate</th><th>Duration</th><th>Derived From</th><th>Streaming</th><th class="text-right">Studio</th></tr></thead>
                    <tbody>
                        @foreach ($asset->versions as $version)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if ($version->isMaster())<x-icon name="shield" class="size-4 text-primary-600" />@endif
                                        <span class="font-medium">{{ $version->label ?? ucfirst(str_replace('_', ' ', $version->version_type)) }}</span>
                                    </div>
                                    <p class="text-xs text-slate-400">{{ $version->file_path }}</p>
                                </td>
                                <td class="uppercase text-xs">{{ $version->format }}</td>
                                <td class="text-sm">{{ $version->bitrate_kbps ? $version->bitrate_kbps.' kbps' : '—' }}</td>
                                <td class="text-sm tabular-nums">{{ gmdate('i:s', $version->duration_seconds) }}</td>
                                <td class="text-xs text-slate-500">{{ $version->derivedFrom?->version_type ?? '—' }}</td>
                                <td>
                                    @if ($version->is_default)
                                        <span class="badge-green">Streaming</span>
                                    @elseif (! $version->isMaster() && $version->version_type !== 'preview')
                                        @can('assets.publish')
                                            <form method="POST" action="{{ route('admin.assets.versions.streaming', [$asset, $version]) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary btn-sm whitespace-nowrap px-2 py-1 text-[11px]" title="Stream this version to the public">Set as streaming</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                                <td class="text-right">
                                    @can('assets.view')
                                        <a href="{{ route('admin.assets.studio', $asset) }}?version={{ $version->id }}"
                                           class="btn-ghost btn-sm" title="Open this version in the Studio">
                                            <x-icon name="wave" class="size-4" /> Open
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- AI safety analysis (M16) — duplicate / violence / anti-government + transcription --}}
        @if ($asset->status === 'analyzing')
            <div class="card">
                <div class="card-body flex items-center gap-3">
                    <svg class="size-5 shrink-0 animate-spin text-primary-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Running AI safety analysis…</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Checking for duplicates, violence and anti-government content, and transcribing the audio. Reload this page in a moment — it usually takes under a minute.</p>
                    </div>
                </div>
            </div>
        @elseif ($job = $asset->latestAiAnalysisJob)
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Safety Analysis</h3>
                    @if ($job->status === 'error')
                        <span class="badge-red">Analysis failed</span>
                    @elseif ($job->isFlagged())
                        <span class="badge-amber">Flagged for review</span>
                    @else
                        <span class="badge-green">Cleared</span>
                    @endif
                </div>
                <div class="card-body space-y-3">
                    @if ($job->status === 'error')
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $job->error ?? 'The analysis service could not be reached.' }} The asset was returned to <span class="font-medium">draft</span> so it is not blocked.</p>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @if ($job->is_duplicate)<span class="badge-amber">Duplicate</span>@endif
                            @if ($job->violence_detected)<span class="badge-red">Violence</span>@endif
                            @if ($job->anti_government_detected)<span class="badge-red">Anti-government</span>@endif
                            @if (! $job->isFlagged())<span class="badge-slate">No issues found</span>@endif
                        </div>
                        @if ($job->summary)<p class="text-sm text-slate-600 dark:text-slate-300">{{ $job->summary }}</p>@endif
                    @endif

                    @if ($asset->status === 'ai_flagged')
                        @can('ai-moderation.view')
                            <a href="{{ route('admin.ai-moderation.show', $asset) }}" class="btn-primary btn-sm"><x-icon name="shield" class="size-4" /> Review in AI Moderation</a>
                        @endcan
                    @elseif ($asset->status === 'ai_rejected')
                        <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                            Rejected by AI Reviewer{{ $job->reviewer ? ' '.$job->reviewer->name : '' }}{{ $job->reviewed_at ? ' · '.$job->reviewed_at->diffForHumans() : '' }} — this asset can never be submitted for approval or published.
                            @if ($job->review_comments) “{{ $job->review_comments }}” @endif
                        </p>
                    @endif
                </div>
            </div>
        @endif

        {{-- 14-day listening + heatmap --}}
        @if ($stats->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Listening Heat Map — most replayed sections (M19)</h3>
                </div>
                <div class="card-body">
                    @php $heatmap = $stats->last()?->heatmap ?? []; $max = max(1, ...($heatmap ?: [1])); @endphp
                    <div class="flex h-16 items-end gap-0.5">
                        @foreach ($heatmap as $bucket)
                            <div class="flex-1 rounded-t-sm bg-primary-600/80 dark:bg-primary-500/80" style="height: {{ (int) ($bucket / $max * 100) }}%"
                                 title="{{ $bucket }} listens"></div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-slate-400">
                        <span>00:00</span><span>{{ gmdate('i:s', (int) ($asset->duration_seconds / 2)) }}</span><span>{{ gmdate('i:s', $asset->duration_seconds) }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4 border-t border-slate-100 pt-4 text-center dark:border-slate-800">
                        <div><p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ number_format($stats->sum('plays')) }}</p><p class="text-xs text-slate-500">Plays (14d)</p></div>
                        <div><p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ round($stats->avg('completion_rate')) }}%</p><p class="text-xs text-slate-500">Avg completion</p></div>
                        <div><p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ round($stats->avg('skip_rate')) }}%</p><p class="text-xs text-slate-500">Skip rate</p></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Transcripts (M16) --}}
        @if ($asset->transcripts->isNotEmpty())
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Transcripts & Lyrics</h3></div>
                <div class="card-body space-y-4">
                    @foreach ($asset->transcripts as $transcript)
                        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="badge-slate">{{ ucfirst($transcript->transcript_type) }}</span>
                                @if ($transcript->is_ai_generated)<span class="badge-purple">AI generated</span>@endif
                                @if ($transcript->is_verified)
                                    <span class="badge-green">Verified</span>
                                @else
                                    <span class="badge-amber">Unverified draft (FR-AIF-06)</span>
                                @endif
                            </div>
                            <div class="space-y-1.5 text-sm text-slate-600 dark:text-slate-300">
                                @foreach (array_slice($transcript->lines ?? [], 0, 4) as $line)
                                    <p><span class="mr-2 tabular-nums text-xs text-slate-400">{{ gmdate('i:s', (int) $line['start']) }}</span>
                                    <span class="mr-1 text-xs font-medium text-primary-700 dark:text-primary-300">{{ $line['speaker'] ?? '' }}:</span>{{ $line['text'] }}</p>
                                @endforeach
                                @if (empty($transcript->lines) && $transcript->full_text)
                                    <p class="font-bangla whitespace-pre-wrap leading-relaxed">{{ Str::limit($transcript->full_text, 600) }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Approval history (M13) — always visible, including for already-published assets --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Approval History (FR-WRK-06)</h3></div>
            <div class="card-body space-y-4">
                @forelse ($asset->approvals as $approval)
                    <div class="flex items-start gap-3">
                        <x-icon name="workflow" class="mt-0.5 size-5 shrink-0 text-primary-600" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $approval->workflow?->name }}</span>
                                <x-status-badge :status="$approval->status" />
                                @if ($approval->currentStage)<span class="text-xs text-slate-500">Stage: {{ $approval->currentStage->name }}</span>@endif
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
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        No approval workflow has been run for this asset yet.
                        @can('assets.edit')
                            @if (in_array($asset->status, ['draft', 'qc_failed', 'rejected'], true))
                                Use “Submit for Approval” above to start one.
                            @endif
                        @endcan
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right rail --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Catalogue Details</h3></div>
            <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                @foreach ([
                    'Station' => $asset->station?->name,
                    'Department' => $asset->department?->name,
                    'Programme' => $asset->programme?->title,
                    'Category' => $asset->category?->name,
                    'Language' => $asset->language?->name,
                    'Recorded' => $asset->recorded_on?->format('j M Y'),
                    'First broadcast' => $asset->first_broadcast_on?->format('j M Y'),
                    'Published' => $asset->published_at?->format('j M Y'),
                    'Uploaded by' => $asset->uploader?->name,
                    'Source' => ucfirst(str_replace('_', ' ', $asset->source)),
                    'Size' => $asset->size_bytes ? round($asset->size_bytes / 1048576, 1).' MB' : null,
                    'Checksum' => $asset->checksum_sha256 ? substr($asset->checksum_sha256, 0, 16).'…' : null,
                ] as $label => $value)
                    @if ($value)
                        <div class="flex justify-between gap-3 px-5 py-2.5">
                            <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
            @if ($asset->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 border-t border-slate-100 px-5 py-3 dark:border-slate-800">
                    @foreach ($asset->tags as $tag)<span class="badge-slate">{{ $tag->name }}</span>@endforeach
                </div>
            @endif
        </div>

        {{-- Rights (M14) --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Rights</h3>
                <x-status-badge :status="$asset->rights_status" />
            </div>
            @forelse ($asset->rightsRecords as $record)
                <div class="border-t border-slate-100 px-5 py-3 text-sm first:border-0 dark:border-slate-800">
                    <p class="font-medium text-slate-700 dark:text-slate-200">{{ $record->rightsHolder?->name ?? 'Unknown holder' }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ implode(', ', array_map('ucfirst', $record->rights_types ?? [])) }} · {{ $record->territory }}
                        @if ($record->valid_until) · until {{ $record->valid_until->format('j M Y') }} @endif
                    </p>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-slate-500">No rights records yet. One is created automatically when the approval workflow completes — complete it in Rights Records and mark it cleared to allow publishing.</p>
            @endforelse
        </div>

        {{-- AI suggestions (M16) --}}
        @if ($asset->aiSuggestions->isNotEmpty())
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Suggestions</h3></div>
                @foreach ($asset->aiSuggestions as $suggestion)
                    <div class="border-t border-slate-100 px-5 py-3 text-sm first:border-0 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <span class="badge-purple">{{ ucfirst($suggestion->suggestion_type) }}</span>
                            <x-status-badge :status="$suggestion->status" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
