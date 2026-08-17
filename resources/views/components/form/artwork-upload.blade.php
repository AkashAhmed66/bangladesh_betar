@props([
    'name' => 'artwork',
    'removeName' => 'remove_artwork',
    'label' => 'Cover image',
    'currentPath' => null,
    'help' => 'JPG, PNG or WebP, up to 8 MB. A square image works best in the public application.',
    'wide' => false,
])

@php
    $currentUrl = $currentPath ? asset('storage/'.$currentPath) : null;
    $inputId = str_replace(['[', ']'], ['_', ''], $name);
@endphp

<div
    x-data="{ preview: @js($currentUrl) }"
    {{ $attributes->merge(['class' => 'space-y-3']) }}
>
    <div>
        <p class="form-label">{{ $label }}</p>
        <p class="form-help">{{ $help }}</p>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="relative {{ $wide ? 'h-32 w-full max-w-sm' : 'size-32 shrink-0' }} overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
            <img x-show="preview" x-cloak :src="preview" alt="{{ $label }} preview" class="size-full object-cover">
            <div x-show="! preview" class="flex size-full flex-col items-center justify-center gap-2 text-slate-400">
                <x-icon name="squares" class="size-8" />
                <span class="text-xs">No image selected</span>
            </div>
        </div>

        <div class="flex flex-col items-start gap-2">
            <label for="{{ $inputId }}" class="btn-secondary cursor-pointer">
                <x-icon name="upload" class="size-4" />
                <span x-text="preview ? 'Replace image' : 'Choose image'"></span>
            </label>
            <input
                id="{{ $inputId }}"
                name="{{ $name }}"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                @change="
                    if ($event.target.files[0]) {
                        preview = URL.createObjectURL($event.target.files[0]);
                        if ($refs.remove) $refs.remove.checked = false;
                    }
                "
            >

            @if ($currentPath)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-rose-600 dark:text-rose-400">
                    <input
                        x-ref="remove"
                        type="checkbox"
                        name="{{ $removeName }}"
                        value="1"
                        @checked(old($removeName))
                        @change="if ($event.target.checked) preview = null"
                        class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-slate-600 dark:bg-slate-800"
                    >
                    Remove current image
                </label>
            @endif
        </div>
    </div>

    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
