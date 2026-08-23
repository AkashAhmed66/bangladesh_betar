@extends('layouts.admin')

@section('title', __($title))

@section('content')
<x-page-header :title="__($title)" :subtitle="__('Manage the bilingual categories used by the admin editor and public portal.')">
    <a href="{{ route('admin.'.$portal.'-categories.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Add Category') }}</a>
</x-page-header>

<div class="card overflow-hidden">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>{{ __('Order') }}</th><th>{{ __('English') }}</th><th>{{ __('Bangla') }}</th><th>Slug</th><th>{{ __('Content') }}</th><th>{{ __('Placement') }}</th><th>{{ __('Status') }}</th><th class="text-right">{{ __('Actions') }}</th></tr></thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->position }}</td>
                        <td><p class="font-semibold text-slate-800 dark:text-slate-100">{{ $category->name }}</p><p class="mt-0.5 max-w-sm text-xs text-slate-400">{{ $category->description }}</p></td>
                        <td><p class="font-bangla font-semibold text-slate-800 dark:text-slate-100">{{ $category->name_bn ?: '—' }}</p><p class="mt-0.5 max-w-sm font-bangla text-xs text-slate-400">{{ $category->description_bn }}</p></td>
                        <td><code class="text-xs">{{ $category->slug }}</code></td>
                        <td>{{ $portal === 'news' ? $category->articles_count : $category->shows_count }}</td>
                        <td><span class="{{ $category->show_in_header ? 'badge-blue' : 'badge-slate' }}">{{ __($category->show_in_header ? 'Heading' : 'More menu') }}</span></td>
                        <td><span class="{{ $category->is_active ? 'badge-emerald' : 'badge-slate' }}">{{ __($category->is_active ? 'Active' : 'Hidden') }}</span></td>
                        <td><div class="flex items-center justify-end gap-1"><a href="{{ route('admin.'.$portal.'-categories.edit', $category) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a><x-confirm-delete :action="route('admin.'.$portal.'-categories.destroy', $category)" :confirm="__('Delete this category?')" /></div></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="funnel" :title="__('No categories')" :message="__('Create the first portal category.')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
