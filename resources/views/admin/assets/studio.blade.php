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
    $loaded = $versions->firstWhere('id', $selectedVersionId);
    // Toggleable workspace panels — all hidden by default, shown from the dock.
    $docks = [
        ['visualizers', 'Live Visualizers', 'chart-bar'],
        ['metering', 'Broadcast Metering', 'wave'],
        ['loudness', 'Loudness Meter', 'radio'],
        ['signal', 'Signal Analysis', 'sparkles'],
        ['tonal', 'Tonal Balance', 'music'],
        ['markers', 'Content Markers', 'queue'],
    ];
@endphp

<div class="studio-root pr-16" x-data>

{{-- ===================== Hero ===================== --}}
<div class="studio-hero mb-5 px-5 py-4 sm:px-6 sm:py-5">
    <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
            <a href="{{ route('admin.assets.show', $asset) }}" class="hero-btn hero-btn-sq" title="Back to asset"><x-icon name="chevron-left" class="size-4" /></a>
            <div class="studio-hero-tile shrink-0"><x-icon name="wave" class="size-7" /></div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl font-bold tracking-tight text-white sm:text-2xl">Audio Studio</h2>
                    <x-status-badge :status="$asset->status" />
                    @if ($loaded && $loaded->version_type === 'preservation_master')
                        <span class="studio-chip chip-amber">Master · immutable</span>
                    @endif
                </div>
                <p class="mt-0.5 truncate text-sm font-medium text-slate-300">{{ $asset->title }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="studio-chip"><span class="dot"></span>{{ $asset->archive_no }}</span>
                    <span class="studio-chip">{{ strtoupper($asset->format ?? '—') }}</span>
                    <span class="studio-chip">{{ $asset->channels == 1 ? 'Mono' : 'Stereo' }}</span>
                    <span class="studio-chip">{{ $asset->sample_rate ? $asset->sample_rate/1000 .' kHz' : '—' }}</span>
                    @if ($loaded)
                        <span class="studio-chip chip-primary">{{ $loaded->label ?? ucfirst(str_replace('_', ' ', $loaded->version_type)) }}{{ $loaded->duration_seconds ? ' · '.gmdate('i:s', (int) $loaded->duration_seconds) : '' }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <div class="eq-bars hidden lg:flex" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
            {{-- Version switcher: load any version of the family into the Studio --}}
            <label class="flex items-center gap-2 text-xs text-slate-300">
                <span class="hidden sm:inline font-medium">Version</span>
                <select class="hero-select w-52" onchange="window.location='{{ route('admin.assets.studio', $asset, false) }}?version='+this.value">
                    @foreach ($versions as $v)
                        <option value="{{ $v->id }}" @selected($v->id === $selectedVersionId)>
                            {{ $v->label ?? ucfirst(str_replace('_', ' ', $v->version_type)) }}{{ $v->is_default ? ' (default)' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            @can('assets.upload')
                <form method="POST" action="{{ route('admin.assets.upload', $asset) }}" enctype="multipart/form-data" class="flex items-center"
                      x-data @change="$refs.f.files.length && $el.submit()">
                    <label class="hero-btn cursor-pointer">
                        <x-icon name="upload" class="size-4" /> Replace
                        <input type="file" x-ref="f" name="audio_file" accept="audio/*,.wav,.flac,.mp3,.m4a,.ogg" class="hidden">
                    </label>
                    @csrf
                </form>
            @endcan
        </div>
    </div>
</div>

<div id="studio-toast" class="hidden fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg dark:bg-slate-700"></div>

<div id="audio-error" class="hidden mb-4 flex items-start gap-2.5 rounded-(--radius-app) border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"></div>

{{-- ===================== Waveform / Spectrogram (full width) ===================== --}}
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
            <label class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400" title="Fade curve shape">
                Curve
                <select id="fade-curve" class="rounded-md border border-slate-200 bg-white px-1.5 py-1 text-[11px] font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="linear">Linear</option>
                    <option value="exp">Exp</option>
                    <option value="log">Log</option>
                    <option value="scurve">S-curve</option>
                </select>
            </label>
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
    <div class="transport-bar flex items-center gap-4 border-t border-slate-200 px-4 py-3 dark:border-slate-800">
        <button id="btn-play" class="play-btn">
            <x-icon name="play" class="size-5 translate-x-0.5" id="icon-play" />
            <x-icon name="pause" class="size-5 hidden" id="icon-pause" />
        </button>
        <button id="btn-stop" class="btn-ghost btn-sm" title="Stop"><x-icon name="x" class="size-4" /></button>
        <button id="btn-ab" disabled aria-pressed="false" class="btn-secondary btn-sm opacity-40" title="A/B compare — flip between the edited preview and the original">
            <x-icon name="arrow-path" class="size-4" /> <span data-ab-label>A/B</span>
        </button>
        <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold tabular-nums text-slate-700 dark:bg-slate-800/70 dark:text-slate-200"><span id="cur-time" class="text-primary-600 dark:text-primary-300">0:00</span> <span class="text-slate-400">/</span> <span id="total-time">0:00</span></div>
        <label class="ml-auto flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            Speed
            <select id="speed" class="form-input py-1 w-20 text-xs">
                <option value="0.5">0.5×</option><option value="0.75">0.75×</option>
                <option value="1" selected>1×</option><option value="1.5">1.5×</option><option value="2">2×</option>
            </select>
        </label>
    </div>
</div>

{{-- ===================== Workspace: toggled panels (2-column masonry) ===================== --}}
{{-- Empty state — spans the full width --}}
<div x-show="!$store.studioPanels.any()" x-cloak x-transition.opacity.duration.200ms class="panel-empty mt-5">
    <span class="sc-ico accent mb-1"><x-icon name="chart-bar" class="size-4" /></span>
    <p class="text-sm font-semibold text-slate-600 dark:text-slate-200">Your workspace is clear</p>
    <p class="mt-1 max-w-sm text-xs text-slate-400">Show meters, analysis and markers from the <b>Panels</b> list on the right. Hide any panel again with its ✕ button.</p>
</div>

{{-- With 2+ panels open they pack into two balanced columns; a single open
     panel stays full width (a lone half-width panel would look off-balance). --}}
<div class="mt-5" :class="$store.studioPanels.openCount() > 1 ? 'studio-grid' : ''">

        {{-- Live audio visualizers --}}
        <div x-show="$store.studioPanels.open.visualizers" x-cloak x-transition.opacity.duration.200ms>
        <div class="card">
            <div class="card-header">
                <div class="sc-title">
                    <span class="sc-ico accent"><x-icon name="chart-bar" class="size-4" /></span>
                    <div>
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Live Audio Visualizers</h3>
                        <p class="text-[11px] text-slate-400">Real-time from the audio graph</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="live-pill"><span class="pulse"></span> LIVE · PRESS PLAY</span>
                    <button type="button" @click="$store.studioPanels.hide('visualizers')" class="panel-x" title="Hide panel"><x-icon name="x" class="size-4" /></button>
                </div>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#38bdf8"></span> Frequency Spectrum</p>
                    <canvas id="spectrum" width="420" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#a78bfa"></span> Equalizer (24-band, peak-hold)</p>
                    <canvas id="equalizer" width="420" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#34d399"></span> Circular Visualizer</p>
                    <canvas id="circular" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#fbbf24"></span> Stereo Goniometer (L/R phase)</p>
                    <canvas id="goniometer" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#f43f5e"></span> Peak / Level Meter (L · R)</p>
                    <canvas id="levels" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="flex flex-col justify-center rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-400">
                    <p class="font-semibold text-slate-600 dark:text-slate-300">Reading the meters</p>
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
        </div>

        {{-- Broadcast metering --}}
        <div x-show="$store.studioPanels.open.metering" x-cloak x-transition.opacity.duration.200ms>
        <div class="card">
            <div class="card-header">
                <div class="sc-title">
                    <span class="sc-ico"><x-icon name="chart-bar" class="size-4" /></span>
                    <div>
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Broadcast Metering</h3>
                        <p class="text-[11px] text-slate-400">Loudness / peak / phase compliance — live while playing</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="live-pill"><span class="pulse"></span> LIVE</span>
                    <button type="button" @click="$store.studioPanels.hide('metering')" class="panel-x" title="Hide panel"><x-icon name="x" class="size-4" /></button>
                </div>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#34d399"></span> Loudness (LUFS · M/S/I)</p>
                    <canvas id="lufs" width="440" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#fbbf24"></span> True Peak (dBTP)</p>
                    <canvas id="truepeak" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#38bdf8"></span> Phase Correlation</p>
                    <canvas id="correlation" width="440" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#f43f5e"></span> VU Meter</p>
                    <canvas id="vu" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#22d3ee"></span> PPM (quasi-peak)</p>
                    <canvas id="ppm" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#4ade80"></span> Oscilloscope</p>
                    <canvas id="scope" width="440" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
                <div class="viz-tile">
                    <p class="viz-label"><span class="d" style="background:#c084fc"></span> Mid / Side</p>
                    <canvas id="midside" width="300" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                </div>
            </div>
        </div>
        </div>

        {{-- Loudness meter --}}
        <div x-show="$store.studioPanels.open.loudness" x-cloak x-transition.opacity.duration.200ms>
        <div class="card">
            <div class="card-header">
                <div class="sc-title">
                    <span class="sc-ico"><x-icon name="chart-bar" class="size-4" /></span>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Loudness Meter</h3>
                </div>
                <button type="button" @click="$store.studioPanels.hide('loudness')" class="panel-x" title="Hide panel"><x-icon name="x" class="size-4" /></button>
            </div>
            <div class="card-body space-y-3">
                <div id="clip-warn" class="hidden rounded-lg bg-rose-100 px-3 py-1.5 text-xs font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">⚠ Clipping / over-level detected</div>
                <div id="clip-ok" class="flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"><x-icon name="check-badge" class="size-3.5" /> Levels OK — no clipping</div>
                <div>
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400"><span>Momentary RMS</span><span id="meter-rms" class="tabular-nums font-medium">— dB</span></div>
                    <div class="mt-1 meter-track"><div id="bar-rms" class="h-full rounded-full meter-fill-rms" style="width:0%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400"><span>Peak</span><span id="meter-peak" class="tabular-nums font-medium">— dB</span></div>
                    <div class="mt-1 meter-track"><div id="bar-peak" class="h-full rounded-full meter-fill-peak" style="width:0%"></div></div>
                </div>
                <dl class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Integrated LUFS</dt><dd class="text-lg font-semibold {{ $loudnessWarn ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $lufs !== null ? number_format($lufs,1) : '—' }}</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Peak dBFS</dt><dd class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $peak !== null ? number_format($peak,1) : '—' }}</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Silence</dt><dd class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $asset->silence_percent !== null ? $asset->silence_percent.'%' : '—' }}</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Target</dt><dd class="text-sm font-medium text-slate-700 dark:text-slate-200">−23 LUFS</dd></div>
                </dl>
                <p class="text-[11px] text-slate-400">EBU R128 broadcast target. Integrated values are measured on ingest.</p>

                {{-- Loudness over time + EBU R128 compliance (on-demand analysis) --}}
                <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Loudness over time · R128</span>
                        <span id="r128-badge" class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">—</span>
                    </div>
                    <button id="btn-loudness" class="btn-secondary btn-sm w-full"><x-icon name="chart-bar" class="size-4" /> Analyze loudness over time</button>
                    <span id="loudness-status" class="mt-1 block text-center text-[11px] text-slate-400"></span>
                    <canvas id="loudness-graph" class="mt-2 w-full rounded-lg bg-slate-950" style="height:120px"></canvas>
                    <div class="mt-1 flex justify-between text-[10px] tabular-nums text-slate-400"><span>−14</span><span>−23 target</span><span>−31 LUFS</span></div>
                    <dl class="mt-2 grid grid-cols-2 gap-2">
                        <div class="stat-tile"><dt class="text-[11px] text-slate-400">Integrated</dt><dd id="r128-integrated" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                        <div class="stat-tile"><dt class="text-[11px] text-slate-400">Loudness range</dt><dd id="r128-lra" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                        <div class="stat-tile"><dt class="text-[11px] text-slate-400">True peak</dt><dd id="r128-tp" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                        <div class="stat-tile"><dt class="text-[11px] text-slate-400">Short-term max</dt><dd id="r128-stmax" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    </dl>
                </div>
            </div>
        </div>
        </div>

        {{-- Signal analysis (ffmpeg astats — restoration QC) --}}
        <div x-show="$store.studioPanels.open.signal" x-cloak x-transition.opacity.duration.200ms>
        <div class="card">
            <div class="card-header">
                <div class="sc-title">
                    <span class="sc-ico accent"><x-icon name="sparkles" class="size-4" /></span>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Signal Analysis</h3>
                </div>
                <button type="button" @click="$store.studioPanels.hide('signal')" class="panel-x" title="Hide panel"><x-icon name="x" class="size-4" /></button>
            </div>
            <div class="card-body space-y-3">
                <button id="btn-astats" class="btn-secondary btn-sm w-full"><x-icon name="chart-bar" class="size-4" /> Analyze signal (astats)</button>
                <span id="astats-status" class="block text-center text-[11px] text-slate-400"></span>
                <dl id="astats-grid" class="hidden grid grid-cols-2 gap-2">
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">DC offset</dt><dd id="as-dc" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Crest factor</dt><dd id="as-crest" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Peak level</dt><dd id="as-peak" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">RMS level</dt><dd id="as-rms" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Noise floor</dt><dd id="as-noise" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Flat factor</dt><dd id="as-flat" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Peak samples</dt><dd id="as-peakn" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                    <div class="stat-tile"><dt class="text-[11px] text-slate-400">Bit depth</dt><dd id="as-bits" class="text-sm font-semibold text-slate-800 dark:text-slate-100">—</dd></div>
                </dl>
                <p class="text-[11px] text-slate-400">One-pass ffmpeg astats — restoration QC: DC bias, dynamics (crest), noise floor and clipping (flat factor / peak samples).</p>
            </div>
        </div>
        </div>

        {{-- Tonal balance (average octave spectrum, on-demand) --}}
        <div x-show="$store.studioPanels.open.tonal" x-cloak x-transition.opacity.duration.200ms>
        <div class="card">
            <div class="card-header">
                <div class="sc-title">
                    <span class="sc-ico"><x-icon name="music" class="size-4" /></span>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Tonal Balance</h3>
                </div>
                <button type="button" @click="$store.studioPanels.hide('tonal')" class="panel-x" title="Hide panel"><x-icon name="x" class="size-4" /></button>
            </div>
            <div class="card-body space-y-3">
                <button id="btn-tonal" class="btn-secondary btn-sm w-full"><x-icon name="chart-bar" class="size-4" /> Analyze tonal balance</button>
                <span id="tonal-status" class="block text-center text-[11px] text-slate-400"></span>
                <canvas id="tonal" width="360" height="150" class="w-full rounded-lg bg-slate-950" style="height:150px"></canvas>
                <p class="text-[11px] text-slate-400">Average energy per octave band (relative to the loudest). A gently falling curve is typical; spikes reveal resonances or hum.</p>
            </div>
        </div>
        </div>

        {{-- Content markers (chapters) --}}
        <div x-show="$store.studioPanels.open.markers" x-cloak x-transition.opacity.duration.200ms>
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <div class="sc-title">
                    <span class="sc-ico accent"><x-icon name="queue" class="size-4" /></span>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Content Markers</h3>
                </div>
                <div class="flex items-center gap-3">
                    <span id="marker-count" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $markers->where('marker_type', 'chapter')->count() }}</span>
                    @can('assets.edit')
                    {{-- Sliding toggle to enable content markers (chapters). --}}
                    <label class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer" title="Enable content markers">
                        <input type="checkbox" id="markers-enable" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-primary-600 dark:bg-slate-600"></span>
                        <span class="absolute left-0.5 top-0.5 size-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                    </label>
                    @endcan
                    <button type="button" @click="$store.studioPanels.hide('markers')" class="panel-x" title="Hide panel"><x-icon name="x" class="size-4" /></button>
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

{{-- Non-editors have no editing rail, so give them a matching mini-rail with
     the Panels launcher. Editors get the same launcher inside the editing rail
     (see partials/editor). Both drive the shared $store.studioPanels. --}}
@cannot('editing.use')
<div class="fixed right-0 top-16 bottom-0 z-40 flex w-16 flex-col items-center gap-0.5 border-l border-slate-200 bg-white/95 p-1.5 shadow-xl backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
    <p class="pb-0.5 text-[9px] font-semibold uppercase tracking-wider text-slate-400">View</p>
    <div class="group relative">
        <button @click="$store.studioPanels.togglePanels()" class="tool-btn" :class="$store.studioPanels.panelsOpen ? 'bg-primary-600 text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-primary-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-primary-300'"><x-icon name="squares" class="size-5" /></button>
        <span x-show="$store.studioPanels.any()" x-cloak class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-primary-600 text-[9px] font-bold text-white" x-text="$store.studioPanels.openCount()"></span>
        <span class="tool-tip">Visualization panels</span>
    </div>
</div>

{{-- Panels dialog — for non-editors this is the only Studio dialog. Editors get
     Panels as a tool inside the editing rail's shared dialog instead, so opening
     it replaces the previous tool in place. State: shared $store.studioPanels. --}}
<div x-show="$store.studioPanels.panelsOpen" x-cloak :style="`left:${$store.studioPanels.dialogX}px; top:${$store.studioPanels.dialogY}px`"
     class="fixed z-50 w-80 rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
    <div class="flex cursor-move items-center justify-between rounded-t-xl border-b border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800" @mousedown="$store.studioPanels.startDrag($event)">
        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Visualization Panels</span>
        <button @click="$store.studioPanels.closePanels()" @mousedown.stop class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><x-icon name="x" class="size-4" /></button>
    </div>
    <div class="max-h-[70vh] space-y-3 overflow-y-auto scrollbar-slim p-4">
        <p class="mb-1 text-[11px] text-slate-400">Show a panel in the workspace below. Hide it with the ✕ on the panel, or toggle it here.</p>
        @foreach ($docks as [$key, $label, $icon])
        <button type="button" @click="$store.studioPanels.toggle('{{ $key }}')" class="panel-toggle" :class="$store.studioPanels.open.{{ $key }} && 'on'">
            <span class="flex min-w-0 items-center gap-2.5">
                <span class="pt-ico"><x-icon name="{{ $icon }}" class="size-4" /></span>
                <span class="truncate">{{ $label }}</span>
            </span>
            <span x-show="$store.studioPanels.open.{{ $key }}"><x-icon name="check-badge" class="size-4" /></span>
            <span x-show="!$store.studioPanels.open.{{ $key }}" x-cloak><x-icon name="plus" class="size-4 text-slate-400" /></span>
        </button>
        @endforeach
        <button type="button" @click="$store.studioPanels.hideAll()" x-show="$store.studioPanels.any()" x-cloak class="mt-1 w-full rounded-lg px-2 py-1.5 text-[11px] font-medium text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300">Hide all</button>
    </div>
</div>
@endcannot

{{-- Editor · Enhancement · Restoration — floating icon toolbar + dialogs --}}
@can('editing.use')
@include('admin.assets.partials.editor')
@endcan

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
            loudness: '{{ route('admin.assets.loudness', $asset, false) }}',
            astats: '{{ route('admin.assets.astats', $asset, false) }}',
        },
    };

    // Workspace panel show/hide state (persisted). All panels start hidden.
    // Shared panel show/hide state as an Alpine store, so the launcher works
    // from both the editing rail (editors) and the view rail (everyone else).
    // All panels start hidden; the choice is persisted.
    document.addEventListener('alpine:init', () => {
        const KEYS = ['visualizers', 'metering', 'loudness', 'signal', 'tonal', 'markers'];
        const saved = (() => { try { return JSON.parse(localStorage.getItem('betar.studioPanels')) || {}; } catch (e) { return {}; } })();
        const open = {}; KEYS.forEach((k) => { open[k] = saved[k] === true; });
        Alpine.store('studioPanels', {
            keys: KEYS,
            open,
            panelsOpen: false,
            dialogX: 0, dialogY: 0,
            persist() { try { localStorage.setItem('betar.studioPanels', JSON.stringify(this.open)); } catch (e) { /* ignore */ } },
            any() { return this.keys.some((k) => this.open[k]); },
            openCount() { return this.keys.filter((k) => this.open[k]).length; },
            show(k) { this.open[k] = true; this.persist(); },
            hide(k) { this.open[k] = false; this.persist(); },
            toggle(k) { this.open[k] = !this.open[k]; this.persist(); },
            hideAll() { this.keys.forEach((k) => (this.open[k] = false)); this.persist(); },
            togglePanels() {
                if (!this.panelsOpen) { this.dialogX = Math.max(16, window.innerWidth - 320); this.dialogY = 128; }
                this.panelsOpen = !this.panelsOpen;
            },
            closePanels() { this.panelsOpen = false; },
            startDrag(e) {
                const dx = e.clientX - this.dialogX, dy = e.clientY - this.dialogY;
                const move = (ev) => {
                    this.dialogX = Math.max(0, Math.min(window.innerWidth - 80, ev.clientX - dx));
                    this.dialogY = Math.max(56, Math.min(window.innerHeight - 40, ev.clientY - dy));
                };
                const up = () => { window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', up); };
                window.addEventListener('mousemove', move); window.addEventListener('mouseup', up);
            },
        });
    });
</script>
@vite('resources/js/studio.js')

<style>
    [x-cloak] { display: none !important; }

    /* ---- Existing functional hooks (kept) ---- */
    .tool-btn { display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: .75rem; transition: background-color .15s, color .15s; }
    .tool-tip { pointer-events: none; position: absolute; right: 100%; top: 50%; margin-right: .5rem; transform: translateY(-50%); white-space: nowrap; border-radius: .375rem; background: #0f172a; padding: .25rem .5rem; font-size: .72rem; line-height: 1rem; color: #fff; opacity: 0; transition: opacity .12s; z-index: 60; }
    .group:hover > .tool-tip { opacity: 1; }
    #waveform ::part(region-content) { font-size: 10px; padding: 1px 4px; color: #fff; font-weight: 600; }
    #waveform ::part(region-handle) { width: 5px; }
    /* Operation toolbar buttons (act on the current selection) */
    .op-btn { display:inline-flex; align-items:center; gap:.3rem; border-radius:.5rem; border:1px solid rgb(226 232 240); background:#fff; padding:.25rem .6rem; font-size:11px; font-weight:600; line-height:1rem; color:rgb(51 65 85); transition:background-color .12s,border-color .12s,opacity .12s; }
    .op-btn:hover:not(:disabled) { background:rgb(248 250 252); border-color:rgb(148 163 184); }
    .op-btn:disabled { opacity:.4; cursor:not-allowed; }
    .dark .op-btn { border-color:rgb(51 65 85); background:rgb(30 41 59); color:rgb(226 232 240); }
    .dark .op-btn:hover:not(:disabled) { background:rgb(51 65 85); border-color:rgb(71 85 105); }
    /* Hide the inactive tab WITHOUT collapsing width. */
    .viz-hidden { position: absolute; left: 1rem; right: 1rem; top: 1rem; height: 0; overflow: hidden; opacity: 0; pointer-events: none; }

    /* ---- Studio premium theme (scoped to .studio-root) ---- */
    .studio-root .card { border-color: rgba(148,163,184,.16); box-shadow: 0 1px 2px rgba(2,6,23,.04), 0 18px 40px -30px rgba(2,6,23,.30); }
    .dark .studio-root .card { background: linear-gradient(180deg,#0f172a 0%, #0c1322 100%); border-color: rgba(148,163,184,.10); }
    .studio-root .card-header { border-bottom-color: rgba(148,163,184,.14); }

    .studio-root .tab-active { background: linear-gradient(135deg, var(--primary-600), var(--primary-700)); color:#fff; box-shadow: 0 4px 10px -4px var(--primary-600); }

    /* Section title icon tiles */
    .sc-title { display:flex; align-items:center; gap:.625rem; }
    .sc-ico { display:flex; align-items:center; justify-content:center; height:2rem; width:2rem; border-radius:.625rem; color:#fff; background:linear-gradient(135deg, var(--primary-500), var(--primary-700)); box-shadow:0 6px 14px -6px var(--primary-600); }
    .sc-ico.accent { background:linear-gradient(135deg, var(--accent-400), var(--accent-600)); box-shadow:0 6px 14px -6px var(--accent-500); }

    /* Panel hide (✕) button + dock toggles + empty state */
    .panel-x { display:flex; align-items:center; justify-content:center; height:1.75rem; width:1.75rem; border-radius:9999px; color:#94a3b8; background:rgba(148,163,184,.12); transition:background-color .12s,color .12s; }
    .panel-x:hover { background:rgba(244,63,94,.15); color:#e11d48; }
    .panel-toggle { display:flex; align-items:center; justify-content:space-between; gap:.5rem; width:100%; border-radius:.6rem; border:1px solid transparent; padding:.5rem .625rem; font-size:.8rem; font-weight:600; color:#475569; text-align:left; cursor:pointer; transition:background-color .12s,color .12s,border-color .12s; }
    .panel-toggle:hover { background:rgba(148,163,184,.12); }
    .dark .panel-toggle { color:#cbd5e1; }
    .panel-toggle.on { background:color-mix(in srgb,var(--primary-500) 14%, transparent); border-color:color-mix(in srgb,var(--primary-500) 30%, transparent); color:var(--primary-700); }
    .dark .panel-toggle.on { color:#bfdbfe; }
    .panel-toggle .pt-ico { display:flex; align-items:center; justify-content:center; height:1.6rem; width:1.6rem; border-radius:.45rem; background:rgba(148,163,184,.15); color:#64748b; }
    .panel-toggle.on .pt-ico { background:linear-gradient(135deg,var(--primary-500),var(--primary-700)); color:#fff; }
    .panel-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.25rem; text-align:center; border-radius:1rem; border:1px dashed rgba(148,163,184,.35); padding:3rem 1.5rem; }
    .dark .panel-empty { border-color:rgba(148,163,184,.22); }

    /* Workspace masonry — open panels pack into two balanced columns on large
       screens, so panels of different heights don't leave awkward grid gaps. */
    .studio-grid > * { margin-bottom: 1.25rem; }
    @media (min-width: 1024px) {
        .studio-grid { columns: 2; column-gap: 1.25rem; }
        .studio-grid > * { break-inside: avoid; }
    }

    /* Hero */
    .studio-hero { position:relative; overflow:hidden; border-radius:1rem;
        background:
            radial-gradient(1100px 220px at 10% -50%, color-mix(in srgb, var(--primary-500) 42%, transparent), transparent 60%),
            radial-gradient(760px 240px at 100% -10%, color-mix(in srgb, var(--accent-500) 32%, transparent), transparent 58%),
            linear-gradient(135deg, #0b1222 0%, #0e1830 55%, #0b1120 100%);
        border:1px solid rgba(148,163,184,.16); box-shadow:0 26px 55px -34px rgba(2,6,23,.75); }
    .studio-hero::after { content:""; position:absolute; inset:0 0 auto 0; height:1px; background:linear-gradient(90deg,transparent, rgba(255,255,255,.28), transparent); }
    .studio-hero-tile { display:flex; align-items:center; justify-content:center; height:3.25rem; width:3.25rem; border-radius:.9rem; color:#fff; background:linear-gradient(135deg, var(--accent-400), var(--primary-600)); box-shadow:0 12px 28px -8px var(--primary-600), inset 0 1px 0 rgba(255,255,255,.28); }
    .studio-chip { display:inline-flex; align-items:center; gap:.375rem; border-radius:9999px; padding:.25rem .625rem; font-size:.72rem; font-weight:600; color:#dbe4f3; background:rgba(148,163,184,.14); border:1px solid rgba(148,163,184,.18); }
    .studio-chip .dot { height:.4rem; width:.4rem; border-radius:9999px; background:var(--accent-400); }
    .studio-chip.chip-amber { color:#fde68a; background:rgba(251,191,36,.15); border-color:rgba(251,191,36,.3); }
    .studio-chip.chip-primary { color:#e0eaff; background:color-mix(in srgb, var(--primary-500) 22%, transparent); border-color:color-mix(in srgb, var(--primary-400) 40%, transparent); }
    .hero-btn-sq { padding:.45rem .5rem; }
    .hero-select { border-radius:.6rem; border:1px solid rgba(148,163,184,.24); background:rgba(15,23,42,.55); color:#e5edf9; padding:.4rem .7rem; font-size:.8rem; }
    .hero-select:focus { outline:2px solid color-mix(in srgb,var(--accent-500) 60%, transparent); }
    .hero-btn { display:inline-flex; align-items:center; gap:.4rem; cursor:pointer; border-radius:.6rem; border:1px solid rgba(148,163,184,.24); background:rgba(148,163,184,.12); color:#e7eefb; padding:.45rem .75rem; font-size:.8rem; font-weight:600; transition:background-color .15s; }
    .hero-btn:hover { background:rgba(148,163,184,.22); }

    .eq-bars { display:flex; align-items:flex-end; gap:3px; height:1.6rem; opacity:.9; }
    .eq-bars span { width:3px; height:30%; border-radius:2px; background:linear-gradient(180deg,var(--accent-400),var(--primary-500)); animation:eqb 1.1s ease-in-out infinite; }
    .eq-bars span:nth-child(2){animation-delay:.15s}.eq-bars span:nth-child(3){animation-delay:.30s}
    .eq-bars span:nth-child(4){animation-delay:.45s}.eq-bars span:nth-child(5){animation-delay:.60s}
    @keyframes eqb { 0%,100%{height:22%} 50%{height:100%} }

    /* Waveform stage */
    .studio-root #waveform-stage { background:
            radial-gradient(560px 180px at 50% 125%, color-mix(in srgb, var(--primary-500) 20%, transparent), transparent 70%),
            linear-gradient(180deg,#0a0f1d 0%, #070b16 100%) !important;
        box-shadow: inset 0 0 0 1px rgba(148,163,184,.08); }

    /* Transport */
    .play-btn { display:flex; height:3rem; width:3rem; align-items:center; justify-content:center; border-radius:9999px; color:#fff; background:linear-gradient(135deg, var(--accent-400), var(--primary-600)); box-shadow:0 12px 26px -8px var(--primary-600), inset 0 1px 0 rgba(255,255,255,.3); transition:transform .12s, box-shadow .15s; }
    .play-btn:hover { transform:translateY(-1px); box-shadow:0 16px 32px -8px var(--primary-600); }

    /* Live pill */
    .live-pill { display:inline-flex; align-items:center; gap:.4rem; border-radius:9999px; padding:.2rem .55rem; font-size:.66rem; font-weight:700; letter-spacing:.05em; color:#fff; background:linear-gradient(135deg,#fb7185,#e11d48); box-shadow:0 6px 14px -6px #e11d48; }
    .live-pill .pulse { height:.45rem; width:.45rem; border-radius:9999px; background:#fff; animation:pulseDot 1.4s infinite; }
    @keyframes pulseDot { 0%{box-shadow:0 0 0 0 rgba(255,255,255,.6)} 70%{box-shadow:0 0 0 6px rgba(255,255,255,0)} 100%{box-shadow:0 0 0 0 rgba(255,255,255,0)} }

    /* Visualizer tiles */
    .viz-tile { border-radius:.85rem; padding:.65rem; background:linear-gradient(180deg,#0b1120,#080d18); border:1px solid rgba(148,163,184,.12); }
    .viz-label { display:flex; align-items:center; gap:.45rem; margin-bottom:.45rem; font-size:.72rem; font-weight:600; color:#9fb0c9; }
    .viz-label .d { height:.5rem; width:.5rem; border-radius:9999px; box-shadow:0 0 8px 0 currentColor; }
    .viz-tile canvas { background:#060a14 !important; }

    /* Meters */
    .meter-track { height:.55rem; border-radius:9999px; background:rgba(148,163,184,.2); overflow:hidden; }
    .meter-fill-rms { background:linear-gradient(90deg,#10b981,#34d399,#a3e635); box-shadow:0 0 10px -2px #34d399; transition:width .1s linear; }
    .meter-fill-peak { background:linear-gradient(90deg,#f59e0b,#fb923c,#f43f5e); box-shadow:0 0 10px -2px #fb923c; transition:width .1s linear; }

    /* Stat tiles */
    .stat-tile { border-radius:.625rem; padding:.45rem .5rem; text-align:center; background:rgba(148,163,184,.10); border:1px solid rgba(148,163,184,.12); }
    .dark .stat-tile { background:rgba(148,163,184,.06); }
</style>
@endsection
