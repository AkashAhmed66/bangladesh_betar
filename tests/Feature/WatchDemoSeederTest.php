<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\WatchShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WatchDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_migration_is_repeatable_and_preserves_editorial_media_and_fields(): void
    {
        Storage::fake('public');
        $migration = require database_path('migrations/2026_09_16_120000_seed_watch_demo_features.php');
        $migration->up();

        $slugs = [
            'green-futures-bangladesh',
            'sundarbans-field-notes',
            'studio-sounds',
            'the-last-platform',
        ];
        $showCount = WatchShow::query()->whereIn('slug', $slugs)->count();
        $episodeCount = WatchShow::query()->whereIn('slug', $slugs)->withCount('episodes')->get()->sum('episodes_count');
        $edited = WatchShow::query()->where('slug', 'green-futures-bangladesh')->firstOrFail();
        Storage::disk('public')->put('watch/trailers/editorial.mp4', 'editorial');
        $edited->update([
            'title' => 'Editorial title',
            'creators' => ['Editorial creator'],
            'trailer_path' => 'watch/trailers/editorial.mp4',
        ]);

        $editorial = WatchShow::query()->create([
            'slug' => 'editorial-demo-slug',
            'title' => 'Editorial demo',
            'description' => 'Editorial content',
            'category' => 'Documentary',
            'image_path' => 'portal/demo/editorial.png',
            'is_published' => true,
        ]);

        $migration->up();

        $this->assertSame($showCount, WatchShow::query()->whereIn('slug', $slugs)->count());
        $this->assertSame($episodeCount, WatchShow::query()->whereIn('slug', $slugs)->withCount('episodes')->get()->sum('episodes_count'));
        $this->assertSame('Editorial title', $edited->fresh()->title);
        $this->assertSame(['Editorial creator'], $edited->fresh()->creators);
        $this->assertSame('watch/trailers/editorial.mp4', $edited->fresh()->trailer_path);
        $this->assertNull($editorial->fresh()->trailer_path);
        $this->assertEmpty($editorial->fresh()->creators);

        foreach ($slugs as $slug) {
            $show = WatchShow::query()->where('slug', $slug)->with('episodes')->firstOrFail();
            $this->assertNotEmpty($show->genres);
            $this->assertNotEmpty($show->audio_languages);
            $this->assertNotEmpty($show->subtitle_languages);
            $this->assertNotEmpty($show->trailer_path);
            Storage::disk('public')->assertExists($show->trailer_path);
            foreach ($show->episodes as $episode) {
                $this->assertNotEmpty($episode->summary);
                $this->assertNotEmpty($episode->summary_bn);
                $this->assertNotEmpty($episode->video_path);
                Storage::disk('public')->assertExists($episode->video_path);
            }
        }
    }
}
