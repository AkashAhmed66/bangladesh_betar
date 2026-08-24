<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchEpisode;
use App\Models\WatchShow;
use Database\Seeders\PortalContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
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

    public function test_watch_playback_requires_a_signed_in_premium_member(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('watch/videos/private-episode.mp4', 'video-content');
        $show = WatchShow::factory()->create(['slug' => 'dynamic-show']);
        WatchEpisode::factory()->for($show, 'show')->create([
            'title' => 'Public episode',
            'is_published' => true,
            'video_path' => 'watch/videos/private-episode.mp4',
        ]);
        WatchEpisode::factory()->for($show, 'show')->create(['title' => 'Draft episode', 'is_published' => false, 'position' => 2]);

        $this->getJson(route('api.v1.watch.index'))
            ->assertOk()
            ->assertJsonPath('data.0.episodes.0.video_url', null);

        $this->getJson(route('api.v1.watch.preview', 'dynamic-show'))
            ->assertOk()
            ->assertJsonMissingPath('data.episodes');

        $this->getJson(route('api.v1.watch.show', 'dynamic-show'))
            ->assertOk()
            ->assertJsonCount(1, 'data.episodes')
            ->assertJsonPath('data.episodes.0.has_video', true)
            ->assertJsonPath('data.episodes.0.video_url', null);

        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener']));
        $this->getJson(route('api.v1.watch.show', 'dynamic-show'))
            ->assertOk()
            ->assertJsonPath('data.episodes.0.has_video', true)
            ->assertJsonPath('data.episodes.0.video_url', null);

        Sanctum::actingAs($this->premiumUser());
        $response = $this->getJson(route('api.v1.watch.show', 'dynamic-show'))
            ->assertOk()
            ->assertJsonCount(1, 'data.episodes')
            ->assertJsonPath('data.episodes.0.title', 'Public episode')
            ->assertJsonPath(
                'data.episodes.0.video_url',
                fn (string $url): bool => str_contains($url, "/api/v1/watch-episodes/{$show->episodes()->firstOrFail()->id}/play")
                    && str_contains($url, 'signature='),
            );

        $playUrl = $response->json('data.episodes.0.video_url');
        $this->getJson(route('api.v1.watch-episodes.play', $show->episodes()->firstOrFail()))->assertForbidden();
        $this->get($playUrl)
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline');
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
            ->assertJsonPath('data.news.0.show_in_header', true)
            ->assertJsonPath('data.news.3.show_in_header', false)
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

    public function test_public_portal_apis_search_their_own_published_content(): void
    {
        NewsArticle::factory()->create(['title' => 'River communities prepare for monsoon']);
        NewsArticle::factory()->create(['title' => 'National budget briefing']);
        NewsArticle::factory()->create(['title' => 'Hidden river draft', 'is_published' => false]);
        WatchShow::factory()->create(['title' => 'River journeys']);
        WatchShow::factory()->create(['title' => 'Children of the delta']);

        $this->getJson(route('api.v1.news.index', ['q' => 'river']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'River communities prepare for monsoon');

        $this->getJson(route('api.v1.watch.index', ['q' => 'river']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'River journeys');
    }

    public function test_admin_category_fields_only_accept_portal_categories(): void
    {
        $user = $this->staffUser(['news.view', 'news.manage', 'watch.view', 'watch.manage', 'records.view-all']);

        $this->actingAs($user)->get(route('admin.news-articles.create'))
            ->assertOk()
            ->assertSee('<select id="category"', false)
            ->assertSee('Bangladesh')
            ->assertSee('Science');

        $this->actingAs($user)->get(route('admin.news-categories.create'))
            ->assertOk()
            ->assertSee('name="show_in_header"', false)
            ->assertSee('Show in heading')
            ->assertSee('Show under More');

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

    public function test_admin_managed_categories_drive_bilingual_content_and_public_filtering(): void
    {
        Storage::fake('public');
        $user = $this->staffUser(['news.view', 'news.manage', 'watch.view', 'watch.manage', 'records.view-all']);

        $this->actingAs($user)->post(route('admin.news-categories.store'), [
            'name' => 'Technology',
            'name_bn' => 'প্রযুক্তি',
            'slug' => 'technology',
            'description' => 'Technology reporting.',
            'description_bn' => 'প্রযুক্তি বিষয়ক প্রতিবেদন।',
            'position' => 20,
            'is_active' => 1,
            'show_in_header' => 0,
        ])->assertRedirect(route('admin.news-categories.index'));

        $category = NewsCategory::query()->where('slug', 'technology')->firstOrFail();

        $this->actingAs($user)->post(route('admin.news-articles.store'), [
            'title' => 'Digital service launched',
            'title_bn' => 'ডিজিটাল সেবা চালু',
            'slug' => 'digital-service-launched',
            'summary' => 'A new service is now available.',
            'summary_bn' => 'নতুন সেবা এখন পাওয়া যাচ্ছে।',
            'category' => 'Technology',
            'body_text' => 'The English article body.',
            'body_text_bn' => 'বাংলা নিবন্ধের মূল লেখা।',
            'read_time_minutes' => 2,
            'position' => 0,
            'is_featured' => 0,
            'artwork' => $this->image('technology.png'),
        ])->assertRedirect(route('admin.news-articles.index'));

        $article = NewsArticle::query()->where('slug', 'digital-service-launched')->firstOrFail();
        $this->assertSame($category->id, $article->news_category_id);
        $this->assertSame('ডিজিটাল সেবা চালু', $article->title_bn);

        $article->update(['is_published' => true, 'published_at' => now()]);

        $this->getJson(route('api.v1.portal-categories.index'))
            ->assertOk()
            ->assertJsonFragment(['slug' => 'technology', 'label_bn' => 'প্রযুক্তি', 'show_in_header' => false]);

        $this->getJson(route('api.v1.news.index', ['category' => 'technology']))
            ->assertOk()
            ->assertJsonPath('data.0.title_bn', 'ডিজিটাল সেবা চালু')
            ->assertJsonPath('data.0.category_slug', 'technology');
    }

    public function test_admin_language_switch_is_persisted_for_the_user_and_session(): void
    {
        $user = $this->staffUser(['news.manage']);

        $this->actingAs($user)->post(route('admin.locale.update'), ['locale' => 'bn'])
            ->assertRedirect()
            ->assertSessionHas('locale', 'bn');

        $this->assertSame('bn', $user->fresh()->locale);
        $this->actingAs($user)->withSession(['locale' => 'bn'])->get(route('admin.news-categories.index'))
            ->assertOk()
            ->assertSee('সংবাদ বিভাগ');
    }

    public function test_category_rename_keeps_existing_content_mapping_in_sync(): void
    {
        $user = $this->staffUser(['watch.manage']);
        $category = WatchCategory::query()->where('slug', 'documentary')->firstOrFail();
        $show = WatchShow::factory()->create([
            'watch_category_id' => $category->id,
            'category' => $category->name,
        ]);

        $this->actingAs($user)->put(route('admin.watch-categories.update', $category), [
            'name' => 'Factual',
            'name_bn' => 'তথ্যচিত্র',
            'slug' => 'factual',
            'description' => 'Factual programmes.',
            'description_bn' => 'তথ্যভিত্তিক অনুষ্ঠান।',
            'position' => 2,
            'is_active' => 1,
            'show_in_header' => 1,
        ])->assertRedirect(route('admin.watch-categories.index'));

        $this->assertSame('Factual', $show->fresh()->category);
        $this->getJson(route('api.v1.watch.index', ['category' => 'factual']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $show->id);
    }

    public function test_admin_publication_time_is_interpreted_as_bangladesh_time(): void
    {
        Storage::fake('public');
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
            'artwork' => $this->image('timezone-news.png'),
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

    private function premiumUser(): User
    {
        $user = User::factory()->create(['user_type' => 'listener', 'status' => 'active']);
        $plan = Plan::query()->create([
            'name' => 'Premium',
            'code' => 'premium',
            'features' => ['premium_content' => 'full'],
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        return $user;
    }

    private function image(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png ?: '');
    }
}
