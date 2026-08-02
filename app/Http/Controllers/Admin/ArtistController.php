<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function index(Request $request): View
    {
        $artists = Artist::query()
            ->visibleTo($request->user())
            ->withCount(['songs', 'followers'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('name_bn', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('artist_type', $request->string('type')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.artists.index', ['artists' => $artists, 'types' => Artist::TYPES]);
    }

    public function create(): View
    {
        $this->authorize('artists.manage');

        return view('admin.artists.form', ['artist' => null, 'types' => Artist::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('artists.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(3));
        $data['photo_path'] = $this->storeImage($request, 'photo', 'artists/photos', null);
        $data['cover_path'] = $this->storeImage($request, 'cover', 'artists/covers', null);

        Artist::query()->create($data);

        return redirect()->route('admin.artists.index')->with('success', 'Artist profile created.');
    }

    public function edit(Artist $artist): View
    {
        $this->authorize('artists.manage');
        $this->authorizeRecordVisibility($artist);

        return view('admin.artists.form', ['artist' => $artist->load('user'), 'types' => Artist::TYPES]);
    }

    public function update(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('artists.manage');
        $this->authorizeRecordVisibility($artist);

        $data = $this->validated($request);
        $data['photo_path'] = $this->storeImage($request, 'photo', 'artists/photos', $artist->photo_path);
        $data['cover_path'] = $this->storeImage($request, 'cover', 'artists/covers', $artist->cover_path);

        $artist->update($data);

        // Keep a linked account's avatar in sync with the artist photo.
        if ($artist->user_id && $request->hasFile('photo')) {
            $artist->user()->update(['avatar_path' => $data['photo_path']]);
        }

        return redirect()->route('admin.artists.index')->with('success', 'Artist profile updated.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        $this->authorize('artists.manage');
        $this->authorizeRecordVisibility($artist);

        $artist->delete();

        return redirect()->route('admin.artists.index')->with('success', 'Artist removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'artist_type' => ['required', Rule::in(Artist::TYPES)],
            'bio' => ['nullable', 'string'],
            'bio_bn' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'is_verified' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'social' => ['nullable', 'array'],
            'social.website' => ['nullable', 'url', 'max:255'],
            'social.facebook' => ['nullable', 'url', 'max:255'],
            'social.instagram' => ['nullable', 'url', 'max:255'],
            'social.youtube' => ['nullable', 'url', 'max:255'],
            'social.twitter' => ['nullable', 'url', 'max:255'],
            'social.spotify' => ['nullable', 'url', 'max:255'],
        ]);

        // Files are stored separately by the caller; map social → social_links.
        unset($data['photo'], $data['cover'], $data['social']);
        $social = array_filter($request->input('social', []));
        $data['social_links'] = $social ?: null;

        return $data;
    }

    /** Store an uploaded image on the public disk, replacing any previous file. */
    private function storeImage(Request $request, string $field, string $dir, ?string $existing): ?string
    {
        if (! $request->hasFile($field)) {
            return $existing;
        }

        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        return $request->file($field)->store($dir, 'public');
    }
}
