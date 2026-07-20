@extends('layouts.admin')

@section('title', 'Analytics')

@section('content')
<x-page-header title="Listening Analytics" subtitle="Aggregate playback insights across the archive (M19/M20 · FR-ANL-02)" />

{{-- Stat grid --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-stat-card label="Total Plays" :value="number_format($stats['total_plays'])" icon="play" color="primary" hint="all recorded daily aggregates" />
    <x-stat-card label="Unique Listeners" :value="number_format($stats['unique_listeners'])" icon="users" color="blue" />
    <x-stat-card label="Avg Completion" value="{{ $stats['avg_completion'] }}%" icon="chart-bar" color="green" />
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- Plays trend --}}
    <div class="card xl:col-span-2">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Plays — last 14 days</h3>
            <span class="badge-primary">Daily aggregates</span>
        </div>
        <div class="card-body">
            @if ($trend->isNotEmpty())
                <canvas id="trendChart" height="110"></canvas>
            @else
                <x-empty-state icon="chart-bar" title="No trend data yet" />
            @endif
        </div>
    </div>

    {{-- Platform breakdown --}}
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Plays by Platform</h3></div>
        <div class="card-body">
            @if ($platforms->isNotEmpty())
                <canvas id="platformChart" height="220"></canvas>
            @else
                <x-empty-state icon="computer" title="No platform data" />
            @endif
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    {{-- Top played --}}
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Most Played</h3></div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($topPlayed as $index => $asset)
                <li class="flex items-center gap-4 px-5 py-3">
                    <span class="w-5 text-sm font-semibold text-slate-400">{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.analytics.asset', $asset) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-primary-700 dark:text-slate-100 dark:hover:text-primary-300">
                            {{ $asset->title }}
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $asset->content_type)) }} · {{ $asset->archive_no }}</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ number_format($asset->play_count) }}</span>
                </li>
            @empty
                <li><x-empty-state icon="music" title="No plays recorded" /></li>
            @endforelse
        </ul>
    </div>

    {{-- Region breakdown --}}
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Plays by Region</h3></div>
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Region</th><th class="text-right">Plays</th><th class="text-right">Share</th></tr></thead>
                <tbody>
                    @php $regionTotal = max(1, $regions->sum('total')); @endphp
                    @forelse ($regions as $region)
                        <tr>
                            <td class="font-medium text-slate-700 dark:text-slate-200">{{ $region->region }}</td>
                            <td class="text-right tabular-nums">{{ number_format($region->total) }}</td>
                            <td class="text-right tabular-nums text-slate-500 dark:text-slate-400">{{ round($region->total / $regionTotal * 100) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty-state icon="globe" title="No region data" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--primary-600').trim();
    const accent = styles.getPropertyValue('--accent-500').trim();
    const gridColor = document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
    const textColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';

    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: @json($trend->pluck('stat_date')->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M'))),
                datasets: [
                    { label: 'Plays', data: @json($trend->pluck('plays')), borderColor: primary, backgroundColor: primary + '22', fill: true, tension: .4, pointRadius: 2 },
                    { label: 'Unique listeners', data: @json($trend->pluck('listeners')), borderColor: accent, backgroundColor: 'transparent', tension: .4, pointRadius: 2 },
                ],
            },
            options: {
                plugins: { legend: { labels: { color: textColor, boxWidth: 12 } } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor } },
                },
            },
        });
    }

    const platformEl = document.getElementById('platformChart');
    if (platformEl) {
        new Chart(platformEl, {
            type: 'doughnut',
            data: {
                labels: @json($platforms->pluck('platform')->map(fn ($p) => ucfirst((string) $p))),
                datasets: [{
                    data: @json($platforms->pluck('total')),
                    backgroundColor: [primary, accent, '#0ea5e9', '#8b5cf6', '#f43f5e', '#10b981', '#f59e0b', '#64748b'],
                    borderWidth: 0,
                }],
            },
            options: { plugins: { legend: { position: 'bottom', labels: { color: textColor, boxWidth: 12 } } }, cutout: '65%' },
        });
    }
});
</script>

{{-- Real-time dashboard visualization (Audio Visualization spec, item 10) --}}
<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- Live activity feed --}}
    <div class="card xl:col-span-2">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Recent Playback Activity</h3>
            <span class="badge-green"><span class="mr-1 inline-block size-2 animate-pulse rounded-full bg-emerald-500"></span>{{ number_format($activeListeners) }} active (24h)</span>
        </div>
        <ul class="max-h-72 divide-y divide-slate-100 overflow-y-auto scrollbar-slim dark:divide-slate-800">
            @forelse ($recentEvents as $event)
                <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                    <span class="badge-slate">{{ ucfirst($event->event_type) }}</span>
                    <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200">{{ $event->audioAsset?->title ?? 'Unknown' }}</span>
                    <span class="text-xs text-slate-400">{{ ucfirst($event->platform) }}{{ $event->region ? ' · '.$event->region : '' }}</span>
                    <span class="text-xs text-slate-400">{{ $event->created_at?->diffForHumans(null, true) }} ago</span>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-slate-400">No recent activity.</li>
            @endforelse
        </ul>
    </div>

    {{-- Device breakdown --}}
    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">By Device</h3></div>
        <div class="card-body space-y-3">
            @php $devTotal = max(1, $devices->sum('total')); @endphp
            @foreach ($devices as $device)
                <div>
                    <div class="flex justify-between text-sm text-slate-600 dark:text-slate-300">
                        <span>{{ ucfirst($device->device) }}</span><span class="tabular-nums">{{ number_format($device->total) }}</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-slate-200 dark:bg-slate-700">
                        <div class="h-2 rounded-full bg-primary-600" style="width: {{ round($device->total / $devTotal * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Plays by time of day --}}
<div class="card mt-6">
    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Playback by Time of Day</h3><span class="text-xs text-slate-400">24-hour heat strip</span></div>
    <div class="card-body">
        @php $hourMax = max(1, $byHour->max()); @endphp
        <div class="flex items-end gap-1">
            @foreach ($byHour as $h => $count)
                <div class="flex-1 text-center">
                    <div class="mx-auto w-full rounded-t-sm bg-primary-600" style="height: {{ max(3, (int) ($count / $hourMax * 64)) }}px; opacity: {{ 0.35 + ($count / $hourMax) * 0.65 }}" title="{{ $count }} plays"></div>
                    @if ($h % 3 === 0)<span class="mt-1 block text-[10px] text-slate-400">{{ sprintf('%02d', $h) }}</span>@endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
