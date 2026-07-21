<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorySubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * M10 — listener story submissions review queue (FR-EVT-05).
 * The submissions list itself is rendered by StoryController@index
 * (Stories + Submissions share one admin page); this controller only
 * handles the review action.
 */
class StorySubmissionController extends Controller
{
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
