<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AudioAsset;
use App\Models\Episode;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AudioAssetArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_unpublishes_an_asset_and_blocks_republication_until_it_is_restored(): void
    {
        Carbon::setTestNow('2026-08-11 10:30:00');
        $user = $this->staffUser(['assets.view', 'assets.edit', 'assets.publish']);
        $asset = $this->asset($user, 'Published Heritage Recording');

        $this->actingAs($user)
            ->get(route('admin.assets.index'))
            ->assertOk()
            ->assertSee('Published Heritage Recording');

        $this->actingAs($user)
            ->post(route('admin.assets.archive', $asset))
            ->assertRedirect(route('admin.archive.index'));

        $asset->refresh();
        $this->assertSame('unpublished', $asset->status);
        $this->assertSame('internal', $asset->access_level);
        $this->assertNull($asset->published_at);
        $this->assertTrue($asset->isArchived());
        $this->assertSame($user->id, $asset->archived_by);
        $this->assertFalse($asset->isPublished());
        $this->assertFalse(AudioAsset::query()->published()->whereKey($asset)->exists());

        $this->actingAs($user)
            ->post(route('admin.assets.publish', $asset))
            ->assertRedirect()
            ->assertSessionHas('error', 'Archived assets cannot be published. Unarchive this asset first.');

        $asset->refresh();
        $this->assertSame('unpublished', $asset->status);
        $this->assertSame('internal', $asset->access_level);
        $this->assertNull($asset->published_at);

        $this->actingAs($user)
            ->get(route('admin.assets.index'))
            ->assertOk()
            ->assertDontSee('Published Heritage Recording');

        $this->actingAs($user)
            ->get(route('admin.archive.index'))
            ->assertOk()
            ->assertSee('Published Heritage Recording')
            ->assertSee('Aug 11, 2026')
            ->assertSee($user->name)
            ->assertSee('Unarchive');

        $this->actingAs($user)
            ->post(route('admin.assets.unarchive', $asset))
            ->assertRedirect(route('admin.archive.index'));

        $asset->refresh();
        $this->assertFalse($asset->isArchived());
        $this->assertNull($asset->archived_by);
        $this->assertSame('unpublished', $asset->status);
        $this->assertSame('internal', $asset->access_level);
        $this->assertNull($asset->published_at);
        $this->assertFalse($asset->isPublished());
        $this->assertFalse(AudioAsset::query()->published()->whereKey($asset)->exists());

        $this->actingAs($user)
            ->get(route('admin.assets.index'))
            ->assertOk()
            ->assertSee('Published Heritage Recording');

        Carbon::setTestNow();
    }

    public function test_view_only_user_cannot_archive_or_unarchive_assets(): void
    {
        $owner = $this->staffUser(['assets.view', 'assets.edit']);
        $viewer = $this->staffUser(['assets.view']);
        $asset = $this->asset($owner, 'Protected Asset');

        $this->actingAs($viewer)
            ->post(route('admin.assets.archive', $asset))
            ->assertForbidden();

        $asset->update(['archived_at' => now(), 'archived_by' => $owner->id]);

        $this->actingAs($viewer)
            ->post(route('admin.assets.unarchive', $asset))
            ->assertForbidden();
    }

    public function test_archiving_removes_linked_catalogue_records_and_unarchiving_does_not_restore_them(): void
    {
        $user = $this->staffUser([
            'assets.view', 'assets.edit', 'songs.view', 'episodes.view',
            'podcasts.view', 'records.view-all',
        ]);
        $asset = $this->asset($user, 'Linked Catalogue Recording');
        $programme = Programme::query()->create([
            'title' => 'Archive Test Programme',
            'slug' => 'archive-test-programme',
            'is_published' => true,
            'created_by' => $user->id,
        ]);
        $channel = PodcastChannel::query()->create([
            'title' => 'Archive Test Podcast',
            'slug' => 'archive-test-podcast',
            'is_published' => true,
            'created_by' => $user->id,
        ]);
        $song = Song::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'original',
            'created_by' => $user->id,
        ]);
        $episode = Episode::query()->create([
            'programme_id' => $programme->id,
            'audio_asset_id' => $asset->id,
            'season_number' => 1,
            'number' => 1,
            'title' => 'Linked Programme Episode',
            'slug' => 'linked-programme-episode',
            'duration_seconds' => 185,
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);
        $podcastEpisode = PodcastEpisode::query()->create([
            'podcast_channel_id' => $channel->id,
            'audio_asset_id' => $asset->id,
            'season_number' => 1,
            'episode_number' => 1,
            'title' => 'Linked Podcast Episode',
            'slug' => 'linked-podcast-episode',
            'duration_seconds' => 185,
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('admin.songs.index'))->assertOk()->assertSee('Linked Catalogue Recording');
        $this->actingAs($user)->get(route('admin.episodes.index'))->assertOk()->assertSee('Linked Programme Episode');
        $this->actingAs($user)->get(route('admin.podcast-episodes.index'))->assertOk()->assertSee('Linked Podcast Episode');
        $this->getJson(route('api.v1.songs.show', $song))->assertOk();
        $this->getJson(route('api.v1.episodes.show', $episode))->assertOk();
        $this->getJson(route('api.v1.podcast-episodes.show', $podcastEpisode))->assertOk();

        $this->actingAs($user)->post(route('admin.assets.archive', $asset))->assertRedirect(route('admin.archive.index'));

        $deletedSong = Song::query()->withTrashed()->findOrFail($song->id);
        $deletedEpisode = Episode::query()->withTrashed()->findOrFail($episode->id);
        $deletedPodcastEpisode = PodcastEpisode::query()->withTrashed()->findOrFail($podcastEpisode->id);
        $this->assertSoftDeleted($deletedSong);
        $this->assertSoftDeleted($deletedEpisode);
        $this->assertSoftDeleted($deletedPodcastEpisode);
        $this->assertFalse($deletedEpisode->is_published);
        $this->assertNull($deletedEpisode->published_at);
        $this->assertSame('unpublished', $deletedPodcastEpisode->status);
        $this->assertNull($deletedPodcastEpisode->published_at);
        $this->assertNull($deletedPodcastEpisode->scheduled_at);
        $this->assertFalse(Song::query()->withoutArchivedAudioAsset()->whereKey($song)->exists());
        $this->assertFalse(Episode::query()->withoutArchivedAudioAsset()->whereKey($episode)->exists());
        $this->assertFalse(PodcastEpisode::query()->withoutArchivedAudioAsset()->whereKey($podcastEpisode)->exists());
        $this->actingAs($user)->get(route('admin.songs.index'))->assertOk()->assertDontSee('Linked Catalogue Recording');
        $this->actingAs($user)->get(route('admin.episodes.index'))->assertOk()->assertDontSee('Linked Programme Episode');
        $this->actingAs($user)->get(route('admin.podcast-episodes.index'))->assertOk()->assertDontSee('Linked Podcast Episode');
        $this->getJson(route('api.v1.songs.show', $song))->assertNotFound();
        $this->getJson(route('api.v1.episodes.show', $episode))->assertNotFound();
        $this->getJson(route('api.v1.podcast-episodes.show', $podcastEpisode))->assertNotFound();

        $this->actingAs($user)->post(route('admin.assets.unarchive', $asset))->assertRedirect(route('admin.archive.index'));

        $this->assertFalse(Song::query()->withoutArchivedAudioAsset()->whereKey($song)->exists());
        $this->assertFalse(Episode::query()->withoutArchivedAudioAsset()->whereKey($episode)->exists());
        $this->assertFalse(PodcastEpisode::query()->withoutArchivedAudioAsset()->whereKey($podcastEpisode)->exists());
        $this->actingAs($user)->get(route('admin.songs.index'))->assertOk()->assertDontSee('Linked Catalogue Recording');
        $this->actingAs($user)->get(route('admin.episodes.index'))->assertOk()->assertDontSee('Linked Programme Episode');
        $this->actingAs($user)->get(route('admin.podcast-episodes.index'))->assertOk()->assertDontSee('Linked Podcast Episode');

        $this->assertSame('unpublished', $asset->fresh()->status);
        $this->assertSame('internal', $asset->fresh()->access_level);
        $this->assertNull($asset->fresh()->published_at);
        $this->assertNull($asset->fresh()->song);
        $this->assertNull($asset->fresh()->episode);
        $this->assertNull($asset->fresh()->podcastEpisode);
        $this->assertSoftDeleted('songs', ['id' => $song->id]);
        $this->assertSoftDeleted('episodes', ['id' => $episode->id]);
        $this->assertSoftDeleted('podcast_episodes', ['id' => $podcastEpisode->id]);
        $this->getJson(route('api.v1.songs.show', $song))->assertNotFound();
        $this->getJson(route('api.v1.episodes.show', $episode))->assertNotFound();
        $this->getJson(route('api.v1.podcast-episodes.show', $podcastEpisode))->assertNotFound();
    }

    /** @param array<int, string> $permissions */
    private function staffUser(array $permissions): User
    {
        $user = User::factory()->create([
            'user_type' => 'staff',
            'status' => 'active',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function asset(User $owner, string $title): AudioAsset
    {
        return AudioAsset::query()->create([
            'archive_no' => 'BB-2026-'.str_pad((string) $owner->id, 6, '0', STR_PAD_LEFT),
            'title' => $title,
            'slug' => str($title)->slug().'-'.$owner->id,
            'content_type' => 'historical',
            'uploaded_by' => $owner->id,
            'duration_seconds' => 185,
            'status' => 'published',
            'access_level' => 'public',
            'rights_status' => 'approved',
            'published_at' => now()->subDay(),
        ]);
    }
}
