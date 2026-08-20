<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\NewsCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertNewsArticleRequest;
use App\Models\NewsArticle;
use App\Services\ArtworkService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NewsArticleController extends Controller
{
    public function __construct(private readonly ArtworkService $artwork) {}

    public function index(Request $request): View
    {
        $articles = NewsArticle::query()
            ->visibleTo($request->user())
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('category', 'like', '%'.$request->string('q').'%')))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderBy('position')
            ->paginate(12)
            ->withQueryString();

        return view('admin.news-articles.index', compact('articles'));
    }

    public function create(): View
    {
        $this->authorize('news.manage');

        return view('admin.news-articles.form', [
            'article' => null,
            'categories' => NewsCategory::options(),
        ]);
    }

    public function store(UpsertNewsArticleRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/news', null);

        NewsArticle::query()->create($data);

        return redirect()->route('admin.news-articles.index')->with('success', 'News article created.');
    }

    public function edit(NewsArticle $newsArticle): View
    {
        $this->authorize('news.manage');
        $this->authorizeRecordVisibility($newsArticle);

        return view('admin.news-articles.form', [
            'article' => $newsArticle,
            'categories' => NewsCategory::options(),
        ]);
    }

    public function update(UpsertNewsArticleRequest $request, NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorizeRecordVisibility($newsArticle);

        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/news', $newsArticle->image_path);
        $newsArticle->update($data);

        return redirect()->route('admin.news-articles.index')->with('success', 'News article updated.');
    }

    public function destroy(NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('news.manage');
        $this->authorizeRecordVisibility($newsArticle);

        $imagePath = $newsArticle->image_path;
        $newsArticle->delete();
        $this->artwork->delete($imagePath);

        return redirect()->route('admin.news-articles.index')->with('success', 'News article removed.');
    }

    /** @return array<string, mixed> */
    private function payload(UpsertNewsArticleRequest $request): array
    {
        $data = $request->validated();
        $data['body'] = array_values(array_filter(
            preg_split('/\R{2,}/u', trim((string) $data['body_text'])) ?: [],
            fn (string $paragraph): bool => trim($paragraph) !== '',
        ));
        $publishedAt = $data['published_at'] ?? null;
        $data['published_at'] = $request->boolean('is_published')
            ? ($publishedAt ?? now())
            : $publishedAt;
        if (is_string($data['published_at'])) {
            $data['published_at'] = CarbonImmutable::parse(
                $data['published_at'],
                (string) config('portal.admin_timezone'),
            )->utc();
        }

        unset($data['body_text'], $data['artwork'], $data['remove_artwork']);

        return $data;
    }
}
