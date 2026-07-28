@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

{{-- Welcome hero — permanent, non-dismissible --}}
<div class="relative mb-6 overflow-hidden rounded-(--radius-app) bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-6 py-7 text-white shadow-lg sm:px-8">
    {{-- ambient glows --}}
    <div class="pointer-events-none absolute -right-16 -top-16 size-56 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-1/3 size-52 rounded-full bg-accent-500/25 blur-3xl"></div>
    {{-- faint equaliser motif --}}
    <div class="pointer-events-none absolute bottom-0 right-6 flex items-end gap-1 opacity-15">
        @foreach ([18, 30, 22, 40, 26, 34, 20] as $h)
            <span class="w-1.5 rounded-t bg-white" style="height: {{ $h }}px"></span>
        @endforeach
    </div>

    <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-inset ring-white/25 backdrop-blur">
                <x-icon name="radio" class="size-7" />
            </span>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/75">{{ $greeting }}, {{ auth()->user()->name }}</p>
                <h1 class="mt-1 text-2xl font-bold leading-tight sm:text-[28px]">Welcome to Bangladesh Betar Audio Archive</h1>
                <p class="mt-1.5 text-sm text-white/80">
                    The sound heritage of the nation
                    <span class="mx-1 text-white/40">·</span>{{ auth()->user()->getRoleNames()->first() ?? 'Team member' }}
                </p>
            </div>
        </div>

        {{-- live clock + date --}}
        <div class="shrink-0 text-left sm:text-right"
             x-data="{ t: '', init() { const f = () => this.t = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); f(); setInterval(f, 1000); } }">
            <p class="font-mono text-3xl font-bold tabular-nums tracking-tight" x-text="t">--:--:--</p>
            <p class="mt-0.5 text-xs font-medium text-white/70">{{ now()->format('l, j F Y') }}</p>
        </div>
    </div>
</div>

{{-- Performance overview --}}
<div class="mb-3 flex items-end justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Performance overview</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Key business metrics, updated live</p>
    </div>
    <span class="hidden items-center gap-1.5 text-xs font-medium text-slate-400 sm:inline-flex dark:text-slate-500">
        <span class="relative flex size-2">
            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
        </span>
        Live
    </span>
</div>

