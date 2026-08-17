<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Programme;
use App\Models\Station;
use App\Services\ArtworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgrammeController extends Controller
{
    public function __construct(private readonly ArtworkService $artwork) {}

    public function index(Request $request): View
    {
        $programmes = Programme::query()
            ->visibleTo($request->user())
            ->with(['station', 'category'])
            ->withCount([
                'episodes' => fn ($episodes) => $episodes->withoutArchivedAudioAsset(),
                'audioAssets' => fn ($assets) => $assets->notArchived(),
            ])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('admin.programmes.index', compact('programmes'));
    }

    public function create(): View
    {
        $this->authorize('programmes.manage');

        return view('admin.programmes.form', ['programme' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('programmes.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']);
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/programmes', null);
        unset($data['artwork'], $data['remove_artwork']);

        Programme::query()->create($data);

        return redirect()->route('admin.programmes.index')->with('success', 'Programme created.');
    }

    public function edit(Programme $programme): View
    {
        $this->authorize('programmes.manage');
        $this->authorizeRecordVisibility($programme);

        return view('admin.programmes.form', ['programme' => $programme] + $this->options());
    }

    public function update(Request $request, Programme $programme): RedirectResponse
    {
        $this->authorize('programmes.manage');
        $this->authorizeRecordVisibility($programme);

        $data = $this->validated($request);
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/programmes', $programme->artwork_path);
        unset($data['artwork'], $data['remove_artwork']);

        $programme->update($data);

        return redirect()->route('admin.programmes.index')->with('success', 'Programme updated.');
    }

    public function destroy(Programme $programme): RedirectResponse
    {
        $this->authorize('programmes.manage');
        $this->authorizeRecordVisibility($programme);

        $programme->delete();
        $this->artwork->delete($programme->artwork_path);

        return redirect()->route('admin.programmes.index')->with('success', 'Programme removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'programme_type' => ['required', Rule::in(['programme', 'drama', 'news', 'event', 'magazine', 'talk_show'])],
            'station_id' => ['nullable', 'exists:stations,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
            'is_published' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'stations' => Station::query()->orderBy('name')->pluck('name', 'id'),
            'categories' => Category::query()->where('type', 'content')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
