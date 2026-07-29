@extends('layouts.admin')

@section('title', 'Analytics · '.$asset->title)

@php
    $k = $a['kpis'];
    $tierColors = [
        'peak' => '#ef4444', 'hot' => '#f97316', 'medium' => '#eab308',
        'low' => '#22c55e', 'cold' => '#94a3b8',
    ];
    $ranges = ['today' => 'Today', 'yesterday' => 'Yesterday', '7d' => '7 days',
        '30d' => '30 days', 'month' => 'This month', 'year' => 'This year', 'all' => 'All time'];
    $fmt = fn ($s) => gmdate($asset->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', (int) $s);
@endphp

@section('content')
{{-- Header ------------------------------------------------------------------}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <a href="{{ route('admin.assets.show', $asset) }}" class="mb-1 inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-primary-600 dark:text-slate-400">
            <x-icon name="chevron-left" class="size-3.5" /> Back to asset
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">Listener Analytics</h2>
            <span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $asset->content_type)) }}</span>
        </div>
        <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">
            {{ $asset->title }} · {{ $asset->archive_no }} · {{ $fmt($asset->duration_seconds) }} runtime
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.assets.analytics', [$asset, 'range' => $a['range'], 'export' => 'csv']) }}"
           class="btn-secondary"><x-icon name="download" class="size-4" /> Export CSV</a>
        <a href="{{ route('admin.assets.studio', $asset) }}" class="btn-accent"><x-icon name="wave" class="size-4" /> Open Studio</a>
    </div>
</div>

{{-- Date-range filter -------------------------------------------------------}}
<div class="mb-6 flex flex-wrap items-center gap-1.5">
    @foreach ($ranges as $key => $label)
        <a href="{{ route('admin.assets.analytics', [$asset, 'range' => $key]) }}"
           class="rounded-lg px-3 py-1.5 text-xs font-medium transition
                  {{ $a['range'] === $key
                     ? 'bg-primary-600 text-white shadow-sm'
                     : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
            {{ $label }}
        </a>
    @endforeach
    <span class="ml-1 text-xs text-slate-400">Showing {{ $a['rangeLabel'] }}</span>
</div>

@unless ($a['hasData'])
    <div class="card">
        <div class="card-body flex flex-col items-center gap-3 py-16 text-center">
            <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                <x-icon name="chart-bar" class="size-7 text-slate-400" />
            </div>
            <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-200">No listening data yet</h3>
            <p class="max-w-md text-sm text-slate-500 dark:text-slate-400">
                Once listeners play this recording in the public app, plays, retention and the
                engagement heat map will appear here. Try widening the date range to <strong>All time</strong>.
            </p>
        </div>
    </div>
@else

{{-- KPI band ----------------------------------------------------------------}}
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
    @php
        $kpiCards = [
            ['Total plays', number_format($k['total_plays']), 'play', 'primary', number_format($k['replays']).' replays'],
            ['Unique listeners', number_format($k['unique_listeners']), 'users', 'blue', 'distinct sessions'],
            ['Avg listen time', $fmt($k['avg_listen_seconds']), 'clock', 'purple', $k['avg_completion_pct'].'% of runtime'],
            ['Completion rate', $k['completion_rate'].'%', 'check-badge', 'green', number_format($k['completed_plays']).' finished'],
            ['Replay rate', $k['replay_rate'].'%', 'arrow-path', 'accent', 'section re-listens'],
            ['Skip rate', $k['skip_rate'].'%', 'chevron-right', 'amber', number_format($k['skips']).' skips'],
        ];
        $kpiTint = [
            'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
            'blue' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
            'purple' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
            'green' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
            'accent' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            'amber' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
        ];
    @endphp
    @foreach ($kpiCards as [$label, $value, $icon, $color, $sub])
        <div class="card p-4">
            <div class="mb-2 flex size-9 items-center justify-center rounded-lg {{ $kpiTint[$color] }}">
                <x-icon name="{{ $icon }}" class="size-4.5" />
            </div>
            <p class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100">{{ $value }}</p>
            <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $sub }}</p>
        </div>
    @endforeach
</div>

{{-- Engagement heat map (hero) ---------------------------------------------}}
<div class="card mb-6">
    <div class="card-header">
        <div>
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Listener Engagement</h3>
            <p class="text-xs text-slate-400">total listeners active across the runtime · coloured blocks below the line show engagement</p>
        </div>
        {{-- Heat-map key (top-right): explains the coloured blocks under the line --}}
        <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
            @foreach (['cold' => 'Cold', 'low' => 'Low', 'medium' => 'Medium', 'hot' => 'High', 'peak' => 'Peak'] as $t => $lbl)
                <span class="flex items-center gap-1">
                    <span class="size-2.5 rounded-sm" style="background: {{ $tierColors[$t] }}"></span>{{ $lbl }}
                </span>
            @endforeach
        </div>
    </div>
    <div class="card-body"><div class="h-72"><canvas id="heatChart"></canvas></div></div>
</div>

{{-- Retention + trend -------------------------------------------------------}}
<div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Audience Retention</h3>
            <span class="text-xs text-slate-400">% still listening across the timeline</span>
        </div>
        <div class="card-body"><div class="h-64"><canvas id="retentionChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Daily Listening Trend</h3>
            <span class="text-xs text-slate-400">plays per day</span>
        </div>
        <div class="card-body"><div class="h-64"><canvas id="trendChart"></canvas></div></div>
    </div>
</div>

@endunless

