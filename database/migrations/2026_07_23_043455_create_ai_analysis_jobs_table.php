<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External audio-postmortem analysis (duplicate / violence / anti-government
     * detection + transcription) run against every uploaded asset. One row per
     * submission to the external service; an asset may have several if it is
     * re-ingested.
     */
    public function up(): void
    {
        Schema::create('ai_analysis_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();

            // External service correlation.
            $table->string('job_id')->nullable()->index();
            $table->string('source_api', 20)->default('analyze'); // analyze | check_copy
            $table->string('status', 15)->default('processing')->index(); // processing | done | error
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('polled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();

            // Transcription (mirrors into the transcripts table on completion too).
            $table->string('language', 20)->nullable();
            $table->decimal('duration_sec', 8, 2)->nullable();
            $table->longText('transcript')->nullable();
            $table->longText('transcript_readable')->nullable();
            $table->boolean('has_audio_fingerprint')->default(false);

            // Duplicate detection.
            $table->boolean('is_duplicate')->nullable();
            $table->decimal('audio_match_pct', 5, 2)->nullable();
            $table->decimal('content_match_pct', 5, 2)->nullable();
            $table->string('matched_job_id')->nullable();
            $table->string('matched_filename')->nullable();
            $table->timestamp('matched_uploaded_at')->nullable();

            // Content safety.
            $table->boolean('violence_detected')->nullable();
            $table->decimal('violence_confidence', 4, 3)->nullable();
            $table->json('violence_evidence')->nullable();
            $table->boolean('anti_government_detected')->nullable();
            $table->decimal('anti_government_confidence', 4, 3)->nullable();
            $table->json('anti_government_evidence')->nullable();
            $table->text('summary')->nullable();

            // Full payload, kept for audit/debugging.
            $table->json('raw_response')->nullable();

            // AI Reviewer decision when this job flagged the asset.
            $table->string('review_status', 15)->default('not_required')->index();
            // not_required | pending | approved | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_jobs');
    }
};
