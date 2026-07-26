@extends('layouts.admin')

@section('title', 'AI Moderation')

@section('content')
<x-page-header title="AI Moderation" subtitle="Assets the audio-postmortem service flagged as a possible duplicate, or containing violent / anti-government content (M16)" />

<div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
    <x-icon name="shield" class="mt-0.5 size-4 shrink-0" />
    <p>Every upload is screened automatically before it can proceed. A reject here is final — the asset can never be submitted for approval or published. Decisions stay on record below.</p>
</div>

<div x-data="{ open: false, action: '', url: '', title: '' }" @keydown.escape.window="open = false">

    {{-- Search + filters --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label for="q" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Search</label>
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Title, archive no, or uploader…" class="form-input w-full">
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Status</label>
            <select id="status" name="status" class="form-input">
                <option value="">All statuses</option>
                <option value="pending" @selected($status === 'pending')>Pending</option>
                <option value="approved" @selected($status === 'approved')>Accepted</option>
                <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            </select>
        </div>
        <div>
            <label for="flag" class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Flag</label>
            <select id="flag" name="flag" class="form-input">
                <option value="">All flags</option>
                <option value="duplicate" @selected($flag === 'duplicate')>Duplicate</option>
                <option value="violence" @selected($flag === 'violence')>Violence</option>
                <option value="anti_government" @selected($flag === 'anti_government')>Anti-government</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary"><x-icon name="funnel" class="size-4" /> Apply</button>
            @if ($search !== '' || $status !== '' || $flag !== '')
                <a href="{{ route('admin.ai-moderation.index') }}" class="btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="table-shell">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Flags</th>
                        <th>Uploaded by</th>
                        <th>Flagged</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $job = $asset->latestAiAnalysisJob;
                            $rs = $job?->review_status ?? 'pending';
                            $statusMap = [
                                'pending' => ['Pending', 'badge-amber'],
                                'approved' => ['Accepted', 'badge-green'],
                                'rejected' => ['Rejected', 'badge-red'],
                            ];
                            [$stLabel, $stClass] = $statusMap[$rs] ?? ['Pending', 'badge-amber'];
                            $isPending = $rs === 'pending' && $asset->status === 'ai_flagged';
                        @endphp
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
                            <td><span class="{{ $stClass }}">{{ $stLabel }}</span></td>
                            <td class="max-w-xs">
                                @if ($job?->review_comments)
                                    <p class="truncate text-sm text-slate-600 dark:text-slate-300" title="{{ $job->review_comments }}">{{ $job->review_comments }}</p>
                                    @if ($job->reviewer)
                                        <p class="text-xs text-slate-400">— {{ $job->reviewer->name }}{{ $job->reviewed_at ? ', '.$job->reviewed_at->diffForHumans() : '' }}</p>
                                    @endif
                                @else
                                    <span class="text-sm text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($isPending)
                                        @can('ai-moderation.review')
                                            <button type="button" class="btn-primary btn-sm"
                                                    @click="action = 'approve'; url = '{{ route('admin.ai-moderation.review', $asset) }}'; title = @js($asset->title); open = true">
                                                <x-icon name="check-badge" class="size-4" /> Accept
                                            </button>
                                            <button type="button" class="btn-danger btn-sm"
                                                    @click="action = 'reject'; url = '{{ route('admin.ai-moderation.review', $asset) }}'; title = @js($asset->title); open = true">
                                                <x-icon name="x" class="size-4" /> Reject
                                            </button>
                                        @endcan
                                    @endif
                                    <a href="{{ route('admin.ai-moderation.show', $asset) }}" class="btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="shield" title="Nothing to moderate" message="Flagged uploads and past decisions will appear here." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($assets->hasPages())
            <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $assets->links() }}</div>
        @endif
    </div>

    {{-- Decision modal (shared) — remark is mandatory for both Accept and Reject --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" style="display:none">
        <div class="w-full max-w-md rounded-xl bg-white shadow-2xl dark:bg-slate-900" @click.outside="open = false">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-800">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">
                    <span x-text="action === 'approve' ? 'Accept' : 'Reject'"></span> — <span class="text-slate-500 dark:text-slate-400" x-text="title"></span>
                </h3>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><x-icon name="x" class="size-4" /></button>
            </div>
            <form method="POST" :action="url">
                @csrf
                <input type="hidden" name="action" :value="action">
                <div class="space-y-2 p-5">
                    <label for="modal-comments" class="form-label">Remarks <span class="text-rose-500">*</span></label>
                    <textarea id="modal-comments" name="comments" rows="3" required class="form-input w-full"
                              placeholder="Explain your decision — this is recorded on the asset (required)."></textarea>
                    <p class="text-xs text-slate-400" x-show="action === 'reject'">A reject is final — the asset can never be submitted for approval or published.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3 dark:border-slate-800">
                    <button type="button" class="btn-secondary" @click="open = false">Cancel</button>
                    <button type="submit" :class="action === 'reject' ? 'btn-danger' : 'btn-primary'">
                        <span x-text="action === 'approve' ? 'Accept' : 'Reject'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
