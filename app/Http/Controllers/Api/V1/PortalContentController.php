<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\NewsCategory;
use App\Enums\WatchCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\NewsArticleResource;
use App\Http\Resources\WatchShowResource;
use App\Models\NewsArticle;
use App\Models\WatchShow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PortalContentController extends Controller
{
    public function news(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 24), 1), 50);
        $category = $this->newsCategory($request);
        $articlesQuery = NewsArticle::query()
            ->published()
            ->when($category, fn (Builder $query, NewsCategory $selected): Builder => $query->where('category', $selected->value));

        if ($request->string('sort')->toString() === 'latest') {
            $articlesQuery->orderByDesc('published_at')->orderByDesc('id');
        } else {
            $articlesQuery
                ->orderByDesc('is_featured')
                ->orderBy('position')
                ->orderByDesc('published_at');
        }

        $articles = $articlesQuery->paginate($perPage)->withQueryString();

        return NewsArticleResource::collection($articles);
    }

    public function newsArticle(string $slug): NewsArticleResource
    {
        $article = NewsArticle::query()->published()->where('slug', $slug)->firstOrFail();

        return new NewsArticleResource($article);
    }

    public function watch(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 24), 1), 50);
        $category = $this->watchCategory($request);
        $shows = WatchShow::query()
            ->published()
            ->with('publishedEpisodes')
            ->withCount('publishedEpisodes')
            ->when($category, fn (Builder $query, WatchCategory $selected): Builder => $query->where('category', $selected->value))
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();

        return WatchShowResource::collection($shows);
    }

    public function watchShow(string $slug): WatchShowResource
    {
        $show = WatchShow::query()
            ->published()
            ->with('publishedEpisodes')
            ->withCount('publishedEpisodes')
            ->where('slug', $slug)
            ->firstOrFail();

        return new WatchShowResource($show);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => [
                'news' => NewsCategory::metadata(),
                'watch' => WatchCategory::metadata(),
            ],
        ]);
    }

    private function newsCategory(Request $request): ?NewsCategory
    {
        if (! $request->filled('category')) {
            return null;
        }

        $category = NewsCategory::fromSlug($request->string('category')->toString());
        abort_if($category === null, 422, 'Unknown News category.');

        return $category;
    }

    private function watchCategory(Request $request): ?WatchCategory
    {
        if (! $request->filled('category')) {
            return null;
        }

        $category = WatchCategory::fromSlug($request->string('category')->toString());
        abort_if($category === null, 422, 'Unknown Watch category.');

        return $category;
    }
}
