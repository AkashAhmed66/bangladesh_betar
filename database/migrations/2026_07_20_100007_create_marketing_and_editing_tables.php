<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M11 — voice artist profiles (FR-MKT-01)
        Schema::create('voice_artists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('artist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('gender', 10)->nullable();
            $table->string('age_range', 20)->nullable();   // child | young | adult | senior
            $table->string('languages')->nullable();       // comma list e.g. "Bangla, English"
            $table->string('accent', 50)->nullable();
            $table->string('tone', 50)->nullable();        // warm | authoritative | energetic ...
            $table->string('style', 50)->nullable();       // commercial | narration | promo ...
            $table->string('sample_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // M11 — scripts with version control (FR-MKT-04)
        Schema::create('scripts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->unsignedInteger('version_number')->default(1);
            $table->foreignId('parent_script_id')->nullable()->constrained('scripts')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft | approved | archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // M11 — marketing production campaigns (FR-MKT-05/06)
        Schema::create('marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('client_name')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            // draft | in_production | pending_approval | approved | completed | archived
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('usage_rights_start')->nullable();
            $table->date('usage_rights_end')->nullable()->index(); // expiry alerts (FR-MKT-06)
            $table->foreignId('final_asset_id')->nullable()->constrained('audio_assets')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // M11 — recording takes / channel variants (FR-MKT-07)
        Schema::create('campaign_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('script_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('voice_artist_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('radio'); // radio | tv | social
            $table->unsignedInteger('take_number')->default(1);
            $table->string('file_path')->nullable();
            $table->boolean('is_final')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // M12 — non-destructive edit sessions (FR-EDT-05)
        Schema::create('edit_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_version_id')->nullable()->constrained('audio_versions')->nullOnDelete();
            $table->foreignId('output_version_id')->nullable()->constrained('audio_versions')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->json('edl')->nullable(); // edit decision list: [{op, start, end, params}]
            $table->string('status', 20)->default('in_progress')->index();
            // in_progress | submitted | approved | rejected | restored
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edit_sessions');
        Schema::dropIfExists('campaign_assets');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('scripts');
        Schema::dropIfExists('voice_artists');
    }
};
