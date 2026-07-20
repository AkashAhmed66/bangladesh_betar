@extends('layouts.admin')

@section('title', 'Studio — '.$asset->title)

@section('content')
@php
    $lufs = $asset->loudness_lufs;
    $peak = $asset->peak_db;
    $loudnessWarn = ($lufs !== null && ($lufs > -14 || $lufs < -30)) || ($peak !== null && $peak > -1);
@endphp

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.assets.show', $asset) }}" class="btn-ghost btn-sm"><x-icon name="chevron-left" class="size-4" /></a>
            <h2 class="page-title">Audio Studio</h2>
            <x-status-badge :status="$asset->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $asset->title }} · {{ $asset->archive_no }} · {{ strtoupper($asset->format ?? '—') }} · {{ $asset->channels == 1 ? 'Mono' : 'Stereo' }} · {{ $asset->sample_rate ? $asset->sample_rate/1000 .' kHz' : '—' }}</p>
    </div>
    <div class="flex items-center gap-2">
        @can('assets.upload')
            <form method="POST" action="{{ route('admin.assets.upload', $asset) }}" enctype="multipart/form-data" class="flex items-center gap-2"
                  x-data @change="$refs.f.files.length && $el.submit()">
                <label class="btn-secondary btn-sm cursor-pointer">
                    <x-icon name="upload" class="size-4" /> Replace audio
                    <input type="file" x-ref="f" name="audio_file" accept="audio/*,.wav,.flac,.mp3,.m4a,.ogg" class="hidden">
                </label>
                @csrf
            </form>
        @endcan
    </div>
</div>

<div id="studio-toast" class="hidden fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg dark:bg-slate-700"></div>

