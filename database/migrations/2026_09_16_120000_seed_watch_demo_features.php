<?php

declare(strict_types=1);

use Database\Seeders\WatchDemoExpansionSeeder;
use Database\Seeders\WatchMetadataSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Install the current Watch demo media/metadata on existing installations.
     * Both seeders are guarded and preserve an administrator's edits/uploads.
     */
    public function up(): void
    {
        if (! Schema::hasTable('watch_shows') || ! Schema::hasTable('watch_episodes')) {
            return;
        }

        $this->runSeeder(WatchDemoExpansionSeeder::class);
        $this->runSeeder(WatchMetadataSeeder::class);
    }

    public function down(): void
    {
        // Demo catalogue data is retained on rollback; never delete media.
    }

    /** @param class-string<Seeder> $seeder */
    private function runSeeder(string $seeder): void
    {
        app($seeder)->run();
    }
};
