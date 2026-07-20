@extends('layouts.admin')

@section('title', $campaign ? 'Edit Campaign' : 'New Campaign')

@section('content')
<x-page-header :title="$campaign ? 'Edit: '.$campaign->name : 'New Ad Campaign'"
               subtitle="Flight dates, budget, priority and targeting (FR-ADV-02)" />

@php
    $targeting = $campaign?->targeting ?? [];
    $categoriesValue = implode(', ', $targeting['categories'] ?? []);
    $platformsValue = implode(', ', $targeting['platforms'] ?? []);
@endphp

<form method="POST" action="{{ $campaign ? route('admin.ad-campaigns.update', $campaign) : route('admin.ad-campaigns.store') }}" class="max-w-3xl">
    @csrf
    @if ($campaign) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.input label="Campaign name" name="name" :value="$campaign?->name" required />
            </div>
            <x-form.select label="Advertiser" name="advertiser_id" :value="$campaign?->advertiser_id" placeholder="House / PSA (no advertiser)"
                           :options="$advertisers->all()" />
            <x-form.select label="Status" name="status" :value="$campaign?->status ?? 'draft'" required
                           :options="['draft' => 'Draft', 'pending_approval' => 'Pending approval', 'active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed']" />
            <x-form.input label="Start date" name="start_date" type="date" :value="$campaign?->start_date?->format('Y-m-d')" />
            <x-form.input label="End date" name="end_date" type="date" :value="$campaign?->end_date?->format('Y-m-d')" />
            <x-form.input label="Budget (৳)" name="budget" type="number" step="0.01" min="0" :value="$campaign?->budget ?? 0" required />
            <x-form.input label="Impression goal" name="impression_goal" type="number" min="0" :value="$campaign?->impression_goal ?? 0" required />
            <x-form.input label="Priority (1 highest – 9 lowest)" name="priority" type="number" min="1" max="9" :value="$campaign?->priority ?? 5" required />
            <x-form.input label="Frequency cap / hour" name="frequency_cap_per_hour" type="number" min="0" :value="$campaign?->frequency_cap_per_hour ?? 4" required
                          help="Max times one listener hears this campaign per hour." />
            <x-form.input label="Target categories" name="targeting_categories" :value="$categoriesValue" help="Comma-separated, e.g. news, drama, music" />
            <x-form.input label="Target platforms" name="targeting_platforms" :value="$platformsValue" help="Comma-separated, e.g. web, android, ios" />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.ad-campaigns.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $campaign ? 'Save Changes' : 'Create Campaign' }}</button>
        </div>
    </div>
</form>
@endsection
