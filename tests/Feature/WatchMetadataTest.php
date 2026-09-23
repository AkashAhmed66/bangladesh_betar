<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class WatchMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_persists_watch_metadata_and_trailer_and_public_api_exposes_it(): void
    {
        Storage::fake('public');
        $user = $this->staffUser();
        $category = WatchCategory::query()->active()->firstOrFail();

        $this->actingAs($user)->post(route('admin.watch-shows.store'), [
            'title' => 'Metadata Demo', 'slug' => 'metadata-demo', 'description' => 'A demo show.',
            'category' => $category->name, 'year' => 2026, 'age_restriction' => '13+',
            'genres' => 'Drama, Mystery', 'creators' => 'Demo Director', 'cast' => 'Demo Cast',
            'audio_languages' => 'Bangla, English', 'subtitle_languages' => 'English',
            'position' => 0, 'is_featured' => 0, 'trailer' => UploadedFile::fake()->create('trailer.mp4', 20, 'video/mp4'),
        ])->assertRedirect();

        $show = WatchShow::query()->where('slug', 'metadata-demo')->firstOrFail();
        $this->assertSame('13+', $show->age_restriction);
        $this->assertSame(['Drama', 'Mystery'], $show->genres);
        $this->assertSame(['Demo Director'], $show->creators);
        $this->assertSame(['Demo Cast'], $show->cast);
        Storage::disk('public')->assertExists($show->trailer_path);

        $episode = $show->episodes()->create([
            'title' => 'Episode One', 'description' => 'Episode description.', 'summary' => 'Episode summary.',
            'audio_languages' => ['Bangla'], 'subtitle_languages' => ['English'], 'is_published' => true,
            'duration_minutes' => 20, 'position' => 1,
        ]);
        $show->update(['is_published' => true, 'published_at' => now()]);

        $this->getJson(route('api.v1.watch.show', $show->slug))
            ->assertOk()
            ->assertJsonPath('data.age_restriction', '13+')
            ->assertJsonPath('data.genres.0', 'Drama')
            ->assertJsonPath('data.audio_languages.0', 'Bangla')
            ->assertJsonPath('data.episodes.0.summary', 'Episode summary.')
            ->assertJsonPath('data.episodes.0.subtitle_languages.0', 'English');

        $this->assertModelExists($episode);
    }

    public function test_admin_can_replace_and_remove_a_watch_trailer(): void
    {
        Storage::fake('public');
        $user = $this->staffUser();
        $category = WatchCategory::query()->active()->firstOrFail();
        $show = WatchShow::factory()->create(['category' => $category->name, 'watch_category_id' => $category->id]);

        $fields = [
            'title' => $show->title, 'slug' => $show->slug, 'description' => $show->description,
            'category' => $category->name, 'position' => 0, 'is_featured' => 0, 'age_restriction' => 'G',
        ];
        $this->actingAs($user)->put(route('admin.watch-shows.update', $show), $fields + [
            'trailer' => UploadedFile::fake()->create('first.mp4', 20, 'video/mp4'),
        ])->assertRedirect();
        $firstPath = $show->fresh()->trailer_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)->put(route('admin.watch-shows.update', $show), $fields + [
            'trailer' => UploadedFile::fake()->create('second.mp4', 20, 'video/mp4'),
        ])->assertRedirect();
        $secondPath = $show->fresh()->trailer_path;
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->actingAs($user)->put(route('admin.watch-shows.update', $show), $fields + ['remove_trailer' => 1])
            ->assertRedirect();
        $this->assertNull($show->fresh()->trailer_path);
        Storage::disk('public')->assertMissing($secondPath);
    }

    private function staffUser(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        Permission::findOrCreate('watch.view', 'web');
        Permission::findOrCreate('watch.manage', 'web');
        $user->givePermissionTo(['watch.view', 'watch.manage']);

        return $user;
    }
}
