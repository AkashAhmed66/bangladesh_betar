<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M16 — timed transcripts, lyrics, subtitles (D9)
        Schema::create('transcripts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transcript_type', 15)->default('transcript'); // transcript | lyrics | subtitle
            $table->json('lines')->nullable(); // [{start, end, speaker, text}]
            $table->longText('full_text')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->boolean('is_verified')->default(false); // FR-AIF-06
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // M16 — AI draft metadata suggestions pending human verification
        Schema::create('ai_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->string('suggestion_type', 30)->index();
            // summary | tags | genre | mood | language | speaker | duplicate | quality_score
            $table->json('payload'); // suggested values with confidence
            $table->string('status', 15)->default('pending')->index(); // pending | accepted | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('transcripts');
    }
};
