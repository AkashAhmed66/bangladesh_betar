@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
<x-page-header :title="'Edit Plan: '.$plan->name"
               subtitle="Pricing, trial and feature limits (FR-SUB-01). Currency in ৳ BDT." />

@php $f = $plan->features ?? []; @endphp

<form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="max-w-3xl">
    @csrf
    @method('PUT')

    <div class="card mb-6">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Plan Details</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$plan->name" required />
            <x-form.input label="Name (Bangla)" name="name_bn" :value="$plan->name_bn" />
            <div class="sm:col-span-2">
                <x-form.textarea label="Description" name="description" :value="$plan->description" rows="2" />
            </div>
            <x-form.input label="Monthly price (৳)" name="price_monthly" type="number" step="0.01" :value="$plan->price_monthly" required />
            <x-form.input label="Annual price (৳)" name="price_annual" type="number" step="0.01" :value="$plan->price_annual" required />
            <x-form.input label="Trial days" name="trial_days" type="number" :value="$plan->trial_days" required help="0 = no trial period" />
            <div class="flex items-end">
                <x-form.toggle label="Plan is active" name="is_active" :checked="$plan->is_active" help="Inactive plans are hidden from the public app." />
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Feature Limits</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <x-form.toggle label="Serve advertisements" name="feat_ads" :checked="$f['ads'] ?? false" help="Free tiers typically show pre-roll ads." />
            </div>
            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <x-form.toggle label="Offline downloads" name="feat_offline_downloads" :checked="$f['offline_downloads'] ?? false" />
            </div>
            <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                <x-form.toggle label="Equalizer" name="feat_equalizer" :checked="$f['equalizer'] ?? false" />
            </div>
            <div></div>
            <x-form.input label="Skips per hour" name="feat_skips_per_hour" type="number"
                          :value="$f['skips_per_hour'] ?? null" help="Leave blank for unlimited skips." />
            <x-form.input label="Max quality (kbps)" name="feat_max_quality_kbps" type="number"
                          :value="$f['max_quality_kbps'] ?? 128" required />
            <x-form.select label="Premium content access" name="feat_premium_content" :value="$f['premium_content'] ?? 'preview'" required
                           :options="['full' => 'Full length', 'preview' => 'Preview only']" />
            <x-form.input label="Preview length (seconds)" name="feat_preview_seconds" type="number"
                          :value="$f['preview_seconds'] ?? null" help="Applies when premium content is preview-only. Blank = n/a." />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.plans.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </div>
</form>
@endsection
