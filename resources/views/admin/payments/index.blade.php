@extends('layouts.admin')

@section('title', 'Payments')

@section('content')
<x-page-header title="Payments" subtitle="Transaction ledger and refunds (M18 · FR-SUB-04, FR-SUB-13)" />

<div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-stat-card label="Completed Revenue" value="৳{{ number_format($stats['revenue'], 0) }}" icon="banknotes" color="green" />
    <x-stat-card label="Total Refunded" value="৳{{ number_format($stats['refunded'], 0) }}" icon="arrow-path" color="red" hint="across all transactions" />
    <x-stat-card label="Transactions" :value="number_format($stats['count'])" icon="credit-card" color="primary" />
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="status" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (['pending', 'completed', 'failed', 'refunded', 'partially_refunded', 'disputed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="method" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All methods</option>
                @foreach (['bkash', 'nagad', 'rocket', 'card', 'google_play', 'apple_iap'] as $method)
                    <option value="{{ $method }}" @selected(request('method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Invoice</th><th>User</th><th class="text-right">Amount</th><th>Method</th><th>Status</th><th>Paid</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    @php $refundable = round($payment->amount - $payment->refunded_amount, 2); @endphp
                    <tr>
                        <td><span class="font-mono text-xs text-slate-600 dark:text-slate-300">{{ $payment->invoice_no }}</span></td>
                        <td>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $payment->user?->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->subscription?->plan?->name }}</p>
                        </td>
                        <td class="text-right tabular-nums">
                            <span class="font-medium text-slate-800 dark:text-slate-100">৳{{ number_format($payment->amount, 2) }}</span>
                            @if ($payment->refunded_amount > 0)
                                <span class="block text-xs text-rose-500">-৳{{ number_format($payment->refunded_amount, 2) }} refunded</span>
                            @endif
                        </td>
                        <td><span class="badge-slate">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span></td>
                        <td><x-status-badge :status="$payment->status" /></td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $payment->paid_at?->format('j M Y') ?? '—' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('payments.refund')
                                    @if (in_array($payment->status, ['completed', 'partially_refunded'], true) && $refundable > 0)
                                        <div x-data="{ open: false }" class="relative">
                                            <button type="button" @click="open = true" class="btn-ghost btn-sm text-rose-600 dark:text-rose-400">
                                                <x-icon name="arrow-path" class="size-4" /> Refund
                                            </button>
                                            <div x-show="open" x-cloak @keydown.escape.window="open = false"
                                                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
                                                <div @click.outside="open = false" class="card w-full max-w-md">
                                                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Refund {{ $payment->invoice_no }}</h3></div>
                                                    <form method="POST" action="{{ route('admin.payments.refund', $payment) }}">
                                                        @csrf
                                                        <div class="card-body space-y-4">
                                                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                                                Refundable balance: <span class="font-semibold text-slate-700 dark:text-slate-200">৳{{ number_format($refundable, 2) }}</span>
                                                            </p>
                                                            <x-form.input label="Refund amount (৳)" name="amount" type="number" step="0.01" min="0.01"
                                                                          :max="$refundable" :value="$refundable" required />
                                                            <x-form.textarea label="Reason" name="refund_reason" rows="2" required />
                                                        </div>
                                                        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                                                            <button type="button" @click="open = false" class="btn-secondary">Cancel</button>
                                                            <button type="submit" class="btn-danger">Process Refund</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="banknotes" title="No payments" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
