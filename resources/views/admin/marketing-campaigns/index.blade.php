@extends('layouts.admin')

@section('title', 'Marketing Campaigns')

@section('content')
<x-page-header title="Marketing Campaigns" subtitle="Client production campaigns with usage-rights tracking (FR-MKT-05/06)">
    @can('marketing.manage')
        <a href="{{ route('admin.marketing-campaigns.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Campaign</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Title or client…" class="form-input w-56">
            <select name="status" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $campaigns->total() }} campaigns</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Campaign</th><th>Client</th><th>Status</th><th>Assets</th><th>Usage Rights End</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($campaigns as $campaign)
                    @php
                        $end = $campaign->usage_rights_end;
                        $expiringSoon = $end && $end->isFuture() && $end->lte(now()->addDays(30));
                        $expired = $end && $end->isPast();
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $campaign->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $campaign->creator?->name ?? 'Unassigned' }}</p>
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $campaign->client_name ?? '—' }}</td>
                        <td><x-status-badge :status="$campaign->status" /></td>
                        <td class="text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ $campaign->assets_count }}</td>
                        <td>
                            @if ($end)
                                <span @class([
                                    'inline-flex items-center gap-1 text-sm',
                                    'font-medium text-amber-700 dark:text-amber-400' => $expiringSoon,
                                    'text-rose-600 dark:text-rose-400' => $expired,
                                    'text-slate-600 dark:text-slate-300' => ! $expiringSoon && ! $expired,
                                ])>
                                    {{ $end->format('d M Y') }}
                                    @if ($expiringSoon)<x-icon name="exclamation" class="size-3.5" />@endif
                                </span>
                                @if ($expiringSoon)
                                    <p class="text-[11px] text-amber-600 dark:text-amber-400">Expiring soon</p>
                                @elseif ($expired)
                                    <p class="text-[11px] text-rose-500 dark:text-rose-400">Expired</p>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('marketing.manage')
                                    <a href="{{ route('admin.marketing-campaigns.edit', $campaign) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.marketing-campaigns.destroy', $campaign)" confirm="Delete campaign “{{ $campaign->title }}”?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="megaphone" title="No campaigns yet" message="Create a campaign to begin production." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($campaigns->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
