<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M19 — raw playback events (FR-ANL-01)
        Schema::create('play_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('anonymous_id', 64)->nullable()->index(); // pseudonymized (FR-ANL-07)
            $table->string('event_type', 15)->index(); // play | pause | seek | replay | skip | progress | complete
            $table->unsignedInteger('position_seconds')->default(0);
            $table->string('platform', 10)->default('web'); // web | android | ios | admin
            $table->string('device', 60)->nullable();
            $table->string('region', 60)->nullable();
            $table->timestamp('created_at')->index();
        });

        // M19 — daily aggregates feeding dashboards & trending (FR-ANL-02/06)
        Schema::create('asset_stats_dailies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date')->index();
            $table->unsignedInteger('plays')->default(0);
            $table->unsignedInteger('unique_listeners')->default(0);
            $table->unsignedInteger('avg_listen_seconds')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('skip_rate', 5, 2)->default(0);
            $table->decimal('replay_rate', 5, 2)->default(0);
            $table->json('heatmap')->nullable(); // second-by-second density buckets (FR-ANL-03)
            $table->timestamps();
            $table->unique(['audio_asset_id', 'stat_date']);
        });

        // M24 — home screen sections (FR-CUR-01)
        // M24 — promotional banners
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('image_path')->nullable();
            $table->string('target_type', 40)->nullable(); // morph class or "url"
            $table->string('target_value')->nullable();    // id or external url
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('asset_stats_dailies');
        Schema::dropIfExists('play_events');
    }
};
