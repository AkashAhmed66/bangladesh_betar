@props(['label', 'value', 'icon' => 'chart-bar', 'hint' => null, 'color' => 'primary'])

@php
    $iconColors = [
        'primary' => 'bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300',
        'accent' => 'bg-accent-100 text-accent-700 dark:bg-accent-500/15 dark:text-accent-300',
        'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'red' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        'blue' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'purple' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    ];
@endphp

<div class="card p-5">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-1.5 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $iconColors[$color] ?? $iconColors['primary'] }}">
            <x-icon :name="$icon" class="size-5" />
        </span>
    </div>
</div>
