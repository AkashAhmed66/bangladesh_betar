<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertWatchEpisodeRequest;
use App\Models\WatchEpisode;
use App\Models\WatchShow;
use App\Services\EditorialApprovalService;
use App\Services\VideoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class WatchEpisodeController extends Controller
{
    public function __construct(
        private readonly VideoUploadService $videos,
        private readonly EditorialApprovalService $approvals,
    ) {}

    public function create(WatchShow $watchShow): View
    {
        $this->authorize('watch.manage');
        $this->authorizeRecordVisibility($watchShow);

        return view('admin.watch-episodes.form', [
            'show' => $watchShow,
            'episode' => null,
        ]);
    }

    public function store(UpsertWatchEpisodeRequest $request, WatchShow $watchShow): RedirectResponse
    {
        $this->authorizeRecordVisibility($watchShow);

        $data = $request->validated();
        $data = $this->normaliseMetadata($data);
        $data['video_path'] = $this->videos->sync($request, null);
        unset($data['video'], $data['remove_video']);
        $watchShow->episodes()->create($data);
        $this->approvals->invalidate($watchShow, $request->user());

        return redirect()->route('admin.watch-shows.edit', $watchShow)->with('success', 'Episode added. The show is a draft until it completes approval.');
    }

    public function edit(WatchEpisode $watchEpisode): View
    {
        $this->authorize('watch.manage');
        $watchEpisode->load('show');
        $this->authorizeRecordVisibility($watchEpisode->show);

        return view('admin.watch-episodes.form', [
            'show' => $watchEpisode->show,
            'episode' => $watchEpisode,
        ]);
    }

    public function update(UpsertWatchEpisodeRequest $request, WatchEpisode $watchEpisode): RedirectResponse
    {
        $watchEpisode->load('show');
        $this->authorizeRecordVisibility($watchEpisode->show);

        $data = $request->validated();
        $data = $this->normaliseMetadata($data);
        $data['video_path'] = $this->videos->sync($request, $watchEpisode->video_path);
        unset($data['video'], $data['remove_video']);
        $watchEpisode->update($data);
        $this->approvals->invalidate($watchEpisode->show, $request->user());

        return redirect()->route('admin.watch-shows.edit', $watchEpisode->show)->with('success', 'Episode updated. The show was unpublished and returned to draft for approval.');
    }

    public function destroy(WatchEpisode $watchEpisode): RedirectResponse
    {
        $this->authorize('watch.manage');
        $watchEpisode->load('show');
        $this->authorizeRecordVisibility($watchEpisode->show);

        $show = $watchEpisode->show;
        $videoPath = $watchEpisode->video_path;
        $watchEpisode->delete();
        $this->videos->delete($videoPath);
        $this->approvals->invalidate($show, auth()->user());

        return redirect()->route('admin.watch-shows.edit', $show)->with('success', 'Episode removed. The show was unpublished and returned to draft for approval.');
    }

    /** @param array<string, mixed> $data */
    private function normaliseMetadata(array $data): array
    {
        foreach (['audio_languages', 'subtitle_languages'] as $field) {
            $value = $data[$field] ?? null;
            $items = is_string($value) ? (preg_split('/[,\n]+/', $value) ?: []) : (is_array($value) ? $value : []);
            $items = array_values(array_unique(array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                $items,
            ), static fn (string $item): bool => $item !== '')));
            $data[$field] = $items === [] ? null : $items;
        }

        return $data;
    }
}
