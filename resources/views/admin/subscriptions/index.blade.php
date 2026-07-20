@extends('layouts.admin')

@section('title', 'Subscriptions')

@section('content')
<x-page-header title="Subscriptions" subtitle="Subscriber lifecycle and billing state (M18 · FR-SUB-05)" />

<div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-stat-card label="Active" :value="number_format($stats['active'] ?? 0)" icon="check-badge" color="green" />
    <x-stat-card label="On Trial" :value="number_format($stats['trialing'] ?? 0)" icon="clock" color="accent" />
    <x-stat-card label="Cancelled" :value="number_format($stats['cancelled'] ?? 0)" icon="x" color="red" />
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (['trialing', 'active', 'grace', 'cancelled', 'expired'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Subscriber</th><th>Plan</th><th>Status</th><th>Cycle</th><th>Started</th><th>Ends</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $subscription->user?->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $subscription->user?->email }}</p>
                        </td>
                        <td class="text-sm">{{ $subscription->plan?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$subscription->status" /></td>
                        <td class="text-sm capitalize">{{ $subscription->billing_cycle }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $subscription->started_at?->format('j M Y') ?? '—' }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $subscription->ends_at?->format('j M Y') ?? '—' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('subscriptions.manage')
                                    @if ($subscription->isActive())
                                        <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription) }}" class="inline"
                                              x-data @submit.prevent="if (confirm('Cancel this subscription? Auto-renew will be turned off.')) $el.submit()">
                                            @csrf
                                            <button type="submit" class="btn-ghost btn-sm text-rose-600 dark:text-rose-400">Cancel</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="star" title="No subscriptions" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($subscriptions->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $subscriptions->links() }}</div>
    @endif
</div>
@endsection
