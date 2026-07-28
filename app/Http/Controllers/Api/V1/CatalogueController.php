<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\EpisodeResource;
use App\Http\Resources\PodcastChannelResource;
use App\Http\Resources\PodcastEpisodeResource;
use App\Http\Resources\ProgrammeResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\Follow;
use App\Models\PodcastChannel;
use App\Models\Programme;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M17 — read-only catalogue browsing of published content:
 * songs, albums, artists, podcasts, programmes, episodes, stories.
 * Only published, rights-cleared online versions are exposed (FR-PUB-03).
 */
class CatalogueController extends Controller
{
    // ---- Songs ----

    public function songs(Request $request): JsonResponse
    {
        $songs = Song::query()->published()
            ->with(['audioAsset', 'album', 'genre', 'mood', 'artists'])
            ->when($request->filled('genre'), fn ($q) => $q->where('genre_id', $request->integer('genre')))
            ->when($request->filled('album'), fn ($q) => $q->where('album_id', $request->integer('album')))
            ->when($request->string('sort')->toString() === 'popular',
                fn ($q) => $q->join('audio_assets', 'songs.audio_asset_id', '=', 'audio_assets.id')->orderByDesc('audio_assets.play_count')->select('songs.*'),
                fn ($q) => $q->latest())
            ->paginate($request->integer('per_page', 20));

        return SongResource::collection($songs)->response();
    }

    public function song(Request $request, Song $song): JsonResponse
    {
        abort_unless($song->audioAsset?->isPublished(), 404);
        $song->load(['audioAsset', 'album.artists', 'genre', 'mood', 'artists', 'masterSong', 'versionFamily.audioAsset']);
        $this->markFavorited($song->audioAsset, $request->user());

        return (new SongResource($song))->response();
    }

    // ---- Albums ----

    public function albums(Request $request): JsonResponse
    {
        $albums = Album::query()->published()->with('artists')->withCount('songs')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('title')->paginate($request->integer('per_page', 20));

        return AlbumResource::collection($albums)->response();
    }

    public function album(Album $album): JsonResponse
    {
        abort_unless($album->is_published, 404);
        $album->load(['artists', 'songs' => fn ($q) => $q->published()->with(['audioAsset', 'artists', 'genre'])]);

        return (new AlbumResource($album))->response();
    }

    // ---- Artists ----

    public function artists(Request $request): JsonResponse
    {
        $artists = Artist::query()->published()
            ->when($request->filled('type'), fn ($q) => $q->where('artist_type', $request->string('type')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('followers_count')->paginate($request->integer('per_page', 20));

        return ArtistResource::collection($artists)->response();
    }

    public function artist(Request $request, Artist $artist): JsonResponse
    {
        abort_unless($artist->is_published, 404);
        $this->markFollowing($artist, $request->user());

        $songs = $artist->songs()->published()->with(['audioAsset', 'album', 'artists'])->take(50)->get();
        $albums = $artist->albums()->where('is_published', true)->get();

        return response()->json([
            'data' => (new ArtistResource($artist))->resolve(),
            'songs' => SongResource::collection($songs),
            'albums' => AlbumResource::collection($albums),
        ]);
    }

    // ---- Programmes & episodes ----

    public function programmes(Request $request): JsonResponse
    {
        $programmes = Programme::query()->published()->with(['station', 'category'])->withCount('episodes')
            ->when($request->filled('type'), fn ($q) => $q->where('programme_type', $request->string('type')))
            ->orderBy('title')->paginate($request->integer('per_page', 20));

        return ProgrammeResource::collection($programmes)->response();
    }

    public function programme(Request $request, Programme $programme): JsonResponse
    {
        abort_unless($programme->is_published, 404);
        $this->markFollowing($programme, $request->user());
        $programme->load(['station', 'category']);
        $episodes = $programme->episodes()->published()->latest('broadcast_date')->paginate(20);

        return response()->json([
            'data' => (new ProgrammeResource($programme))->resolve(),
            // ->response()->getData(true) keeps the paginated {data, links, meta}
            // shape; a bare nested resource collection would drop the wrapper.
            'episodes' => EpisodeResource::collection($episodes)->response()->getData(true),
        ]);
    }

    public function episode(Episode $episode): JsonResponse
    {
        abort_unless($episode->is_published, 404);
        $episode->load(['programme']);

        return (new EpisodeResource($episode))->response();
    }

    // ---- Podcasts ----

    public function podcasts(Request $request): JsonResponse
    {
        $channels = PodcastChannel::query()->published()->with('category')->withCount('episodes')
            ->orderByDesc('followers_count')->paginate($request->integer('per_page', 20));

        return PodcastChannelResource::collection($channels)->response();
    }

    public function podcast(Request $request, PodcastChannel $podcastChannel): JsonResponse
    {
        abort_unless($podcastChannel->is_published, 404);
        $this->markFollowing($podcastChannel, $request->user());
        $podcastChannel->load('category');
        $episodes = $podcastChannel->episodes()->published()->latest('published_at')->paginate(20);

        return response()->json([
            'data' => (new PodcastChannelResource($podcastChannel))->resolve(),
            'episodes' => PodcastEpisodeResource::collection($episodes)->response()->getData(true),
        ]);
    }

    public function podcastEpisode(\App\Models\PodcastEpisode $podcastEpisode): JsonResponse
    {
        abort_unless($podcastEpisode->status === 'published', 404);
        $podcastEpisode->load(['channel', 'artists']);

        return (new PodcastEpisodeResource($podcastEpisode))->response();
    }

    // ---- Playlists (public view — editorial & shared user playlists) ----

    public function playlist(Request $request, \App\Models\Playlist $playlist): JsonResponse
    {
        abort_unless($playlist->is_public, 404);
        $playlist->load(['items.playable', 'user'])->loadCount('items');
        $this->markFollowing($playlist, $request->user());

        return (new \App\Http\Resources\PlaylistResource($playlist))->response();
    }

    // ---- Generic asset ----

    public function asset(Request $request, AudioAsset $asset): JsonResponse
    {
        abort_unless($asset->isPublished(), 404);
        $asset->load([
            'category', 'language', 'station', 'programme', 'artists',
            // Chapters for the public player (content markers of type 'chapter').
            'markers' => fn ($q) => $q->where('marker_type', 'chapter')->orderBy('start_seconds'),
        ]);
        $this->markFavorited($asset, $request->user());
        $this->markMyRating($asset, $request->user());

        return (new AudioAssetResource($asset))->response();
    }

    /* ------------------------------------------------------------------ */

    private function markFavorited(?AudioAsset $asset, ?\App\Models\User $user): void
    {
        if ($asset && $user) {
            $asset->is_favorited = Favorite::query()->where('user_id', $user->id)
                ->where('favoritable_type', 'audio_asset')->where('favoritable_id', $asset->id)->exists();
        }
    }

    /** Attaches the signed-in listener's own rating for this asset, if any. */
    private function markMyRating(?AudioAsset $asset, ?\App\Models\User $user): void
    {
        if ($asset && $user) {
            $asset->my_rating = \App\Models\Rating::query()->where('user_id', $user->id)
                ->where('ratable_type', 'audio_asset')->where('ratable_id', $asset->id)->value('rating');
        }
    }

    private function markFollowing(\Illuminate\Database\Eloquent\Model $model, ?\App\Models\User $user): void
    {
        if ($user) {
            $model->is_following = Follow::query()->where('user_id', $user->id)
                ->where('followable_type', $model->getMorphClass())->where('followable_id', $model->getKey())->exists();
        }
    }
}
