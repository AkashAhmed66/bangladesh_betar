<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArtistController extends Controller
{
    private const TYPES = ['singer', 'composer', 'lyricist', 'presenter', 'producer', 'voice_artist', 'narrator', 'speaker', 'band'];

    public function index(Request $request): View
    {
        $artists = Artist::query()
            ->withCount(['songs', 'followers'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('name_bn', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('artist_type', $request->string('type')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.artists.index', ['artists' => $artists, 'types' => self::TYPES]);
    }

    public function create(): View
    {
        $this->authorize('artists.manage');

        return view('admin.artists.form', ['artist' => null, 'types' => self::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('artists.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(3));

        Artist::query()->create($data);

        return redirect()->route('admin.artists.index')->with('success', 'Artist profile created.');
    }

    public function edit(Artist $artist): View
    {
        $this->authorize('artists.manage');

        return view('admin.artists.form', ['artist' => $artist, 'types' => self::TYPES]);
    }

    public function update(Request $request, Artist $artist): RedirectResponse
    {
        $this->authorize('artists.manage');

        $artist->update($this->validated($request));

        return redirect()->route('admin.artists.index')->with('success', 'Artist profile updated.');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        $this->authorize('artists.manage');

        $artist->delete();

        return redirect()->route('admin.artists.index')->with('success', 'Artist removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'artist_type' => ['required', Rule::in(self::TYPES)],
            'bio' => ['nullable', 'string'],
            'bio_bn' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
        ]);
    }
}
