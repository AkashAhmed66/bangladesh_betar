<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M11 (marketing production) was removed from the product; drop its tables and
 * the permissions/role that gated it. Fresh installs never create these
 * (the create-migration no longer defines them) — this cleans up existing DBs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('campaign_assets'); // references the other three
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('scripts');
        Schema::dropIfExists('voice_artists');

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('name', 'like', 'marketing.%')
                ->orWhere('name', 'like', 'analytics.%')
                ->delete();
        }
        if (Schema::hasTable('roles')) {
            DB::table('roles')->where('name', 'Marketing User')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible — the marketing module was removed from the application.
    }
};
