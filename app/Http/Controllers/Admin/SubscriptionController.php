<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M18 — subscriber lifecycle management (FR-SUB-05).
 */
class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = Subscription::query()
            ->with(['user', 'plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('started_at')
            ->paginate(15)
            ->withQueryString();

        $stats = Subscription::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.subscriptions.index', compact('subscriptions', 'stats'));
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorize('subscriptions.manage');

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        return back()->with('success', 'Subscription cancelled.');
    }
}
