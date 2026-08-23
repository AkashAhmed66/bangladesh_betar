@extends('layouts.admin')

@section('title', 'Watch Studio — '.$channel->name)

@section('content')
<x-page-header :title="'Watch Studio: '.$channel->name" subtitle="Preview your camera and microphone, then send a live video feed to the public Watch portal.">
    <a href="{{ route('admin.watch-live-channels.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Channels</a>
</x-page-header>

<div id="watch-live-error" class="mb-5 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" role="alert"></div>

@unless($channel->is_active)
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">This channel is disabled. Enable it before going live.</div>
@endunless

<div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-5">
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-3 text-white">
                <div class="flex items-center gap-3"><span id="watch-status-dot" class="size-2.5 rounded-full bg-slate-500"></span><span id="watch-status" class="text-xs font-black uppercase tracking-[0.18em] text-white/70">Preview offline</span></div>
                <div class="flex items-center gap-4 text-xs"><span><strong id="watch-viewers" class="tabular-nums text-white">0</strong> viewers</span><span id="watch-elapsed" class="font-bold tabular-nums text-white">00:00</span></div>
            </div>
            <div class="relative aspect-video bg-black">
                <video id="watch-preview" autoplay muted playsinline class="size-full object-cover"></video>
                <div id="watch-preview-empty" class="absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_center,#17303a,#080b10_65%)] text-center text-white/65">
                    <div><span class="mx-auto grid size-20 place-items-center rounded-full border border-white/15 bg-white/5"><x-icon name="play" class="size-9" /></span><p class="mt-4 text-sm font-semibold">Prepare your camera to preview the live composition</p></div>
                </div>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-between bg-gradient-to-t from-black/80 to-transparent p-5 text-white">
                    <div><p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-300">Bangladesh Betar · Watch Live</p><p class="mt-1 font-display text-xl font-bold">{{ $channel->name }}</p></div>
                    <span id="watch-live-badge" class="hidden items-center gap-2 rounded-full bg-rose-600 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider"><span class="size-1.5 animate-pulse rounded-full bg-white"></span> Live</span>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 border-t border-white/10 p-4">
                <button id="watch-prepare" type="button" class="btn-secondary"><x-icon name="eye" class="size-4" /> Prepare Camera</button>
                <button id="watch-toggle-mic" type="button" disabled class="btn-secondary"><x-icon name="microphone" class="size-4" /><span>Mute</span></button>
                <button id="watch-toggle-camera" type="button" disabled class="btn-secondary"><x-icon name="play" class="size-4" /><span>Camera off</span></button>
                <button id="watch-go-live" type="button" disabled class="btn-primary"><span class="size-2 rounded-full bg-white"></span> Go Live</button>
                <button id="watch-stop" type="button" class="btn-danger hidden"><x-icon name="x" class="size-4" /> End Broadcast</button>
            </div>
        </section>

        <div class="card">
            <div class="card-header"><span class="text-sm font-semibold">Broadcast details</span></div>
            <div class="card-body"><label for="watch-topic" class="mb-1.5 block text-xs font-semibold text-slate-500 dark:text-slate-400">Live programme title</label><input id="watch-topic" maxlength="120" class="form-input w-full" placeholder="e.g. Evening news and national weather"></div>
        </div>
    </div>

    <aside class="space-y-5">
        <div class="card">
            <div class="card-header"><span class="text-sm font-semibold">Camera & microphone</span></div>
            <div class="card-body space-y-4">
                <div><label for="watch-camera" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Camera</label><select id="watch-camera" class="form-input w-full"><option value="">Default camera</option></select></div>
                <div><label for="watch-microphone" class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">Microphone</label><select id="watch-microphone" class="form-input w-full"><option value="">Default microphone</option></select></div>
                <p class="text-[11px] leading-relaxed text-slate-400">Use headphones when monitoring nearby devices. Camera and microphone access requires localhost or HTTPS.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="text-sm font-semibold">Public watch link</span></div>
            <div class="card-body space-y-3">
                <input id="watch-public-url" readonly value="{{ $watchUrl }}" class="form-input w-full text-xs" onclick="this.select()">
                <div class="flex gap-2"><button id="watch-copy-link" type="button" class="btn-secondary btn-sm flex-1"><x-icon name="clipboard-check" class="size-4" /> Copy</button><a href="{{ $watchUrl }}" target="_blank" rel="noreferrer" class="btn-secondary btn-sm flex-1"><x-icon name="globe" class="size-4" /> Open</a></div>
            </div>
        </div>

        <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 text-cyan-950 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
            <p class="text-xs font-black uppercase tracking-[0.16em]">Live checklist</p>
            <ul class="mt-3 space-y-2 text-xs leading-relaxed"><li>✓ Camera framing and lighting checked</li><li>✓ Microphone level and room noise checked</li><li>✓ Programme title entered</li><li>✓ Public link ready to share</li></ul>
        </div>
    </aside>
</div>

<script>
window.__WATCH_LIVE__ = {
    csrf: @json(csrf_token()),
    active: @json((bool) $channel->is_active),
    wsUrl: @json($wsUrl),
    publicUrl: @json($watchUrl),
    urls: {
        goLive: @json(route('admin.watch-live-channels.go-live', $channel)),
        stop: @json(route('admin.watch-live-channels.stop', $channel)),
        status: @json(route('admin.watch-live-channels.status', $channel)),
    },
};
</script>
@vite('resources/js/watch-live-studio.js')
@endsection
