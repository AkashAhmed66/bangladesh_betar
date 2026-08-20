@extends('layouts.admin')

@section('title', $article ? 'Edit News Article' : 'New News Article')

@section('content')
<x-page-header :title="$article ? 'Edit: '.$article->title : 'Create News Article'" subtitle="Published articles appear immediately in the public News portal." />

<form method="POST" action="{{ $article ? route('admin.news-articles.update', $article) : route('admin.news-articles.store') }}" enctype="multipart/form-data" class="max-w-4xl">
    @csrf
    @if ($article) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-form.input label="Headline" name="title" :value="$article?->title" required /></div>
            <x-form.input label="URL slug" name="slug" :value="$article?->slug" required help="Use lowercase words separated by hyphens." />
            <x-form.select label="Category" name="category" :value="$article?->category ?? 'Bangladesh'" required :options="$categories" help="This controls which News category page displays the article." />
            <div class="sm:col-span-2"><x-form.textarea label="Summary" name="summary" :value="$article?->summary" rows="3" required help="Short introduction used on story cards and at the top of the article." /></div>
            <div class="sm:col-span-2">
                <x-form.textarea label="Article body" name="body_text" :value="$article ? implode(PHP_EOL.PHP_EOL, $article->body ?? []) : null" rows="12" required help="Separate paragraphs with one blank line." />
            </div>
            <x-form.artwork-upload class="sm:col-span-2" :current-path="$article?->image_path" label="Article image" :wide="true" help="JPG, PNG or WebP, up to 8 MB. A wide 16:9 image is recommended." />
            <x-form.input label="Read time (minutes)" name="read_time_minutes" type="number" :value="$article?->read_time_minutes ?? 3" required />
            <x-form.input label="Display position" name="position" type="number" :value="$article?->position ?? 0" required help="Lower numbers appear first after featured stories." />
            <x-form.input label="Publish date and time" name="published_at" type="datetime-local" :value="$article?->published_at?->setTimezone(config('portal.admin_timezone'))->format('Y-m-d\TH:i')" help="Bangladesh time. Leave empty to publish immediately." />
            <div class="flex flex-col justify-end gap-4 pb-1">
                <x-form.toggle label="Featured story" name="is_featured" :checked="(bool) $article?->is_featured" help="Featured stories receive priority in the News layout." />
                <x-form.toggle label="Published" name="is_published" :checked="(bool) $article?->is_published" help="Drafts are never returned by the public API." />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.news-articles.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $article ? 'Save Changes' : 'Create Article' }}</button>
        </div>
    </div>
</form>
@endsection
