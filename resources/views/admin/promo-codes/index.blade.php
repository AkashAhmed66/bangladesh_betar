@extends('layouts.admin')

@section('title', 'Promo Codes')

@section('content')
<x-page-header title="Promo Codes" subtitle="Discount codes for subscription plans (FR-SUB-03)">
    @can('plans.manage')
        <a href="{{ route('admin.promo-codes.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Code</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Code</th><th>Discount</th><th>Plan</th><th>Validity</th><th>Usage</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($codes as $code)
                    <tr>
                        <td><span class="font-mono font-semibold text-slate-800 dark:text-slate-100">{{ $code->code }}</span></td>
                        <td><span class="badge-primary">{{ $code->discount_percent }}% off</span></td>
                        <td class="text-sm">{{ $code->plan?->name ?? 'Any plan' }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $code->valid_from?->format('j M Y') ?? '—' }} → {{ $code->valid_until?->format('j M Y') ?? '—' }}
                        </td>
                        <td class="text-sm tabular-nums">
                            {{ number_format($code->used_count) }} / {{ $code->max_uses === 0 ? '∞' : number_format($code->max_uses) }}
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if ($code->is_active)<span class="badge-green">Active</span>@else<span class="badge-slate">Inactive</span>@endif
                                @if ($code->isValid())<span class="badge-blue">Valid now</span>@else<span class="badge-amber">Not usable</span>@endif
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('plans.manage')
                                    <a href="{{ route('admin.promo-codes.edit', $code) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.promo-codes.destroy', $code)" confirm="Delete promo code {{ $code->code }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="credit-card" title="No promo codes" message="Create a discount code to run a subscription promotion." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($codes->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $codes->links() }}</div>
    @endif
</div>
@endsection
