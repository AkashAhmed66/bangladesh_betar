<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\User;
use App\Models\WatchEpisode;
use App\Models\WatchShow;
use Database\Seeders\PortalContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class PortalContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_news_api_only_returns_published_articles(): void
    {
        NewsArticle::factory()->create(['slug' => 'published-story', 'title' => 'Published story']);
        NewsArticle::factory()->create(['slug' => 'draft-story', 'title' => 'Draft story', 'is_published' => false]);

        $this->getJson(route('api.v1.news.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'published-story');

        $this->getJson(route('api.v1.news.show', 'draft-story'))->assertNotFound();
    }

    public function test_public_watch_api_includes_only_published_episodes(): void
    {
        $show = WatchShow::factory()->create(['slug' => 'dynamic-show']);
        WatchEpisode::factory()->for($show, 'show')->create(['title' => 'Public episode', 'is_published' => true]);
        WatchEpisode::factory()->for($show, 'show')->create(['title' => 'Draft episode', 'is_published' => false, 'position' => 2]);

        $this->getJson(route('api.v1.watch.show', 'dynamic-show'))
            ->assertOk()
            ->assertJsonCount(1, 'data.episodes')
            ->assertJsonPath('data.episodes.0.title', 'Public episode');
    }

    public function test_public_portal_apis_publish_category_metadata_and_filter_content(): void
    {
        NewsArticle::factory()->create(['title' => 'Bangladesh report', 'category' => 'Bangladesh']);
        NewsArticle::factory()->create(['title' => 'Economy report', 'category' => 'Economy']);
        WatchShow::factory()->create(['title' => 'Drama programme', 'category' => 'Drama']);
        WatchShow::factory()->create(['title' => 'Kids programme', 'category' => 'Kids']);

        $this->getJson(route('api.v1.portal-categories.index'))
            ->assertOk()
            ->assertJsonPath('data.news.0.slug', 'bangladesh')
            ->assertJsonPath('data.watch.0.slug', 'live-tv');

        $this->getJson(route('api.v1.news.index', ['category' => 'bangladesh']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Bangladesh report');

        $this->getJson(route('api.v1.watch.index', ['category' => 'kids']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Kids programme');

        $this->getJson(route('api.v1.news.index', ['category' => 'not-a-category']))
            ->assertUnprocessable();
    }

    public function test_admin_category_fields_only_accept_portal_categories(): void
    {
        $user = $this->staffUser(['news.view', 'news.manage', 'watch.view', 'watch.manage', 'records.view-all']);

        $this->actingAs($user)->get(route('admin.news-articles.create'))
            ->assertOk()
            ->assertSee('<select id="category"', false)
            ->assertSee('Bangladesh')
            ->assertSee('Science');

        $this->actingAs($user)->get(route('admin.watch-shows.create'))
            ->assertOk()
            ->assertSee('<select id="category"', false)
            ->assertSee('Live TV')
            ->assertSee('Culture &amp; music', false);

        $this->actingAs($user)->post(route('admin.news-articles.store'), [
            'title' => 'Invalid category article',
            'slug' => 'invalid-category-article',
            'summary' => 'The category must be controlled.',
            'category' => 'Anything',
            'body_text' => 'Article body.',
            'read_time_minutes' => 2,
            'position' => 0,
            'is_featured' => 0,
            'is_published' => 1,
        ])->assertSessionHasErrors('category');
    }

    public function test_admin_publication_time_is_interpreted_as_bangladesh_time(): void
    {
        $user = $this->staffUser(['news.view', 'news.manage', 'records.view-all']);

        $this->actingAs($user)->post(route('admin.news-articles.store'), [
            'title' => 'Timezone-safe article',
            'slug' => 'timezone-safe-article',
            'summary' => 'This article uses a Bangladesh-local publication time.',
            'category' => 'Bangladesh',
            'body_text' => 'Article body.',
            'read_time_minutes' => 2,
            'position' => 0,
            'is_featured' => 0,
            'is_published' => 1,
            'published_at' => '2026-08-20T17:30',
        ])->assertRedirect(route('admin.news-articles.index'));

        $article = NewsArticle::query()->where('slug', 'timezone-safe-article')->firstOrFail();
        $this->assertSame('2026-08-20T11:30:00.000000Z', $article->published_at?->toISOString());
    }

    public function test_admin_can_create_news_and_watch_content_with_uploads(): void
    {
        Storage::fake('public');
        $user = $this->staffUser(['news.view', 'news.manage', 'watch.view', 'watch.manage', 'records.view-all']);

        $this->actingAs($user)->post(route('admin.news-articles.store'), [
            'title' => 'Uploaded News',
            'slug' => 'uploaded-news',
            'summary' => 'A summary for the uploaded article.',
            'category' => 'Bangladesh',
            'body_text' => "First paragraph.\n\nSecond paragraph.",
            'read_time_minutes' => 3,
            'position' => 0,
            'is_featured' => 1,
            'is_published' => 1,
            'artwork' => $this->image('news.png'),
        ])->assertRedirect(route('admin.news-articles.index'));

        $article = NewsArticle::query()->where('slug', 'uploaded-news')->firstOrFail();
        Storage::disk('public')->assertExists($article->image_path);

        $this->actingAs($user)->post(route('admin.watch-shows.store'), [
            'title' => 'Uploaded Show',
            'slug' => 'uploaded-show',
            'eyebrow' => 'New programme',
            'description' => 'A dynamically managed show.',
            'category' => 'Documentary',
            'year' => 2026,
            'rating' => 'G',
            'position' => 0,
            'is_featured' => 1,
            'is_published' => 1,
            'artwork' => $this->image('show.png'),
        ])->assertRedirect();

        $show = WatchShow::query()->where('slug', 'uploaded-show')->firstOrFail();
        Storage::disk('public')->assertExists($show->image_path);

        $this->actingAs($user)->post(route('admin.watch-shows.episodes.store', $show), [
            'title' => 'Uploaded Episode',
            'description' => 'The first episode.',
            'duration_minutes' => 24,
            'position' => 1,
            'is_published' => 1,
            'video' => UploadedFile::fake()->create('episode.mp4', 128, 'video/mp4'),
        ])->assertRedirect(route('admin.watch-shows.edit', $show));

        $episode = $show->episodes()->firstOrFail();
        Storage::disk('public')->assertExists($episode->video_path);
    }

    public function test_portal_content_seeder_installs_the_existing_demo_catalogue_and_images(): void
    {
        Storage::fake('public');

        $this->seed(PortalContentSeeder::class);

        $this->assertSame(6, NewsArticle::query()->count());
        $this->assertSame(8, WatchShow::query()->count());
        $this->assertSame(15, WatchEpisode::query()->count());
        Storage::disk('public')->assertExists('portal/demo/news-hero.png');
        Storage::disk('public')->assertExists('portal/demo/watch-hero.png');
    }

    /** @param array<int, string> $permissions */
    private function staffUser(array $permissions): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function image(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png ?: '');
    }
}
