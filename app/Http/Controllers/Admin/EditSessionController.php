<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M12 — read-only register of non-destructive edit sessions (FR-EDT-01/05).
 */
class EditSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = EditSession::query()
            ->visibleTo($request->user())
            ->with(['audioAsset', 'editor'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = ['in_progress', 'submitted', 'approved', 'rejected', 'restored'];

        return view('admin.edit-sessions.index', compact('sessions', 'statuses'));
    }
}
