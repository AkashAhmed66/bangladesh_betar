@php
    $isNews = $kind === 'news';
    $moduleLabel = $isNews ? 'News Articles' : 'Watch Shows';
    $moduleRoute = $isNews ? 'admin.news-articles.index' : 'admin.watch-shows.index';
@endphp

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100"><x-icon :name="$isNews ? 'document-text' : 'play'" class="size-4.5 text-primary-600" /> {{ $moduleLabel }} — editorial approval</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Content cannot be published until this workflow is complete.</p>
        </div>
        <a href="{{ route($moduleRoute) }}" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Open module →</a>
    </div>
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Content</th><th>Category</th><th>Current stage</th><th>Submitted by</th><th>Waiting</th><th>Status</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($items as $approval)
                    @php $content = $approval->approvable; @endphp
                    <tr>
                        <td><p class="max-w-sm font-medium text-slate-800 dark:text-slate-100">{{ $content?->title ?? 'Deleted item #'.$approval->approvable_id }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $isNews ? ($content?->read_time_minutes.' min read') : (($content?->year ?: 'Year unset').' · '.($content?->rating ?: 'Not rated')) }}</p></td>
                        <td><span class="badge-slate">{{ $content?->category ?? '—' }}</span></td>
                        <td><p class="text-sm text-slate-700 dark:text-slate-200">{{ $approval->currentStage?->name ?? 'Complete' }}</p><p class="text-xs text-slate-400">{{ $approval->currentStage?->approver_role ?? '—' }}</p></td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $approval->submitter?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ $approval->submitted_at?->diffForHumans() ?? '—' }}</td>
                        <td><x-status-badge :status="$approval->status" />@if($approval->isActionableBy(auth()->user()))<p class="mt-1 text-[11px] font-semibold text-primary-600 dark:text-primary-400">Needs your action</p>@endif</td>
                        <td><div class="flex justify-end"><a href="{{ route('admin.approvals.show', $approval) }}" class="btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> {{ in_array($approval->status, ['pending', 'changes_requested'], true) ? 'Review' : 'History' }}</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state :icon="$isNews ? 'document-text' : 'play'" :title="$scope === 'approvals' ? 'Nothing needs your approval' : 'No editorial submissions yet'" :message="$scope === 'approvals' ? 'No '.$moduleLabel.' are waiting for your role.' : 'Submitted '.$moduleLabel.' will appear here with their complete history.'" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
