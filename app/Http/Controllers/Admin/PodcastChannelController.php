<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\PodcastChannel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * M09 — podcast channels (RSS-capable feeds).
 */
class PodcastChannelController extends Controller
{
    public function index(Request $request): View
    {
        $channels = PodcastChannel::query()
            ->with(['category', 'language', 'owner'])
            ->withCount('episodes')
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

        PodcastChannel::query()->create($data);

        return redirect()->route('admin.podcast-channels.index')->with('success', 'Podcast channel created.');
    }

    public function edit(PodcastChannel $podcastChannel): View
    {
        $this->authorize('podcasts.manage');

        return view('admin.podcast-channels.form', ['channel' => $podcastChannel] + $this->options());
    }

    public function update(Request $request, PodcastChannel $podcastChannel): RedirectResponse
    {
        $this->authorize('podcasts.manage');

        $podcastChannel->update($this->validated($request));

        return redirect()->route('admin.podcast-channels.index')->with('success', 'Podcast channel updated.');
    }

    public function destroy(PodcastChannel $podcastChannel): RedirectResponse
    {
        $this->authorize('podcasts.manage');

        $podcastChannel->delete();

        return redirect()->route('admin.podcast-channels.index')->with('success', 'Podcast channel removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'artwork_path' => ['nullable', 'string', 'max:255'],
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
            'owners' => User::query()->where('user_type', 'staff')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
