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
use App\Models\HomeSection;
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
        $user = $request->user();

        $sections = HomeSection::query()->live()->with('items.curatable')->get()
            ->map(fn (HomeSection $section) => [
                'id' => $section->id,
                'title' => $section->title,
                'title_bn' => $section->title_bn,
                'type' => $section->section_type,
                'layout' => $section->layout,
                'items' => $this->resolveSectionItems($section, $user),
            ])
            ->filter(fn ($section) => ! empty($section['items']))
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

    public function editorialPlaylists(): JsonResponse
    {
        return \App\Http\Resources\PlaylistResource::collection(
            Playlist::query()->editorial()->withCount('items')->take(20)->get(),
        )->response();
    }

    /* ------------------------------------------------------------------ */

    private function resolveSectionItems(HomeSection $section, ?\App\Models\User $user): array
    {
        $items = match ($section->section_type) {
            'trending' => AudioAsset::query()->published()->orderByDesc('play_count')->take($section->max_items)->get()
                ->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'new_releases' => AudioAsset::query()->published()->latest('published_at')->take($section->max_items)->get()
                ->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'top_played' => AudioAsset::query()->published()->orderByDesc('play_count')->take($section->max_items)->get()
                ->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'on_this_day' => AudioAsset::query()->published()
                ->whereMonth('first_broadcast_on', now()->month)->whereDay('first_broadcast_on', now()->day)
                ->take($section->max_items)->get()->map(fn ($a) => (new AudioAssetResource($a))->resolve()),
            'featured_artists' => Artist::query()->published()->where('is_featured', true)->take($section->max_items)->get()
                ->map(fn ($a) => (new ArtistResource($a))->resolve()),
            'featured_albums' => Album::query()->published()->withCount('songs')->take($section->max_items)->get()
                ->map(fn ($a) => (new AlbumResource($a))->resolve()),
            'curated_playlists' => Playlist::query()->editorial()->withCount('items')->take($section->max_items)->get()
                ->map(fn ($p) => (new \App\Http\Resources\PlaylistResource($p))->resolve()),
            default => $this->manualItems($section), // 'custom' and others use curated items
        };

        return $items->values()->all();
    }

    private function manualItems(HomeSection $section): \Illuminate\Support\Collection
    {
        return $section->items->map(function ($item) {
            $model = $item->curatable;

            return match (true) {
                $model instanceof Song => (new SongResource($model))->resolve(),
                $model instanceof Album => (new AlbumResource($model))->resolve(),
                $model instanceof Artist => (new ArtistResource($model))->resolve(),
                $model instanceof Playlist => (new \App\Http\Resources\PlaylistResource($model))->resolve(),
                $model instanceof PodcastChannel => (new PodcastChannelResource($model))->resolve(),
                $model instanceof Programme => (new ProgrammeResource($model))->resolve(),
                $model instanceof AudioAsset => (new AudioAssetResource($model))->resolve(),
                default => null,
            };
        })->filter();
    }
}
