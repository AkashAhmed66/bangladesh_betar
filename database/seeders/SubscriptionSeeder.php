<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M18 — sample subscriptions & payment transactions across statuses.
 */
class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $premium = Plan::query()->where('code', 'premium')->first();
        if (! $premium) {
            return;
        }

        $scenarios = [
            // [email, status, cycle, method, payment_status]
            ['listener1@example.com', 'active', 'monthly', 'bkash', 'completed'],
            ['listener2@example.com', 'active', 'annual', 'card', 'completed'],
            ['listener3@example.com', 'trialing', 'monthly', 'nagad', 'pending'],
            ['listener4@example.com', 'grace', 'monthly', 'bkash', 'failed'],
            ['listener5@example.com', 'expired', 'monthly', 'rocket', 'refunded'],
        ];

        foreach ($scenarios as [$email, $status, $cycle, $method, $paymentStatus]) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $startedAt = match ($status) {
                'trialing' => now()->subDays(3),
                'expired' => now()->subMonths(4),
                default => now()->subDays(random_int(10, 200)),
            };

            $subscription = Subscription::query()->updateOrCreate(
                ['user_id' => $user->id, 'plan_id' => $premium->id],
                [
                    'status' => $status,
                    'billing_cycle' => $cycle,
                    'started_at' => $startedAt,
                    'trial_ends_at' => $status === 'trialing' ? now()->addDays(4) : null,
                    'ends_at' => match ($status) {
                        'expired' => now()->subMonths(3),
                        'grace' => now()->subDays(2),
                        'trialing' => now()->addDays(4),
                        default => now()->addDays($cycle === 'annual' ? 300 : 20),
                    },
                    'grace_ends_at' => $status === 'grace' ? now()->addDays(5) : null,
                    'auto_renew' => in_array($status, ['active', 'trialing'], true),
                    'purchase_channel' => 'gateway',
                ],
            );

            $amount = $cycle === 'annual' ? $premium->price_annual : $premium->price_monthly;

            Payment::query()->firstOrCreate(
                ['user_id' => $user->id, 'subscription_id' => $subscription->id, 'method' => $method],
                [
                    'invoice_no' => Payment::nextInvoiceNo(),
                    'amount' => $amount,
                    'currency' => 'BDT',
                    'status' => $paymentStatus,
                    'gateway_reference' => strtoupper($method).'-'.strtoupper(substr(md5($email), 0, 10)),
                    'paid_at' => $paymentStatus === 'completed' ? $startedAt : null,
                    'refunded_amount' => $paymentStatus === 'refunded' ? $amount : 0,
                    'refund_reason' => $paymentStatus === 'refunded' ? 'Duplicate charge reported by subscriber.' : null,
                    'refunded_at' => $paymentStatus === 'refunded' ? now()->subMonths(3) : null,
                ],
            );
        }

        $this->command?->info('Subscriptions: '.count($scenarios).' scenarios with payments seeded');
    }
}
