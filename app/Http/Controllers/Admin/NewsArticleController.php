<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertNewsArticleRequest;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Services\ArtworkService;
use App\Services\EditorialApprovalService;
use App\Services\NewsArticleMediaService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class NewsArticleController extends Controller
{
    public function __construct(
        private readonly ArtworkService $artwork,
        private readonly EditorialApprovalService $approvals,
        private readonly NewsArticleMediaService $media,
    ) {}

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
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(UpsertNewsArticleRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/news', null);
        $data['is_published'] = false;
        $data['approval_status'] = 'draft';

        $article = NewsArticle::query()->create($data);
        try {
            $this->media->sync($article, $request);
        } catch (Throwable $exception) {
            $this->media->deleteAll($article);
            $this->artwork->delete($article->image_path);
            $article->forceDelete();

            throw $exception;
        }

        return redirect()->route('admin.news-articles.index')->with('success', 'News article created as a draft. Submit it for approval when it is ready.');
    }

    public function edit(NewsArticle $newsArticle): View
    {
        $this->authorize('news.manage');
        $this->authorizeRecordVisibility($newsArticle);
        $newsArticle->load(['approvals.currentStage', 'media']);

        return view('admin.news-articles.form', [
            'article' => $newsArticle,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpsertNewsArticleRequest $request, NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorizeRecordVisibility($newsArticle);

        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/news', $newsArticle->image_path);
        $newsArticle->update($data);
        $this->media->sync($newsArticle, $request);
        $this->approvals->invalidate($newsArticle, $request->user());

        return redirect()->route('admin.news-articles.edit', $newsArticle)->with('success', 'News article updated and returned to draft for fresh approval.');
    }

    public function submit(Request $request, NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('news.manage');
        $this->authorizeRecordVisibility($newsArticle);

        if (! $newsArticle->image_path) {
            return back()->with('error', 'Add the required lead image before submitting this article.');
        }

        try {
            $this->approvals->submit($newsArticle, $request->user(), 'news_article', $request->string('comments')->toString());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'News article submitted for approval. Assigned approvers have been notified.');
    }

    public function publish(NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('news.publish');
        $this->authorizeRecordVisibility($newsArticle);

        if ($newsArticle->approval_status !== 'approved') {
            return back()->with('error', 'This article must complete the approval workflow before it can be published.');
        }

        if (! $newsArticle->image_path) {
            return back()->with('error', 'A lead image is required before this article can be published.');
        }

        $newsArticle->update(['is_published' => true, 'published_at' => now()]);

        return back()->with('success', 'News article published to the public portal.');
    }

    public function unpublish(NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('news.publish');
        $this->authorizeRecordVisibility($newsArticle);
        $newsArticle->update(['is_published' => false]);

        return back()->with('success', 'News article removed from the public portal. Its approval remains valid until the content is edited.');
    }

    public function destroy(NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('news.manage');
        $this->authorizeRecordVisibility($newsArticle);

        $imagePath = $newsArticle->image_path;
        $this->media->deleteAll($newsArticle);
        $newsArticle->delete();
        $this->artwork->delete($imagePath);

        return redirect()->route('admin.news-articles.index')->with('success', 'News article removed.');
    }

    /** @return array<string, mixed> */
    private function payload(UpsertNewsArticleRequest $request): array
    {
        $data = $request->validated();
        $category = isset($data['news_category_id'])
            ? NewsCategory::query()->findOrFail($data['news_category_id'])
            : NewsCategory::query()->where('name', $data['category'])->firstOrFail();
        $data['news_category_id'] = $category->id;
        $data['category'] = $category->name;
        $data['body'] = array_values(array_filter(
            preg_split('/\R{2,}/u', trim((string) $data['body_text'])) ?: [],
            fn (string $paragraph): bool => trim($paragraph) !== '',
        ));
        $data['body_bn'] = filled($data['body_text_bn'] ?? null)
            ? array_values(array_filter(
                preg_split('/\R{2,}/u', trim((string) $data['body_text_bn'])) ?: [],
                fn (string $paragraph): bool => trim($paragraph) !== '',
            ))
            : null;
        $publishedAt = $data['published_at'] ?? null;
        $data['published_at'] = $publishedAt;
        if (is_string($data['published_at'])) {
            $data['published_at'] = CarbonImmutable::parse(
                $data['published_at'],
                (string) config('portal.admin_timezone'),
            )->utc();
        }

        unset(
            $data['body_text'],
            $data['body_text_bn'],
            $data['artwork'],
            $data['remove_artwork'],
            $data['is_published'],
            $data['images'],
            $data['videos'],
            $data['audios'],
            $data['documents'],
            $data['youtube_links'],
            $data['remove_media'],
        );

        return $data;
    }

    /** @return array<string, string> */
    private function categoryOptions(): array
    {
        return NewsCategory::query()->active()->with('parent')->orderByRaw('parent_id is not null')
            ->orderBy('position')->orderBy('name')->get()->mapWithKeys(fn (NewsCategory $category): array => [
                $category->name => $category->parent ? $category->parent->name.' — '.$category->name : $category->name,
            ])->all();
    }
}
