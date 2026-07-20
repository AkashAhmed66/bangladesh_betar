@extends('layouts.admin')

@section('title', $campaign ? 'Edit Campaign' : 'New Campaign')

@section('content')
<x-page-header :title="$campaign ? 'Edit: '.$campaign->title : 'New Marketing Campaign'"
               subtitle="Track production status and licensed usage-rights window (FR-MKT-05/06)" />

<form method="POST" action="{{ $campaign ? route('admin.marketing-campaigns.update', $campaign) : route('admin.marketing-campaigns.store') }}" class="max-w-3xl">
    @csrf
    @if ($campaign) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$campaign?->title" required />
            <x-form.input label="Client name" name="client_name" :value="$campaign?->client_name" />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$campaign?->description" rows="3" /></div>
            <x-form.select label="Status" name="status" :value="$campaign?->status ?? 'draft'" required
                           :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()" />
            <x-form.select label="Final delivered asset" name="final_asset_id" :value="$campaign?->final_asset_id" placeholder="— not yet delivered —"
                           :options="$assets->all()" help="The approved final master for this campaign." />
            <x-form.input label="Campaign start" name="start_date" type="date" :value="$campaign?->start_date?->format('Y-m-d')" />
            <x-form.input label="Campaign end" name="end_date" type="date" :value="$campaign?->end_date?->format('Y-m-d')" />
            <x-form.input label="Usage rights start" name="usage_rights_start" type="date" :value="$campaign?->usage_rights_start?->format('Y-m-d')" />
            <x-form.input label="Usage rights end" name="usage_rights_end" type="date" :value="$campaign?->usage_rights_end?->format('Y-m-d')" help="Licensed usage window expiry (FR-MKT-06)." />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.marketing-campaigns.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $campaign ? 'Save Changes' : 'Create Campaign' }}</button>
        </div>
    </div>
</form>
@endsection
