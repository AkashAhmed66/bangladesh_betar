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
    // 'deleted' is a virtual status: the comment was soft-deleted (e.g. by its
    // author). The record is retained here for the moderation trail — only a
    // permanent purge truly removes it.
    private const STATUSES = ['pending', 'approved', 'hidden', 'removed', 'deleted'];

    /** Maps a moderator action to the resulting comment status. */
    private const ACTION_MAP = [
        'approve' => 'approved',
        'hide' => 'hidden',
        'remove' => 'removed',
    ];

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $comments = Comment::query()
            ->withTrashed() // keep every record visible; nothing silently disappears
            ->with(['user', 'commentable', 'moderator'])
            ->when($status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->when($status !== '' && $status !== 'deleted',
                fn ($q) => $q->whereNull('deleted_at')->where('status', $status))
            ->orderByRaw('(deleted_at IS NOT NULL)') // active first, deleted last
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