@if ($a['hasData'])
@php
    // Precompute JS payloads as plain variables — passing a closure with array
    // literals directly into @json(...) confuses Blade's directive parser.
    $heatChartJs = collect($a['heat'])->map(fn ($b) => [
        'label' => $fmt($b['start']),
        'start' => $b['start'], 'end' => $b['end'],
        'plays' => $b['plays'], 'replays' => $b['replays'], 'retention' => $b['retention'],
        'tier' => $b['tier'], 'color' => $tierColors[$b['tier']],
    ])->values();
@endphp
<script>
const HEATC = @json($heatChartJs);

function fmtTime(s) {
    if (!isFinite(s)) return '0:00';
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = Math.floor(s % 60);
    const mm = h ? String(m).padStart(2, '0') : m;
    return (h ? h + ':' : '') + mm + ':' + String(sec).padStart(2, '0');
}

document.addEventListener('DOMContentLoaded', () => {
    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--primary-600').trim() || '#0d9488';
    const dark = document.documentElement.classList.contains('dark');
    const gridColor = dark ? 'rgba(148,163,184,.12)' : 'rgba(100,116,139,.10)';
    const textColor = dark ? '#94a3b8' : '#64748b';
    const PALETTE = ['#14b8a6', '#6366f1', '#f59e0b', '#0ea5e9', '#10b981', '#8b5cf6', '#f43f5e', '#64748b'];
    const tip = {
        backgroundColor: dark ? '#020617' : '#0f172a', titleColor: '#e2e8f0',
        bodyColor: '#cbd5e1', padding: 10, cornerRadius: 8, displayColors: false,
    };
    const noLegend = { legend: { display: false } };

    // Listener engagement — total listeners active at each point of the runtime,
    // with a heat-map strip of tier-coloured blocks along the bottom of the plot.
    const heatEl = document.getElementById('heatChart');
    if (heatEl) {
        const hctx = heatEl.getContext('2d');
        const hgrad = hctx.createLinearGradient(0, 0, 0, 288);
        hgrad.addColorStop(0, 'rgba(20,184,166,.35)');
        hgrad.addColorStop(1, 'rgba(20,184,166,0)');

        const heatBlocks = {
            id: 'heatBlocks',
            afterDatasetsDraw(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea || !HEATC.length) return;
                const n = HEATC.length;
                const h = 10;
                const y = chartArea.bottom + 8; // strip sits below the plot's bottom axis line
                const w = (chartArea.right - chartArea.left) / n;
                ctx.save();
                for (let i = 0; i < n; i++) {
                    ctx.fillStyle = HEATC[i].color;
                    ctx.fillRect(chartArea.left + i * w, y, w + 0.6, h);
                }
                ctx.restore();
            },
        };

        new Chart(heatEl, {
            type: 'line',
            plugins: [heatBlocks],
            data: {
                labels: HEATC.map((b) => b.label),
                datasets: [{ data: HEATC.map((b) => b.plays), borderColor: primary, backgroundColor: hgrad,
                    fill: true, borderWidth: 2, tension: .35, pointRadius: 0 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { ...tip, callbacks: {
                    title: (t) => { const b = HEATC[t[0].dataIndex]; return b ? fmtTime(b.start) + '–' + fmtTime(b.end) : ''; },
                    label: (c) => {
                        const b = HEATC[c.dataIndex];
                        const tier = b ? b.tier.charAt(0).toUpperCase() + b.tier.slice(1) : '';
                        return [c.parsed.y.toLocaleString() + ' listeners', 'Engagement: ' + tier];
                    },
                } } },
                scales: {
                    x: { grid: { display: false },
                        title: { display: true, text: 'Duration (mm:ss)', color: textColor, font: { size: 11 } },
                        ticks: { color: textColor, maxRotation: 0, autoSkip: true, maxTicksLimit: 10, padding: 24 } },
                    y: { beginAtZero: true, grid: { color: gridColor },
                        title: { display: true, text: 'Total listeners', color: textColor, font: { size: 11 } },
                        ticks: { color: textColor, precision: 0 } },
                },
            },
        });
    }

    // Audience retention (area line)
    const retention = @json($a['retention']);
    const retEl = document.getElementById('retentionChart');
    if (retEl) {
        const ctx = retEl.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 240);
        grad.addColorStop(0, 'rgba(20,184,166,.35)');
        grad.addColorStop(1, 'rgba(20,184,166,0)');
        new Chart(retEl, {
            type: 'line',
            data: {
                labels: retention.map((_, i) => i),
                datasets: [{ data: retention, borderColor: primary, backgroundColor: grad, fill: true,
                    borderWidth: 2, tension: .35, pointRadius: 0 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { ...tip, callbacks: {
                    title: (t) => 'Section ' + (t[0].dataIndex + 1),
                    label: (c) => c.parsed.y + '% still listening' } } },
                scales: {
                    x: { display: false, grid: { display: false } },
                    y: { min: 0, max: 100, grid: { color: gridColor }, ticks: { color: textColor, callback: (v) => v + '%' } },
                },
            },
        });
    }

    // Daily trend
    const trend = @json($a['trend']);
    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        const ctx = trendEl.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 240);
        grad.addColorStop(0, 'rgba(99,102,241,.30)');
        grad.addColorStop(1, 'rgba(99,102,241,0)');
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trend.map((t) => t.date.slice(5)),
                datasets: [{ data: trend.map((t) => t.plays), borderColor: '#6366f1', backgroundColor: grad,
                    fill: true, borderWidth: 2, tension: .35, pointRadius: 0 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: tip },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 8 } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 } },
                },
            },
        });
    }
});
</script>
@endif
@endsection
