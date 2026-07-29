<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The ad creative is an existing audio asset (audio_asset_id on ad_campaigns
     * and ad_impressions). The original advertisement-tables migration
     * (2026_07_20_100014) was later edited in place to add that column, so any
     * database that had already run the ORIGINAL version never received it —
     * Laravel considers that migration "ran" and won't re-apply it. Creating an
     * ad campaign then fails with: Unknown column 'audio_asset_id'.
     *
     * This forward-only, idempotent migration adds the column wherever it is
     * missing. It is a no-op on databases (e.g. local) that already have it.
     */
    public function up(): void
    {
        if (Schema::hasTable('ad_campaigns') && ! Schema::hasColumn('ad_campaigns', 'audio_asset_id')) {
            Schema::table('ad_campaigns', function (Blueprint $table): void {
                $table->foreignId('audio_asset_id')->nullable()->after('advertiser_id')
                    ->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('ad_impressions') && ! Schema::hasColumn('ad_impressions', 'audio_asset_id')) {
            Schema::table('ad_impressions', function (Blueprint $table): void {
                $table->foreignId('audio_asset_id')->nullable()->after('ad_campaign_id')
                    ->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['ad_campaigns', 'ad_impressions'] as $name) {
            if (Schema::hasColumn($name, 'audio_asset_id')) {
                Schema::table($name, function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('audio_asset_id');
                });
            }
        }
    }
};
