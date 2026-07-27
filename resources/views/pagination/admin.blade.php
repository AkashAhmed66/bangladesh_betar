@php
    $btn = 'inline-flex h-9 min-w-9 items-center justify-center gap-1 rounded-lg px-3 text-sm font-medium transition select-none';
    $enabled = 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white';
    $disabled = 'cursor-not-allowed border border-slate-200 bg-slate-50 text-slate-300 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-600';
    $active = 'border border-primary-600 bg-primary-600 text-white shadow-sm dark:border-primary-500 dark:bg-primary-600';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col-reverse items-center justify-between gap-3 sm:flex-row">
        {{-- Results summary --}}
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() ?? 0 }}</span>
            –
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() ?? 0 }}</span>
            of
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span>
            {{ $paginator->total() === 1 ? 'result' : 'results' }}
        </p>

        {{-- Controls --}}
        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Previous page" class="{{ $btn }} {{ $disabled }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span class="hidden sm:inline">Prev</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" class="{{ $btn }} {{ $enabled }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span class="hidden sm:inline">Prev</span>
                </a>
            @endif

            {{-- Numbered pages (desktop) --}}
            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $btn }} border-transparent text-slate-400 dark:text-slate-600">…</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $btn }} {{ $active }}">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Go to page {{ $page }}" class="{{ $btn }} {{ $enabled }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Compact page indicator (mobile) --}}
            <span class="px-2 text-xs font-medium tabular-nums text-slate-500 dark:text-slate-400 sm:hidden">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" class="{{ $btn }} {{ $enabled }}">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Next page" class="{{ $btn }} {{ $disabled }}">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
