@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'message' => null])

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <span class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
        <x-icon :name="$icon" class="size-7" />
    </span>
    <p class="mt-4 text-sm font-medium text-slate-700 dark:text-slate-300">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $message }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
