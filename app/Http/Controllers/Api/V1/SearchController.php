<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\PodcastChannelResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\PodcastChannel;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M06 — public search & discovery over the published catalogue.
 * Results are always filtered to published, rights-cleared content (FR-SRC-05).
 */
class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['message' => 'Provide a search query with ?q=', 'data' => []], 422);
        }

        $like = '%'.$q.'%';
        $type = $request->string('type')->toString(); // optional filter

        $results = [];

        if ($type === '' || $type === 'song') {
            $results['songs'] = SongResource::collection(
                Song::query()->published()->with(['audioAsset', 'artists', 'album', 'genre'])
                    ->whereHas('audioAsset', fn ($a) => $a->where('title', 'like', $like)->orWhere('title_bn', 'like', $like))
                    ->take(10)->get(),
            );
        }

        if ($type === '' || $type === 'artist') {
            $results['artists'] = ArtistResource::collection(
                Artist::query()->published()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('name_bn', 'like', $like))->take(10)->get(),
            );
        }

        if ($type === '' || $type === 'album') {
            $results['albums'] = AlbumResource::collection(
                Album::query()->published()->where('title', 'like', $like)->with('artists')->take(10)->get(),
            );
        }

        if ($type === '' || $type === 'podcast') {
            $results['podcasts'] = PodcastChannelResource::collection(
                PodcastChannel::query()->published()->where('title', 'like', $like)->take(10)->get(),
            );
        }

        if ($type === '' || $type === 'programme' || $type === 'audio') {
            $results['audio'] = AudioAssetResource::collection(
                AudioAsset::query()->published()
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)->orWhere('archive_no', 'like', $like))
                    ->take(10)->get(),
            );
        }

        return response()->json(['query' => $q, 'results' => $results]);
    }

    /** FR-SRC-01 — type-ahead suggestions across titles, people, albums. */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }
        $like = $q.'%';

        $suggestions = collect()
            ->merge(AudioAsset::query()->published()->where('title', 'like', $like)->take(5)->pluck('title')
                ->map(fn ($t) => ['text' => $t, 'type' => 'title']))
            ->merge(Artist::query()->published()->where('name', 'like', $like)->take(4)->pluck('name')
                ->map(fn ($t) => ['text' => $t, 'type' => 'artist']))
            ->merge(Album::query()->published()->where('title', 'like', $like)->take(3)->pluck('title')
                ->map(fn ($t) => ['text' => $t, 'type' => 'album']))
            ->take(10)->values();

        return response()->json(['data' => $suggestions]);
    }

    /**
     * FR-SRC-09 — semantic (natural-language) search stub. Without an
     * embeddings backend this degrades to keyword search over metadata and
     * verified transcripts, but the endpoint contract is stable.
     */
    public function semantic(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['message' => 'Provide a query with ?q=', 'data' => []], 422);
        }

        $terms = collect(preg_split('/\s+/', $q))->filter(fn ($t) => strlen($t) > 2);

        $assets = AudioAsset::query()->published()
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->orWhere('title', 'like', "%$term%")
                        ->orWhere('description', 'like', "%$term%")
                        ->orWhereHas('transcripts', fn ($t) => $t->where('is_verified', true)->where('full_text', 'like', "%$term%"));
                }
            })
            ->orderByDesc('play_count')->take(20)->get();

        return response()->json([
            'query' => $q,
            'mode' => 'semantic (keyword fallback — embeddings backend not configured)',
            'data' => AudioAssetResource::collection($assets),
        ]);
    }
}
