<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-default-mode="{{ \App\Support\Theme::mode() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ \App\Support\Theme::brand('name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600&display=swap" rel="stylesheet">
    <style>:root { {!! \App\Support\Theme::cssVariables() !!} }</style>
    <script>
        (() => {
            const mode = localStorage.getItem('color-mode') || document.documentElement.dataset.defaultMode || 'system';
            const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    {{-- Encrypted-HLS playback (download protection): players carry
         data-hls / data-fallback and call window.betarHls($el) on init. --}}
    <script src="{{ asset('vendor/hls.min.js') }}" defer></script>
    <script>
        window.betarHls = function (el) {
            var src = el.dataset.hls || '', fallback = el.dataset.fallback || '';
            var start = function () {
                if (src && window.Hls && Hls.isSupported()) {
                    var h = new Hls({ enableWorker: false });
                    h.on(Hls.Events.ERROR, function (_, data) {
                        if (data.fatal) { h.destroy(); if (fallback) el.src = fallback; }
                    });
                    h.loadSource(src);
                    h.attachMedia(el);
                } else if (src && el.canPlayType('application/vnd.apple.mpegurl')) {
                    el.src = src;
                } else if (fallback) {
                    el.src = fallback;
                }
            };
            if (window.Hls || ! src) { start(); } else { window.addEventListener('DOMContentLoaded', start); }
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full" x-data :class="$store.ui.sidebarCollapsed ? 'sidebar-collapsed' : ''">
<div class="flex h-full">

    {{-- Mobile overlay --}}
    <div x-show="$store.ui.sidebarOpenMobile" x-transition.opacity @click="$store.ui.sidebarOpenMobile = false"
         class="fixed inset-0 z-30 bg-slate-950/60 lg:hidden" x-cloak></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex flex-col bg-slate-900 transition-all duration-200 dark:border-r dark:border-slate-800
                  -translate-x-full lg:translate-x-0"
           :class="[$store.ui.sidebarCollapsed ? 'lg:w-[68px]' : 'lg:w-64', $store.ui.sidebarOpenMobile ? 'translate-x-0 w-64' : '']">

        {{-- Brand --}}
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-4">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-600/90">
                <x-icon name="radio" class="size-5 text-white" />
            </div>
            <div class="brand-text min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ \App\Support\Theme::brand('name') }}</p>
                <p class="truncate text-[11px] text-slate-400">Admin Portal</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="scrollbar-slim flex-1 overflow-y-auto px-3 pb-6">
            @include('partials.sidebar')
        </nav>

        {{-- Collapse toggle (desktop) --}}
        <button @click="$store.ui.toggleSidebar()"
                class="hidden h-11 shrink-0 items-center justify-center gap-2 border-t border-white/10 text-xs font-medium text-slate-400 hover:bg-white/5 hover:text-white lg:flex">
            <x-icon name="chevron-left" class="size-4 transition-transform" ::class="$store.ui.sidebarCollapsed ? 'rotate-180' : ''" />
            <span class="nav-label">Collapse</span>
        </button>
    </aside>

    {{-- Main column --}}
    <div class="flex min-w-0 flex-1 flex-col transition-all duration-200"
         :class="$store.ui.sidebarCollapsed ? 'lg:pl-[68px]' : 'lg:pl-64'">

        @include('partials.topbar')

        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div x-data="{ open: true }" x-show="open" x-transition
                     class="mb-5 flex items-start justify-between gap-3 rounded-(--radius-app) border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <div class="flex items-start gap-2.5">
                        <x-icon name="check-badge" class="mt-0.5 size-4.5 shrink-0" />
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="open = false"><x-icon name="x" class="size-4" /></button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ open: true }" x-show="open" x-transition
                     class="mb-5 flex items-start justify-between gap-3 rounded-(--radius-app) border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                    <div class="flex items-start gap-2.5">
                        <x-icon name="exclamation" class="mt-0.5 size-4.5 shrink-0" />
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="open = false"><x-icon name="x" class="size-4" /></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="border-t border-slate-200 px-6 py-4 text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">
            {{ \App\Support\Theme::brand('full_name') }} · v1.1 · © {{ date('Y') }} Bangladesh Betar
        </footer>
    </div>
</div>

@include('partials.notification-toaster')
</body>
</html>
