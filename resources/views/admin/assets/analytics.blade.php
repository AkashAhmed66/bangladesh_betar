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
<style>
    /* Popularity-rank hero — a rich broadcast-teal gradient with a soft
       top-right sheen. Self-contained (no design-token dependency) so it
       renders identically in light and dark. */
    .rank-hero {
        background:
            radial-gradient(1100px 180px at 100% -40%, rgba(255, 255, 255, .16), transparent 55%),
            linear-gradient(135deg, #0f766e 0%, #0d9488 48%, #0891b2 100%);
    }
    .rank-trend-badge {
        position: absolute;
        top: 0;
        right: 0;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .35rem .7rem;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .02em;
        color: #7c2d12;
        background: linear-gradient(135deg, #fde68a, #f59e0b);
        border-bottom-left-radius: .85rem;
        box-shadow: 0 4px 14px -4px rgba(245, 158, 11, .65);
    }
    .rank-chip {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .15rem .55rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, .18);
        font-size: 11px;
        font-weight: 700;
        width: fit-content;
    }
    /* Section label with a small accent tick. */
    .section-label {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 2rem 0 .85rem;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }
    .dark .section-label { color: rgb(148 163 184); }
    .section-label::before {
        content: "";
        width: .25rem;
        height: 1rem;
        border-radius: 9999px;
        background: linear-gradient(180deg, var(--primary-500, #14b8a6), var(--primary-700, #0f766e));
    }
    .section-label span { margin-left: -.15rem; }
    /* Content-type icon tile in the header. */
    .asset-thumb {
        background: linear-gradient(135deg, #0f766e, #0891b2);
        color: #fff;
    }
    /* Gentle staggered entrance for the dashboard blocks. */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .fade-up { animation: fadeUp .45s cubic-bezier(.16, .84, .44, 1) both; }
    @media (prefers-reduced-motion: reduce) { .fade-up { animation: none; } }
    /* KPI hover lift. */
    .kpi-card { transition: transform .18s ease, box-shadow .18s ease; }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 14px 26px -16px rgba(15, 23, 42, .45); }
    .dark .kpi-card:hover { box-shadow: 0 14px 26px -14px rgba(0, 0, 0, .65); }
</style>
{{-- Header ------------------------------------------------------------------}}
@php
    $ctIcon = [
        'song' => 'music', 'programme' => 'radio', 'podcast' => 'microphone',
        'story' => 'chat', 'news' => 'document-text', 'interview' => 'users',
        'drama' => 'sparkles', 'speech' => 'megaphone', 'jingle' => 'music',
        'psa' => 'megaphone', 'advert' => 'megaphone', 'voice_over' => 'microphone',
        'historical' => 'archive',
    ][$asset->content_type] ?? 'wave';
@endphp
<div class="mb-6">
    <a href="{{ route('admin.assets.show', $asset) }}" class="mb-2 inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-primary-600 dark:text-slate-400">
        <x-icon name="chevron-left" class="size-3.5" /> Back to asset
    </a>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-4">
            <div class="asset-thumb hidden size-14 shrink-0 items-center justify-center rounded-xl shadow-sm sm:flex">
                <x-icon name="{{ $ctIcon }}" class="size-7" />
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="page-title">Listener Analytics</h2>
                    <span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $asset->content_type)) }}</span>
                </div>
                <p class="mt-1 truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ $asset->title }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-1"><x-icon name="archive" class="size-3.5 text-slate-400" /> {{ $asset->archive_no }}</span>
                    <span class="inline-flex items-center gap-1"><x-icon name="clock" class="size-3.5 text-slate-400" /> {{ $fmt($asset->duration_seconds) }} runtime</span>
                    @if ($asset->published_at)
                        <span class="inline-flex items-center gap-1"><x-icon name="calendar" class="size-3.5 text-slate-400" /> Published {{ $asset->published_at->format('d M Y') }}</span>
                    @endif
                    @if ($asset->rating_count > 0)
                        <span class="inline-flex items-center gap-1 font-medium text-amber-500"><x-icon name="star" class="size-3.5" /> {{ number_format($asset->avg_rating, 1) }} <span class="font-normal text-slate-400">({{ number_format($asset->rating_count) }})</span></span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.assets.analytics', [$asset, 'range' => $a['range'], 'export' => 'csv']) }}"
               class="btn-secondary"><x-icon name="download" class="size-4" /> Export CSV</a>
            <a href="{{ route('admin.assets.studio', $asset) }}" class="btn-accent"><x-icon name="wave" class="size-4" /> Open Studio</a>
        </div>
    </div>
</div>

