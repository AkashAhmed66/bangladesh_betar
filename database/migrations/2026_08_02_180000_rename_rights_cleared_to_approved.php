<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rights vocabulary rename: "cleared" → "approved" (FR-CPR-04/05). Applies to
 * rights_records.status and the mirrored audio_assets.rights_status; all code
 * and UI now use "approved". Idempotent (scoped by the old value) — the
 * deploy entrypoint runs `migrate` on every boot.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('rights_records')->where('status', 'cleared')->update(['status' => 'approved']);
        DB::table('audio_assets')->where('rights_status', 'cleared')->update(['rights_status' => 'approved']);
    }

    public function down(): void
    {
        DB::table('rights_records')->where('status', 'approved')->update(['status' => 'cleared']);
        DB::table('audio_assets')->where('rights_status', 'approved')->update(['rights_status' => 'cleared']);
    }
};
