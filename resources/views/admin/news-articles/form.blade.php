@extends('layouts.admin')

@section('title', $article ? 'Edit News Article' : 'New News Article')

@section('content')
<x-page-header :title="$article ? 'Edit: '.$article->title : 'Create News Article'" subtitle="Create the story, complete editorial approval, then publish it to the public News portal.">
    @if ($article)
        <div class="flex flex-wrap items-center gap-2">
            @if (! $article->is_published && in_array($article->approval_status, ['draft', 'rejected', 'changes_requested'], true))
                <form method="POST" action="{{ route('admin.news-articles.submit', $article) }}">@csrf<button class="btn-accent"><x-icon name="upload" class="size-4" /> {{ __('Submit for Approval') }}</button></form>
            @endif
            @can('news.publish')
                @if ($article->is_published)
                    <form method="POST" action="{{ route('admin.news-articles.unpublish', $article) }}">@csrf<button class="btn-secondary"><x-icon name="eye" class="size-4" /> {{ __('Unpublish') }}</button></form>
                @elseif ($article->approval_status === 'approved')
                    <form method="POST" action="{{ route('admin.news-articles.publish', $article) }}">@csrf<button class="btn-primary"><x-icon name="check-badge" class="size-4" /> {{ __('Publish') }}</button></form>
                @endif
            @endcan
        </div>
    @endif
</x-page-header>

@if ($article)
    @php $activeApproval = $article->approvals->sortByDesc('submitted_at')->first(); @endphp
    <div class="mb-5 max-w-4xl rounded-xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Editorial workflow') }}</p><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __($activeApproval?->currentStage?->name ?? ($article->approval_status === 'approved' ? 'Approval complete — ready to publish' : 'Save the final draft, then submit it for approval.')) }}</p></div>
            <div class="flex items-center gap-2"><x-status-badge :status="$article->approval_status" /><span class="{{ $article->is_published ? 'badge-emerald' : 'badge-slate' }}">{{ __($article->is_published ? 'Public' : 'Not public') }}</span></div>
        </div>
    </div>
@endif

