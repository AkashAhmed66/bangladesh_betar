@extends('layouts.admin')

@section('title', 'Home Sections')

@section('content')
<x-page-header title="Home Screen Sections" subtitle="Curate the public home layout — rows, grids, banners & spotlights (M24 · FR-CUR-01/03)">
    @can('curation.manage')
        <a href="{{ route('admin.home-sections.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Section</a>
    @endcan
</x-page-header>

<div class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-800/40 dark:text-slate-300">
    <x-icon name="info" class="size-5 shrink-0 text-primary-600 dark:text-primary-400" />
    <p>Dynamic section types (trending, new releases, top played, recommended, on this day…) resolve their content at request time. Only <span class="font-medium">custom</span> sections use curated items, which are managed separately / seeded.</p>
</div>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Section</th><th>Type</th><th>Layout</th><th>Position</th><th>Items</th><th>Schedule</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($sections as $section)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $section->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $section->title_bn ?: $section->slug }}</p>
                        </td>
                        <td><span class="badge-slate">{{ ucwords(str_replace('_', ' ', $section->section_type)) }}</span></td>
                        <td><span class="badge-blue">{{ ucfirst($section->layout) }}</span></td>
                        <td class="text-sm tabular-nums">{{ $section->position }}</td>
                        <td class="text-sm tabular-nums">
                            @if ($section->section_type === 'custom')
                                {{ $section->items_count }} / {{ $section->max_items }}
                            @else
                                <span class="text-slate-400 dark:text-slate-500">auto · {{ $section->max_items }}</span>
                            @endif
                        </td>
                        <td class="text-xs text-slate-500 dark:text-slate-400">
                            @if ($section->starts_at || $section->ends_at)
                                {{ $section->starts_at?->format('d M Y H:i') ?? '—' }}<br>→ {{ $section->ends_at?->format('d M Y H:i') ?? 'open' }}
                            @else
                                <span class="text-slate-400 dark:text-slate-500">Always on</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$section->is_active ? 'active' : 'inactive'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('curation.manage')
                                    <a href="{{ route('admin.home-sections.edit', $section) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.home-sections.destroy', $section)" confirm="Delete section {{ $section->title }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="squares" title="No home sections yet" message="Create sections to compose the public home screen." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($sections->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $sections->links() }}</div>
    @endif
</div>
@endsection
