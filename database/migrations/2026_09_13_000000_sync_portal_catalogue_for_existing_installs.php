<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\BroadcastChannelSeeder;
use Database\Seeders\ListenArtworkExpansionSeeder;
use Database\Seeders\NewsArtworkExpansionSeeder;
use Database\Seeders\PortalCategorySeeder;
use Database\Seeders\PortalContentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WatchDemoExpansionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Synchronize the portal catalogue once when an existing installation
     * receives this release. Fresh databases are seeded by DatabaseSeeder
     * after migrations, so this migration deliberately skips them.
     */
    public function up(): void
    {
        if (! Schema::hasTable('news_articles')
            || ! Schema::hasTable('watch_shows')
            || ! User::query()->exists()) {
            return;
        }

        // Existing databases may predate the News/Watch permissions. Sync the
        // built-in roles before the sidebar and record visibility are used.
        $this->runSeeder(RolePermissionSeeder::class);
        $this->runSeeder(PortalCategorySeeder::class);
        $this->runSeeder(PortalContentSeeder::class);
        $this->runSeeder(WatchDemoExpansionSeeder::class);
        $this->runSeeder(BroadcastChannelSeeder::class);
        $this->runSeeder(NewsArtworkExpansionSeeder::class);
        $this->runSeeder(ListenArtworkExpansionSeeder::class);
    }

    public function down(): void
    {
        // Portal catalogue rows are content, not schema. Never delete them
        // automatically when rolling back an application release.
    }

    /** @param class-string<Seeder> $seeder */
    private function runSeeder(string $seeder): void
    {
        app($seeder)->run();
    }
};
