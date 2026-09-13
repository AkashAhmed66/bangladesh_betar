<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class NewsArticleMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_article_requires_a_lead_image(): void
    {
        $this->actingAs($this->editor())
            ->post(route('admin.news-articles.store'), $this->articlePayload())
            ->assertSessionHasErrors('artwork');

        $this->assertDatabaseCount('news_articles', 0);
    }

    public function test_editor_can_upload_multiple_media_types_and_public_api_groups_them(): void
    {
        Storage::fake('public');

        $payload = $this->articlePayload() + [
            'artwork' => $this->image('lead.png'),
            'images' => [$this->image('gallery-one.png'), $this->image('gallery-two.png')],
            'videos' => [UploadedFile::fake()->create('report.mp4', 256, 'video/mp4')],
            'audios' => [UploadedFile::fake()->create('interview.mp3', 128, 'audio/mpeg')],
            'documents' => [UploadedFile::fake()->createWithContent('briefing.pdf', '%PDF-1.4 test document')],
        ];

        $this->actingAs($this->editor())
            ->post(route('admin.news-articles.store'), $payload)
            ->assertRedirect(route('admin.news-articles.index'));

        $article = NewsArticle::query()->where('slug', 'multimedia-report')->with('media')->firstOrFail();
        $this->assertNotNull($article->image_path);
        $this->assertCount(5, $article->media);
        $this->assertSame(['image', 'image', 'video', 'audio', 'document'], $article->media->pluck('media_type')->all());

        Storage::disk('public')->assertExists($article->image_path);
        $article->media->each(fn ($media) => Storage::disk('public')->assertExists($media->path));

        $article->update(['is_published' => true, 'published_at' => now()]);

        $this->getJson(route('api.v1.news.show', $article->slug))
            ->assertOk()
            ->assertJsonCount(6, 'data.media')
            ->assertJsonPath('data.media.0.type', 'image')
            ->assertJsonPath('data.media.1.name', 'gallery-one.png')
            ->assertJsonPath('data.media.3.type', 'video')
            ->assertJsonPath('data.media.4.type', 'audio')
            ->assertJsonPath('data.media.5.type', 'document');
    }

    public function test_editor_can_remove_an_attachment_but_not_the_required_lead_image(): void
    {
        Storage::fake('public');
        $editor = $this->editor();

        $this->actingAs($editor)->post(route('admin.news-articles.store'), $this->articlePayload() + [
            'artwork' => $this->image('lead.png'),
            'documents' => [UploadedFile::fake()->createWithContent('old.pdf', '%PDF-1.4 old')],
        ])->assertRedirect();

        $article = NewsArticle::query()->with('media')->firstOrFail();
        $document = $article->media->firstOrFail();

        $this->actingAs($editor)->put(route('admin.news-articles.update', $article), $this->articlePayload() + [
            'remove_media' => [$document->id],
        ])->assertRedirect(route('admin.news-articles.edit', $article));

        Storage::disk('public')->assertMissing($document->path);
        $this->assertDatabaseMissing('news_article_media', ['id' => $document->id]);
        $this->assertNotNull($article->fresh()->image_path);

        $this->actingAs($editor)->put(route('admin.news-articles.update', $article), $this->articlePayload() + [
            'remove_artwork' => 1,
        ])->assertSessionHasErrors('artwork');
    }

    public function test_editor_can_upload_an_m4a_file_detected_as_an_mp4_container(): void
    {
        Storage::fake('public');

        $this->actingAs($this->editor())
            ->post(route('admin.news-articles.store'), $this->articlePayload() + [
                'artwork' => $this->image('lead.png'),
                // Fileinfo commonly identifies a valid M4A container as video/mp4.
                'audios' => [UploadedFile::fake()->create('interview.m4a', 128, 'video/mp4')],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.news-articles.index'));

        $article = NewsArticle::query()->with('media')->firstOrFail();
        $audio = $article->media->sole();

        $this->assertSame('audio', $audio->media_type);
        $this->assertSame('interview.m4a', $audio->original_name);
        $this->assertSame('video/mp4', $audio->mime_type);
        Storage::disk('public')->assertExists($audio->path);
    }

    public function test_editor_can_add_remove_and_publicly_embed_youtube_videos(): void
    {
        Storage::fake('public');
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.news-articles.store'), $this->articlePayload() + [
                'artwork' => $this->image('lead.png'),
                'youtube_links' => [
                    'https://youtu.be/dQw4w9WgXcQ?t=12',
                    'https://www.youtube.com/shorts/aqz-KE-bpKQ',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.news-articles.index'));

        $article = NewsArticle::query()->with('media')->firstOrFail();
        $this->assertSame(['youtube', 'youtube'], $article->media->pluck('media_type')->all());
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $article->media->first()->path);
        $this->assertTrue($article->media->every(fn ($media): bool => $media->disk === 'external'));

        $article->update(['is_published' => true, 'published_at' => now()]);
        $this->getJson(route('api.v1.news.show', $article->slug))
            ->assertOk()
            ->assertJsonPath('data.media.1.type', 'youtube')
            ->assertJsonPath('data.media.1.url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->assertJsonPath('data.media.1.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
            ->assertJsonPath('data.media.2.embed_url', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');

        $youtube = $article->media->firstOrFail();
        $this->actingAs($editor)->put(route('admin.news-articles.update', $article), $this->articlePayload() + [
            'remove_media' => [$youtube->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('news_article_media', ['id' => $youtube->id]);
    }

    public function test_news_article_rejects_non_youtube_video_links(): void
    {
        Storage::fake('public');

        $this->actingAs($this->editor())
            ->post(route('admin.news-articles.store'), $this->articlePayload() + [
                'artwork' => $this->image('lead.png'),
                'youtube_links' => ['https://example.com/not-a-youtube-video'],
            ])
            ->assertSessionHasErrors('youtube_links.0');

        $this->assertDatabaseCount('news_articles', 0);
    }

    /** @return array<string, mixed> */
    private function articlePayload(): array
    {
        return [
            'title' => 'Multimedia report',
            'slug' => 'multimedia-report',
            'summary' => 'A complete report with supporting media.',
            'category' => 'Bangladesh',
            'body_text' => "First paragraph.\n\nSecond paragraph.",
            'read_time_minutes' => 4,
            'position' => 0,
            'is_featured' => 1,
        ];
    }

    private function editor(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        foreach (['news.view', 'news.manage', 'records.view-all'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['news.view', 'news.manage', 'records.view-all']);

        return $user;
    }

    private function image(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png ?: '');
    }
}
