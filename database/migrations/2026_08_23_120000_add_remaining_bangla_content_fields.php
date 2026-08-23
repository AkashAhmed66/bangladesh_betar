<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add Bangla companions for public-facing descriptive content. */
    public function up(): void
    {
        $columns = [
            'episodes' => 'description_bn',
            'podcast_channels' => 'description_bn',
            'podcast_episodes' => 'description_bn',
            'playlists' => 'description_bn',
            'broadcast_channels' => 'description_bn',
            'plans' => 'description_bn',
            'stations' => 'description_bn',
        ];

        foreach ($columns as $table => $column) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->text($column)->nullable()->after('description'));
            }
        }

        if (Schema::hasTable('banners') && ! Schema::hasColumn('banners', 'subtitle_bn')) {
            Schema::table('banners', fn (Blueprint $table) => $table->string('subtitle_bn')->nullable()->after('subtitle'));
        }
    }

    public function down(): void
    {
        foreach (['episodes', 'podcast_channels', 'podcast_episodes', 'playlists', 'broadcast_channels', 'plans', 'stations'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'description_bn')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('description_bn'));
            }
        }

        if (Schema::hasTable('banners') && Schema::hasColumn('banners', 'subtitle_bn')) {
            Schema::table('banners', fn (Blueprint $table) => $table->dropColumn('subtitle_bn'));
        }
    }
};
