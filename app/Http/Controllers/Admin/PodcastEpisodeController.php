<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M09 — podcast episodes (scheduled publishing, premium gating).
 */
class PodcastEpisodeController extends Controller
{
    public function index(Request $request): View
    {
        $episodes = PodcastEpisode::query()
            ->visibleTo($request->user())
            ->with(['channel', 'audioAsset'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('channel'), fn ($q) => $q->where('podcast_channel_id', $request->integer('channel')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.podcast-episodes.index', compact('episodes') + $this->options());
    }

    public function create(): View
    {
        $this->authorize('podcasts.manage');

        return view('admin.podcast-episodes.form', ['episode' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('podcasts.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']).'-'.uniqid();

        PodcastEpisode::query()->create($data);

        return redirect()->route('admin.podcast-episodes.index')->with('success', 'Podcast episode created.');
    }

    public function edit(PodcastEpisode $podcastEpisode): View
    {
        $this->authorize('podcasts.manage');
        $this->authorizeRecordVisibility($podcastEpisode);

        return view('admin.podcast-episodes.form', ['episode' => $podcastEpisode] + $this->options());
    }

    public function update(Request $request, PodcastEpisode $podcastEpisode): RedirectResponse
    {
        $this->authorize('podcasts.manage');
        $this->authorizeRecordVisibility($podcastEpisode);

        $podcastEpisode->update($this->validated($request));

        return redirect()->route('admin.podcast-episodes.index')->with('success', 'Podcast episode updated.');
    }

    public function destroy(PodcastEpisode $podcastEpisode): RedirectResponse
    {
        $this->authorize('podcasts.manage');
        $this->authorizeRecordVisibility($podcastEpisode);

        $podcastEpisode->delete();

        return redirect()->route('admin.podcast-episodes.index')->with('success', 'Podcast episode removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'podcast_channel_id' => ['required', 'exists:podcast_channels,id'],
            'audio_asset_id' => ['nullable', 'exists:audio_assets,id'],
            'season_number' => ['required', 'integer', 'min:1'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_premium' => ['boolean'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'unpublished'])],
            'scheduled_at' => ['nullable', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function options(): array
    {
        return [
            'channels' => PodcastChannel::query()->visibleTo(auth()->user())->orderBy('title')->pluck('title', 'id'),
            // Asset picker honours record visibility for restricted users.
            'assets' => AudioAsset::query()->visibleTo(auth()->user())->where('content_type', 'podcast')->orderByDesc('id')->take(200)->pluck('title', 'id'),
        ];
    }
}
