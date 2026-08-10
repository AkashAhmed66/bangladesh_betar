@props(['title', 'subtitle' => null])

<div class="mb-5 flex flex-wrap items-end justify-between gap-3 sm:mb-6 sm:gap-4">
    <div class="min-w-0">
        <h2 class="page-title">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim($slot) !== '')
        <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto [&>*]:max-sm:flex-1">{{ $slot }}</div>
    @endif
</div>
