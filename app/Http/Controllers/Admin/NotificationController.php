<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * M30 — in-app notifications: approval stages, AI moderation and rights
 * clearance events land here (and in the topbar bell).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Lightweight poll for the toaster: unread count plus any notifications
     * that arrived after `since` (so the client can pop them up with sound).
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $since = null;
        if ($request->filled('since')) {
            try {
                $since = Carbon::parse($request->query('since'));
            } catch (\Throwable) {
                $since = null;
            }
        }

        $fresh = $since
            ? $user->unreadNotifications()->where('created_at', '>', $since)->latest()->take(5)->get()
            : collect();

        return response()->json([
            'now' => now()->toISOString(),
            'unread' => $user->unreadNotifications()->count(),
            'new' => $fresh->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'url' => route('admin.notifications.open', $n->id, absolute: false),
            ])->values(),
        ]);
    }

    /** Open a notification: mark read, then follow its target URL. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Legacy notifications may contain an absolute :8080 URL. Strip its
        // origin at click time so redirects always reuse the current request's
        // scheme, host and port (for production, :15000).
        $storedUrl = $notification->data['url'] ?? null;
        $url = is_string($storedUrl) ? AdminUrl::relative($storedUrl) : null;

        return $url ? redirect($url) : redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
