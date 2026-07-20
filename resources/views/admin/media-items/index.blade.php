@extends('layouts.admin')

@section('title', 'Digitization')

@section('content')
<x-page-header title="Digitization Register" subtitle="Physical media pipeline: register → capture → restore → QC → archive (M03)">
    @can('digitization.manage')
        <a href="{{ route('admin.media-items.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Register Item</a>
    @endcan
</x-page-header>

{{-- Progress overview (FR-DIG-06) --}}
<div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-8">
    @foreach (['registered', 'in_progress', 'captured', 'restored', 'qc_pending', 'qc_passed', 'archived', 'failed'] as $stage)
        <div class="card px-3 py-2.5 text-center">
            <p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $progress[$stage] ?? 0 }}</p>
            <p class="text-[11px] text-slate-500 capitalize dark:text-slate-400">{{ str_replace('_', ' ', $stage) }}</p>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Item code or title…" class="form-input w-56">
            <select name="media_type" class="form-input w-32" onchange="this.form.submit()">
                <option value="">All media</option>
                @foreach (['reel', 'cassette', 'dat', 'vinyl', 'cd', 'minidisc'] as $mt)
                    <option value="{{ $mt }}" @selected(request('media_type') === $mt)>{{ ucfirst($mt) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (['registered', 'in_progress', 'captured', 'restored', 'qc_pending', 'qc_passed', 'archived', 'failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Item</th><th>Media</th><th>Condition</th><th>Priority</th><th>Status</th><th>Linked Asset</th><th>Digitized By</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $item->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item->item_code }} · {{ $item->location }}</p>
                        </td>
                        <td><span class="badge-slate">{{ ucfirst($item->media_type) }}</span></td>
                        <td><span class="badge-{{ ['good' => 'green', 'fair' => 'blue', 'poor' => 'amber', 'critical' => 'red'][$item->condition] ?? 'slate' }}">{{ ucfirst($item->condition) }}</span></td>
                        <td><span class="badge-{{ ['high' => 'red', 'medium' => 'amber', 'low' => 'slate'][$item->priority] }}">{{ ucfirst($item->priority) }}</span></td>
                        <td><x-status-badge :status="$item->status" /></td>
                        <td class="text-sm">
                            @if ($item->audioAsset)
                                <a href="{{ route('admin.assets.show', $item->audioAsset) }}" class="text-primary-700 hover:underline dark:text-primary-300">{{ $item->audioAsset->archive_no }}</a>
                            @else — @endif
                        </td>
                        <td class="text-sm">{{ $item->digitizedBy?->name ?? '—' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('digitization.manage')
                                    <a href="{{ route('admin.media-items.edit', $item) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.media-items.destroy', $item)" confirm="Remove {{ $item->item_code }} from the register?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="disc" title="No physical media registered" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $items->links() }}</div>
    @endif
</div>
@endsection
