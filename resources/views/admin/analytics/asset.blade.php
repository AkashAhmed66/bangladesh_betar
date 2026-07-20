@extends('layouts.admin')

@section('title', 'Analytics — '.$asset->title)

@section('content')
<x-page-header :title="$asset->title" :subtitle="$asset->archive_no.' · per-asset listening analytics (M19 · FR-ANL-03)'">
    <a href="{{ route('admin.analytics.index') }}" class="btn-secondary btn-sm"><x-icon name="chevron-left" class="size-4" /> All analytics</a>
    @can('assets.view')
        <a href="{{ route('admin.assets.show', $asset) }}" class="btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> Asset</a>
    @endcan
</x-page-header>

{{-- Stat grid --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-stat-card label="Lifetime Plays" :value="number_format($asset->play_count)" icon="play" color="primary" />
    <x-stat-card label="Avg Completion (14d)" value="{{ round($stats->avg('completion_rate')) }}%" icon="chart-bar" color="green" />
    <x-stat-card label="Avg Skip Rate (14d)" value="{{ round($stats->avg('skip_rate')) }}%" icon="scissors" color="amber" />
    <x-stat-card label="Avg Replay Rate (14d)" value="{{ round($stats->avg('replay_rate')) }}%" icon="arrow-path" color="purple" />
</div>

{{-- Heatmap --}}
<div class="card mt-6">
    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Listening Heat Map — most replayed sections (FR-ANL-03)</h3></div>
    <div class="card-body">
        @if (! empty($heatmap))
            @php $max = max(1, ...($heatmap ?: [1])); @endphp
            <div class="flex h-24 items-end gap-0.5">
                @foreach ($heatmap as $bucket)
                    <div class="flex-1 rounded-t-sm bg-primary-600/80 dark:bg-primary-500/80" style="height: {{ (int) ($bucket / $max * 100) }}%"
                         title="{{ $bucket }} listens"></div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs text-slate-400">
                <span>00:00</span>
                <span>{{ gmdate('i:s', (int) ($asset->duration_seconds / 2)) }}</span>
                <span>{{ gmdate('i:s', $asset->duration_seconds) }}</span>
            </div>
        @else
            <x-empty-state icon="chart-bar" title="No heat-map data yet" />
        @endif
    </div>
</div>

{{-- Trend chart --}}
<div class="card mt-6">
    <div class="card-header">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Plays — last 14 days</h3>
        <span class="badge-primary">{{ number_format($stats->sum('plays')) }} plays</span>
    </div>
    <div class="card-body">
        @if ($stats->isNotEmpty())
            <canvas id="assetTrend" height="90"></canvas>
        @else
            <x-empty-state icon="chart-bar" title="No trend data yet" />
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('assetTrend');
    if (!el) return;

    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--primary-600').trim();
    const accent = styles.getPropertyValue('--accent-500').trim();
    const gridColor = document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.12)';
    const textColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';

    new Chart(el, {
        type: 'line',
        data: {
            labels: @json($stats->pluck('stat_date')->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M'))),
            datasets: [
                { label: 'Plays', data: @json($stats->pluck('plays')), borderColor: primary, backgroundColor: primary + '22', fill: true, tension: .4, pointRadius: 2 },
                { label: 'Unique listeners', data: @json($stats->pluck('unique_listeners')), borderColor: accent, backgroundColor: 'transparent', tension: .4, pointRadius: 2 },
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
});
</script>

{{-- Drop-off / retention curve derived from the second-by-second heat-map --}}
@if (! empty($heatmap))
    @php
        $peak = max(1, ...$heatmap);
        $retention = array_map(fn ($v) => round($v / $peak * 100), $heatmap);
        // Biggest single drop between adjacent buckets = the main drop-off point.
        $maxDrop = 0; $dropAt = 0;
        for ($i = 1; $i < count($retention); $i++) {
            $delta = $retention[$i - 1] - $retention[$i];
            if ($delta > $maxDrop) { $maxDrop = $delta; $dropAt = $i; }
        }
        $dropPct = round($dropAt / max(1, count($retention)) * 100);
    @endphp
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Listener Retention & Drop-off</h3>
            @if ($maxDrop > 5)<span class="badge-amber">Main drop-off at {{ $dropPct }}% of the track</span>@endif
        </div>
        <div class="card-body">
            <div class="relative flex h-32 items-end gap-px">
                @foreach ($retention as $i => $r)
                    <div class="flex-1 rounded-t-sm {{ $i === $dropAt ? 'bg-rose-500' : 'bg-primary-500/80' }}"
                         style="height: {{ max(2, $r) }}%" title="{{ $r }}% retained"></div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-xs text-slate-400">
                <span>Start (100%)</span><span>Mid</span><span>End</span>
            </div>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                Retention starts at 100% and shows the share of listeners still playing at each point.
                The red bar marks the largest single drop-off — a candidate for editorial review.
            </p>
        </div>
    </div>
@endif
@endsection
