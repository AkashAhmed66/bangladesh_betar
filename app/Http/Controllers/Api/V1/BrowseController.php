<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\AudioAssetResource;
use App\Http\Resources\PodcastChannelResource;
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
            ['new_releases', 'New to the Archive', 'নতুন প্রকাশ'],
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

    /** FR-ANL-06 — trending over a configurable window. */
    public function trending(Request $request): JsonResponse
    {
        $assets = AudioAsset::query()->published()
            ->where('published_at', '>=', now()->subDays(30))
            ->orderByDesc('play_count')
            ->take(20)->get();

        return AudioAssetResource::collection($assets)->response();
    }

    public function topPlayed(): JsonResponse
    {
        return AudioAssetResource::collection(
            AudioAsset::query()->published()->orderByDesc('play_count')->take(20)->get(),
        )->response();
    }

    public function newReleases(): JsonResponse
    {
        return AudioAssetResource::collection(
            AudioAsset::query()->published()->latest('published_at')->take(20)->get(),
        )->response();
    }

    /** FR-CUR-04 — On This Day: content first broadcast on today's date. */
    public function onThisDay(): JsonResponse
    {
        $assets = AudioAsset::query()->published()
            ->whereMonth('first_broadcast_on', now()->month)
            ->whereDay('first_broadcast_on', now()->day)
            ->orderByDesc('play_count')->take(20)->get();

        return response()->json([
            'date' => now()->toDateString(),
            'data' => AudioAssetResource::collection($assets),
        ]);
    }

    public function featuredArtists(): JsonResponse
    {
        return ArtistResource::collection(
            Artist::query()->published()->where('is_featured', true)->take(20)->get(),
        )->response();
    }

    /* ------------------------------------------------------------------ */

    private function resolveSectionItems(string $type, int $max): array
    {
        $items = match ($type) {
            'trending' => AudioAsset::query()->published()->orderByDesc('play_count')->take($max)->get()
                ->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'new_releases' => AudioAsset::query()->published()->latest('published_at')->take($max)->get()
                ->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'top_played' => AudioAsset::query()->published()->orderByDesc('play_count')->take($max)->get()
                ->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'on_this_day' => AudioAsset::query()->published()
                ->whereMonth('first_broadcast_on', now()->month)->whereDay('first_broadcast_on', now()->day)
                ->take($max)->get()->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'featured_artists' => Artist::query()->published()->where('is_featured', true)->take($max)->get()
                ->map(fn ($a) => (new ArtistResource($a))->resolve()),
            'featured_albums' => Album::query()->published()->withCount('songs')->take($max)->get()
                ->map(fn ($a) => (new AlbumResource($a))->resolve()),
            default => collect(),
        };

        return $items->values()->all();
    }
}
