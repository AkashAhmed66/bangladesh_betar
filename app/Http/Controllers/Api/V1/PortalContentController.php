<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsArticleResource;
use App\Http\Resources\WatchShowResource;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\WatchCategory;
use App\Models\WatchShow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class PortalContentController extends Controller
{
    public function news(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 24), 1), 50);
        $category = $this->newsCategory($request);
        $categoryIds = $category === null
            ? []
            : ($category->parent_id === null ? $category->children()->pluck('id')->prepend($category->id)->all() : [$category->id]);
        $categoryNames = $category === null
            ? []
            : ($category->parent_id === null ? $category->children()->pluck('name')->prepend($category->name)->all() : [$category->name]);
        $articlesQuery = NewsArticle::query()
            ->published()
            ->with('portalCategory')
            ->when($category, fn (Builder $query): Builder => $query->where(function (Builder $match) use ($categoryIds, $categoryNames): void {
                $match->whereIn('news_category_id', $categoryIds)->orWhereIn('category', $categoryNames);
            }))
            ->when($request->boolean('featured'), fn (Builder $query): Builder => $query->where('is_featured', true))
            ->when($request->boolean('exclude_featured'), fn (Builder $query): Builder => $query->where('is_featured', false));

        $this->applySearch($articlesQuery, $request, ['title', 'title_bn', 'summary', 'summary_bn', 'category']);

        if ($request->string('sort')->toString() === 'popular') {
            $articlesQuery->orderByDesc('views_count')->orderByDesc('published_at')->orderByDesc('id');
        } elseif ($request->string('sort')->toString() === 'latest') {
            $articlesQuery->orderByDesc('published_at')->orderByDesc('id');
        } else {
            $articlesQuery->orderByDesc('is_featured')->orderBy('position')->orderByDesc('published_at');
        }

        return NewsArticleResource::collection($articlesQuery->paginate($perPage)->withQueryString());
    }

    public function newsArticle(string $slug): NewsArticleResource
    {
        return new NewsArticleResource(
            NewsArticle::query()->published()->with(['media', 'portalCategory'])->where('slug', $slug)->firstOrFail(),
        );
    }

    public function recordNewsView(string $slug): Response
    {
        NewsArticle::query()->published()->where('slug', $slug)->firstOrFail()->increment('views_count');

        return response()->noContent();
    }

    public function watch(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 24), 1), 50);
        $category = $this->watchCategory($request);
        $shows = WatchShow::query()
            ->published()
            ->with(['publishedEpisodes', 'portalCategory'])
            ->withCount('publishedEpisodes')
            ->when($category, fn (Builder $query, WatchCategory $selected): Builder => $query->where(
                fn (Builder $match): Builder => $match->where('watch_category_id', $selected->id)->orWhere('category', $selected->name),
            ));

        $this->applySearch($shows, $request, ['title', 'title_bn', 'description', 'description_bn', 'category']);

        $shows = $shows
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();

        return WatchShowResource::collection($shows);
    }

    public function watchShow(string $slug): WatchShowResource
    {
        return new WatchShowResource(
            WatchShow::query()
                ->published()
                ->with(['publishedEpisodes', 'portalCategory'])
                ->withCount('publishedEpisodes')
                ->where('slug', $slug)
                ->firstOrFail(),
        );
    }

    /** Public metadata for social cards; episodes and video URLs are intentionally excluded. */
    public function watchShowPreview(string $slug): WatchShowResource
    {
        return new WatchShowResource(
            WatchShow::query()
                ->published()
                ->with('portalCategory')
                ->withCount('publishedEpisodes')
                ->where('slug', $slug)
                ->firstOrFail(),
        );
    }

    public function categories(): JsonResponse
    {
        return response()->json(['data' => [
            'news' => NewsCategory::query()->active()->whereNull('parent_id')
                ->with(['children' => fn ($query) => $query->active()])
                ->orderBy('position')->orderBy('name')->get()
                ->map(fn (NewsCategory $category): array => $this->categoryMetadata($category, true))->all(),
            'watch' => WatchCategory::query()->active()->orderBy('position')->get()->map(fn (WatchCategory $category): array => $this->categoryMetadata($category))->all(),
        ]]);
    }

    private function newsCategory(Request $request): ?NewsCategory
    {
        if (! $request->filled('category')) {
            return null;
        }
        $category = NewsCategory::query()->active()->where('slug', $request->string('category')->toString())->first();
        abort_if($category === null, 422, 'Unknown News category.');

        return $category;
    }

    private function watchCategory(Request $request): ?WatchCategory
    {
        if (! $request->filled('category')) {
            return null;
        }
        $category = WatchCategory::query()->active()->where('slug', $request->string('category')->toString())->first();
        abort_if($category === null, 422, 'Unknown Watch category.');

        return $category;
    }

    /** @param array<int, string> $columns */
    private function applySearch(Builder $query, Request $request, array $columns): void
    {
        $term = trim($request->string('q')->toString());
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $match) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $match->{$method}($column, 'like', '%'.$term.'%');
            }
        });
    }

    /** @return array<string, mixed> */
    private function categoryMetadata(NewsCategory|WatchCategory $category, bool $withChildren = false): array
    {
        $metadata = [
            'id' => $category->id,
            'parent_id' => $category instanceof NewsCategory ? $category->parent_id : null,
            'value' => $category->name,
            'label' => $category->name,
            'label_bn' => $category->name_bn,
            'slug' => $category->slug,
            'description' => $category->description,
            'description_bn' => $category->description_bn,
            'show_in_header' => $category->show_in_header,
        ];

        if ($withChildren && $category instanceof NewsCategory) {
            $metadata['subcategories'] = $category->children
                ->map(fn (NewsCategory $child): array => $this->categoryMetadata($child))->values()->all();
        }

        return $metadata;
    }
}
