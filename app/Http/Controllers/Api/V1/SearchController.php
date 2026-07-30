<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\EpisodeResource;
use App\Http\Resources\LiveChannelResource;
use App\Http\Resources\PodcastChannelResource;
use App\Http\Resources\PodcastEpisodeResource;
use App\Http\Resources\ProgrammeResource;
use App\Http\Resources\SongResource;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\BroadcastChannel;
use App\Models\Episode;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use App\Models\Song;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * M06 — public search & discovery over the published catalogue.
 *
 * Backed by Elasticsearch (typo-tolerant, relevance-ranked, Bangla + English)
 * via {@see SearchService}. Elasticsearch does the matching + ranking; the real
 * models are then re-hydrated from MySQL through the API Resources so the JSON
 * contract is unchanged. If Elasticsearch is unavailable the endpoints degrade
 * gracefully to the original SQL LIKE search — results are always filtered to
 * published, rights-cleared content (FR-SRC-05).
 */
class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['message' => 'Provide a search query with ?q=', 'data' => []], 422);
        }

        $type = $request->string('type')->toString(); // optional filter

        try {
            if (! $this->search->enabled()) {
                throw new \RuntimeException('Elasticsearch driver not active.');
            }
            $results = $this->elasticResults($q, $type);
        } catch (\Throwable $e) {
            report($e); // log, then serve the SQL fallback so search never 500s
            $results = $this->sqlResults($q, $type);
        }

        return response()->json(['query' => $q, 'results' => $results]);
    }

    /** FR-SRC-01 — type-ahead suggestions across titles and people. */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        try {
            if (! $this->search->enabled()) {
                throw new \RuntimeException('Elasticsearch driver not active.');
            }
            $data = $this->search->suggest($q);
        } catch (\Throwable $e) {
            report($e);
            $data = $this->sqlSuggest($q);
        }

        return response()->json(['data' => $data]);
    }

    /* ===================== Elasticsearch (primary) ===================== */

    /**
     * Hydrate the ranked Elasticsearch hits back into API Resources, preserving
     * the relevance order Elasticsearch returned.
     *
     * @return array<string, array{data: mixed}>
     */
    private function elasticResults(string $q, string $type): array
    {
        $ranked = $this->search->search($q, $type !== '' ? $type : null);

        $buckets = [
            'song' => fn (array $ids) => ['songs' => ['data' => SongResource::collection(
                $this->ordered(Song::query()->published()->with(['audioAsset', 'artists', 'album', 'genre']), $ids),
            )]],
            'artist' => fn (array $ids) => ['artists' => ['data' => ArtistResource::collection(
                $this->ordered(Artist::query()->published(), $ids),
            )]],
            'programme' => fn (array $ids) => ['programmes' => ['data' => ProgrammeResource::collection(
                $this->ordered(Programme::query()->published()->withCount('episodes'), $ids),
            )]],
            'episode' => fn (array $ids) => ['episodes' => ['data' => EpisodeResource::collection(
                $this->ordered(Episode::query()->published()->with('programme')->whereNotNull('audio_asset_id'), $ids),
            )]],
            'podcast' => fn (array $ids) => ['podcasts' => ['data' => PodcastChannelResource::collection(
                $this->ordered(PodcastChannel::query()->published(), $ids),
            )]],
            'podcast_episode' => fn (array $ids) => ['podcast_episodes' => ['data' => PodcastEpisodeResource::collection(
                $this->ordered(PodcastEpisode::query()->published()->with('channel')->whereNotNull('audio_asset_id'), $ids),
            )]],
            'live_radio' => fn (array $ids) => ['live_radios' => ['data' => LiveChannelResource::collection(
                $this->ordered(BroadcastChannel::query()->where('is_active', true)->with(['station', 'liveSession.broadcaster']), $ids),
            )]],
        ];

        $results = [];
        foreach ($buckets as $t => $build) {
            if (! array_key_exists($t, $ranked)) {
                continue; // type not requested
            }
            $ids = array_map(static fn (array $h): int => $h['id'], $ranked[$t]);
            $results += $build($ids);
        }

        return $results;
    }

    /**
     * Load models by id and return them in the exact order of $ids (the
     * Elasticsearch relevance order), dropping any that are no longer visible.
     */
    private function ordered(Builder $query, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $models = $query->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(static fn (int $id) => $models->get($id))
            ->filter()
            ->values();
    }

    /* ===================== SQL LIKE (fallback) ======================== */

    /**
     * The original SQL search, kept as a resilient fallback for when
     * Elasticsearch is unreachable. Mirrors the published-only scoping and now
     * also covers artists and live-radio channels for parity with the ES path.
     *
     * @return array<string, array{data: mixed}>
     */
    private function sqlResults(string $q, string $type): array
    {
        $like = '%'.$q.'%';
        $wants = fn (string $t): bool => $type === '' || $type === $t;

        // Transcript match: an item also matches when the query appears in the
        // spoken text of its recording.
        $txt = fn ($t) => $t->where('full_text', 'like', $like);

        $results = [];

        if ($wants('song')) {
            $results['songs'] = ['data' => SongResource::collection(
                Song::query()->published()->with(['audioAsset', 'artists', 'album', 'genre'])
                    ->where(function ($w) use ($like, $txt) {
                        $w->whereHas('audioAsset', fn ($a) => $a->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)))
                            ->orWhereHas('audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('artist')) {
            $results['artists'] = ['data' => ArtistResource::collection(
                Artist::query()->published()
                    ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('name_bn', 'like', $like))
                    ->take(20)->get(),
            )];
        }

        if ($wants('programme')) {
            $results['programmes'] = ['data' => ProgrammeResource::collection(
                Programme::query()->published()->withCount('episodes')
                    ->where(function ($w) use ($like, $txt) {
                        $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('audioAssets.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('episode')) {
            $results['episodes'] = ['data' => EpisodeResource::collection(
                Episode::query()->published()->with('programme')->whereNotNull('audio_asset_id')
                    ->where(function ($w) use ($like, $txt) {
                        $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('podcast')) {
            $results['podcasts'] = ['data' => PodcastChannelResource::collection(
                PodcastChannel::query()->published()
                    ->where(function ($w) use ($like, $txt) {
                        $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('episodes.audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('podcast_episode')) {
            $results['podcast_episodes'] = ['data' => PodcastEpisodeResource::collection(
                PodcastEpisode::query()->published()->with('channel')->whereNotNull('audio_asset_id')
                    ->where(function ($w) use ($like, $txt) {
                        $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('live_radio')) {
            $results['live_radios'] = ['data' => LiveChannelResource::collection(
                BroadcastChannel::query()->where('is_active', true)->with(['station', 'liveSession.broadcaster'])
                    ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('name_bn', 'like', $like)->orWhere('description', 'like', $like))
                    ->take(20)->get(),
            )];
        }

        return $results;
    }

    /** SQL fallback for {@see suggest()}. */
    private function sqlSuggest(string $q): array
    {
        $like = $q.'%';
        $bnQuery = (bool) preg_match('/[^\x00-\x7F]/', $q);

        return collect()
            ->merge(Song::query()->published()->with('audioAsset')
                ->whereHas('audioAsset', fn ($a) => $a->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)))
                ->take(5)->get()
                ->map(fn ($s) => ['text' => $bnQuery && $s->audioAsset?->title_bn ? $s->audioAsset->title_bn : $s->audioAsset?->title, 'type' => 'song']))
            ->merge(Artist::query()->published()
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('name_bn', 'like', $like))
                ->take(3)->get(['name', 'name_bn'])
                ->map(fn ($a) => ['text' => $bnQuery && $a->name_bn ? $a->name_bn : $a->name, 'type' => 'artist']))
            ->merge(Programme::query()->published()
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like))
                ->take(3)->get(['title', 'title_bn'])
                ->map(fn ($p) => ['text' => $bnQuery && $p->title_bn ? $p->title_bn : $p->title, 'type' => 'programme']))
            ->merge(PodcastChannel::query()->published()
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like))
                ->take(2)->get(['title', 'title_bn'])
                ->map(fn ($p) => ['text' => $bnQuery && $p->title_bn ? $p->title_bn : $p->title, 'type' => 'podcast']))
            ->filter(fn ($s) => ! empty($s['text']))
            ->unique('text')
            ->take(10)->values()
            ->all();
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
