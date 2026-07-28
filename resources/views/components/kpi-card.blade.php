@props([
    'label',
    'value',
    'icon' => 'chart-bar',
    'color' => 'primary',
    'delta' => null,        // signed % change (float) or null to hide the chip
    'deltaLabel' => null,   // supporting context, e.g. "vs last month"
    'series' => null,       // optional numeric array → sparkline
])

@php
    $iconColors = [
        'primary' => 'bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300',
        'accent' => 'bg-accent-100 text-accent-700 dark:bg-accent-500/15 dark:text-accent-300',
        'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'red' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        'blue' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'purple' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    ];
    $sparkColors = [
        'primary' => 'text-primary-500 dark:text-primary-400',
        'accent' => 'text-accent-500 dark:text-accent-400',
        'green' => 'text-emerald-500 dark:text-emerald-400',
        'red' => 'text-rose-500 dark:text-rose-400',
        'blue' => 'text-sky-500 dark:text-sky-400',
        'purple' => 'text-violet-500 dark:text-violet-400',
    ];

    $d = $delta === null ? null : (float) $delta;
    $up = $d !== null && $d > 0;
    $down = $d !== null && $d < 0;
    $deltaClass = $up
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
        : ($down
            ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'
            : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300');
    $arrow = $up ? '▲' : ($down ? '▼' : '—');
    $hasSeries = is_array($series) && count($series) > 1;
@endphp

<div {{ $attributes->merge(['class' => 'card group relative flex flex-col overflow-hidden p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-[28px] font-bold leading-none tracking-tight text-slate-900 dark:text-white">{{ $value }}</p>
        </div>
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $iconColors[$color] ?? $iconColors['primary'] }}">
            <x-icon :name="$icon" class="size-[22px]" />
        </span>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
        @if ($d !== null)
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $deltaClass }}">
                <span class="text-[9px] leading-none">{{ $arrow }}</span>{{ number_format(abs($d), 1) }}%
            </span>
        @endif
        @if ($deltaLabel)
            <span class="truncate text-xs text-slate-400 dark:text-slate-500">{{ $deltaLabel }}</span>
        @endif
    </div>

    @if ($hasSeries)
        <div class="mt-auto pt-4">
            <x-sparkline :points="$series" :height="36" class="{{ $sparkColors[$color] ?? $sparkColors['primary'] }}" />
        </div>
    @endif
</div>
