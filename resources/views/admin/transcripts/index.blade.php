@extends('layouts.admin')

@section('title', 'Transcripts')

@section('content')
<x-page-header title="Transcripts & Lyrics" subtitle="Timed transcripts, lyrics and subtitles (M16)" />

<div class="mb-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
    <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
    <p>AI-generated output stays a draft until verified by an authorized user (FR-AIF-06). Verified transcripts feed the full-text search index (FR-AIF-07).</p>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="verified" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All states</option>
                <option value="1" @selected(request('verified') === '1')>Verified</option>
                <option value="0" @selected(request('verified') === '0')>Unverified</option>
            </select>
            <select name="ai" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All sources</option>
                <option value="1" @selected(request('ai') === '1')>AI generated</option>
                <option value="0" @selected(request('ai') === '0')>Human</option>
            </select>
            <select name="type" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach (['transcript' => 'Transcript', 'lyrics' => 'Lyrics', 'subtitle' => 'Subtitle'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Asset</th><th>Type</th><th>Language</th><th>Source</th><th>State</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($transcripts as $transcript)
                    <tr>
                        <td>
                            @if ($transcript->audioAsset)
                                <a href="{{ route('admin.assets.show', $transcript->audioAsset) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $transcript->audioAsset->title }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transcript->audioAsset->archive_no }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><span class="badge-slate">{{ ucfirst($transcript->transcript_type) }}</span></td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $transcript->language?->name ?? '—' }}</td>
                        <td>
                            @if ($transcript->is_ai_generated)
                                <span class="badge-purple">AI generated</span>
                            @else
                                <span class="badge-blue">Human</span>
                            @endif
                        </td>
                        <td>
                            @if ($transcript->is_verified)
                                <span class="badge-green">Verified</span>
                                @if ($transcript->verifier)<p class="mt-0.5 text-xs text-slate-400">{{ $transcript->verifier->name }}</p>@endif
                            @else
                                <span class="badge-amber">Unverified draft</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @unless ($transcript->is_verified)
                                @can('transcripts.manage')
                                    <form method="POST" action="{{ route('admin.transcripts.verify', $transcript) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-primary btn-sm"><x-icon name="check-badge" class="size-4" /> Verify</button>
                                    </form>
                                @endcan
                            @else
                                <span class="text-slate-300 dark:text-slate-600">—</span>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="document-text" title="No transcripts" message="Transcripts, lyrics and subtitles will appear here for verification." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transcripts->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $transcripts->links() }}</div>
    @endif
</div>
@endsection
