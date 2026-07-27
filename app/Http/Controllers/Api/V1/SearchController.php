<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\EpisodeResource;
use App\Http\Resources\PodcastChannelResource;
use App\Http\Resources\PodcastEpisodeResource;
use App\Http\Resources\ProgrammeResource;
use App\Http\Resources\SongResource;
use App\Models\AudioAsset;
use App\Models\Episode;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
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
        $wants = fn (string $t): bool => $type === '' || $type === $t;

        // POC transcript search: a catalogue item also matches when the query
        // appears in the spoken text of its recording. Reused across buckets via
        // the relevant relation path to `transcripts.full_text`. (POC: matches
        // ALL transcripts incl. unverified/AI drafts; tighten with
        // ->where('is_verified', true) later.)
        $txt = fn ($t) => $t->where('full_text', 'like', $like);

        // Every bucket is wrapped as { data: [...] } so the client can read a
        // consistent shape (a bare resource collection nested inside a json
        // array loses its wrapper). Titles are matched on both the English
        // (`title`) and Bangla (`title_bn`) columns.
        $results = [];

        if ($wants('song')) {
            $results['songs'] = ['data' => SongResource::collection(
                Song::query()->published()->with(['audioAsset', 'artists', 'album', 'genre'])
                    ->where(function ($q) use ($like, $txt) {
                        $q->whereHas('audioAsset', fn ($a) => $a->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)))
                            ->orWhereHas('audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('programme')) {
            $results['programmes'] = ['data' => ProgrammeResource::collection(
                Programme::query()->published()->withCount('episodes')
                    ->where(function ($q) use ($like, $txt) {
                        $q->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('audioAssets.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('episode')) {
            $results['episodes'] = ['data' => EpisodeResource::collection(
                Episode::query()->published()->with('programme')->whereNotNull('audio_asset_id')
                    ->where(function ($q) use ($like, $txt) {
                        $q->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('podcast_episode')) {
            $results['podcast_episodes'] = ['data' => PodcastEpisodeResource::collection(
                PodcastEpisode::query()->published()->with('channel')->whereNotNull('audio_asset_id')
                    ->where(function ($q) use ($like, $txt) {
                        $q->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        if ($wants('podcast')) {
            $results['podcasts'] = ['data' => PodcastChannelResource::collection(
                PodcastChannel::query()->published()
                    ->where(function ($q) use ($like, $txt) {
                        $q->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)
                            ->orWhereHas('episodes.audioAsset.transcripts', $txt);
                    })
                    ->take(20)->get(),
            )];
        }

        // Public search is limited to the published *catalogue* — songs,
        // programmes and podcasts (with their episodes). Raw archive
        // recordings (AudioAssets) and internal metadata (artists/albums) are
        // deliberately NOT exposed here.
        return response()->json(['query' => $q, 'results' => $results]);
    }

    /** FR-SRC-01 — type-ahead suggestions across titles, people, albums. */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }
        $like = $q.'%';
        // A Bangla (non-ASCII) query should surface Bangla titles as the label.
        $bnQuery = (bool) preg_match('/[^\x00-\x7F]/', $q);

        // Suggestions mirror the search scope: only the published catalogue
        // (songs / programmes / podcasts), never raw archive recordings.
        $suggestions = collect()
            ->merge(Song::query()->published()->with('audioAsset')
                ->whereHas('audioAsset', fn ($a) => $a->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_bn', 'like', $like)))
                ->take(5)->get()
                ->map(fn ($s) => ['text' => $bnQuery && $s->audioAsset?->title_bn ? $s->audioAsset->title_bn : $s->audioAsset?->title, 'type' => 'song']))
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
