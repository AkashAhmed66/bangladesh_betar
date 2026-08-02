<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\Genre;
use App\Models\Mood;
use App\Models\Song;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SongController extends Controller
{
    public function index(Request $request): View
    {
        $songs = Song::query()
            ->visibleTo($request->user())
            ->with(['audioAsset', 'album', 'genre', 'mood', 'artists'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('audioAsset', fn ($a) => $a
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('title_bn', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('genre'), fn ($q) => $q->where('genre_id', $request->integer('genre')))
            ->when($request->filled('album'), fn ($q) => $q->where('album_id', $request->integer('album')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.songs.index', [
            'songs' => $songs,
            'genres' => Genre::query()->orderBy('name')->pluck('name', 'id'),
            'albums' => Album::query()->orderBy('title')->pluck('title', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('songs.manage');

        return view('admin.songs.form', ['song' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('songs.manage');

        $data = $this->validated($request);
        $artists = $this->pullArtists($data);

        $song = Song::query()->create($data);
        $this->syncArtists($song, $artists);

        return redirect()->route('admin.songs.index')->with('success', 'Song record created.');
    }

    public function edit(Song $song): View
    {
        $this->authorize('songs.manage');
        $this->authorizeRecordVisibility($song);

        $song->load('artists');

        return view('admin.songs.form', ['song' => $song] + $this->options());
    }

    public function update(Request $request, Song $song): RedirectResponse
    {
        $this->authorize('songs.manage');
        $this->authorizeRecordVisibility($song);

        $data = $this->validated($request);
        $artists = $this->pullArtists($data);

        $song->update($data);
        $this->syncArtists($song, $artists);

        return redirect()->route('admin.songs.index')->with('success', 'Song record updated.');
    }

    public function destroy(Song $song): RedirectResponse
    {
        $this->authorize('songs.manage');
        $this->authorizeRecordVisibility($song);

        $song->delete();

        return redirect()->route('admin.songs.index')->with('success', 'Song removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'audio_asset_id' => ['required', 'exists:audio_assets,id'],
            'album_id' => ['nullable', 'exists:albums,id'],
            'track_number' => ['nullable', 'integer', 'min:1'],
            'genre_id' => ['nullable', 'exists:genres,id'],
            'mood_id' => ['nullable', 'exists:moods,id'],
            'version_type' => ['required', Rule::in(['original', 'live', 'remastered', 'instrumental', 'cover'])],
            'master_song_id' => ['nullable', 'exists:songs,id'],
            'release_year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'lyrics' => ['nullable', 'string'],
            'lyrics_bn' => ['nullable', 'string'],
            'mood_genre_verified' => ['boolean'],
            'is_featured' => ['boolean'],
            'singer_id' => ['nullable', 'exists:artists,id'],
            'composer_id' => ['nullable', 'exists:artists,id'],
            'lyricist_id' => ['nullable', 'exists:artists,id'],
        ]);
    }

    /** @return array{singer: ?int, composer: ?int, lyricist: ?int} */
    private function pullArtists(array &$data): array
    {
        $artists = [
            'singer' => $data['singer_id'] ?? null,
            'composer' => $data['composer_id'] ?? null,
            'lyricist' => $data['lyricist_id'] ?? null,
        ];
        unset($data['singer_id'], $data['composer_id'], $data['lyricist_id']);

        return $artists;
    }

    private function syncArtists(Song $song, array $artists): void
    {
        $sync = [];
        foreach ($artists as $role => $artistId) {
            if ($artistId) {
                $sync[$artistId] = ['role' => $role];
            }
        }
        $song->artists()->sync($sync);
    }

    private function options(): array
    {
        return [
            // The asset picker honours record visibility: restricted users may
            // only attach assets they can see.
            'assets' => AudioAsset::query()->visibleTo(auth()->user())->where('content_type', 'song')->orderBy('title')->pluck('title', 'id'),
            'albums' => Album::query()->orderBy('title')->pluck('title', 'id'),
            'genres' => Genre::query()->orderBy('name')->pluck('name', 'id'),
            'moods' => Mood::query()->orderBy('name')->pluck('name', 'id'),
            'singers' => Artist::query()->whereIn('artist_type', ['singer', 'band'])->orderBy('name')->pluck('name', 'id'),
            'composers' => Artist::query()->where('artist_type', 'composer')->orderBy('name')->pluck('name', 'id'),
            'lyricists' => Artist::query()->where('artist_type', 'lyricist')->orderBy('name')->pluck('name', 'id'),
            'masterSongs' => Song::query()->with('audioAsset')->whereNull('master_song_id')->get()
                ->mapWithKeys(fn (Song $s) => [$s->id => $s->audioAsset?->title ?? "Song #{$s->id}"]),
        ];
    }
}
