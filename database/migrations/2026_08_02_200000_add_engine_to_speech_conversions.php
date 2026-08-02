<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track which TTS engine produced each conversion: `neural` (Piper/MMS
 * sidecar) or `espeak` (in-container fallback). Guarded — the deploy
 * entrypoint runs `migrate` on every boot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('speech_conversions', 'engine')) {
            Schema::table('speech_conversions', function (Blueprint $table): void {
                $table->string('engine', 10)->nullable()->after('voice');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('speech_conversions', 'engine')) {
            Schema::table('speech_conversions', function (Blueprint $table): void {
                $table->dropColumn('engine');
            });
        }
    }
};
