@extends('layouts.admin')

@section('title', 'Audio Books')

@section('content')
<x-page-header title="Audio Books"
               subtitle="Narrated books in both male and female voices — approved books go live for premium listeners with read-along text.">
    <a href="{{ route('admin.audiobooks.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Add New Audio Book</a>
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Title</th><th>Language</th><th>Narrations</th><th>Status</th><th>Creator</th><th>Created</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($books as $book)
                    <tr>
                        <td>
                            <a href="{{ route('admin.audiobooks.show', $book) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $book->title }}</a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ strtoupper($book->source_type) }}@if ($book->used_ocr) · OCR @endif
                                @if ($book->characters) · {{ number_format($book->characters) }} chars @endif
                            </p>
                        </td>
                        <td class="text-sm">{{ $book->language === 'bn' ? 'Bangla' : ($book->language === 'en' ? 'English' : 'Auto') }}</td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">
                            @if ($book->duration_male || $book->duration_female)
                                ♂ {{ gmdate('i:s', $book->duration_male) }} · ♀ {{ gmdate('i:s', $book->duration_female) }}
                                @if ($book->engine)
                                    <span class="badge-{{ $book->engine === 'neural' ? 'green' : 'slate' }}">{{ $book->engine === 'neural' ? 'Neural' : 'Basic' }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td><x-status-badge :status="$book->status" /></td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $book->user?->name }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $book->created_at->diffForHumans() }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.audiobooks.show', $book) }}" class="btn-secondary btn-sm">
                                    <x-icon name="eye" class="size-4" />
                                    {{ $book->status === 'pending_approval' && auth()->user()->can('audiobooks.approve') ? 'Review' : 'View' }}
                                </a>
                                @if ($book->status === 'rejected' && ($book->user_id === auth()->id() || auth()->user()->can('records.view-all')))
                                    <form method="POST" action="{{ route('admin.audiobooks.submit', $book) }}">@csrf
                                        <button type="submit" class="btn-primary btn-sm"><x-icon name="arrow-path" class="size-4" /> Resubmit</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="megaphone" title="No audio books yet" message="Click “Add New Audio Book” to create one — both narrations are generated and it is submitted for approval automatically." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($books->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $books->links() }}</div>
    @endif
</div>

@if ($books->contains(fn ($b) => $b->isPending()))
    <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
@endif
@endsection
