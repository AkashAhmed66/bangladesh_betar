<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M24 — home screen sections (FR-CUR-01/03).
 *
 * Dynamic section types (trending, new_releases, …) resolve their content at
 * request time; only 'custom' sections read from curated home_section_items,
 * which are managed separately / seeded.
 */
class HomeSectionController extends Controller
{
    private const SECTION_TYPES = [
        'custom', 'trending', 'new_releases', 'top_played', 'recently_played',
        'continue_listening', 'recommended', 'on_this_day', 'featured_artists',
        'featured_albums', 'curated_playlists',
    ];

    private const LAYOUTS = ['row', 'grid', 'banner', 'spotlight'];

    public function index(): View
    {
        $sections = HomeSection::query()
            ->withCount('items')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.home-sections.index', compact('sections'));
    }

    public function create(): View
    {
        $this->authorize('curation.manage');

        return view('admin.home-sections.form', ['section' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('curation.manage');

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);

        HomeSection::query()->create($data);

        return redirect()->route('admin.home-sections.index')->with('success', 'Home section created.');
    }

    public function edit(HomeSection $homeSection): View
    {
        $this->authorize('curation.manage');

        return view('admin.home-sections.form', ['section' => $homeSection] + $this->options());
    }

    public function update(Request $request, HomeSection $homeSection): RedirectResponse
    {
        $this->authorize('curation.manage');

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title'], $homeSection->id);

        $homeSection->update($data);

        return redirect()->route('admin.home-sections.index')->with('success', 'Home section updated.');
    }

    public function destroy(HomeSection $homeSection): RedirectResponse
    {
        $this->authorize('curation.manage');

        $homeSection->delete();

        return redirect()->route('admin.home-sections.index')->with('success', 'Home section removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'section_type' => ['required', Rule::in(self::SECTION_TYPES)],
            'layout' => ['required', Rule::in(self::LAYOUTS)],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'max_items' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    private function uniqueSlug(string $title, ?int $ignore = null): string
    {
        $base = Str::slug($title) ?: 'section';
        $slug = $base;
        $suffix = 1;

        while (HomeSection::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))
            ->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    private function options(): array
    {
        return [
            'sectionTypes' => collect(self::SECTION_TYPES)
                ->mapWithKeys(fn ($t) => [$t => ucwords(str_replace('_', ' ', $t))])->all(),
            'layouts' => collect(self::LAYOUTS)
                ->mapWithKeys(fn ($l) => [$l => ucfirst($l)])->all(),
        ];
    }
}