{{-- Date-range filter (segmented control) -----------------------------------}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="inline-flex flex-wrap items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900/60">
        @foreach ($ranges as $key => $label)
            <a href="{{ route('admin.assets.analytics', [$asset, 'range' => $key]) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-semibold transition
                      {{ $a['range'] === $key
                         ? 'bg-white text-primary-700 shadow-sm dark:bg-slate-700 dark:text-white'
                         : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <span class="inline-flex items-center gap-1 text-xs text-slate-400">
        <x-icon name="calendar" class="size-3.5" /> Showing {{ $a['rangeLabel'] }}
    </span>
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

{{-- Popularity rank + engagement (hero) -------------------------------------}}
@php $rk = $a['ranking']; $eng = $a['engagement']; @endphp
<div class="rank-hero fade-up mb-6 overflow-hidden rounded-2xl text-white shadow-lg">
    <div class="grid sm:grid-cols-[1.5fr_1fr]">
        {{-- Left — rank + trend + momentum ---------------------------------}}
        <div class="relative p-5 sm:p-6">
            @if ($rk['is_trending'])
                <span class="rank-trend-badge"><x-icon name="fire" class="size-3.5" /> Trending</span>
            @endif

            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-white/70">
                <x-icon name="trophy" class="size-4" /> Popularity rank
                <span class="font-normal normal-case tracking-normal text-white/45">· {{ $a['rangeLabel'] }}</span>
            </div>

            @if ($rk['rank'])
                <div class="mt-3 flex flex-wrap items-end gap-x-4 gap-y-2">
                    <span class="text-5xl font-black leading-none sm:text-6xl">#{{ number_format($rk['rank']) }}</span>
                    <div class="mb-1 flex flex-col gap-1.5">
                        @if ($rk['top_pct'] !== null)
                            <span class="rank-chip"><x-icon name="sparkles" class="size-3" /> Top {{ $rk['top_pct'] }}%</span>
                        @endif
                        @if ($rk['movement'] !== null && $rk['movement'] !== 0)
                            <span class="flex items-center gap-1 text-xs font-semibold {{ $rk['movement'] > 0 ? 'text-emerald-200' : 'text-rose-200' }}">
                                <x-icon name="{{ $rk['movement'] > 0 ? 'arrow-trending-up' : 'arrow-trending-down' }}" class="size-4" />
                                {{ $rk['movement'] > 0 ? 'Up' : 'Down' }} {{ abs($rk['movement']) }} vs last period
                            </span>
                        @elseif ($rk['is_new'])
                            <span class="flex items-center gap-1 text-xs font-semibold text-amber-200">
                                <x-icon name="sparkles" class="size-4" /> New entry this period
                            </span>
                        @elseif ($rk['has_prev'])
                            <span class="text-xs font-medium text-white/55">Holding steady vs last period</span>
                        @endif
                    </div>
                </div>

                <p class="mt-2.5 text-sm text-white/75">
                    out of <span class="font-semibold text-white">{{ number_format($rk['total']) }}</span>
                    {{ \Illuminate\Support\Str::plural('recording', $rk['total']) }} played in this period
                </p>

                @if ($rk['top_plays'] > 0)
                    <div class="mt-4 max-w-md">
                        <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-white/60">
                            <span>{{ number_format($rk['plays']) }} plays</span>
                            <span>#1 has {{ number_format($rk['top_plays']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full bg-white/90"
                                 style="width: {{ max(4, (int) round($rk['plays'] / max(1, $rk['top_plays']) * 100)) }}%"></div>
                        </div>
                    </div>
                @endif
            @else
                <div class="mt-3 flex items-center gap-4">
                    <span class="text-4xl font-black leading-none text-white/45">—</span>
                    <p class="max-w-sm text-sm text-white/75">
                        No plays in this period, so this recording isn't ranked yet.
                        Widen the range to <strong class="text-white">All time</strong> to see its standing.
                    </p>
                </div>
            @endif
        </div>

        {{-- Right — favourites / downloads / shares ------------------------}}
        <div class="grid grid-cols-3 border-t border-white/10 sm:grid-cols-1 sm:border-l sm:border-t-0">
            @php
                $engCards = [
                    ['label' => 'Favourites', 'value' => $eng['favorites'], 'delta' => $eng['favorites_new'], 'icon' => 'heart',    'sub' => 'saved to libraries'],
                    ['label' => 'Downloads',  'value' => $eng['downloads'],  'delta' => $eng['downloads_new'], 'icon' => 'download', 'sub' => 'offline saves'],
                    ['label' => 'Shares',     'value' => $eng['shares'],     'delta' => $eng['shares_new'],    'icon' => 'share',    'sub' => 'shared out'],
                ];
            @endphp
            @foreach ($engCards as $c)
                <div class="flex items-center gap-3 px-4 py-4 [&:not(:last-child)]:border-r [&:not(:last-child)]:border-white/10 sm:[&:not(:last-child)]:border-b sm:[&:not(:last-child)]:border-r-0">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                        <x-icon name="{{ $c['icon'] }}" class="size-4.5" />
                    </div>
                    <div class="min-w-0">
                        <p class="flex items-baseline gap-1.5 text-xl font-bold leading-none">
                            {{ number_format($c['value']) }}
                            @if ($c['delta'] > 0)
                                <span class="text-[11px] font-semibold text-emerald-200">+{{ number_format($c['delta']) }}</span>
                            @endif
                        </p>
                        <p class="mt-1 truncate text-xs font-medium text-white/70">{{ $c['label'] }}</p>
                        <p class="truncate text-[10px] text-white/45">{{ $c['sub'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Performance overview ----------------------------------------------------}}
<p class="section-label"><span>Performance overview</span></p>
<div class="mb-2 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
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
        <div class="kpi-card card fade-up p-4" style="animation-delay: {{ 40 * $loop->index }}ms">
            <div class="mb-2 flex size-9 items-center justify-center rounded-lg {{ $kpiTint[$color] }}">
                <x-icon name="{{ $icon }}" class="size-4.5" />
            </div>
            <p class="text-2xl font-bold tracking-tight text-slate-800 tabular-nums dark:text-slate-100">{{ $value }}</p>
            <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $sub }}</p>
        </div>
    @endforeach
</div>

{{-- Listener engagement -----------------------------------------------------}}
<p class="section-label"><span>Listener engagement</span></p>

{{-- Engagement heat map (hero chart) --}}
<div class="card mb-6">
    <div class="card-header">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="chart-bar" class="size-4 text-primary-500" /> Engagement across the runtime
            </h3>
            <p class="mt-0.5 text-xs text-slate-400">total listeners active at each point · coloured strip below the line shows engagement intensity</p>
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

{{-- Notable moments — the standout sections the listening data reveals ------}}
@php
    $sec = $a['sections'];
    $notable = [];
    if (! empty($sec['most_played'])) {
        $notable[] = ['title' => 'Peak listening', 'icon' => 'fire',
            'tint' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
            'b' => $sec['most_played'], 'metric' => number_format($sec['most_played']['plays']).' listeners here'];
    }
    if (! empty($sec['most_replayed'])) {
        $notable[] = ['title' => 'Most replayed', 'icon' => 'arrow-path',
            'tint' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
            'b' => $sec['most_replayed'], 'metric' => number_format($sec['most_replayed']['replays']).' replays'];
    }
    if (! empty($sec['most_skipped'])) {
        $notable[] = ['title' => 'Most skipped', 'icon' => 'chevron-right',
            'tint' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            'b' => $sec['most_skipped'], 'metric' => number_format($sec['most_skipped']['skips']).' skips / seeks'];
    }
    if (! empty($sec['drop_off'])) {
        $notable[] = ['title' => 'Biggest drop-off', 'icon' => 'arrow-trending-down',
            'tint' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
            'b' => $sec['drop_off'], 'metric' => 'audience fell to '.$sec['drop_off']['retention'].'%'];
    }
@endphp
@if (count($notable))
    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ($notable as $n)
            <div class="card fade-up p-4" style="animation-delay: {{ 50 * $loop->index }}ms">
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-lg {{ $n['tint'] }}">
                        <x-icon name="{{ $n['icon'] }}" class="size-4" />
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $n['title'] }}</p>
                </div>
                <p class="mt-3 text-lg font-bold tabular-nums text-slate-800 dark:text-slate-100">
                    {{ $fmt($n['b']['start']) }}<span class="mx-0.5 font-normal text-slate-300 dark:text-slate-600">–</span>{{ $fmt($n['b']['end']) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-400">{{ $n['metric'] }}</p>
            </div>
        @endforeach
    </div>
@endif

{{-- Retention + trend -------------------------------------------------------}}
<div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="funnel" class="size-4 text-primary-500" /> Audience retention
            </h3>
            <span class="text-xs text-slate-400">% still listening across the timeline</span>
        </div>
        <div class="card-body"><div class="h-64"><canvas id="retentionChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="calendar" class="size-4 text-indigo-500" /> Daily listening trend
            </h3>
            <span class="text-xs text-slate-400">plays per day</span>
        </div>
        <div class="card-body"><div class="h-64"><canvas id="trendChart"></canvas></div></div>
    </div>
</div>

{{-- Audience breakdown — where and how listeners tuned in ------------------}}
@php
    $breakdowns = array_values(array_filter([
        ['title' => 'By platform', 'icon' => 'computer', 'color' => '#14b8a6', 'data' => $a['platform']],
        ['title' => 'By device',   'icon' => 'radio',    'color' => '#6366f1', 'data' => $a['device']],
        ['title' => 'By region',   'icon' => 'globe',    'color' => '#f59e0b', 'data' => $a['region']],
    ], fn ($bd) => ! empty($bd['data'])));
@endphp
@if (count($breakdowns))
    <p class="section-label"><span>Audience breakdown</span></p>
    <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($breakdowns as $bd)
            @php $tot = max(1, array_sum($bd['data'])); $mx = max($bd['data']); @endphp
            <div class="card fade-up" style="animation-delay: {{ 50 * $loop->index }}ms">
                <div class="card-header">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                        <x-icon name="{{ $bd['icon'] }}" class="size-4 text-slate-400" /> {{ $bd['title'] }}
                    </h3>
                    <span class="text-xs text-slate-400">{{ number_format($tot) }} plays</span>
                </div>
                <div class="card-body space-y-3">
                    @foreach ($bd['data'] as $label => $count)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-600 dark:text-slate-300">{{ ucfirst((string) $label) }}</span>
                                <span class="tabular-nums text-slate-400">{{ number_format($count) }} · {{ round($count / $tot * 100) }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full" style="width: {{ max(3, round($count / max(1, $mx) * 100)) }}%; background: {{ $bd['color'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

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
