<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M18 — plans with configurable limits (FR-SUB-01)
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('code', 20)->unique(); // free | premium
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_annual', 10, 2)->default(0);
            $table->string('currency', 5)->default('BDT');
            $table->unsignedInteger('trial_days')->default(0);
            $table->json('features');
            // {ads: bool, skips_per_hour: int|null, max_quality_kbps: int|null,
            //  offline_downloads: bool, equalizer: bool, premium_content: "full"|"preview",
            //  preview_seconds: int}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status', 15)->default('active')->index();
            // trialing | active | grace | cancelled | expired
            $table->string('billing_cycle', 10)->default('monthly'); // monthly | annual
            $table->timestamp('started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('grace_ends_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('purchase_channel', 20)->default('gateway'); // gateway | google_play | apple_iap
            $table->timestamps();
        });

        // M18 — payment transactions incl. refunds (FR-SUB-04, FR-SUB-13)
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_no', 30)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5)->default('BDT');
            $table->string('method', 20); // bkash | nagad | rocket | card | google_play | apple_iap
            $table->string('status', 25)->default('pending')->index();
            // pending | completed | failed | refunded | partially_refunded | disputed
            $table->string('gateway_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->text('refund_reason')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->unsignedInteger('discount_percent');
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedInteger('max_uses')->default(0); // 0 = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