{{-- Headline KPI band — business metrics with growth deltas --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($headline as $kpi)
        <x-kpi-card
            :label="$kpi['label']"
            :value="$kpi['value']"
            :icon="$kpi['icon']"
            :color="$kpi['color']"
            :delta="$kpi['delta']"
            :delta-label="$kpi['deltaLabel']"
            :series="$kpi['series']" />
    @endforeach
</div>

{{-- Operational widgets — appear per role/permission --}}
@php
    $hasOps = $stats['my_approvals'] !== null || $stats['pending_approvals'] !== null
        || $stats['rights_expiring'] !== null || $stats['digitization_pending'] !== null
        || $stats['moderation_queue'] !== null;
@endphp
@if ($hasOps)
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($stats['my_approvals'] !== null)
            <x-stat-card label="Awaiting My Approval" :value="$stats['my_approvals']" hint="Action needed from you" icon="clipboard-check" color="accent" />
        @elseif ($stats['pending_approvals'] !== null)
            <x-stat-card label="Pending Approvals" :value="$stats['pending_approvals']" hint="Across all stages" icon="clipboard-check" color="accent" />
        @endif
        @if ($stats['rights_expiring'] !== null)
            <x-stat-card label="Rights Expiring (90 days)" :value="$stats['rights_expiring']" hint="Renew to keep publishing" icon="scale" color="red" />
        @endif
        @if ($stats['digitization_pending'] !== null)
            <x-stat-card label="Digitization In Pipeline" :value="$stats['digitization_pending']" hint="Awaiting QC / archive" icon="disc" color="purple" />
        @endif
        @if ($stats['moderation_queue'] !== null)
            <x-stat-card label="Comments Awaiting Moderation" :value="$stats['moderation_queue']" hint="Public feedback queue" icon="chat" color="accent" />
        @endif
    </div>
@endif

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Listening trend chart --}}
    <div class="card xl:col-span-2">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Listening engagement</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Plays &amp; unique listeners · last 14 days</p>
            </div>
            <span class="badge-primary">All published content</span>
        </div>
        <div class="card-body">
            <div class="relative h-64 sm:h-72">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Content mix --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Archive composition</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Recordings by content type</p>
            </div>
        </div>
        <div class="card-body">
            <canvas id="mixChart" height="220"></canvas>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">

    {{-- Top played --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Most Played</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Your best-performing recordings</p>
            </div>
            @can('assets.view')
                <a href="{{ route('admin.assets.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">View all →</a>
            @endcan
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($topPlayed as $index => $asset)
                @php
                    $rankClass = match ($index) {
                        0 => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                        1 => 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                        2 => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300',
                        default => 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500',
                    };
                @endphp
                <li class="flex items-center gap-3.5 px-5 py-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold {{ $rankClass }}">{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.assets.show', $asset) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100 dark:hover:text-primary-300">
                            {{ $asset->title }}
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($asset->content_type) }} · {{ $asset->archive_no }}</p>
                    </div>
                    <div class="hidden w-24 sm:block">
                        <x-waveform :peaks="array_slice($asset->waveform_peaks ?? [], 0, 40)" :height="24" />
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="block text-sm font-bold tabular-nums text-slate-800 dark:text-slate-100">{{ number_format($asset->play_count) }}</span>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">plays</span>
                    </div>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-400 dark:text-slate-500">No play data yet.</li>
            @endforelse
        </ul>
    </div>

    {{-- My approval queue OR recent uploads --}}
    @if ($myQueue->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">My Approval Queue</h3>
                <a href="{{ route('admin.approvals.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">View all →</a>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($myQueue as $approval)
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <x-icon name="clipboard-check" class="size-5 shrink-0 text-accent-600" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.approvals.show', $approval) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100">
                                {{ $approval->approvable?->title ?? class_basename((string) $approval->approvable_type).' #'.$approval->approvable_id }}
                            </a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Stage: {{ $approval->currentStage?->name }} · submitted {{ $approval->submitted_at?->diffForHumans() }}
                            </p>
                        </div>
                        <x-status-badge :status="$approval->status" />
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Recent Uploads</h3>
                @can('assets.view')
                    <a href="{{ route('admin.assets.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">All assets →</a>
                @endcan
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($recentUploads as $asset)
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <x-icon name="upload" class="size-5 shrink-0 text-primary-600" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.assets.show', $asset) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100">{{ $asset->title }}</a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $asset->uploader?->name ?? 'System' }} · {{ $asset->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <x-status-badge :status="$asset->status" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--primary-600').trim();
    const accent = styles.getPropertyValue('--accent-500').trim();
    const dark = document.documentElement.classList.contains('dark');
    const gridColor = dark ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.10)';
    const textColor = dark ? '#94a3b8' : '#64748b';

    const tooltip = {
        backgroundColor: dark ? '#020617' : '#0f172a',
        titleColor: '#e2e8f0',
        bodyColor: '#cbd5e1',
        padding: 10,
        cornerRadius: 8,
        boxPadding: 4,
        usePointStyle: true,
    };
    const legend = {
        labels: { color: textColor, boxWidth: 8, usePointStyle: true, pointStyle: 'circle', padding: 16 },
    };

    const trendEl = document.getElementById('trendChart');
    const fill = trendEl.getContext('2d').createLinearGradient(0, 0, 0, 180);
    fill.addColorStop(0, primary + '3d');
    fill.addColorStop(1, primary + '00');

    new Chart(trendEl, {
        type: 'line',
        data: {
            labels: @json($trend->pluck('stat_date')->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M'))),
            datasets: [
                { label: 'Plays', data: @json($trend->pluck('plays')), borderColor: primary, backgroundColor: fill, fill: true, tension: .4, pointRadius: 0, pointHoverRadius: 5, borderWidth: 2.5 },
                { label: 'Unique listeners', data: @json($trend->pluck('listeners')), borderColor: accent, backgroundColor: 'transparent', borderDash: [5, 4], fill: false, tension: .4, pointRadius: 0, pointHoverRadius: 5, borderWidth: 2 },
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend, tooltip: { ...tooltip, mode: 'index', intersect: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: textColor, maxRotation: 0, autoSkipPadding: 12 }, border: { display: false } },
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0, maxTicksLimit: 5 }, border: { display: false } },
            },
        },
    });

    new Chart(document.getElementById('mixChart'), {
        type: 'doughnut',
        data: {
            labels: @json($contentByType->pluck('content_type')->map(fn ($t) => ucfirst(str_replace('_', ' ', $t)))),
            datasets: [{
                data: @json($contentByType->pluck('total')),
                backgroundColor: [primary, accent, '#0ea5e9', '#8b5cf6', '#f43f5e', '#10b981', '#f59e0b', '#64748b', '#14b8a6', '#a855f7'],
                borderWidth: 2,
                borderColor: dark ? '#0f172a' : '#ffffff',
                hoverOffset: 6,
            }],
        },
        options: {
            cutout: '68%',
            plugins: { legend: { position: 'bottom', ...legend }, tooltip },
        },
    });
});
</script>
@endsection
