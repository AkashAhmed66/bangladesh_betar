<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\PodcastChannel;
use App\Models\User;
use App\Services\ArtworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * M09 — podcast channels (RSS-capable feeds).
 */
class PodcastChannelController extends Controller
{
    public function __construct(private readonly ArtworkService $artwork) {}

    public function index(Request $request): View
    {
        $channels = PodcastChannel::query()
            ->visibleTo($request->user())
            ->with(['category', 'language', 'owner'])
            ->withCount(['episodes' => fn ($episodes) => $episodes->withoutArchivedAudioAsset()])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('title_bn', 'like', '%'.$request->string('q').'%')))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('admin.podcast-channels.index', compact('channels'));
    }

    public function create(): View
    {
        $this->authorize('podcasts.manage');

        return view('admin.podcast-channels.form', ['channel' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('podcasts.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']);
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/podcasts', null);
        unset($data['artwork'], $data['remove_artwork']);

        PodcastChannel::query()->create($data);

        return redirect()->route('admin.podcast-channels.index')->with('success', 'Podcast channel created.');
    }

    public function edit(PodcastChannel $podcastChannel): View
    {
        $this->authorize('podcasts.manage');
        $this->authorizeRecordVisibility($podcastChannel);

        return view('admin.podcast-channels.form', ['channel' => $podcastChannel] + $this->options());
    }

    public function update(Request $request, PodcastChannel $podcastChannel): RedirectResponse
    {
        $this->authorize('podcasts.manage');
        $this->authorizeRecordVisibility($podcastChannel);

        $data = $this->validated($request);
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/podcasts', $podcastChannel->artwork_path);
        unset($data['artwork'], $data['remove_artwork']);

        $podcastChannel->update($data);

        return redirect()->route('admin.podcast-channels.index')->with('success', 'Podcast channel updated.');
    }

    public function destroy(PodcastChannel $podcastChannel): RedirectResponse
    {
        $this->authorize('podcasts.manage');
        $this->authorizeRecordVisibility($podcastChannel);

        $podcastChannel->delete();
        $this->artwork->delete($podcastChannel->artwork_path);

        return redirect()->route('admin.podcast-channels.index')->with('success', 'Podcast channel removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'rss_enabled' => ['boolean'],
            'rss_include_premium' => ['boolean'],
            'is_published' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'categories' => Category::query()->where('type', 'content')->orderBy('name')->pluck('name', 'id'),
            'languages' => Language::query()->orderBy('name')->pluck('name', 'id'),
            // Staff and dual-app accounts (e.g. artists) can own a channel.
            'owners' => User::query()->whereIn('user_type', ['staff', 'both'])->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
