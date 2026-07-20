@extends('layouts.admin')

@section('title', 'AI Suggestions')

@section('content')
<x-page-header title="AI Suggestions" subtitle="Draft metadata proposed by AI, pending human review (M16)" />

<div class="mb-4 flex items-start gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-200">
    <x-icon name="sparkles" class="mt-0.5 size-4 shrink-0" />
    <p>All AI-generated metadata is draft until verified by an authorized user (FR-AIF-06).</p>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="type" class="form-input w-44" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Asset</th><th>Type</th><th>Suggested Payload</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($suggestions as $suggestion)
                    <tr>
                        <td>
                            @if ($suggestion->audioAsset)
                                <a href="{{ route('admin.assets.show', $suggestion->audioAsset) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $suggestion->audioAsset->title }}</a>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $suggestion->audioAsset->archive_no }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><span class="badge-purple">{{ ucfirst(str_replace('_', ' ', $suggestion->suggestion_type)) }}</span></td>
                        <td class="max-w-sm">
                            <code class="block truncate text-xs text-slate-500 dark:text-slate-400" title="{{ json_encode($suggestion->payload) }}">{{ Str::limit(json_encode($suggestion->payload), 120) }}</code>
                        </td>
                        <td><x-status-badge :status="$suggestion->status" /></td>
                        <td class="text-right">
                            @if ($suggestion->status === 'pending')
                                @can('ai-suggestions.review')
                                    <div class="flex items-center justify-end gap-1">
                                        <form method="POST" action="{{ route('admin.ai-suggestions.review', $suggestion) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn-primary btn-sm"><x-icon name="check-badge" class="size-4" /> Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ai-suggestions.review', $suggestion) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-danger btn-sm"><x-icon name="x" class="size-4" /> Reject</button>
                                        </form>
                                    </div>
                                @endcan
                            @else
                                <span class="text-xs text-slate-400">Reviewed{{ $suggestion->reviewer ? ' · '.$suggestion->reviewer->name : '' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="sparkles" title="No AI suggestions" message="AI-generated metadata drafts will appear here for review." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($suggestions->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $suggestions->links() }}</div>
    @endif
</div>
@endsection
