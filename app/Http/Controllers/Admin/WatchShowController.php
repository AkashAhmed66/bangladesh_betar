<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertWatchShowRequest;
use App\Models\WatchCategory;
use App\Models\WatchShow;
use App\Services\ArtworkService;
use App\Services\EditorialApprovalService;
use App\Services\VideoUploadService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WatchShowController extends Controller
{
    public function __construct(
        private readonly ArtworkService $artwork,
        private readonly VideoUploadService $videos,
        private readonly EditorialApprovalService $approvals,
    ) {}

    public function index(Request $request): View
    {
        $shows = WatchShow::query()
            ->visibleTo($request->user())
            ->withCount('episodes')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('category', 'like', '%'.$request->string('q').'%')))
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.watch-shows.index', compact('shows'));
    }

    public function create(): View
    {
        $this->authorize('watch.manage');

        return view('admin.watch-shows.form', [
            'show' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(UpsertWatchShowRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/watch', null);
        $data['trailer_path'] = $this->videos->sync($request, null, 'trailer', 'watch/trailers');
        $data['is_published'] = false;
        $data['approval_status'] = 'draft';
        $show = WatchShow::query()->create($data);

        return redirect()->route('admin.watch-shows.edit', $show)->with('success', 'Watch show created as a draft. Add its episodes, then submit it for approval.');
    }

    public function edit(WatchShow $watchShow): View
    {
        $this->authorize('watch.manage');
        $this->authorizeRecordVisibility($watchShow);
        $watchShow->load(['episodes', 'approvals.currentStage']);

        return view('admin.watch-shows.form', [
            'show' => $watchShow,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpsertWatchShowRequest $request, WatchShow $watchShow): RedirectResponse
    {
        $this->authorizeRecordVisibility($watchShow);

        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/watch', $watchShow->image_path);
        $data['trailer_path'] = $this->videos->sync($request, $watchShow->trailer_path, 'trailer', 'watch/trailers');
        $watchShow->update($data);
        $this->approvals->invalidate($watchShow, $request->user());

        return redirect()->route('admin.watch-shows.edit', $watchShow)->with('success', 'Watch show updated and returned to draft for fresh approval.');
    }

    public function submit(Request $request, WatchShow $watchShow): RedirectResponse
    {
        $this->authorize('watch.manage');
        $this->authorizeRecordVisibility($watchShow);

        if (! $watchShow->episodes()->where('is_published', true)->whereNotNull('video_path')->exists()) {
            return back()->with('error', 'Upload at least one active episode video before submitting this show for approval.');
        }

        try {
            $this->approvals->submit($watchShow, $request->user(), 'watch_show', $request->string('comments')->toString());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Watch show submitted for approval. Assigned approvers have been notified.');
    }

    public function publish(WatchShow $watchShow): RedirectResponse
    {
        $this->authorize('watch.publish');
        $this->authorizeRecordVisibility($watchShow);

        if ($watchShow->approval_status !== 'approved') {
            return back()->with('error', 'This show must complete the approval workflow before it can be published.');
        }
        if (! $watchShow->episodes()->where('is_published', true)->whereNotNull('video_path')->exists()) {
            return back()->with('error', 'A published episode video is required before this show can go live.');
        }

        $watchShow->update(['is_published' => true, 'published_at' => now()]);

        return back()->with('success', 'Watch show published to the public portal.');
    }

    public function unpublish(WatchShow $watchShow): RedirectResponse
    {
        $this->authorize('watch.publish');
        $this->authorizeRecordVisibility($watchShow);
        $watchShow->update(['is_published' => false]);

        return back()->with('success', 'Watch show removed from the public portal. Its approval remains valid until the content is edited.');
    }

    public function destroy(WatchShow $watchShow): RedirectResponse
    {
        $this->authorize('watch.manage');
        $this->authorizeRecordVisibility($watchShow);

        $watchShow->load('episodes');
        foreach ($watchShow->episodes as $episode) {
            $this->videos->delete($episode->video_path);
            $episode->delete();
        }

        $imagePath = $watchShow->image_path;
        $trailerPath = $watchShow->trailer_path;
        $watchShow->delete();
        $this->artwork->delete($imagePath);
        $this->videos->delete($trailerPath);

        return redirect()->route('admin.watch-shows.index')->with('success', 'Watch show and its episodes were removed.');
    }

    /** @return array<string, mixed> */
    private function payload(UpsertWatchShowRequest $request): array
    {
        $data = $request->validated();
        $category = isset($data['watch_category_id'])
            ? WatchCategory::query()->findOrFail($data['watch_category_id'])
            : WatchCategory::query()->where('name', $data['category'])->firstOrFail();
        $data['watch_category_id'] = $category->id;
        $data['category'] = $category->name;
        $publishedAt = $data['published_at'] ?? null;
        $data['published_at'] = $publishedAt;
        if (is_string($data['published_at'])) {
            $data['published_at'] = CarbonImmutable::parse(
                $data['published_at'],
                (string) config('portal.admin_timezone'),
            )->utc();
        }

        foreach (['genres', 'creators', 'cast', 'audio_languages', 'subtitle_languages'] as $field) {
            $data[$field] = $this->parseList($data[$field] ?? null);
        }

        if (array_key_exists('age_restriction', $data)) {
            $data['rating'] = $data['age_restriction'];
        }

        unset($data['artwork'], $data['remove_artwork'], $data['trailer'], $data['remove_trailer'], $data['is_published']);

        return $data;
    }

    /** @return array<int, string>|null */
    private function parseList(mixed $value): ?array
    {
        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value)) {
            $items = preg_split('/[,\n]+/', $value) ?: [];
        } else {
            return null;
        }

        $items = array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $items,
        ), static fn (string $item): bool => $item !== '')));

        return $items === [] ? null : $items;
    }

    /** @return array<string, string> */
    private function categoryOptions(): array
    {
        return WatchCategory::query()
            ->active()
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (WatchCategory $category): array => [
                $category->name => $category->slug === 'culture' ? 'Culture & music' : $category->name,
            ])
            ->all();
    }
}
