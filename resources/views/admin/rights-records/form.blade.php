@extends('layouts.admin')

@section('title', $record ? 'Edit Rights Record' : 'New Rights Record')

@section('content')
<x-page-header :title="$record ? 'Edit Rights Record' : 'New Rights Record'"
               subtitle="Clearance terms for a single asset. Saving syncs the asset's rights status (FR-CPR-04)." />

<form method="POST" action="{{ $record ? route('admin.rights-records.update', $record) : route('admin.rights-records.store') }}" class="max-w-3xl">
    @csrf
    @if ($record) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form.select label="Audio asset" name="audio_asset_id" :value="$record?->audio_asset_id" required
                               placeholder="Select an asset…" :options="$assets->all()" />
            </div>
            <x-form.select label="Rights holder" name="rights_holder_id" :value="$record?->rights_holder_id"
                           placeholder="— Unknown / unassigned —" :options="$holders->all()" />
            <x-form.input label="Territory" name="territory" :value="$record?->territory ?? 'Bangladesh'" required />

            <div class="sm:col-span-2">
                <label class="form-label">Rights types <span class="text-rose-500">*</span></label>
                <div class="mt-1 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($rightsTypes as $type)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <input type="checkbox" name="rights_types[]" value="{{ $type }}"
                                   @checked(in_array($type, old('rights_types', $record?->rights_types ?? []), true))
                                   class="size-4 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ ucfirst($type) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('rights_types')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <x-form.input label="Valid from" name="valid_from" type="date" :value="$record?->valid_from?->format('Y-m-d')" />
            <x-form.input label="Valid until" name="valid_until" type="date" :value="$record?->valid_until?->format('Y-m-d')" help="Leave blank for perpetual. Expiry drives clearance alerts (FR-CPR-06)." />

            <x-form.select label="Status" name="status" :value="$record?->status ?? 'pending'" required
                           :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()"
                           help="Only 'cleared' assets can be published (FR-CPR-05)." />

            <div class="flex items-end">
                <x-form.toggle label="Royalty required" name="royalty_required" :checked="(bool) ($record?->royalty_required)" help="Ongoing royalty payments apply." />
            </div>

            <div class="sm:col-span-2"><x-form.textarea label="Royalty notes" name="royalty_notes" :value="$record?->royalty_notes" rows="2" /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Notes" name="notes" :value="$record?->notes" rows="2" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.rights-records.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $record ? 'Save Changes' : 'Create Record' }}</button>
        </div>
    </div>
</form>
@endsection
