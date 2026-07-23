@extends('layouts.admin')

@section('title', 'Studio — '.$channel->name)

@section('content')
<x-page-header :title="'Studio: '.$channel->name" subtitle="Go on air from your microphone. Listeners tune in live on the public app.">
    <a href="{{ route('admin.broadcast-channels.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Channels</a>
</x-page-header>

<div id="broadcast-error" class="mb-5 hidden rounded-(--radius-app) border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300"></div>

@unless ($channel->is_active)
    <div class="mb-5 rounded-(--radius-app) border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
        This channel is currently <strong>disabled</strong>. Re-enable it in the channel settings before going live.
    </div>
@endunless

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    {{-- On-air control --}}
    <div class="card lg:col-span-2">
        <div class="card-body flex flex-col items-center gap-6 py-10">
            {{-- Status pill --}}
            <div id="status-pill" class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-sm font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                <span class="size-2.5 rounded-full bg-slate-400"></span>
                <span id="status-text">Off air</span>
            </div>

            {{-- Big mic button --}}
            <button id="go-live-btn" type="button"
                    class="group flex size-40 flex-col items-center justify-center gap-2 rounded-full bg-primary-600 text-white shadow-lg transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50">
                <x-icon name="microphone" class="size-12" />
                <span class="text-sm font-semibold">Go Live</span>
            </button>

            <button id="stop-btn" type="button"
                    class="hidden flex size-40 flex-col items-center justify-center gap-2 rounded-full bg-rose-600 text-white shadow-lg transition hover:bg-rose-700">
                <x-icon name="x" class="size-12" />
                <span class="text-sm font-semibold">Stop Broadcast</span>
            </button>

            {{-- Mic level meter --}}
            <div class="w-full max-w-xs">
                <p class="mb-1.5 text-center text-xs font-medium text-slate-400">Microphone level</p>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div id="mic-level" class="h-full w-0 rounded-full bg-emerald-500 transition-[width] duration-75"></div>
                </div>
            </div>

            <p id="mic-hint" class="text-center text-xs text-slate-400">
                Click <strong>Go Live</strong> and allow microphone access when your browser prompts you.
            </p>
        </div>
    </div>

    {{-- Live stats --}}
    <div class="card">
        <div class="card-header"><span class="text-sm font-semibold">Broadcast</span></div>
        <div class="card-body space-y-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Elapsed</p>
                <p id="elapsed" class="text-2xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">00:00</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Listeners now</p>
                <p id="listeners" class="text-2xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">0</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Peak listeners</p>
                <p id="peak-listeners" class="text-2xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">0</p>
            </div>
            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
                <p class="mb-1 text-xs uppercase tracking-wide text-slate-400">Listeners tune in at</p>
                <a href="{{ $listenUrl }}" target="_blank" rel="noreferrer"
                   class="inline-flex items-center gap-1.5 break-all text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                    <x-icon name="globe" class="size-4 shrink-0" /> {{ $listenUrl }}
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    window.__BROADCAST__ = {
        csrf: '{{ csrf_token() }}',
        channel: { id: {{ $channel->id }}, name: @json($channel->name) },
        wsUrl: @json($wsUrl),
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
