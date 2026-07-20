@extends('layouts.admin')

@section('title', 'Workflows')

@section('content')
<x-page-header title="Approval Workflows" subtitle="Configurable multi-stage review chains per content type (FR-WRK-01)">
    @can('workflows.manage')
        <a href="{{ route('admin.workflows.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Workflow</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Workflow</th><th>Content Type</th><th>Stages</th><th>Escalation</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($workflows as $workflow)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $workflow->name }}</p>
                            @if ($workflow->description)
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($workflow->description, 70) }}</p>
                            @endif
                        </td>
                        <td><span class="badge-slate">{{ ucfirst($workflow->content_type) }}</span></td>
                        <td class="text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ $workflow->stages_count }} stage{{ $workflow->stages_count === 1 ? '' : 's' }}</td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">{{ $workflow->escalation_hours }}h</td>
                        <td><x-status-badge :status="$workflow->is_active ? 'active' : 'inactive'" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('workflows.manage')
                                    <a href="{{ route('admin.workflows.edit', $workflow) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.workflows.destroy', $workflow)" confirm="Delete workflow “{{ $workflow->name }}” and its stages?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="workflow" title="No workflows configured" message="Define an approval chain to route content for review." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($workflows->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $workflows->links() }}</div>
    @endif
</div>
@endsection
