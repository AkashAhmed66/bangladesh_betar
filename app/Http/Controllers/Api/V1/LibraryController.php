<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\PlaylistResource;
use App\Models\AudioAsset;
use App\Models\Favorite;
use App\Models\Follow;
use App\Models\PlayHistory;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\UserQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * M17 — the signed-in listener's library: playlists, favourites, follows,
 * history / continue-listening and the playback queue. All require auth.
 */
class LibraryController extends Controller
{
    private const PLAYABLE_TYPES = ['song', 'audio_asset', 'podcast_episode', 'episode'];
    private const FOLLOWABLE_TYPES = ['artist', 'programme', 'podcast_channel', 'playlist'];

    // ---- Playlists (FR-PUB-04) ----

    public function playlists(Request $request): JsonResponse
    {
        $playlists = $request->user()->playlists()->withCount('items')->latest()->get();

        return PlaylistResource::collection($playlists)->response();
    }

    public function showPlaylist(Request $request, Playlist $playlist): JsonResponse
    {
        abort_unless($playlist->is_editorial || $playlist->user_id === $request->user()->id || $playlist->is_public, 403);
        $playlist->load('items.playable');

        return (new PlaylistResource($playlist))->response();
    }

    public function createPlaylist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_public' => ['boolean'],
        ]);

        $playlist = $request->user()->playlists()->create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)),
            'description' => $data['description'] ?? null,
            'is_public' => (bool) ($data['is_public'] ?? false),
            'is_published' => false,
            'is_editorial' => false,
        ]);

        return (new PlaylistResource($playlist))->response()->setStatusCode(201);
    }

    public function updatePlaylist(Request $request, Playlist $playlist): JsonResponse
    {
        $this->ensureOwner($request, $playlist);

        $playlist->update($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_public' => ['sometimes', 'boolean'],
        ]));

        return (new PlaylistResource($playlist))->response();
    }

    public function deletePlaylist(Request $request, Playlist $playlist): JsonResponse
    {
        $this->ensureOwner($request, $playlist);
        $playlist->delete();

        return response()->json(['message' => 'Playlist deleted.']);
    }

    public function addPlaylistItem(Request $request, Playlist $playlist): JsonResponse
    {
        $this->ensureOwner($request, $playlist);
        $data = $request->validate([
            'playable_type' => ['required', Rule::in(self::PLAYABLE_TYPES)],
            'playable_id' => ['required', 'integer'],
        ]);

        $position = (int) $playlist->items()->max('position') + 1;
        $playlist->items()->create($data + ['position' => $position]);

        return response()->json(['message' => 'Added to playlist.'], 201);
    }

    public function removePlaylistItem(Request $request, Playlist $playlist, PlaylistItem $item): JsonResponse
    {
        $this->ensureOwner($request, $playlist);
        abort_unless($item->playlist_id === $playlist->id, 404);
        $item->delete();

        return response()->json(['message' => 'Removed from playlist.']);
    }

    public function reorderPlaylist(Request $request, Playlist $playlist): JsonResponse
    {
        $this->ensureOwner($request, $playlist);
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        foreach ($data['order'] as $position => $itemId) {
            $playlist->items()->where('id', $itemId)->update(['position' => $position]);
        }

        return response()->json(['message' => 'Playlist reordered.']);
    }

    // ---- Favourites ----

    public function favorites(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()->where('favoritable_type', 'audio_asset')
            ->with('favoritable')->latest()->paginate(30);

        $assets = $favorites->getCollection()->map(fn ($f) => $f->favoritable)->filter();

        return response()->json([
            'data' => AudioAssetResource::collection($assets),
            'meta' => ['total' => $favorites->total(), 'current_page' => $favorites->currentPage()],
        ]);
    }

    public function toggleFavorite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'favoritable_type' => ['required', Rule::in(self::PLAYABLE_TYPES)],
            'favoritable_id' => ['required', 'integer'],
        ]);

        $existing = Favorite::query()->where('user_id', $request->user()->id)
            ->where('favoritable_type', $data['favoritable_type'])
            ->where('favoritable_id', $data['favoritable_id'])->first();

        if ($existing) {
            $existing->delete();
            if ($data['favoritable_type'] === 'audio_asset') {
                AudioAsset::query()->where('id', $data['favoritable_id'])->decrement('favorite_count');
            }

            return response()->json(['favorited' => false]);
        }

        Favorite::query()->create($data + ['user_id' => $request->user()->id]);
        if ($data['favoritable_type'] === 'audio_asset') {
            AudioAsset::query()->where('id', $data['favoritable_id'])->increment('favorite_count');
        }

        return response()->json(['favorited' => true], 201);
    }

    // ---- Follows (FR-PUB-12) ----

    public function follows(Request $request): JsonResponse
    {
        $follows = $request->user()->follows()->with('followable')->latest()->get()
            ->groupBy('followable_type')
            ->map(fn ($group) => $group->map(fn ($f) => [
                'type' => $f->followable_type,
                'id' => $f->followable_id,
                'name' => $f->followable?->name ?? $f->followable?->title,
            ])->values());

        return response()->json(['data' => $follows]);
    }

    public function toggleFollow(Request $request): JsonResponse
    {
        $data = $request->validate([
            'followable_type' => ['required', Rule::in(self::FOLLOWABLE_TYPES)],
            'followable_id' => ['required', 'integer'],
        ]);

        $existing = Follow::query()->where('user_id', $request->user()->id)
            ->where('followable_type', $data['followable_type'])
            ->where('followable_id', $data['followable_id'])->first();

        $modelClass = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($data['followable_type']);

        if ($existing) {
            $existing->delete();
            $modelClass::query()->where('id', $data['followable_id'])->decrement('followers_count');

            return response()->json(['following' => false]);
        }

        Follow::query()->create($data + ['user_id' => $request->user()->id]);
        $modelClass::query()->where('id', $data['followable_id'])->increment('followers_count');

        return response()->json(['following' => true], 201);
    }

    // ---- History & Continue Listening (FR-PUB-14) ----

    public function history(Request $request): JsonResponse
    {
        $history = $request->user()->playHistories()->where('playable_type', 'audio_asset')
            ->with('audioAsset')->latest('last_played_at')->paginate(30);

        return response()->json([
            'data' => $history->getCollection()->map(fn (PlayHistory $h) => [
                'progress_seconds' => $h->progress_seconds,
                'completed' => (bool) $h->completed,
                'last_played_at' => $h->last_played_at?->toIso8601String(),
                'asset' => $h->audioAsset ? (new AudioAssetResource($h->audioAsset))->resolve() : null,
            ]),
            'meta' => ['total' => $history->total()],
        ]);
    }

    public function continueListening(Request $request): JsonResponse
    {
        $items = $request->user()->playHistories()->where('playable_type', 'audio_asset')
            ->where('completed', false)->where('progress_seconds', '>', 15)
            ->with('audioAsset')->latest('last_played_at')->take(15)->get();

        return response()->json([
            'data' => $items->map(fn (PlayHistory $h) => [
                'progress_seconds' => $h->progress_seconds,
                'asset' => $h->audioAsset ? (new AudioAssetResource($h->audioAsset))->resolve() : null,
            ])->filter(fn ($i) => $i['asset'] !== null)->values(),
        ]);
    }

    // ---- Queue (FR-PLY-11) ----

    public function getQueue(Request $request): JsonResponse
    {
        $queue = $request->user()->queue;

        return response()->json([
            'items' => $queue?->items ?? [],
            'repeat_mode' => $queue?->repeat_mode ?? 'off',
            'shuffle' => (bool) ($queue?->shuffle ?? false),
        ]);
    }

    public function saveQueue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
            'repeat_mode' => ['nullable', Rule::in(['off', 'all', 'one'])],
            'shuffle' => ['boolean'],
        ]);

        UserQueue::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'items' => $data['items'],
                'repeat_mode' => $data['repeat_mode'] ?? 'off',
                'shuffle' => (bool) ($data['shuffle'] ?? false),
            ],
        );

        return response()->json(['message' => 'Queue saved.']);
    }

    /* ------------------------------------------------------------------ */

    private function ensureOwner(Request $request, Playlist $playlist): void
    {
        abort_unless($playlist->user_id === $request->user()->id, 403, 'You do not own this playlist.');
    }
}
