@extends('layouts.admin')

@section('title', 'AI Moderation')

@section('content')
<x-page-header title="AI Moderation" subtitle="Assets the audio-postmortem service flagged as a possible duplicate, or containing violent / anti-government content (M16)" />

<div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
    <x-icon name="shield" class="mt-0.5 size-4 shrink-0" />
    <p>Every upload is screened automatically before it can proceed. A reject here is final — the asset can never be submitted for approval or published.</p>
</div>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Asset</th><th>Flags</th><th>Uploaded by</th><th>Flagged</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($assets as $asset)
                    @php $job = $asset->latestAiAnalysisJob; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.ai-moderation.show', $asset) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $asset->title }}</a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $asset->archive_no }}</p>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @if ($job?->is_duplicate)<span class="badge-amber">Duplicate</span>@endif
                                @if ($job?->violence_detected)<span class="badge-red">Violence</span>@endif
                                @if ($job?->anti_government_detected)<span class="badge-red">Anti-government</span>@endif
                            </div>
                        </td>
                        <td class="text-sm">{{ $asset->uploader?->name ?? '—' }}</td>
                        <td class="text-sm text-slate-500 dark:text-slate-400">{{ $asset->updated_at?->diffForHumans() }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.ai-moderation.show', $asset) }}" class="btn-primary btn-sm"><x-icon name="eye" class="size-4" /> Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="shield" title="Nothing awaiting AI review" message="Flagged uploads will appear here for sign-off." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $assets->links() }}</div>
    @endif
</div>
@endsection
