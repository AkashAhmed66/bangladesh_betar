@extends('layouts.admin')

@section('title', 'Banners')

@section('content')
<x-page-header title="Promotional Banners" subtitle="Hero banners for the public home screen with scheduling (M24 · FR-CUR-01/03)">
    @can('curation.manage')
        <a href="{{ route('admin.banners.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Banner</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Banner</th><th>Target</th><th>Position</th><th>Schedule</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($banners as $banner)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $banner->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $banner->title_bn ?: $banner->subtitle ?: '—' }}</p>
                        </td>
                        <td class="text-sm">
                            @if ($banner->target_type)
                                <span class="badge-slate">{{ $banner->target_type === 'url' ? 'URL' : ucwords(str_replace('_', ' ', $banner->target_type)) }}</span>
                                <p class="mt-1 max-w-xs truncate text-xs text-slate-500 dark:text-slate-400">{{ $banner->target_value }}</p>
                            @else
                                <span class="text-slate-400 dark:text-slate-500">None</span>
                            @endif
                        </td>
                        <td class="text-sm tabular-nums">{{ $banner->position }}</td>
                        <td class="text-xs text-slate-500 dark:text-slate-400">
                            @if ($banner->starts_at || $banner->ends_at)
                                {{ $banner->starts_at?->format('d M Y H:i') ?? '—' }}<br>→ {{ $banner->ends_at?->format('d M Y H:i') ?? 'open' }}
                            @else
                                <span class="text-slate-400 dark:text-slate-500">Always on</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$banner->is_active ? 'active' : 'inactive'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('curation.manage')
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.banners.destroy', $banner)" confirm="Delete banner {{ $banner->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="megaphone" title="No banners yet" message="Create a banner to promote content on the home screen." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($banners->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $banners->links() }}</div>
    @endif
</div>
@endsection
