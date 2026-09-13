<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\AudioBookResource;
use App\Http\Resources\LiveChannelResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\PodcastChannelResource;
use App\Http\Resources\ProgrammeResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\BroadcastChannel;
use App\Models\Playlist;
use App\Models\PodcastChannel;
use App\Models\Programme;
use App\Models\Song;
use App\Models\User;
use Database\Seeders\DemoArtworkSeeder;
use Database\Seeders\ListenArtworkExpansionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleArtworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_replace_and_remove_album_artwork(): void
    {
        Storage::fake('public');
        $user = $this->staffUser(['albums.view', 'albums.manage', 'records.view-all']);

        $this->actingAs($user)->post(route('admin.albums.store'), [
            'title' => 'Test Album',
            'album_type' => 'album',
            'is_published' => '1',
            'artwork' => $this->image('first.png'),
        ])->assertRedirect(route('admin.albums.index'));

        $album = Album::query()->where('title', 'Test Album')->firstOrFail();
        $firstPath = $album->artwork_path;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $this->getJson(route('api.v1.albums.show', $album))
            ->assertOk()
            ->assertJsonPath('data.artwork_url', asset('storage/'.$firstPath));

        $this->actingAs($user)->put(route('admin.albums.update', $album), [
            'title' => $album->title,
            'album_type' => $album->album_type,
            'is_published' => '1',
            'artwork' => $this->image('replacement.png'),
        ])->assertRedirect(route('admin.albums.index'));

        $album->refresh();
        $replacementPath = $album->artwork_path;
        $this->assertNotSame($firstPath, $replacementPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($replacementPath);

        $this->actingAs($user)->put(route('admin.albums.update', $album), [
            'title' => $album->title,
            'album_type' => $album->album_type,
            'is_published' => '1',
            'remove_artwork' => '1',
        ])->assertRedirect(route('admin.albums.index'));

        $this->assertNull($album->fresh()->artwork_path);
        Storage::disk('public')->assertMissing($replacementPath);
    }

    public function test_every_requested_public_module_exposes_its_uploaded_image_url(): void
    {
        $request = Request::create('/api/v1/catalogue');
        $path = 'artwork/example/cover.webp';

        $asset = new AudioAsset(['artwork_path' => $path]);
        $song = new Song(['id' => 1]);
        $song->setRelation('audioAsset', $asset);

        $book = new AudioBook(['id' => 2, 'title' => 'Book', 'artwork_path' => $path]);
        $book->setRelation('user', null);

        $resources = [
            [(new LiveChannelResource(new BroadcastChannel(['id' => 3, 'artwork_path' => $path])))->resolve($request), 'artwork_url'],
            [(new SongResource($song))->resolve($request), 'artwork_url'],
            [(new AlbumResource(new Album(['id' => 4, 'artwork_path' => $path])))->resolve($request), 'artwork_url'],
            [(new ArtistResource(new Artist(['id' => 5, 'photo_path' => $path])))->resolve($request), 'photo_url'],
            [(new ProgrammeResource(new Programme(['id' => 6, 'artwork_path' => $path])))->resolve($request), 'artwork_url'],
            [(new PodcastChannelResource(new PodcastChannel(['id' => 7, 'artwork_path' => $path])))->resolve($request), 'artwork_url'],
            [(new AudioBookResource($book))->resolve($request), 'artwork_url'],
            [(new PlaylistResource(new Playlist(['id' => 8, 'artwork_path' => $path])))->resolve($request), 'artwork_url'],
        ];

        foreach ($resources as [$resource, $key]) {
            $this->assertSame(asset('storage/'.$path), $resource[$key]);
        }
    }

    public function test_each_admin_module_accepts_an_image_upload(): void
    {
        Storage::fake('public');
        Queue::fake();
        config(['scout.driver' => 'null']);

        $user = $this->staffUser([
            'programmes.view', 'programmes.manage',
            'podcasts.view', 'podcasts.manage',
            'broadcasts.view', 'broadcasts.manage',
            'artists.view', 'artists.manage',
            'songs.view', 'songs.manage',
            'audiobooks.use', 'playlists.view',
            'records.view-all',
        ]);

        $this->actingAs($user)->post(route('admin.programmes.store'), [
            'title' => 'Image Programme',
            'programme_type' => 'programme',
            'artwork' => $this->image('programme.png'),
        ])->assertRedirect(route('admin.programmes.index'));

        $this->actingAs($user)->post(route('admin.podcast-channels.store'), [
            'title' => 'Image Podcast',
            'artwork' => $this->image('podcast.png'),
        ])->assertRedirect(route('admin.podcast-channels.index'));

        $this->actingAs($user)->post(route('admin.broadcast-channels.store'), [
            'name' => 'Image Radio',
            'artwork' => $this->image('radio.png'),
        ])->assertRedirect(route('admin.broadcast-channels.index'));

        $this->actingAs($user)->post(route('admin.artists.store'), [
            'name' => 'Image Artist',
            'artist_type' => 'singer',
            'photo' => $this->image('artist.png'),
        ])->assertRedirect(route('admin.artists.index'));

        $asset = AudioAsset::query()->create([
            'archive_no' => 'BB-2026-999991',
            'title' => 'Image Song Asset',
            'slug' => 'image-song-asset',
            'content_type' => 'song',
            'uploaded_by' => $user->id,
        ]);
        $this->actingAs($user)->post(route('admin.songs.store'), [
            'audio_asset_id' => $asset->id,
            'version_type' => 'original',
            'artwork' => $this->image('song.png'),
        ])->assertRedirect(route('admin.songs.index'));

        $this->actingAs($user)->post(route('admin.audiobooks.store'), [
            'title' => 'Image Audio Book',
            'language' => 'en',
            'text' => 'Enough source text for an audio book.',
            'artwork' => $this->image('book.png'),
        ])->assertRedirect(route('admin.audiobooks.index'));

        $listener = User::factory()->create();
        $playlist = Playlist::query()->create([
            'user_id' => $listener->id,
            'title' => 'Image Playlist',
            'slug' => 'image-playlist',
            'is_editorial' => false,
        ]);
        $this->actingAs($user)->post(route('admin.playlists.update-artwork', $playlist), [
            'artwork' => $this->image('playlist.png'),
        ])->assertRedirect();

        $paths = [
            Programme::query()->where('title', 'Image Programme')->value('artwork_path'),
            PodcastChannel::query()->where('title', 'Image Podcast')->value('artwork_path'),
            BroadcastChannel::query()->where('name', 'Image Radio')->value('artwork_path'),
            Artist::query()->where('name', 'Image Artist')->value('photo_path'),
            $asset->fresh()->artwork_path,
            AudioBook::query()->where('title', 'Image Audio Book')->value('artwork_path'),
            $playlist->fresh()->artwork_path,
        ];

        foreach ($paths as $path) {
            $this->assertNotNull($path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_demo_artwork_seeder_fills_every_module_without_overwriting_uploads(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $album = Album::query()->create([
            'title' => 'Demo Album',
            'slug' => 'demo-album',
        ]);
        $artist = Artist::query()->create([
            'name' => 'Demo Artist',
            'slug' => 'demo-artist',
        ]);
        $book = AudioBook::query()->create([
            'user_id' => $user->id,
            'title' => 'Demo Book',
            'source_type' => 'text',
        ]);
        $channel = BroadcastChannel::query()->create([
            'name' => 'Demo Radio',
            'slug' => 'demo-radio',
            'room_name' => 'demo-radio-room',
        ]);
        $playlist = Playlist::query()->create([
            'user_id' => $user->id,
            'title' => 'Demo Playlist',
            'slug' => 'demo-playlist',
        ]);
        $podcast = PodcastChannel::query()->create([
            'title' => 'Demo Podcast',
            'slug' => 'demo-podcast',
        ]);
        $programme = Programme::query()->create([
            'title' => 'Demo Programme',
            'slug' => 'demo-programme',
        ]);
        $asset = AudioAsset::query()->create([
            'archive_no' => 'BB-2026-999992',
            'title' => 'Demo Song',
            'slug' => 'demo-song',
            'content_type' => 'song',
        ]);
        Song::query()->create(['audio_asset_id' => $asset->id]);

        $customAlbum = Album::query()->create([
            'title' => 'Uploaded Album',
            'slug' => 'uploaded-album',
            'artwork_path' => 'artwork/albums/uploaded.png',
        ]);

        $this->seed(DemoArtworkSeeder::class);

        $this->assertSame('demo-artwork/albums.png', $album->fresh()->artwork_path);
        $this->assertSame('demo-artwork/artists.png', $artist->fresh()->photo_path);
        $this->assertSame('demo-artwork/artists.png', $artist->fresh()->cover_path);
        $this->assertSame('demo-artwork/audiobooks.png', $book->fresh()->artwork_path);
        $this->assertSame('demo-artwork/live-radio.png', $channel->fresh()->artwork_path);
        $this->assertSame('demo-artwork/playlists.png', $playlist->fresh()->artwork_path);
        $this->assertSame('demo-artwork/podcasts.png', $podcast->fresh()->artwork_path);
        $this->assertSame('demo-artwork/programmes.png', $programme->fresh()->artwork_path);
        $this->assertSame('demo-artwork/songs.png', $asset->fresh()->artwork_path);
        $this->assertSame('artwork/albums/uploaded.png', $customAlbum->fresh()->artwork_path);

        foreach ([
            'albums', 'artists', 'audiobooks', 'live-radio',
            'playlists', 'podcasts', 'programmes', 'songs',
        ] as $module) {
            Storage::disk('public')->assertExists("demo-artwork/{$module}.png");
        }
    }

    public function test_modern_listen_artwork_replaces_only_legacy_demo_images_with_varied_covers(): void
    {
        Storage::fake('public');

        $legacySongs = collect(range(1, 6))->map(function (int $number): AudioAsset {
            $asset = AudioAsset::query()->create([
                'archive_no' => "BB-2026-ART{$number}",
                'title' => "Modern artwork song {$number}",
                'slug' => "modern-artwork-song-{$number}",
                'content_type' => 'song',
                'artwork_path' => 'demo-artwork/songs.png',
            ]);
            Song::query()->create(['audio_asset_id' => $asset->id]);

            return $asset;
        });

        $legacyAlbum = Album::query()->create([
            'title' => 'Legacy Album',
            'slug' => 'legacy-album',
            'artwork_path' => 'demo-artwork/albums.png',
        ]);
        $uploadedAlbum = Album::query()->create([
            'title' => 'Uploaded Album',
            'slug' => 'uploaded-album',
            'artwork_path' => 'artwork/albums/uploaded.png',
        ]);

        $this->seed(ListenArtworkExpansionSeeder::class);

        $songPaths = $legacySongs->map(fn (AudioAsset $asset): ?string => $asset->fresh()->artwork_path);

        $this->assertTrue($songPaths->every(
            fn (?string $path): bool => str_starts_with((string) $path, 'listen-artwork/'),
        ));
        $this->assertGreaterThan(1, $songPaths->unique()->count());
        $this->assertStringStartsWith('listen-artwork/', (string) $legacyAlbum->fresh()->artwork_path);
        $this->assertSame('artwork/albums/uploaded.png', $uploadedAlbum->fresh()->artwork_path);

        foreach ($songPaths->push($legacyAlbum->fresh()->artwork_path) as $path) {
            Storage::disk('public')->assertExists((string) $path);
        }
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

    private function image(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $png ?: '');
    }
}
