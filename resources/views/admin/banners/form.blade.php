@extends('layouts.admin')

@section('title', $banner ? 'Edit Banner' : 'New Banner')

@section('content')
<x-page-header :title="$banner ? 'Edit: '.$banner->title : 'New Banner'"
               subtitle="Promotional banner with an optional deep-link or external URL target (FR-CUR-01)" />

<form method="POST" action="{{ $banner ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" class="max-w-3xl">
    @csrf
    @if ($banner) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$banner?->title" required />
            <x-form.input label="Title (Bangla)" name="title_bn" :value="$banner?->title_bn" />

            <div class="sm:col-span-2">
                <x-form.input label="Subtitle" name="subtitle" :value="$banner?->subtitle" help="Short supporting line shown under the title." />
            </div>

            <div class="sm:col-span-2">
                <x-form.input label="Image path" name="image_path" :value="$banner?->image_path" help="Path/URL to the banner artwork. Upload handled separately — leave blank if none." />
            </div>

            <x-form.select label="Target type" name="target_type" :value="$banner?->target_type" placeholder="None" :options="$targetTypes"
                           help="Where tapping the banner leads: an external URL or a content deep-link." />
            <x-form.input label="Target value" name="target_value" :value="$banner?->target_value" help="External URL, or the record ID for a deep-link target." />

            <x-form.input label="Position" name="position" type="number" :value="$banner?->position ?? 0" required help="Lower numbers appear first." />
            <div><!-- spacer --></div>

            <x-form.input label="Starts at" name="starts_at" type="datetime-local" :value="$banner?->starts_at?->format('Y-m-d\TH:i')" help="Leave blank to start immediately." />
            <x-form.input label="Ends at" name="ends_at" type="datetime-local" :value="$banner?->ends_at?->format('Y-m-d\TH:i')" help="Leave blank for no end date." />

            <div class="sm:col-span-2">
                <x-form.toggle label="Active" name="is_active" :checked="$banner?->is_active ?? true" help="Only active banners within their schedule window appear on the public home screen." />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.banners.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $banner ? 'Save Changes' : 'Create Banner' }}</button>
        </div>
    </div>
</form>
@endsection
