@extends('layouts.admin')

@section('title', 'Rights Records')

@section('content')
<x-page-header title="Rights Records" subtitle="Per-asset rights clearances — publication is gated on cleared rights (FR-CPR-04/05)">
    @can('rights.manage')
        <a href="{{ route('admin.rights-records.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Record</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
            <a href="{{ route('admin.rights-records.index', ['expiring' => 1]) }}"
               class="btn-sm {{ request()->boolean('expiring') ? 'btn-accent' : 'btn-ghost' }}">
                <x-icon name="clock" class="size-4" /> Expiring soon (90d)
            </a>
            @if (request()->boolean('expiring') || request('status'))
                <a href="{{ route('admin.rights-records.index') }}" class="btn-ghost btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Asset</th><th>Holder</th><th>Rights</th><th>Territory</th><th>Valid Until</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    @php
                        $expiringSoon = $record->status === 'cleared'
                            && $record->valid_until
                            && $record->valid_until->betweenIncluded(now(), now()->addDays(90));
                    @endphp
                    <tr>
                        <td>
                            @if ($record->audioAsset)
                                <a href="{{ route('admin.assets.show', $record->audioAsset) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $record->audioAsset->title }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $record->audioAsset->archive_no }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $record->rightsHolder?->name ?? 'Unknown holder' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($record->rights_types ?? [] as $type)
                                    <span class="badge-slate">{{ ucfirst($type) }}</span>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $record->territory }}</td>
                        <td class="text-sm">
                            @if ($record->valid_until)
                                <span class="tabular-nums {{ $expiringSoon ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-300' }}">
                                    {{ $record->valid_until->format('j M Y') }}
                                </span>
                                @if ($expiringSoon)<x-icon name="exclamation" class="ml-1 inline size-4 text-amber-500" />@endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$record->status" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('rights.manage')
                                    <a href="{{ route('admin.rights-records.edit', $record) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.rights-records.destroy', $record)" confirm="Delete this rights record?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="scale" title="No rights records" message="Create rights records so cleared assets can be published." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($records->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $records->links() }}</div>
    @endif
</div>
@endsection
