<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\WatchCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertWatchShowRequest;
use App\Models\WatchShow;
use App\Services\ArtworkService;
use App\Services\VideoUploadService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WatchShowController extends Controller
{
    public function __construct(
        private readonly ArtworkService $artwork,
        private readonly VideoUploadService $videos,
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
            'categories' => WatchCategory::options(),
        ]);
    }

    public function store(UpsertWatchShowRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/watch', null);
        $show = WatchShow::query()->create($data);

        return redirect()->route('admin.watch-shows.edit', $show)->with('success', 'Watch show created. You can now add episodes.');
    }

    public function edit(WatchShow $watchShow): View
    {
        $this->authorize('watch.manage');
        $this->authorizeRecordVisibility($watchShow);
        $watchShow->load('episodes');

        return view('admin.watch-shows.form', [
            'show' => $watchShow,
            'categories' => WatchCategory::options(),
        ]);
    }

    public function update(UpsertWatchShowRequest $request, WatchShow $watchShow): RedirectResponse
    {
        $this->authorizeRecordVisibility($watchShow);

        $data = $this->payload($request);
        $data['image_path'] = $this->artwork->sync($request, 'portal/watch', $watchShow->image_path);
        $watchShow->update($data);

        return redirect()->route('admin.watch-shows.edit', $watchShow)->with('success', 'Watch show updated.');
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
        $watchShow->delete();
        $this->artwork->delete($imagePath);

        return redirect()->route('admin.watch-shows.index')->with('success', 'Watch show and its episodes were removed.');
    }

    /** @return array<string, mixed> */
    private function payload(UpsertWatchShowRequest $request): array
    {
        $data = $request->validated();
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

        unset($data['artwork'], $data['remove_artwork']);

        return $data;
    }
}
