<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\ArtworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function __construct(private readonly ArtworkService $artwork) {}

    public function index(Request $request): View
    {
        $albums = Album::query()
            ->visibleTo($request->user())
            ->with('artists')
            ->withCount('songs')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('admin.albums.index', compact('albums'));
    }

    public function create(): View
    {
        $this->authorize('albums.manage');

        return view('admin.albums.form', ['album' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('albums.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(3));
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/albums', null);
        unset($data['artwork'], $data['remove_artwork']);

        Album::query()->create($data);

        return redirect()->route('admin.albums.index')->with('success', 'Album created.');
    }

    public function edit(Album $album): View
    {
        $this->authorize('albums.manage');
        $this->authorizeRecordVisibility($album);

        return view('admin.albums.form', compact('album'));
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        $this->authorize('albums.manage');
        $this->authorizeRecordVisibility($album);

        $data = $this->validated($request);
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/albums', $album->artwork_path);
        unset($data['artwork'], $data['remove_artwork']);

        $album->update($data);

        return redirect()->route('admin.albums.index')->with('success', 'Album updated.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        $this->authorize('albums.manage');
        $this->authorizeRecordVisibility($album);

        $album->delete();
        $this->artwork->delete($album->artwork_path);

        return redirect()->route('admin.albums.index')->with('success', 'Album removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'album_type' => ['required', Rule::in(['album', 'film', 'compilation', 'single'])],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'description' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);
    }
}
