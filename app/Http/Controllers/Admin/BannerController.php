<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * M24 — promotional banners for the public home screen (FR-CUR-01/03).
 */
class BannerController extends Controller
{
    /** Allowed banner targets: an external URL or a deep-link morph alias. */
    private const TARGET_TYPES = [
        'url', 'audio_asset', 'song', 'album', 'artist', 'playlist',
        'programme', 'podcast_channel', 'podcast_episode', 'episode',
    ];

    public function index(): View
    {
        $banners = Banner::query()
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        $this->authorize('curation.manage');

        return view('admin.banners.form', ['banner' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('curation.manage');

        Banner::query()->create($this->validated($request));

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function edit(Banner $banner): View
    {
        $this->authorize('curation.manage');

        return view('admin.banners.form', ['banner' => $banner] + $this->options());
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $this->authorize('curation.manage');

        $banner->update($this->validated($request));

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('curation.manage');

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Banner removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'target_type' => ['nullable', 'string', 'max:40'],
            'target_value' => ['nullable', 'string', 'max:2048'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    private function options(): array
    {
        return [
            'targetTypes' => collect(self::TARGET_TYPES)
                ->mapWithKeys(fn ($t) => [$t => $t === 'url' ? 'External URL' : ucwords(str_replace('_', ' ', $t))])->all(),
        ];
    }
}
