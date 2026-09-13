<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsArticle;
use Database\Seeders\NewsArtworkExpansionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class NewsArtworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_modern_news_artwork_replaces_legacy_demo_images_and_preserves_uploads(): void
    {
        Storage::fake('public');

        $legacy = collect([
            ['title' => 'Rail link connects river communities', 'slug' => 'news-rail-link'],
            ['title' => 'Farmers adopt climate resilient rice', 'slug' => 'news-farmers'],
            ['title' => 'Students open a digital classroom', 'slug' => 'news-students'],
            ['title' => 'National cricket team wins', 'slug' => 'news-cricket'],
        ])->map(fn (array $attributes): NewsArticle => NewsArticle::factory()->create($attributes));

        $uploaded = NewsArticle::factory()->create([
            'title' => 'Admin uploaded editorial photo',
            'slug' => 'admin-uploaded-editorial-photo',
            'image_path' => 'news/uploads/editorial-photo.jpg',
        ]);

        $this->seed(NewsArtworkExpansionSeeder::class);

        $paths = $legacy->map(fn (NewsArticle $article): ?string => $article->fresh()->image_path);

        $this->assertTrue($paths->every(
            fn (?string $path): bool => str_starts_with((string) $path, 'news-artwork/'),
        ));
        $this->assertGreaterThan(1, $paths->unique()->count());
        $this->assertSame('news/uploads/editorial-photo.jpg', $uploaded->fresh()->image_path);

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists((string) $path);
        }
    }
}
