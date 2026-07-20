@props(['label', 'name', 'checked' => false, 'help' => null])

<label class="flex cursor-pointer items-start gap-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
           class="mt-0.5 size-4.5 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
    <span>
        <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
        @if ($help)<span class="block text-xs text-slate-500 dark:text-slate-400">{{ $help }}</span>@endif
    </span>
</label>
