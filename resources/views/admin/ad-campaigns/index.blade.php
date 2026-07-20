@extends('layouts.admin')

@section('title', 'Ad Campaigns')

@section('content')
<x-page-header title="Ad Campaigns" subtitle="Campaign flights, budgets and delivery (M27 · FR-ADV-02)">
    @can('ads.manage')
        <a href="{{ route('admin.ad-campaigns.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Campaign</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Campaign</th><th>Advertiser</th><th>Status</th><th>Flight</th><th class="text-right">Budget</th><th>Priority</th><th class="text-right">Impressions</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($campaigns as $campaign)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $campaign->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Goal {{ number_format($campaign->impression_goal) }} impressions</p>
                        </td>
                        <td class="text-sm">{{ $campaign->advertiser?->name ?? 'House / PSA' }}</td>
                        <td><x-status-badge :status="$campaign->status" /></td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $campaign->start_date?->format('j M Y') ?? '—' }} → {{ $campaign->end_date?->format('j M Y') ?? '—' }}
                        </td>
                        <td class="text-right tabular-nums">৳{{ number_format($campaign->budget, 0) }}</td>
                        <td><span class="badge-slate">P{{ $campaign->priority }}</span></td>
                        <td class="text-right tabular-nums">{{ number_format($campaign->impressions_count) }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('ads.manage')
                                    <a href="{{ route('admin.ad-campaigns.edit', $campaign) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.ad-campaigns.destroy', $campaign)" confirm="Delete campaign {{ $campaign->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="megaphone" title="No campaigns" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($campaigns->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
