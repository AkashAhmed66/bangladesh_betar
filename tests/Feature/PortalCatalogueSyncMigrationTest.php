<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PortalCatalogueSyncMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_migration_syncs_portal_content_on_an_existing_installation(): void
    {
        Storage::fake('public');
        User::factory()->create([
            'email' => 'admin@betar.gov.bd',
            'user_type' => 'staff',
            'status' => 'active',
        ]);

        $migration = require database_path('migrations/2026_09_13_000000_sync_portal_catalogue_for_existing_installs.php');
        $migration->up();

        $this->assertGreaterThan(0, NewsCategory::query()->count());
        $this->assertGreaterThan(0, WatchCategory::query()->count());
        $this->assertGreaterThan(0, NewsArticle::query()->count());
        $this->assertGreaterThan(0, WatchShow::query()->count());
        Storage::disk('public')->assertExists('portal/demo/news-hero.png');
        Storage::disk('public')->assertExists('portal/demo/watch-innovation.png');
        Storage::disk('public')->assertExists('news-artwork/train.webp');
    }
}
