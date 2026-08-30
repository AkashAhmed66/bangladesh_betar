@extends('layouts.admin')

@section('title', __($title))

@section('content')
<x-page-header :title="__($title)" :subtitle="__('English and Bangla labels are returned through the public API.')" />

@php($fixedNewsHeader = $portal === 'news' && $category?->isFixedHeader())

<form method="POST" action="{{ $category ? route('admin.'.$portal.'-categories.update', $category) : route('admin.'.$portal.'-categories.store') }}" class="max-w-3xl">
    @csrf
    @if ($category) @method('PUT') @endif
    <div class="card">
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input :label="__('Name (English)')" name="name" :value="$category?->name" required />
            <x-form.input :label="__('Name (Bangla)')" name="name_bn" :value="$category?->name_bn" />
            <x-form.input :label="__('URL slug')" name="slug" :value="$category?->slug" required :help="__('Stable lowercase identifier used in public links.')" :readonly="$fixedNewsHeader" />
            <x-form.input :label="__('Display order')" name="position" type="number" :value="$category?->position ?? 0" required :readonly="$fixedNewsHeader" />
            <x-form.textarea :label="__('Description (English)')" name="description" :value="$category?->description" rows="4" />
            <x-form.textarea :label="__('Description (Bangla)')" name="description_bn" :value="$category?->description_bn" rows="4" />
            @if ($portal === 'news')
                <input type="hidden" name="show_in_header" value="{{ $fixedNewsHeader ? 1 : 0 }}">
                <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full {{ $fixedNewsHeader ? 'bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}"><x-icon :name="$fixedNewsHeader ? 'shield-check' : 'menu'" class="size-4" /></span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __($fixedNewsHeader ? 'Fixed heading' : 'More menu') }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __($fixedNewsHeader ? 'This protected category stays in the News header and cannot be deleted or moved.' : 'New News categories are automatically placed under More.') }}</p>
                        </div>
                    </div>
                </div>
            @else
                @php($showInHeader = (int) old('show_in_header', $category ? (int) $category->show_in_header : 0))
                <fieldset class="sm:col-span-2">
                <legend class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Navigation placement') }} <span class="text-red-500">*</span></legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label for="show_in_header_yes" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-primary-400 dark:border-slate-700 dark:hover:border-primary-500">
                        <input id="show_in_header_yes" name="show_in_header" type="radio" value="1" class="mt-0.5 size-4 border-slate-300 text-primary-600 focus:ring-primary-500" @checked($showInHeader === 1) required>
                        <span><span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Show in heading') }}</span><span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Display this category directly in the portal navigation heading.') }}</span></span>
                    </label>
                    <label for="show_in_header_no" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-primary-400 dark:border-slate-700 dark:hover:border-primary-500">
                        <input id="show_in_header_no" name="show_in_header" type="radio" value="0" class="mt-0.5 size-4 border-slate-300 text-primary-600 focus:ring-primary-500" @checked($showInHeader === 0) required>
                        <span><span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Show under More') }}</span><span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Keep this category inside the portal More menu.') }}</span></span>
                    </label>
                </div>
                @error('show_in_header') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </fieldset>
            @endif
            @if ($fixedNewsHeader)
                <input type="hidden" name="is_active" value="1">
            @else
                <x-form.toggle :label="__('Active category')" name="is_active" :checked="$category ? (bool) $category->is_active : true" :help="__('Hidden categories disappear from public navigation and new content forms.')" />
            @endif
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800"><a href="{{ route('admin.'.$portal.'-categories.index') }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __($category ? 'Save Category' : 'Create Category') }}</button></div>
    </div>
</form>
@endsection
