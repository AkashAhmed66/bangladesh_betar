@extends('layouts.admin')

@section('title', $item ? 'Edit Media Item' : 'Register Media Item')

@section('content')
<x-page-header :title="$item ? 'Edit: '.$item->item_code : 'Register Physical Media Item'"
               subtitle="Track condition, priority and digitization status (FR-DIG-01/02)" />

<form method="POST" action="{{ $item ? route('admin.media-items.update', $item) : route('admin.media-items.store') }}" class="max-w-3xl">
    @csrf
    @if ($item) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Item code" name="item_code" :value="$item?->item_code" required help="e.g. REEL-1971-0001" />
            <x-form.input label="Title" name="title" :value="$item?->title" required />
            <x-form.select label="Media type" name="media_type" :value="$item?->media_type ?? 'reel'" required
                           :options="['reel' => 'Reel-to-reel', 'cassette' => 'Cassette', 'dat' => 'DAT', 'vinyl' => 'Vinyl', 'cd' => 'CD', 'minidisc' => 'MiniDisc']" />
            <x-form.select label="Condition" name="condition" :value="$item?->condition ?? 'good'" required
                           :options="['good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor', 'critical' => 'Critical']" />
            <x-form.input label="Storage location" name="location" :value="$item?->location" help="e.g. Vault A / Shelf 3" />
            <x-form.select label="Priority" name="priority" :value="$item?->priority ?? 'medium'" required
                           :options="['high' => 'High', 'medium' => 'Medium', 'low' => 'Low']" />
            <x-form.select label="Status" name="status" :value="$item?->status ?? 'registered'" required
                           :options="collect(['registered', 'in_progress', 'captured', 'restored', 'qc_pending', 'qc_passed', 'archived', 'failed'])->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()" />
            <x-form.select label="Station" name="station_id" :value="$item?->station_id" placeholder="—" :options="$stations->all()" />
            <x-form.select label="Linked digital asset" name="audio_asset_id" :value="$item?->audio_asset_id" placeholder="—"
                           :options="$assets->all()" help="Preserves the physical → digital link (FR-DIG-07)." />
            <div class="sm:col-span-2"><x-form.textarea label="Restoration notes" name="restoration_notes" :value="$item?->restoration_notes" rows="2" help="FR-DIG-04: raw capture and restored version are both retained." /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Notes" name="notes" :value="$item?->notes" rows="2" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.media-items.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $item ? 'Save Changes' : 'Register Item' }}</button>
        </div>
    </div>
</form>
@endsection
