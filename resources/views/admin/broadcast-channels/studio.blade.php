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
    <div class="flex items-center gap-4 text-xs">
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
            <div class="card-header flex items-center justify-between">
                <div id="status-pill" class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <span class="size-2.5 rounded-full bg-slate-400"></span>
                    <span id="status-text">Off air</span>
                </div>
                <div class="text-right">
                    <span id="elapsed" class="text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">00:00</span>
                    <span class="ml-1 text-[10px] uppercase tracking-wide text-slate-400">on air</span>
                </div>
            </div>

            <div class="card-body flex flex-col items-center gap-6 py-8">
                {{-- On-air lamp + big control --}}
                <div class="relative flex size-44 items-center justify-center">
                    <div id="lamp-ring" class="absolute inset-0 rounded-full border-4 border-slate-200 transition-colors dark:border-slate-800"></div>
                    <button id="go-live-btn" type="button"
                            class="group z-10 flex size-36 flex-col items-center justify-center gap-2 rounded-full bg-primary-600 text-white shadow-lg transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <x-icon name="microphone" class="size-11" />
                        <span class="text-sm font-semibold">Go Live</span>
                    </button>
                    <button id="stop-btn" type="button"
                            class="z-10 hidden size-36 flex-col items-center justify-center gap-2 rounded-full bg-rose-600 text-white shadow-lg transition hover:bg-rose-700">
                        <x-icon name="x" class="size-11" />
                        <span class="text-sm font-semibold">Stop</span>
                    </button>
                </div>

                {{-- Transport controls (active while live) --}}
                <div class="flex items-center gap-2">
                    <button id="mute-btn" type="button" disabled
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                        <x-icon name="pause" class="size-4" /> <span id="mute-label">Mute</span>
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

        {{-- Recent broadcasts --}}
        @if ($recentSessions->isNotEmpty())
            <div class="card">
                <div class="card-header"><span class="text-sm font-semibold">Recent broadcasts</span></div>
                <div class="card-body space-y-3">
                    @foreach ($recentSessions as $s)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $s->title ?: 'Untitled session' }}</p>
                                <p class="truncate text-slate-400">{{ $s->broadcaster?->name ?? '—' }}</p>
                            </div>
                            <div class="shrink-0 text-right text-slate-400">
                                <p class="tabular-nums">{{ $s->started_at?->diffForHumans(short: true) }}</p>
                                <p class="tabular-nums">peak {{ $s->peak_listeners }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    window.__BROADCAST__ = {
        csrf: '{{ csrf_token() }}',
        channel: { id: {{ $channel->id }}, name: @json($channel->name) },
        broadcaster: @json(auth()->user()?->name),
        wsUrl: @json($wsUrl),
        listenUrl: @json($listenUrl),
        active: @json((bool) $channel->is_active),
        urls: {
            goLive: '{{ route('admin.broadcast-channels.go-live', $channel) }}',
            stop: '{{ route('admin.broadcast-channels.stop', $channel) }}',
            status: '{{ route('admin.broadcast-channels.status', $channel) }}',
        },
    };
</script>
@vite('resources/js/broadcast-studio.js')
@endsection