<div id="audio-error" class="hidden mb-4 flex items-start gap-2.5 rounded-(--radius-app) border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"></div>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-4">

    {{-- Main visualization column --}}
    <div class="space-y-5 xl:col-span-3">

        {{-- Waveform / Spectrogram --}}
        <div class="card overflow-hidden">
            <div class="card-header">
                <div class="flex items-center gap-1 rounded-lg border border-slate-200 p-0.5 dark:border-slate-700">
                    <button id="tab-waveform" class="tab-active rounded-md px-3 py-1 text-sm font-medium">Waveform</button>
                    <button id="tab-spectrogram" class="rounded-md px-3 py-1 text-sm font-medium text-slate-500">Spectrogram</button>
                </div>
                <div class="flex items-center gap-1.5">
                    <button id="btn-zoom-out" class="btn-ghost btn-sm" title="Zoom out"><x-icon name="search" class="size-4" />−</button>
                    <span id="zoom-val" class="w-14 text-center text-xs text-slate-400">fit</span>
                    <button id="btn-zoom-in" class="btn-ghost btn-sm" title="Zoom in"><x-icon name="search" class="size-4" />+</button>
                    <button id="btn-detect-silence" class="btn-secondary btn-sm" title="Detect silent/low sections">
                        <x-icon name="wave" class="size-4" /> Silence
                    </button>
                    <span id="silence-count" class="text-xs text-slate-400"></span>
                </div>
            </div>
            <div class="bg-slate-900 p-4 dark:bg-slate-950">
                {{-- Waveform view --}}
                <div id="waveform-view">
                    <div id="waveform" class="w-full"></div>
                    <div id="timeline" class="mt-1"></div>
                    <div id="minimap" class="mt-2 opacity-70"></div>
                </div>
                {{-- Spectrogram view --}}
                <div id="spectrogram-view" class="hidden">
                    <div id="spectrogram" class="w-full overflow-x-auto scrollbar-slim"></div>
                    <p id="spectrogram-hint" class="mt-2 text-xs text-slate-400">Frequency (vertical, mel) × time (horizontal) × intensity (colour) — reveals noise, hiss, silence and clipping.</p>
                </div>
            </div>

            {{-- Transport --}}
            <div class="flex items-center gap-4 border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                <button id="btn-play" class="flex size-11 items-center justify-center rounded-full bg-primary-600 text-white hover:bg-primary-500">
                    <x-icon name="play" class="size-5 translate-x-0.5" id="icon-play" />
                    <x-icon name="pause" class="size-5 hidden" id="icon-pause" />
                </button>
                <button id="btn-stop" class="btn-ghost btn-sm" title="Stop"><x-icon name="x" class="size-4" /></button>
                <div class="text-sm tabular-nums text-slate-600 dark:text-slate-300"><span id="cur-time">0:00</span> / <span id="total-time">0:00</span></div>
                <label class="ml-auto flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    Speed
                    <select id="speed" class="form-input py-1 w-20 text-xs">
                        <option value="0.5">0.5×</option><option value="0.75">0.75×</option>
                        <option value="1" selected>1×</option><option value="1.5">1.5×</option><option value="2">2×</option>
                    </select>
                </label>
            </div>
        </div>

        {{-- Live audio visualizers (Audio Visualization spec) --}}
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Live Audio Visualizers</h3>
                <span class="text-xs text-slate-400">▶ Press play — real-time from the audio graph</span>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <p class="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Frequency Spectrum</p>
                    <canvas id="spectrum" width="420" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Equalizer (24-band, peak-hold)</p>
                    <canvas id="equalizer" width="420" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Circular Visualizer</p>
                    <canvas id="circular" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Stereo Goniometer (L/R phase)</p>
                    <canvas id="goniometer" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Peak / Level Meter (L · R)</p>
                    <canvas id="levels" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="flex flex-col justify-center rounded-lg bg-slate-50 p-3 text-xs text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                    <p class="font-medium text-slate-600 dark:text-slate-300">Reading the meters</p>
                    <ul class="mt-1.5 space-y-1">
                        <li>• Spectrum / equalizer: energy per frequency (low→high).</li>
                        <li>• Circular: radial spectrum + level-pulsed core.</li>
                        <li>• Goniometer: vertical = mono, wide = stereo.</li>
                        <li>• Levels: green→red per channel, white cap = peak-hold, red = clip.</li>
                        <li>• Full spectrogram: use the tab on the waveform above.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Editing toolbar (M12) --}}
        @can('editing.use')
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Non-destructive Editing</h3>
                <button id="btn-edit-mode" class="btn-secondary btn-sm" aria-pressed="false">
                    <x-icon name="scissors" class="size-4" /> Enable edit mode
                </button>
            </div>
            <div id="edit-toolbar" class="hidden card-body space-y-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">Drag on the waveform to select a region, then apply an operation. The preservation master is never modified — saving creates a new edited version (FR-EDT-01).</p>
                <div class="flex flex-wrap gap-2">
                    <button class="btn-secondary btn-sm" data-op="trim">Trim to selection</button>
                    <button class="btn-secondary btn-sm" data-op="cut">Cut selection</button>
                    <button class="btn-secondary btn-sm" data-op="split">Split at cursor</button>
                    <button class="btn-secondary btn-sm" data-op="fade_in">Fade in</button>
                    <button class="btn-secondary btn-sm" data-op="fade_out">Fade out</button>
                    <button class="btn-secondary btn-sm" data-op="gain">Gain +3 dB</button>
                    <span class="mx-1 w-px bg-slate-200 dark:bg-slate-700"></span>
                    <button id="btn-undo" class="btn-ghost btn-sm"><x-icon name="arrow-path" class="size-4 -scale-x-100" /> Undo</button>
                    <button id="btn-redo" class="btn-ghost btn-sm"><x-icon name="arrow-path" class="size-4" /> Redo</button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="mb-1 text-xs font-medium text-slate-500 dark:text-slate-400">Edit Decision List (<span id="edl-count">0</span>)</p>
                        <ul id="edl-list" class="space-y-1 rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-800"></ul>
                    </div>
                    <div class="flex flex-col justify-end gap-2">
                        <input id="edit-title" class="form-input" placeholder="Edited version name (optional)">
                        <button id="btn-save-edit" class="btn-primary"><x-icon name="check-badge" class="size-4" /> Save as new version</button>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- Compare original vs edited --}}
        @if ($versions->count() > 1)
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Compare Versions</h3>
                <select id="compare-version" class="form-input w-64 text-sm">
                    <option value="">Select a version to compare…</option>
                    @foreach ($versions as $v)
                        @if (! $v->is_default)
                            <option value="{{ $v->id }}">{{ $v->label ?? ucfirst(str_replace('_',' ',$v->version_type)) }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div id="compare-wrap" class="hidden bg-slate-900 p-4 dark:bg-slate-950">
                <p class="mb-2 text-xs text-slate-400">Comparison version (default/online version is shown above)</p>
                <div id="compare-waveform"></div>
                <div id="compare-timeline" class="mt-1"></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Right rail: meters + markers + segments --}}
    <div class="space-y-5">

        {{-- Loudness meter --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Loudness Meter</h3></div>
            <div class="card-body space-y-3">
                <div id="clip-warn" class="hidden rounded-lg bg-rose-100 px-3 py-1.5 text-xs font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">⚠ Clipping / over-level detected</div>
                <div>
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400"><span>Momentary RMS</span><span id="meter-rms">— dB</span></div>
                    <div class="mt-1 h-2 rounded-full bg-slate-200 dark:bg-slate-700"><div id="bar-rms" class="h-2 rounded-full bg-emerald-500" style="width:0%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400"><span>Peak</span><span id="meter-peak">— dB</span></div>
                    <div class="mt-1 h-2 rounded-full bg-slate-200 dark:bg-slate-700"><div id="bar-peak" class="h-2 rounded-full bg-amber-500" style="width:0%"></div></div>
                </div>
                <dl class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-center dark:border-slate-800">
                    <div><dt class="text-xs text-slate-400">Integrated LUFS</dt><dd class="text-lg font-semibold {{ $loudnessWarn ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $lufs !== null ? number_format($lufs,1) : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Peak dBFS</dt><dd class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $peak !== null ? number_format($peak,1) : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Silence</dt><dd class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $asset->silence_percent !== null ? $asset->silence_percent.'%' : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Target</dt><dd class="text-sm font-medium text-slate-700 dark:text-slate-200">−23 LUFS</dd></div>
                </dl>
                <p class="text-[11px] text-slate-400">EBU R128 broadcast target. Integrated values are measured on ingest.</p>
            </div>
        </div>

        {{-- Content markers --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Content Markers</h3><span class="text-xs text-slate-400">{{ $markers->count() }}</span></div>
            <div class="card-body space-y-3">
                @can('assets.edit')
                <div class="flex flex-wrap items-center gap-2">
                    <select id="marker-type" class="form-input w-28 py-1.5 text-xs">
                        @foreach ($markerTypes as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                    </select>
                    <input id="marker-label" class="form-input flex-1 py-1.5 text-xs" placeholder="Label (at cursor/selection)">
                    <button id="btn-add-marker" class="btn-primary btn-sm"><x-icon name="plus" class="size-3.5" /></button>
                </div>
                @endcan
                <div id="marker-list" class="max-h-64 space-y-0.5 overflow-y-auto scrollbar-slim"></div>
            </div>
        </div>

        {{-- Speaker / segment navigation --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Speakers & Segments</h3></div>
            <div id="segment-list" class="card-body max-h-72 space-y-0.5 overflow-y-auto scrollbar-slim">
                @if ($segments->isEmpty())<p class="text-sm text-slate-400">No transcript segments. Add a transcript to enable speaker navigation.</p>@endif
            </div>
        </div>
    </div>
</div>

<script>
    window.__STUDIO__ = {
        assetId: {{ $asset->id }},
        csrf: '{{ csrf_token() }}',
        canEdit: {{ auth()->user()->can('assets.edit') ? 'true' : 'false' }},
        defaultVersionId: {{ $defaultVersion?->id ?? 'null' }},
        duration: {{ (int) $asset->duration_seconds }},
        peaks: @json($asset->waveform_peaks ?? []),
        needsPeaks: {{ $needsPeaks ? 'true' : 'false' }},
        heatmap: @json($heatmap),
        segments: @json($segments),
        markers: @json($markersData),
        urls: {
            streamTemplate: '{{ route('admin.assets.stream', ['asset' => $asset->id, 'version' => '__V__'], false) }}',
            markerStore: '{{ route('admin.assets.markers.store', $asset, false) }}',
            markerDelete: '{{ route('admin.assets.markers.destroy', ['asset' => $asset->id, 'marker' => '__ID__'], false) }}',
            edit: '{{ route('admin.assets.edit-session', $asset, false) }}',
            peaks: '{{ route('admin.assets.peaks', $asset, false) }}',
        },
    };
</script>
@vite('resources/js/studio.js')

<style>
    .tab-active { background: var(--primary-600); color: #fff; }
    #waveform ::part(region-content) { font-size: 10px; padding: 1px 4px; }
</style>
@endsection
