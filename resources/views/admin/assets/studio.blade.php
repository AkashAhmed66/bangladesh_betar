@extends('layouts.admin')

@section('title', 'Studio — '.$asset->title)

@section('content')
@php
    $lufs = $asset->loudness_lufs;
    $peak = $asset->peak_db;
    $loudnessWarn = ($lufs !== null && ($lufs > -14 || $lufs < -30)) || ($peak !== null && $peak > -1);
    // When the edit rail is shown, reserve a right gutter so the fixed rail
    // never overlaps the content.
    $canEditStudio = auth()->user()->can('editing.use');
@endphp

<div class="{{ $canEditStudio ? 'pr-16' : '' }}">

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
        {{-- Version switcher: load any version of the family into the Studio --}}
        <label class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="hidden sm:inline">Version</span>
            <select class="form-input w-56 py-1.5 text-sm" onchange="window.location='{{ route('admin.assets.studio', $asset, false) }}?version='+this.value">
                @foreach ($versions as $v)
                    <option value="{{ $v->id }}" @selected($v->id === $selectedVersionId)>
                        {{ $v->label ?? ucfirst(str_replace('_', ' ', $v->version_type)) }}{{ $v->is_default ? ' (default)' : '' }}
                    </option>
                @endforeach
            </select>
        </label>
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

{{-- Which version is loaded --}}
@php $loaded = $versions->firstWhere('id', $selectedVersionId); @endphp
@if ($loaded)
    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-500 dark:text-slate-400">Now editing / listening to:</span>
        <span class="badge-primary">{{ $loaded->label ?? ucfirst(str_replace('_', ' ', $loaded->version_type)) }}</span>
        @if ($loaded->version_type === 'preservation_master')<span class="badge-amber">Master — immutable</span>@endif
        <span class="text-xs text-slate-400">{{ strtoupper($loaded->format ?? '') }} · {{ $loaded->bitrate_kbps ? $loaded->bitrate_kbps.' kbps' : '' }} · {{ gmdate('i:s', (int) $loaded->duration_seconds) }}</span>
    </div>
