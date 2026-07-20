<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\AudioAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M13 — approval queue and multi-stage review actions (FR-WRK-03/05).
 */
class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $scope = $request->query('scope') === 'all' ? 'all' : 'mine';

        $approvals = Approval::query()
            ->when(
                $scope === 'mine',
                fn ($q) => $q->actionableBy($request->user()),
                fn ($q) => $q->pending(),
            )
            ->with(['approvable', 'workflow', 'currentStage', 'submitter'])
            ->orderBy('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.approvals.index', compact('approvals', 'scope'));
    }

    public function show(Approval $approval): View
    {
        $approval->load(['approvable', 'workflow.stages', 'currentStage', 'submitter', 'actions.user']);

        return view('admin.approvals.show', compact('approval'));
    }

    public function act(Request $request, Approval $approval): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'request_changes'])],
            'comments' => ['nullable', 'string', 'required_if:action,reject', 'required_if:action,request_changes'],
        ]);

        if (! in_array($approval->status, ['pending', 'changes_requested'], true)) {
            return back()->with('error', 'This approval has already been completed.');
        }

        $stage = $approval->currentStage;
        $asset = $approval->approvable instanceof AudioAsset ? $approval->approvable : null;

        ApprovalAction::query()->create([
            'approval_id' => $approval->id,
            'workflow_stage_id' => $stage?->id,
            'user_id' => $request->user()->id,
            'action' => match ($data['action']) {
                'approve' => 'approved',
                'reject' => 'rejected',
                'request_changes' => 'correction_requested',
            },
            'comments' => $data['comments'] ?? null,
        ]);

        if ($data['action'] === 'approve') {
            $next = $stage?->next();

            if ($next) {
                $approval->update(['current_stage_id' => $next->id]);
                $message = "Approved. Advanced to stage: {$next->name}.";
            } else {
                $approval->update(['status' => 'approved', 'completed_at' => now()]);
                $asset?->update(['status' => 'approved']);
                $message = 'Approved. Workflow complete.';
            }
        } elseif ($data['action'] === 'reject') {
            $approval->update(['status' => 'rejected', 'completed_at' => now()]);
            $asset?->update(['status' => 'rejected']);
            $message = 'Approval rejected.';
        } else {
            $approval->update(['status' => 'changes_requested']);
            $asset?->update(['status' => 'draft']);
            $message = 'Changes requested from the submitter.';
        }

        return back()->with('success', $message);
    }
}
