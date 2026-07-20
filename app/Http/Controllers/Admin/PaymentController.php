<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M18 — payment ledger and refunds (FR-SUB-04, FR-SUB-13).
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['user', 'subscription.plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'revenue' => (float) Payment::query()->where('status', 'completed')->sum('amount'),
            'refunded' => (float) Payment::query()->sum('refunded_amount'),
            'count' => Payment::query()->count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('payments.refund');

        $refundable = round($payment->amount - $payment->refunded_amount, 2);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$refundable],
            'refund_reason' => ['required', 'string', 'max:1000'],
        ]);

        $refundedTotal = round($payment->refunded_amount + (float) $data['amount'], 2);

        // Payment uses the Auditable trait, so this update is recorded in the audit trail.
        $payment->update([
            'refunded_amount' => $refundedTotal,
            'refund_reason' => $data['refund_reason'],
            'refunded_by' => $request->user()->id,
            'refunded_at' => now(),
            'status' => $refundedTotal >= $payment->amount ? 'refunded' : 'partially_refunded',
        ]);

        return back()->with('success', 'Refund of ৳'.number_format((float) $data['amount'], 2).' processed for '.$payment->invoice_no.'.');
    }
}
