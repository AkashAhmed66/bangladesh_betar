@extends('layouts.admin')

@section('title', 'Studio — '.$channel->name)

@section('content')
<x-page-header :title="'Studio: '.$channel->name" subtitle="On-air console — go live from your microphone. Listeners tune in on the public app.">
    <a href="{{ route('admin.broadcast-channels.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Channels</a>
</x-page-header>

<div id="broadcast-error" class="mb-5 hidden rounded-(--radius-app) border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300"></div>

@unless ($channel->is_active)
    <div class="mb-5 rounded-(--radius-app) border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
        This channel is currently <strong>disabled</strong>. Re-enable it in the channel settings before going live.
    </div>
@endunless

{{-- Top status strip: channel identity · on-air clock · connection quality --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-(--radius-app) border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex min-w-0 items-center gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
            <x-icon name="radio" class="size-5" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $channel->name }}</p>
            <p class="truncate text-xs text-slate-400">
                {{ $channel->station?->name ?? 'No station' }}
                @if ($channel->name_bn) · <span style="font-family:'Noto Sans Bengali',sans-serif;">{{ $channel->name_bn }}</span>@endif
            </p>
        </div>
    </div>
    <div class="flex w-full items-center justify-between gap-2 text-xs sm:w-auto sm:justify-end sm:gap-4">
        <span class="inline-flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
            <x-icon name="clock" class="size-4" /> <span id="onair-clock" class="font-medium tabular-nums">--:--:--</span>
        </span>
        <span id="conn-quality" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
            <span id="conn-dot" class="size-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
            <span id="conn-quality-text">Not connected</span>
        </span>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-3">
    {{-- ============================ MAIN CONSOLE ============================ --}}
    <div class="space-y-5 xl:col-span-2">
        {{-- On-air control --}}
        <div class="card">
            <div class="card-header flex items-start justify-between gap-3 sm:items-center">
                <div class="flex flex-wrap items-center gap-2">
                    <div id="status-pill" class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <span class="size-2.5 rounded-full bg-slate-400"></span>
                        <span id="status-text">Off air</span>
                    </div>
                    <div id="recording-pill" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <span class="size-2 rounded-full bg-slate-400"></span>
                        <span id="recording-text">Recorder ready</span>
                    </div>
                </div>
                <div class="text-right">
                    <span id="elapsed" class="block text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100 sm:inline">00:00</span>
                    <span class="text-[10px] uppercase tracking-wide text-slate-400 sm:ml-1">on air</span>
                </div>
            </div>

            <div class="card-body flex flex-col items-center gap-6 py-8">
                {{-- On-air lamp + big control --}}
                <div class="relative flex size-36 items-center justify-center sm:size-44">
                    <div id="lamp-ring" class="absolute inset-0 rounded-full border-4 border-slate-200 transition-colors dark:border-slate-800"></div>
                    <button id="go-live-btn" type="button"
                            class="group z-10 flex size-28 flex-col items-center justify-center gap-2 rounded-full bg-primary-600 text-white shadow-lg transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 sm:size-36">
                        <x-icon name="microphone" class="size-11" />
                        <span class="text-sm font-semibold">Go Live</span>
                    </button>
                    <button id="stop-btn" type="button"
                            class="z-10 hidden size-28 flex-col items-center justify-center gap-2 rounded-full bg-rose-600 text-white shadow-lg transition hover:bg-rose-700 sm:size-36">
                        <x-icon name="x" class="size-11" />
                        <span class="text-sm font-semibold">Stop</span>
                    </button>
                </div>

                {{-- Transport controls (active while live) --}}
                <div class="flex items-center gap-2">
                    <button id="mute-btn" type="button" disabled aria-label="Mute microphone" title="Mute microphone"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                        <span id="mute-icon" class="inline-flex size-4" aria-hidden="true">
                            <x-icon name="microphone" class="size-4" />
                        </span>
                        <span id="mute-label">Mute</span>
                    </button>
                    <button id="monitor-btn" type="button" disabled
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 14v-2a8 8 0 1 1 16 0v2M4 14a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h1v-5H4Zm16 0a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-1v-5h1Z"/></svg>
                        <span id="monitor-label">Monitor</span>
                    </button>
                </div>

                {{-- On-air topic --}}
                <div class="w-full max-w-md">
                    <label for="topic-input" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">On-air topic <span class="text-slate-400">(optional)</span></label>
                    <input id="topic-input" type="text" maxlength="120" placeholder="e.g. Morning news bulletin"
                           class="form-input w-full">
                </div>

                <p id="mic-hint" class="max-w-md text-center text-xs text-slate-400">
                    Click <strong>Go Live</strong> and allow microphone access when your browser prompts you. Use <kbd class="rounded border border-slate-300 px-1 text-[10px] dark:border-slate-600">M</kbd> to mute while on air.
                </p>
            </div>
        </div>

        {{-- Input monitor: level meter + spectrum --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <span class="text-sm font-semibold">Input Monitor</span>
                <div class="flex items-center gap-3 text-xs">
                    <span class="text-slate-400">Peak</span>
                    <span id="peak-db" class="w-16 text-right font-semibold tabular-nums text-slate-700 dark:text-slate-200">−∞ dB</span>
                    <span id="clip-led" class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-slate-200 text-slate-400 dark:bg-slate-800 dark:text-slate-500">Clip</span>
                </div>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <canvas id="meter-canvas" height="44" class="w-full rounded-md bg-slate-100 dark:bg-slate-950" style="height:44px"></canvas>
                    <div class="mt-1 flex justify-between px-0.5 text-[9px] tabular-nums text-slate-400">
                        <span>−60</span><span>−48</span><span>−36</span><span>−24</span><span>−18</span><span>−12</span><span>−6</span><span>0</span>
                    </div>
                </div>
                <div>
                    <p class="mb-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Frequency spectrum</p>
                    <canvas id="spectrum-canvas" height="120" class="w-full rounded-lg bg-slate-950" style="height:120px"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================== SIDEBAR ============================== --}}
    <div class="space-y-5">
        {{-- Audience --}}
        <div class="card">
            <div class="card-header"><span class="text-sm font-semibold">Audience</span></div>
            <div class="card-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Listeners now</p>
                        <p id="listeners" class="text-3xl font-bold tabular-nums text-slate-800 dark:text-slate-100">0</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Peak</p>
                        <p id="peak-listeners" class="text-3xl font-bold tabular-nums text-slate-800 dark:text-slate-100">0</p>
                    </div>
                </div>
                <div>
                    <p class="mb-1 text-xs text-slate-400">Listeners over time</p>
                    <canvas id="spark-canvas" height="48" class="w-full rounded bg-slate-50 dark:bg-slate-950/40" style="height:48px"></canvas>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                    <span class="text-slate-400">Started</span>
                    <span id="started-at" class="tabular-nums text-slate-600 dark:text-slate-300">—</span>
                </div>
            </div>
        </div>

        {{-- Interactive audience: invite listeners to speak on air --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <span class="text-sm font-semibold">Listeners</span>
                <span id="listeners-count" class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500 dark:bg-slate-800 dark:text-slate-400">0</span>
            </div>
            <div class="card-body">
                <p id="listeners-empty" class="py-6 text-center text-xs text-slate-400">
                    Go live to see who's tuned in — then invite a listener to speak.
                </p>
                <div id="listeners-list" class="hidden max-h-80 space-y-2 overflow-y-auto pr-1"></div>
            </div>
        </div>

        {{-- Microphone / input settings --}}
        <div class="card">
            <div class="card-header"><span class="text-sm font-semibold">Microphone</span></div>
            <div class="card-body space-y-4">
                <div>
                    <label for="mic-select" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Input device</label>
                    <select id="mic-select" class="form-input w-full text-sm">
                        <option value="">Default device</option>
                    </select>
                </div>
                <div>
                    <label for="gain-range" class="mb-1 flex justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                        <span>Input gain</span><span id="gain-val" class="tabular-nums text-slate-600 dark:text-slate-300">0 dB</span>
                    </label>
                    <input id="gain-range" type="range" min="-12" max="12" step="0.5" value="0" class="w-full">
                </div>
                <div class="space-y-2">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Processing</p>
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input id="proc-echo" type="checkbox" checked class="rounded"> Echo cancellation</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input id="proc-noise" type="checkbox" checked class="rounded"> Noise suppression</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input id="proc-agc" type="checkbox" checked class="rounded"> Auto gain control</label>
                </div>
                <button id="arm-btn" type="button" class="btn-secondary btn-sm w-full">
                    <x-icon name="microphone" class="size-4" /> Test microphone
                </button>
                <p class="text-[11px] text-slate-400">Device &amp; processing lock while on air; gain and monitor stay adjustable.</p>
            </div>
        </div>

        {{-- Listen link --}}
        <div class="card">
            <div class="card-header"><span class="text-sm font-semibold">Listen link</span></div>
            <div class="card-body space-y-3">
                <div class="flex items-center gap-2">
                    <input id="listen-url" type="text" readonly value="{{ $listenUrl }}" class="form-input w-full text-xs" onclick="this.select()">
                    <button id="copy-link-btn" type="button" class="btn-secondary btn-sm shrink-0" title="Copy link">
                        <x-icon name="clipboard-check" class="size-4" />
                    </button>
                </div>
                <a href="{{ $listenUrl }}" target="_blank" rel="noreferrer"
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                    <x-icon name="globe" class="size-4" /> Open public listen page
                </a>
            </div>
        </div>

    </div>
</div>

{{-- Complete recording history for this channel. --}}
<div class="card mt-5" id="recording-history">
    <div class="card-header flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <x-icon name="archive" class="size-4 text-primary-600 dark:text-primary-400" />
                <span class="text-sm font-semibold">Broadcast recordings</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $recordedSessions->total() }}</span>
            </div>
            <p class="mt-1 text-xs font-normal text-slate-400">Protected recordings for {{ $channel->name }}. Published audio appears for Premium listeners; direct downloads are disabled.</p>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['recordings' => $recordedSessions->currentPage()]) }}#recording-history" class="btn-secondary btn-sm">
            <x-icon name="arrow-path" class="size-4" /> Refresh
        </a>
    </div>

    @if ($recordedSessions->isEmpty())
        <div class="card-body py-12 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <x-icon name="microphone" class="size-6" />
            </span>
            <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">No recordings yet</p>
            <p class="mt-1 text-xs text-slate-400">The next broadcast will be recorded automatically and will appear here after it is stopped.</p>
        </div>
    @else
        <div class="table-shell" aria-label="Broadcast recordings table; scroll horizontally for more columns on small screens">
            <table class="w-full min-w-[1280px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:border-slate-800">
                        <th class="px-5 py-3">Broadcast</th>
                        <th class="px-4 py-3">Date &amp; time</th>
                        <th class="px-4 py-3">Length</th>
                        <th class="px-4 py-3">Audience</th>
                        <th class="px-4 py-3">File</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3">Audio</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($recordedSessions as $session)
                        @php
                            $recording = $session->recording;
                            $duration = $recording->duration_seconds
                                ?? ($session->started_at ? (int) $session->started_at->diffInSeconds($session->ended_at ?? now()) : 0);
                            $durationLabel = $duration >= 3600
                                ? sprintf('%d:%02d:%02d', intdiv($duration, 3600), intdiv($duration % 3600, 60), $duration % 60)
                                : sprintf('%02d:%02d', intdiv($duration, 60), $duration % 60);
                            $sizeLabel = $recording->file_size
                                ? ($recording->file_size >= 1048576
                                    ? number_format($recording->file_size / 1048576, 1).' MB'
                                    : number_format($recording->file_size / 1024, 1).' KB')
                                : '—';
                            $statusStyle = match ($recording->status) {
                                'complete' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                'active' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
                                'starting', 'finalizing', 'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                default => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
                            };
                            $statusLabel = match ($recording->status) {
                                'active' => 'Recording',
                                'finalizing' => 'Processing',
                                'complete' => 'Ready',
                                default => ucfirst($recording->status),
                            };
                            $hlsUrl = $recording->isPlayable()
                                ? \App\Support\Hls::broadcastRecordingUrl($recording, admin: true)
                                : null;
                        @endphp
                        <tr class="align-middle hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                        <x-icon name="radio" class="size-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="max-w-64 truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $session->title ?: 'Untitled broadcast' }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-400">{{ $session->broadcaster?->name ?? 'Unknown broadcaster' }} · Session #{{ $session->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <p class="font-medium text-slate-600 dark:text-slate-300">{{ $session->started_at?->format('M j, Y') ?? '—' }}</p>
                                <p class="mt-0.5 tabular-nums text-slate-400">{{ $session->started_at?->format('g:i A') ?? '—' }}@if ($session->ended_at) – {{ $session->ended_at->format('g:i A') }}@endif</p>
                            </td>
                            <td class="px-4 py-4 text-sm font-semibold tabular-nums text-slate-600 dark:text-slate-300">{{ $durationLabel }}</td>
                            <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <p><span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ $session->peak_listeners }}</span> peak</p>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <p class="font-medium uppercase">{{ $recording->format ?: 'OGG' }}</p>
                                <p class="mt-0.5 tabular-nums text-slate-400">{{ $sizeLabel }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $statusStyle }}">
                                    @if ($recording->status === 'active')<span class="size-1.5 animate-pulse rounded-full bg-current"></span>@endif
                                    {{ $statusLabel }}
                                </span>
                                @if ($recording->isPublished())
                                    <span class="mt-1 inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">Premium public</span>
                                @elseif ($recording->isPlayable())
                                    <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">Unpublished</span>
                                @endif
                                @if ($recording->error)
                                    <p class="mt-1 max-w-48 truncate text-[10px] text-rose-500" title="{{ $recording->error }}">{{ $recording->error }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($hlsUrl)
                                    <audio controls controlslist="nodownload noplaybackrate" oncontextmenu="return false" preload="none" class="h-10 w-64 max-w-full sm:w-72"
                                           data-hls="{{ $hlsUrl }}" x-init="window.betarHls($el)">
                                        Your browser does not support audio playback.
                                    </audio>
                                @elseif ($recording->isPlayable())
                                    <span class="inline-flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400"><span class="size-3 animate-spin rounded-full border-2 border-amber-300 border-t-amber-600"></span> Preparing protected stream</span>
                                @elseif (in_array($recording->status, ['starting', 'active', 'finalizing', 'pending'], true))
                                    <span class="inline-flex items-center gap-2 text-xs text-slate-400"><span class="size-3 animate-spin rounded-full border-2 border-slate-300 border-t-primary-500"></span> Audio is being prepared</span>
                                @else
                                    <span class="text-xs text-slate-400">Audio unavailable</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @can('broadcasts.manage')
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($recording->isPlayable())
                                            @if ($recording->isPublished())
                                                <form method="POST" action="{{ route('admin.broadcast-recordings.unpublish', $recording) }}"
                                                      onsubmit="return confirm('Remove this recording from the Premium public archive?');">
                                                    @csrf
                                                    <button type="submit" class="btn-secondary btn-sm"><x-icon name="x" class="size-4" /> Unpublish</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.broadcast-recordings.publish', $recording) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-primary btn-sm"><x-icon name="check" class="size-4" /> Publish</button>
                                                </form>
                                            @endif
                                        @endif
                                        @unless (in_array($recording->status, ['pending', 'starting', 'active', 'finalizing'], true))
                                            <form method="POST" action="{{ route('admin.broadcast-recordings.destroy', $recording) }}"
                                                  onsubmit="return confirm('Permanently delete this recording? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger btn-sm" title="Delete recording"><x-icon name="trash" class="size-4" /><span class="sr-only">Delete</span></button>
                                            </form>
                                        @endunless
                                    </div>
                                @else
                                    <span class="block text-right text-xs text-slate-400">—</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($recordedSessions->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                {{ $recordedSessions->fragment('recording-history')->links() }}
            </div>
        @endif
    @endif
</div>

<script>
    window.__BROADCAST__ = {
        csrf: '{{ csrf_token() }}',
        channel: { id: {{ $channel->id }}, name: @json($channel->name) },
        broadcaster: @json(auth()->user()?->name),
        wsUrl: @json($wsUrl),
        listenUrl: @json($listenUrl),
        active: @json((bool) $channel->is_active),
        recordingStatus: @json($channel->liveSession?->recording?->status),
        urls: {
            goLive: '{{ route('admin.broadcast-channels.go-live', $channel) }}',
            stop: '{{ route('admin.broadcast-channels.stop', $channel) }}',
            status: '{{ route('admin.broadcast-channels.status', $channel) }}',
            participants: '{{ route('admin.broadcast-channels.participants', $channel) }}',
            grant: '{{ route('admin.broadcast-channels.grant-speak', $channel) }}',
            revoke: '{{ route('admin.broadcast-channels.revoke-speak', $channel) }}',
        },
    };
</script>
@vite('resources/js/broadcast-studio.js')
@endsection
