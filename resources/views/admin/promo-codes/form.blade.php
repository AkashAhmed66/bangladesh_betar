@extends('layouts.admin')

@section('title', $promoCode ? 'Edit Promo Code' : 'New Promo Code')

@section('content')
<x-page-header :title="$promoCode ? 'Edit: '.$promoCode->code : 'New Promo Code'"
               subtitle="Discount code for subscription checkout (FR-SUB-03)" />

<form method="POST" action="{{ $promoCode ? route('admin.promo-codes.update', $promoCode) : route('admin.promo-codes.store') }}" class="max-w-2xl">
    @csrf
    @if ($promoCode) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Code" name="code" :value="$promoCode?->code" required help="e.g. BETAR50 — stored uppercase" class="font-mono uppercase" />
            <x-form.input label="Discount percent" name="discount_percent" type="number" min="1" max="100" :value="$promoCode?->discount_percent" required />
            <div class="sm:col-span-2">
                <x-form.select label="Applies to plan" name="plan_id" :value="$promoCode?->plan_id" placeholder="Any plan" :options="$plans->all()"
                               help="Leave blank to allow the code on any plan." />
            </div>
            <x-form.input label="Valid from" name="valid_from" type="date" :value="$promoCode?->valid_from?->format('Y-m-d')" />
            <x-form.input label="Valid until" name="valid_until" type="date" :value="$promoCode?->valid_until?->format('Y-m-d')" />
            <x-form.input label="Max uses" name="max_uses" type="number" min="0" :value="$promoCode?->max_uses ?? 0" required help="0 = unlimited redemptions" />
            <div class="flex items-end">
                <x-form.toggle label="Active" name="is_active" :checked="$promoCode?->is_active ?? true" />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.promo-codes.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $promoCode ? 'Save Changes' : 'Create Code' }}</button>
        </div>
    </div>
</form>
@endsection
