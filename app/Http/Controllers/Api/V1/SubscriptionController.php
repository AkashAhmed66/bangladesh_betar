<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PromoCode;
use App\Models\Subscription;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * M18 — freemium plans, subscribe/upgrade, payment and entitlement.
 * Payment gateways (bKash/Nagad/Rocket/card) are simulated here.
 */
class SubscriptionController extends Controller
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    /** Public: plan comparison (FR-SUB-02). */
    public function plans(): JsonResponse
    {
        return PlanResource::collection(
            Plan::query()->where('is_active', true)->orderBy('price_monthly')->get(),
        )->response();
    }

    /** Current listener's entitlements + active subscription. */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        return response()->json([
            'entitlements' => $this->entitlements->entitlements($user),
            'subscription' => $subscription ? [
                'plan' => $subscription->plan?->code,
                'status' => $subscription->status,
                'billing_cycle' => $subscription->billing_cycle,
                'started_at' => $subscription->started_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'auto_renew' => (bool) $subscription->auto_renew,
            ] : null,
        ]);
    }

    /**
     * Subscribe to / upgrade Premium. Simulates a gateway charge and
     * activates entitlements immediately on success (FR-SUB-03/05).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_code' => ['required', Rule::in(['premium'])],
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual'])],
            'method' => ['required', Rule::in(['bkash', 'nagad', 'rocket', 'card'])],
            'promo_code' => ['nullable', 'string'],
            'start_trial' => ['boolean'],
        ]);

        $user = $request->user();
        $plan = Plan::query()->where('code', $data['plan_code'])->firstOrFail();

        $amount = $data['billing_cycle'] === 'annual' ? $plan->price_annual : $plan->price_monthly;
        $discount = 0;

        if (! empty($data['promo_code'])) {
            $promo = PromoCode::query()->where('code', $data['promo_code'])->first();
            if ($promo === null || ! $promo->isValid()) {
                return response()->json(['message' => 'Invalid or expired promo code.'], 422);
            }
            $discount = $amount * $promo->discount_percent / 100;
            $promo->increment('used_count');
        }

        $payable = max(0, $amount - $discount);
        $trial = (bool) ($data['start_trial'] ?? false) && $plan->trial_days > 0;

        $subscription = Subscription::query()->updateOrCreate(
            ['user_id' => $user->id, 'plan_id' => $plan->id],
            [
                'status' => $trial ? 'trialing' : 'active',
                'billing_cycle' => $data['billing_cycle'],
                'started_at' => now(),
                'trial_ends_at' => $trial ? now()->addDays($plan->trial_days) : null,
                'ends_at' => $trial ? now()->addDays($plan->trial_days)
                    : now()->addDays($data['billing_cycle'] === 'annual' ? 365 : 30),
                'auto_renew' => true,
                'purchase_channel' => 'gateway',
                'cancelled_at' => null,
            ],
        );

        // Simulate a successful gateway charge (skipped for a trial).
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'invoice_no' => Payment::nextInvoiceNo(),
            'amount' => $trial ? 0 : $payable,
            'currency' => $plan->currency,
            'method' => $data['method'],
            'status' => $trial ? 'pending' : 'completed',
            'gateway_reference' => strtoupper($data['method']).'-'.strtoupper(str()->random(10)),
            'paid_at' => $trial ? null : now(),
        ]);

        AuditLog::record('subscription_activated', $subscription, null, null, "Premium ({$data['billing_cycle']}) via {$data['method']}");

        return response()->json([
            'message' => $trial ? 'Free trial started.' : 'Premium activated.',
            'subscription' => [
                'status' => $subscription->status,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
            'invoice' => [
                'invoice_no' => $payment->invoice_no,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'discount_applied' => (float) $discount,
            ],
            'entitlements' => $this->entitlements->entitlements($user->fresh()),
        ], 201);
    }

    public function cancel(Request $request): JsonResponse
    {
        $subscription = $request->user()->activeSubscription;
        abort_if($subscription === null, 404, 'No active subscription.');

        // Remain active until period end, then downgrade (FR-SUB-08).
        $subscription->update(['auto_renew' => false, 'cancelled_at' => now()]);

        return response()->json([
            'message' => 'Auto-renewal cancelled. Premium remains active until the end of the period.',
            'active_until' => $subscription->ends_at?->toIso8601String(),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $payments = $request->user()->payments()->latest()->take(50)->get()
            ->map(fn (Payment $p) => [
                'invoice_no' => $p->invoice_no,
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'method' => $p->method,
                'status' => $p->status,
                'paid_at' => $p->paid_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $payments]);
    }
}
