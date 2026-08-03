@extends('layouts.admin')

@section('title', 'New Audio Book')

@section('content')
<x-page-header title="New Audio Book"
               subtitle="Upload a PDF or paste text — both a male and a female narration are generated. Review them, then submit the book for publication." />

<form method="POST" action="{{ route('admin.audiobooks.store') }}" enctype="multipart/form-data" class="max-w-3xl"
      x-data="{ mode: '{{ old('text') ? 'text' : 'pdf' }}' }">
    @csrf

    <div class="card">
        <div class="card-body space-y-5">
            <x-form.input label="Title" name="title" :value="old('title')" required
                          placeholder="e.g. ইতিহাসের গল্প — প্রথম অধ্যায়" />

            <div>
                <label class="form-label">Source</label>
                <div class="flex max-w-sm gap-1 rounded-lg border border-slate-200 p-0.5 dark:border-slate-700">
                    <button type="button" @click="mode = 'pdf'"
                            :class="mode === 'pdf' ? 'bg-primary-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                            class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition">PDF file</button>
                    <button type="button" @click="mode = 'text'"
                            :class="mode === 'text' ? 'bg-primary-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                            class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition">Paste text</button>
                </div>
            </div>

            <div x-show="mode === 'pdf'">
                <label class="form-label">PDF document</label>
                <input type="file" name="pdf" accept=".pdf"
                       class="form-input mt-1 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium dark:file:bg-slate-800">
                <p class="form-help">Digital PDFs are read directly; scanned PDFs — and Bangla PDFs whose text layer extracts garbled (a common font issue) — go through Bangla+English OCR automatically. Up to 50&nbsp;MB.</p>
                @error('pdf')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div x-show="mode === 'text'" x-cloak>
                <x-form.textarea label="Text" name="text" :value="old('text')" rows="10"
                                 help="English or Bangla — up to 120,000 characters." />
            </div>

            <div class="max-w-sm">
                <x-form.select label="Language" name="language" :value="old('language', 'auto')" required
                               :options="['auto' => 'Auto-detect', 'bn' => 'Bangla', 'en' => 'English']"
                               help="Both a male and a female narration are generated automatically." />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.audiobooks.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary"><x-icon name="megaphone" class="size-4" /> Create Audio Book</button>
        </div>
    </div>
</form>
@endsection
