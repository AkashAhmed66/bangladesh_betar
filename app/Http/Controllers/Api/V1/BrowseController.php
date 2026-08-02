<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\PresentsCataloguedAssets;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\PodcastChannelResource;
use App\Http\Resources\PodcastEpisodeResource;
use App\Http\Resources\ProgrammeResource;
use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M17/M24/M25 — public discovery: home screen, curated sections,
 * banners, browse-by-category and On This Day.
 */
class BrowseController extends Controller
{
    use PresentsCataloguedAssets;

    /**
     * Home screen: curated + dynamic rows assembled from live sections (FR-PUB-01).
     */
    public function home(Request $request): JsonResponse
    {
        // Static home rows (no longer admin-managed). The CONTENT of each row is
        // still live (trending / latest / most-played …); only the SET of rows
        // is fixed here in code.
        $rows = [
            ['trending', 'Trending Now', 'এখন ট্রেন্ডিং'],
            ['new_releases', 'New Releases', 'নতুন প্রকাশ'],
            ['on_this_day', 'On This Day', 'ইতিহাসের এই দিনে'],
            ['top_played', 'Top Played', 'সর্বাধিক শোনা'],
        ];

        $sections = collect($rows)
            ->map(fn (array $r) => [
                'id' => $r[0],
                'title' => $r[1],
                'title_bn' => $r[2],
                'type' => $r[0],
                'layout' => 'row',
                'items' => $this->resolveSectionItems($r[0], 12),
            ])
            ->filter(fn (array $section) => ! empty($section['items']))
            ->values();

        return response()->json([
            'banners' => Banner::query()->live()->get()->map(fn (Banner $b) => [
                'id' => $b->id,
                'title' => $b->title,
                'title_bn' => $b->title_bn,
                'subtitle' => $b->subtitle,
                'image_url' => $b->image_path ? asset('storage/'.$b->image_path) : null,
                'target_type' => $b->target_type,
                'target_value' => $b->target_value,
            ]),
            'sections' => $sections,
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()->where('is_active', true)->where('type', 'content')
                ->orderBy('name')->get()->map(fn (Category $c) => [
                    'id' => $c->id, 'name' => $c->name, 'name_bn' => $c->name_bn, 'slug' => $c->slug,
                ]),
        ]);
    }

    public function genres(): JsonResponse
    {
        return response()->json([
            'data' => Genre::query()->where('is_active', true)->orderBy('name')->get()
                ->map(fn (Genre $g) => ['id' => $g->id, 'name' => $g->name, 'name_bn' => $g->name_bn, 'slug' => $g->slug]),
        ]);
    }

    /** FR-ANL-06 — trending over a configurable window (catalogued content only). */
    public function trending(Request $request): JsonResponse
    {
        $assets = AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
            ->where('published_at', '>=', now()->subDays(30))
            ->orderByDesc('play_count')
            ->take(20)->get();

        return response()->json(['data' => $this->presentCatalogued($assets)]);
    }

    public function topPlayed(): JsonResponse
    {
        $assets = AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
            ->orderByDesc('play_count')->take(20)->get();

        return response()->json(['data' => $this->presentCatalogued($assets)]);
    }

    /** New Releases — the latest published songs, podcast episodes and programmes. */
    public function newReleases(): JsonResponse
    {
        return response()->json(['data' => $this->newReleaseItems(20)]);
    }

    /** FR-CUR-04 — On This Day: content first broadcast on today's date (catalogued only). */
    public function onThisDay(): JsonResponse
    {
        $assets = AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
            ->whereMonth('first_broadcast_on', now()->month)
            ->whereDay('first_broadcast_on', now()->day)
            ->orderByDesc('play_count')->take(20)->get();

        return response()->json([
            'date' => now()->toDateString(),
            'data' => $this->presentCatalogued($assets),
        ]);
    }

    public function featuredArtists(): JsonResponse
    {
        return ArtistResource::collection(
            Artist::query()->published()->where('is_featured', true)->take(20)->get(),
        )->response();
    }

    /**
     * A pool of randomly-ordered published recordings, used to power the
     * "radio" autoplay that free listeners drop into once they have spent
     * their daily pick budget. Excludes premium-only content so the mix keeps
     * playing without hitting a preview wall.
     */
    public function radio(Request $request): JsonResponse
    {
        $exclude = array_filter(array_map('intval', explode(',', (string) $request->query('exclude', ''))));

        $assets = AudioAsset::query()->published()->catalogued()
            ->where('is_premium', false)
            ->when($exclude, fn ($q) => $q->whereNotIn('id', $exclude))
            ->inRandomOrder()
            ->take(25)->get();

        return AudioAssetResource::collection($assets)->response();
    }

    /* ------------------------------------------------------------------ */

    private function resolveSectionItems(string $type, int $max): array
    {
        $items = match ($type) {
            'trending', 'top_played' => $this->presentCatalogued(
                AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
                    ->orderByDesc('play_count')->take($max)->get(),
            ),
            'new_releases' => collect($this->newReleaseItems($max)),
            'on_this_day' => $this->presentCatalogued(
                AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
                    ->whereMonth('first_broadcast_on', now()->month)->whereDay('first_broadcast_on', now()->day)
                    ->take($max)->get(),
            ),
            'featured_artists' => Artist::query()->published()->where('is_featured', true)->take($max)->get()
                ->map(fn ($a) => (new ArtistResource($a))->resolve()),
            'featured_albums' => Album::query()->published()->withCount('songs')->take($max)->get()
                ->map(fn ($a) => (new AlbumResource($a))->resolve()),
            default => collect(),
        };

        return $items->values()->all();
    }

    /**
     * "New Releases": the newest published songs, podcast episodes and
     * programmes, interleaved by publish date.
     */
    private function newReleaseItems(int $max): array
    {
        $songs = Song::query()->published()->with(['audioAsset', 'artists', 'genre'])
            ->join('audio_assets', 'audio_assets.id', '=', 'songs.audio_asset_id')
            ->orderByDesc('audio_assets.published_at')
            ->select('songs.*')
            ->take($max)->get();

        $episodes = PodcastEpisode::query()->published()->with(['channel', 'audioAsset'])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->take($max)->get();

        $programmes = Programme::query()->published()->withCount('episodes')
            ->latest()->take($max)->get();

        return collect()
            ->concat($songs->map(fn (Song $s) => [
                'at' => $s->audioAsset?->published_at ?? $s->created_at,
                'item' => (new SongResource($s))->resolve(),
            ]))
            ->concat($episodes->map(fn (PodcastEpisode $e) => [
                'at' => $e->published_at ?? $e->created_at,
                'item' => (new PodcastEpisodeResource($e))->resolve(),
            ]))
            ->concat($programmes->map(fn (Programme $p) => [
                'at' => $p->created_at,
                'item' => (new ProgrammeResource($p))->resolve(),
            ]))
            ->sortByDesc('at')
            ->take($max)
            ->pluck('item')
            ->values()
            ->all();
    }
}
