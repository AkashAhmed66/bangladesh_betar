<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Third audio-book narration: the "Enhanced" track from the Google-backed
 * TTS API (alongside the male and female voices). Guarded — the deploy
 * entrypoint runs `migrate` on every boot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_books', function (Blueprint $table): void {
            if (! Schema::hasColumn('audio_books', 'audio_enhanced_path')) {
                $table->string('audio_enhanced_path')->nullable()->after('audio_female_path');
            }
            if (! Schema::hasColumn('audio_books', 'duration_enhanced')) {
                $table->unsignedInteger('duration_enhanced')->default(0)->after('duration_female');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audio_books', function (Blueprint $table): void {
            foreach (['audio_enhanced_path', 'duration_enhanced'] as $column) {
                if (Schema::hasColumn('audio_books', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
