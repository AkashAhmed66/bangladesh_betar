<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\BroadcastChannelSeeder;
use Database\Seeders\ListenArtworkExpansionSeeder;
use Database\Seeders\WatchDemoExpansionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair the Listen artwork and Watch Live catalogue for installations
     * that already applied the initial portal-catalogue migration.
     *
     * Fresh databases are handled by DatabaseSeeder after all migrations, so
     * this intentionally waits until an existing installation has a user.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')
            || ! User::query()->exists()
            || ! Schema::hasTable('broadcast_channels')) {
            return;
        }

        // The Watch seeder installs the poster used by the video channel.
        $this->runSeeder(WatchDemoExpansionSeeder::class);
        $this->runSeeder(BroadcastChannelSeeder::class);
        $this->runSeeder(ListenArtworkExpansionSeeder::class);
    }

    public function down(): void
    {
        // Seeded catalogue and artwork are content; never remove them on
        // rollback or risk deleting an administrator's uploaded files.
    }

    /** @param class-string<Seeder> $seeder */
    private function runSeeder(string $seeder): void
    {
        app($seeder)->run();
    }
};
