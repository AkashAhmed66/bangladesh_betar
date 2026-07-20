<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M27 — advertisers & campaigns
        Schema::create('advertisers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertiser_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable()->index();
            $table->decimal('budget', 12, 2)->default(0);
            $table->unsignedBigInteger('impression_goal')->default(0);
            $table->unsignedTinyInteger('priority')->default(5); // 1 (highest) .. 9
            $table->string('status', 20)->default('draft')->index();
            // draft | pending_approval | active | paused | completed
            $table->json('targeting')->nullable(); // {categories: [], time_of_day: [], platforms: []}
            $table->unsignedInteger('frequency_cap_per_hour')->default(4); // per-listener capping
            $table->timestamps();
        });

        // M27 — ad asset library (FR-ADV-01)
        Schema::create('ad_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_campaign_id')->nullable()->constrained()->nullOnDelete(); // null => house/PSA
            $table->string('title');
            $table->string('ad_type', 15)->default('commercial')->index(); // commercial | house | psa
            $table->string('file_path')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 50)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('pending_approval')->index();
            // pending_approval | active | inactive | expired
            $table->timestamps();
        });

        // M27 — impressions / completions log (FR-ADV-06)
        Schema::create('ad_impressions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('anonymous_id', 64)->nullable();
            $table->string('slot', 15)->default('pre_roll'); // pre_roll | between
            $table->string('platform', 10)->default('web');
            $table->boolean('completed')->default(false);
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_impressions');
        Schema::dropIfExists('ad_assets');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('advertisers');
    }
};
