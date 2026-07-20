<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorySubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M10 — listener story submissions review queue (FR-EVT-05).
 */
class StorySubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $submissions = StorySubmission::query()
            ->with(['user', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.story-submissions.index', compact('submissions'));
    }

    public function review(Request $request, StorySubmission $submission): RedirectResponse
    {
        $this->authorize('submissions.review');

        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'accepted', 'rejected'])],
            'review_notes' => ['nullable', 'string'],
        ]);

        $submission->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Submission reviewed.');
    }
}
