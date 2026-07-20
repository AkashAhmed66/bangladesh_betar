<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M26 — comment moderation queue (FR-ENG-01/03/05).
 */
class CommentModerationController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'hidden', 'removed'];

    /** Maps a moderator action to the resulting comment status. */
    private const ACTION_MAP = [
        'approve' => 'approved',
        'hide' => 'hidden',
        'remove' => 'removed',
    ];

    public function index(Request $request): View
    {
        $comments = Comment::query()
            ->with(['user', 'commentable', 'moderator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'hidden', 'removed')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.comments.index', [
            'comments' => $comments,
            'statuses' => self::STATUSES,
        ]);
    }

    public function moderate(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('moderation.manage');

        $data = $request->validate([
            'action' => ['required', Rule::in(array_keys(self::ACTION_MAP))],
        ]);

        $comment->update([
            'status' => self::ACTION_MAP[$data['action']],
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with('success', 'Comment '.self::ACTION_MAP[$data['action']].'.');
    }
}
