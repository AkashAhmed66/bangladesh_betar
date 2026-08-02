<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PDF-to-Speech tool (offline: poppler/tesseract extraction + espeak-ng TTS).
 * One row per conversion request. Guarded — the deploy entrypoint runs
 * `migrate` on every boot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speech_conversions')) {
            return;
        }

        Schema::create('speech_conversions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('source_type', 10);              // pdf | text
            $table->string('source_path')->nullable();      // uploaded PDF (private disk)
            $table->string('language', 5)->default('auto'); // en | bn | auto (resolved on run)
            $table->string('voice', 10)->default('female'); // male | female
            $table->string('status', 15)->default('queued')->index(); // queued | extracting | synthesizing | done | failed
            $table->text('error')->nullable();
            $table->string('output_path')->nullable();      // generated MP3 (private disk)
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('characters')->default(0);
            $table->boolean('used_ocr')->default(false);
            $table->longText('source_text')->nullable();    // pasted text, or extracted text (for review)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speech_conversions');
    }
};