@endif

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

            {{-- Inline waveform editor bar — trim / cut / move with real-time preview --}}
            @can('editing.use')
            <div id="edit-bar" class="border-t border-slate-100 px-4 py-2 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Edit</span>
                    <span id="sel-info" class="tabular-nums font-medium text-primary-600 dark:text-primary-300"></span>
                    <span class="text-slate-400">Drag to select → pick an action. <b>Del</b> cuts · double-click a block to remove · drag a block to move.</span>
                    <div class="ml-auto flex items-center gap-1.5">
                        <button id="btn-magnet" type="button" class="btn-ghost btn-sm" aria-pressed="false" title="Snap edits to the nearest zero-crossing for clean cuts">
                            <x-icon name="sparkles" class="size-3.5" /> Snap
                        </button>
                        <button id="btn-edit-undo" type="button" class="btn-ghost btn-sm" title="Undo (Ctrl+Z)"><x-icon name="arrow-path" class="size-3.5" /> Undo</button>
                        <button id="btn-edit-redo" type="button" class="btn-ghost btn-sm" title="Redo (Ctrl+Y)">Redo</button>
                        <button id="btn-edit-clear" type="button" class="btn-ghost btn-sm" title="Remove all edits">Clear</button>
                        <span id="edit-status" class="tabular-nums text-slate-400">no edits</span>
                    </div>
                </div>
                {{-- Operation toolbar — acts on the current selection --}}
                <div id="op-toolbar" class="mt-2 flex flex-wrap items-center gap-1.5">
                    <button type="button" data-op="delete" class="op-btn" title="Delete / cut the selection (Del)"><x-icon name="scissors" class="size-3.5" /> Cut</button>
                    <button type="button" data-op="trim" class="op-btn" title="Crop — keep only the selection">⇥ Crop</button>
                    <button type="button" data-op="silence" class="op-btn" title="Silence the selection">🔇 Silence</button>
                    <button type="button" data-op="fadein" class="op-btn" title="Fade in over the selection">⟋ Fade in</button>
                    <button type="button" data-op="fadeout" class="op-btn" title="Fade out over the selection">⟍ Fade out</button>
                    <span class="mx-0.5 h-5 w-px self-center bg-slate-200 dark:bg-slate-700"></span>
                    <label class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        <x-icon name="wave" class="size-3.5" /> Volume
                        <input id="vol-db" type="number" value="-3" step="0.5" min="-60" max="24" class="form-input w-16 py-0.5 text-xs" title="Gain in dB — negative makes it quieter, positive louder">
                        dB
                    </label>
                    <button type="button" data-op="volume" class="op-btn" title="Apply the volume change to the selection">Apply</button>
                    <span id="op-hint" class="ml-1 text-[11px] text-slate-400">Select part of the waveform to enable actions</span>
                </div>
            </div>
            @endcan

            {{-- position: relative anchors the width-preserving hide so WaveSurfer
                 never sees a 0-width resize when switching tabs. --}}
            <div id="waveform-stage" class="relative bg-slate-900 p-4 dark:bg-slate-950">
                {{-- Waveform view --}}
                <div id="waveform-view">
                    <div id="waveform" class="w-full"></div>
                    <div id="timeline" class="mt-1"></div>
                    <div id="minimap" class="mt-2 opacity-70"></div>
                </div>
                {{-- Busy overlay while a heavy-effect preview renders into the waveform --}}
                <div id="waveform-busy" class="hidden absolute inset-0 z-30 flex items-center justify-center gap-2 rounded-lg bg-slate-950/70 text-sm text-white">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    Rendering preview into the waveform…
                </div>
                {{-- Spectrogram view (starts width-preserved-hidden, not display:none) --}}
                <div id="spectrogram-view" class="viz-hidden">
                    <div class="relative isolate">
                        <div id="spectrogram" class="relative z-0 w-full cursor-pointer overflow-x-auto scrollbar-slim"></div>
                        {{-- Playhead: vertical cursor tracking the current playback time, above the canvas --}}
                        <div id="spectrogram-playhead" class="pointer-events-none absolute inset-y-0 z-20 w-0.5 bg-accent-400 shadow-[0_0_6px_1px] shadow-accent-500/70 transition-none" style="left:0; display:none">
                            <span class="absolute -top-1 left-1/2 size-2.5 -translate-x-1/2 rounded-full bg-accent-400"></span>
                        </div>
                    </div>
                    <p id="spectrogram-hint" class="mt-2 text-xs text-slate-400">Frequency (vertical, mel) × time (horizontal) × intensity (colour). The amber line marks the moment currently playing — click to seek.</p>
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

        {{-- Editor · Enhancement · Restoration — floating icon toolbar + dialogs --}}
        @can('editing.use')
        @include('admin.assets.partials.editor')
        @endcan
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

        {{-- Content markers (chapters) --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Content Markers</h3>
                <div class="flex items-center gap-3">
                    <span id="marker-count" class="text-xs text-slate-400">{{ $markers->where('marker_type', 'chapter')->count() }}</span>
                    @can('assets.edit')
                    {{-- Sliding toggle to enable content markers (chapters). --}}
                    <label class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer" title="Enable content markers">
                        <input type="checkbox" id="markers-enable" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-primary-600 dark:bg-slate-600"></span>
                        <span class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                    </label>
                    @endcan
                </div>
            </div>
            <div class="card-body space-y-3">
                <div id="markers-panel" class="hidden space-y-2">
                    <p class="text-xs text-slate-400">Play to a point, then <strong>Add break</strong> to start a new chapter — the new part is named <em>Ending</em> (rename it as you like). Listeners can jump to chapters in the public app.</p>
                    <div id="marker-list" class="max-h-64 space-y-1 overflow-y-auto scrollbar-slim"></div>
                    @can('assets.edit')
                    <div class="flex items-center gap-2">
                        <button id="btn-add-break" class="btn-secondary btn-sm flex-1"><x-icon name="plus" class="size-3.5" /> Add break</button>
                        <button id="btn-save-markers" class="btn-primary btn-sm flex-1 opacity-50" disabled><x-icon name="check-badge" class="size-3.5" /> Save</button>
                    </div>
                    <p id="markers-dirty-hint" class="hidden text-[11px] font-medium text-amber-600 dark:text-amber-400">Unsaved chapter changes — click Save to apply.</p>
                    @endcan
                </div>
            </div>
        </div>

    </div>
</div>

</div>{{-- /edit-rail gutter --}}

<script>
    window.__STUDIO__ = {
        assetId: {{ $asset->id }},
        csrf: '{{ csrf_token() }}',
        canEdit: {{ auth()->user()->can('assets.edit') ? 'true' : 'false' }},
        defaultVersionId: {{ $defaultVersion?->id ?? 'null' }},
        duration: {{ (int) $asset->duration_seconds }},
        sampleRate: {{ (int) ($asset->sample_rate ?: 48000) }},
        peaks: @json($asset->waveform_peaks ?? []),
        needsPeaks: {{ $needsPeaks ? 'true' : 'false' }},
        heatmap: @json($heatmap),
        segments: @json($segments),
        markers: @json($markersData),
        urls: {
            streamTemplate: '{{ route('admin.assets.stream', ['asset' => $asset->id, 'version' => '__V__'], false) }}',
            markerStore: '{{ route('admin.assets.markers.store', $asset, false) }}',
            markerSync: '{{ route('admin.assets.markers.sync', $asset, false) }}',
            markerUpdate: '{{ route('admin.assets.markers.update', ['asset' => $asset->id, 'marker' => '__ID__'], false) }}',
            markerDelete: '{{ route('admin.assets.markers.destroy', ['asset' => $asset->id, 'marker' => '__ID__'], false) }}',
            edit: '{{ route('admin.assets.edit-session', $asset, false) }}',
            render: '{{ route('admin.assets.render', $asset, false) }}',
            renderStatus: '{{ route('admin.assets.render.status', ['asset' => $asset->id, 'editSession' => '__ID__'], false) }}',
            preview: '{{ route('admin.assets.preview', $asset, false) }}',
            peaks: '{{ route('admin.assets.peaks', $asset, false) }}',
        },
    };
</script>
@vite('resources/js/studio.js')

<style>
    [x-cloak] { display: none !important; }
    .tab-active { background: var(--primary-600); color: #fff; }
    .tool-btn { display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: .75rem; transition: background-color .15s, color .15s; }
    .tool-tip { pointer-events: none; position: absolute; right: 100%; top: 50%; margin-right: .5rem; transform: translateY(-50%); white-space: nowrap; border-radius: .375rem; background: #0f172a; padding: .25rem .5rem; font-size: .72rem; line-height: 1rem; color: #fff; opacity: 0; transition: opacity .12s; z-index: 60; }
    .group:hover > .tool-tip { opacity: 1; }
    #waveform ::part(region-content) { font-size: 10px; padding: 1px 4px; color: #fff; font-weight: 600; }
    /* Operation toolbar buttons (act on the current selection) */
    .op-btn { display:inline-flex; align-items:center; gap:.3rem; border-radius:.5rem; border:1px solid rgb(226 232 240); background:#fff; padding:.25rem .6rem; font-size:11px; font-weight:600; line-height:1rem; color:rgb(51 65 85); transition:background-color .12s,border-color .12s,opacity .12s; }
    .op-btn:hover:not(:disabled) { background:rgb(248 250 252); border-color:rgb(148 163 184); }
    .op-btn:disabled { opacity:.4; cursor:not-allowed; }
    .dark .op-btn { border-color:rgb(51 65 85); background:rgb(30 41 59); color:rgb(226 232 240); }
    .dark .op-btn:hover:not(:disabled) { background:rgb(51 65 85); border-color:rgb(71 85 105); }
    /* Region handles a touch chunkier so they're easy to grab for move/resize */
    #waveform ::part(region-handle) { width: 5px; }
    /* Hide the inactive tab WITHOUT collapsing width — keeps the WaveSurfer
       wrapper at full width so no 0-width redraw wipes the spectrogram. */
    .viz-hidden {
        position: absolute;
        left: 1rem; right: 1rem; top: 1rem;
        height: 0; overflow: hidden; opacity: 0; pointer-events: none;
    }
</style>
@endsection