<form method="POST" action="{{ $article ? route('admin.news-articles.update', $article) : route('admin.news-articles.store') }}" enctype="multipart/form-data" class="max-w-6xl">
    @csrf
    @if ($article) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input :label="__('Headline (English)')" name="title" :value="$article?->title" required />
            <x-form.input :label="__('Headline (Bangla)')" name="title_bn" :value="$article?->title_bn" />
            <x-form.input :label="__('URL slug')" name="slug" :value="$article?->slug" required help="Use lowercase words separated by hyphens." />
            <x-form.select :label="__('Category')" name="category" :value="$article?->category" required :options="$categories" help="Managed from News → Categories and reflected in the public portal." />
            <x-form.textarea :label="__('Summary (English)')" name="summary" :value="$article?->summary" rows="3" required help="Short introduction used on story cards and at the top of the article." />
            <x-form.textarea :label="__('Summary (Bangla)')" name="summary_bn" :value="$article?->summary_bn" rows="3" />
            <x-form.textarea :label="__('Article body (English)')" name="body_text" :value="$article ? implode(PHP_EOL.PHP_EOL, $article->body ?? []) : null" rows="12" required help="Separate paragraphs with one blank line." />
            <x-form.textarea :label="__('Article body (Bangla)')" name="body_text_bn" :value="$article ? implode(PHP_EOL.PHP_EOL, $article->body_bn ?? []) : null" rows="12" help="প্রতিটি অনুচ্ছেদের মাঝে একটি ফাঁকা লাইন রাখুন।" />
            <x-form.artwork-upload class="sm:col-span-2" :current-path="$article?->image_path" label="Required lead image" :wide="true" :required="true" :allow-remove="false" help="Required. JPG, PNG or WebP, up to 8 MB. A wide 16:9 image is recommended and becomes the first gallery image." />

            <section class="sm:col-span-2 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/60">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ __('Article media gallery') }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Add multiple images, uploaded videos, YouTube videos, audio recordings and supporting documents. The public article groups them by type and provides previous/next controls.') }}</p>
                </div>

                @if ($article?->media?->isNotEmpty())
                    <div class="border-b border-slate-200 p-5 dark:border-slate-700">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Existing gallery files') }}</p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($article->media as $media)
                                <label class="group flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 transition hover:border-rose-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-rose-700">
                                    @if ($media->media_type === 'image')
                                        <img src="{{ asset('storage/'.$media->path) }}" alt="" class="size-12 shrink-0 rounded-lg object-cover">
                                    @else
                                        <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-slate-100 text-xs font-black uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-300">{{ substr($media->media_type, 0, 3) }}</span>
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $media->original_name }}</span>
                                        <span class="mt-0.5 block text-[11px] capitalize text-slate-400">{{ $media->media_type }} · {{ number_format($media->size_bytes / 1048576, 1) }} MB</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                        <input type="checkbox" name="remove_media[]" value="{{ $media->id }}" @checked(in_array($media->id, old('remove_media', []))) class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-slate-600 dark:bg-slate-800">
                                        {{ __('Remove') }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    @foreach ([
                        ['images', 'Additional images', 'image/jpeg,image/png,image/webp', 'Up to 30 images, 8 MB each'],
                        ['videos', 'Videos', 'video/mp4,video/webm,video/quicktime', 'Up to 20 videos, 100 MB each'],
                        ['audios', 'Audio recordings', 'audio/*,.mp3,.wav,.ogg,.m4a,.aac,.flac', 'Up to 30 audio files, 50 MB each'],
                        ['documents', 'Documents', '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt', 'Up to 30 documents, 25 MB each'],
                    ] as [$field, $label, $accept, $help])
                        <label class="flex min-h-32 cursor-pointer flex-col justify-between rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 transition hover:border-indigo-400 hover:bg-indigo-50/40 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500 dark:hover:bg-indigo-950/20">
                            <span>
                                <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">{{ __($label) }}</span>
                                <span class="mt-1 block text-xs text-slate-400">{{ __($help) }}</span>
                            </span>
                            <input type="file" name="{{ $field }}[]" accept="{{ $accept }}" multiple class="mt-4 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 dark:text-slate-400 dark:file:bg-indigo-950 dark:file:text-indigo-300">
                            @error($field)<span class="form-error">{{ $message }}</span>@enderror
                            @error($field.'.*')<span class="form-error">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                </div>

                @php($youtubeLinks = old('youtube_links', ['']))
                <div
                    class="border-t border-slate-200 p-5 dark:border-slate-700"
                    x-data="{ links: {{ Js::from(array_values(is_array($youtubeLinks) && count($youtubeLinks) ? $youtubeLinks : [''])) }} }"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('YouTube videos') }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ __('Paste up to 20 YouTube watch, Shorts, live, embed or youtu.be links. Videos play inside the public article gallery.') }}</p>
                        </div>
                        <button type="button" class="btn-secondary btn-sm" @click="links.push('')"><x-icon name="plus" class="size-4" /> {{ __('Add YouTube link') }}</button>
                    </div>
                    <div class="mt-4 space-y-3">
                        <template x-for="(link, index) in links" :key="index">
                            <div class="flex items-center gap-2">
                                <input type="url" :name="`youtube_links[${index}]`" x-model="links[index]" placeholder="https://www.youtube.com/watch?v=..." class="form-input min-w-0 flex-1">
                                <button type="button" @click="links.length === 1 ? links[0] = '' : links.splice(index, 1)" class="btn-ghost btn-sm shrink-0 text-rose-600" aria-label="{{ __('Remove YouTube link') }}"><x-icon name="x" class="size-4" /></button>
                            </div>
                        </template>
                    </div>
                    @error('youtube_links')<span class="form-error mt-2">{{ $message }}</span>@enderror
                    @error('youtube_links.*')<span class="form-error mt-2">{{ $message }}</span>@enderror
                </div>
            </section>
            <x-form.input label="Read time (minutes)" name="read_time_minutes" type="number" :value="$article?->read_time_minutes ?? 3" required />
            <x-form.input label="Display position" name="position" type="number" :value="$article?->position ?? 0" required help="Lower numbers appear first after featured stories." />
            <div class="flex flex-col justify-end gap-4 pb-1">
                <x-form.toggle label="Featured story" name="is_featured" :checked="(bool) $article?->is_featured" help="Featured stories receive priority in the News layout." />
                <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-500 dark:bg-slate-800/70 dark:text-slate-400">Publishing is intentionally separate from editing. Any saved content change unpublishes the article and requires fresh approval.</p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.news-articles.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __($article ? 'Save as Draft' : 'Create Draft') }}</button>
        </div>
    </div>
</form>
@endsection
