@extends('layouts.admin')

@section('title', $adAsset ? 'Edit Ad Asset' : 'New Ad Asset')

@section('content')
<x-page-header :title="$adAsset ? 'Edit: '.$adAsset->title : 'New Ad Asset'"
               subtitle="Ad creative metadata. Audio file upload is handled separately (FR-ADV-01)." />

<form method="POST" action="{{ $adAsset ? route('admin.ad-assets.update', $adAsset) : route('admin.ad-assets.store') }}" class="max-w-3xl">
    @csrf
    @if ($adAsset) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input label="Title" name="title" :value="$adAsset?->title" required />
            </div>
            <x-form.select label="Ad type" name="ad_type" :value="$adAsset?->ad_type ?? 'commercial'" required
                           :options="['commercial' => 'Commercial', 'house' => 'House ad', 'psa' => 'Public service (PSA)']" />
            <x-form.select label="Campaign" name="ad_campaign_id" :value="$adAsset?->ad_campaign_id" placeholder="None (house / PSA)"
                           :options="$campaigns->all()" help="Leave empty for house ads and PSAs." />
            <x-form.input label="Duration (seconds)" name="duration_seconds" type="number" min="0" :value="$adAsset?->duration_seconds ?? 0" required />
            <x-form.select label="Language" name="language_id" :value="$adAsset?->language_id" placeholder="—" :options="$languages->all()" />
            <x-form.input label="Category" name="category" :value="$adAsset?->category" help="e.g. telecom, finance, awareness" />
            <x-form.select label="Status" name="status" :value="$adAsset?->status ?? 'pending_approval'" required
                           :options="['pending_approval' => 'Pending approval', 'active' => 'Active', 'inactive' => 'Inactive', 'expired' => 'Expired']" />
            <x-form.input label="Valid from" name="valid_from" type="date" :value="$adAsset?->valid_from?->format('Y-m-d')" />
            <x-form.input label="Valid until" name="valid_until" type="date" :value="$adAsset?->valid_until?->format('Y-m-d')" />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.ad-assets.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $adAsset ? 'Save Changes' : 'Create Ad Asset' }}</button>
        </div>
    </div>
</form>
@endsection
