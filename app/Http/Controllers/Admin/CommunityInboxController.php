<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M26 — unified Community Inbox: abuse reports, content issues and general
 * feedback in one queue with a shared status workflow (FR-ENG-04/07/09).
 * Replaces the separate Reported Content / Issue Reports / Feedback screens.
 */
class CommunityInboxController extends Controller
{
    public function index(Request $request): View
    {
        $submissions = CommunitySubmission::query()
            ->with(['user', 'subject', 'handler'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw("FIELD(status, 'new', 'in_progress', 'resolved', 'dismissed')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.community-inbox.index', [
            'submissions' => $submissions,
            'types' => CommunitySubmission::TYPES,
            'statuses' => CommunitySubmission::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, CommunitySubmission $submission): RedirectResponse
    {
        $this->authorize('moderation.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(CommunitySubmission::STATUSES)],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->update($data + [
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        return back()->with('success', 'Marked as '.str_replace('_', ' ', $data['status']).'.');
    }
}
