<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Editorial playlists / curated collections (M08 playlists + FR-CUR-02).
 */
class PlaylistController extends Controller
{
    public function index(Request $request): View
    {
        $playlists = Playlist::query()
            ->with('user')
            ->withCount('items')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->string('scope')->toString() === 'listener', fn ($q) => $q->where('is_editorial', false))
            ->when($request->string('scope')->toString() !== 'listener', fn ($q) => $q->where('is_editorial', true))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.playlists.index', compact('playlists'));
    }

    public function create(): View
    {
        $this->authorize('playlists.manage');

        return view('admin.playlists.form', ['playlist' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('playlists.manage');

        $data = $this->validated($request);
        $items = $data['items'] ?? [];
        unset($data['items']);

        $playlist = Playlist::query()->create($data + [
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(3)),
            'is_editorial' => true,
            'is_public' => true,
        ]);

        $this->syncItems($playlist, $items);

        return redirect()->route('admin.playlists.index')->with('success', 'Curated collection created.');
    }

    public function edit(Playlist $playlist): View
    {
        $this->authorize('playlists.manage');

        $playlist->load('items.playable');

        return view('admin.playlists.form', ['playlist' => $playlist] + $this->options());
    }

    public function update(Request $request, Playlist $playlist): RedirectResponse
    {
        $this->authorize('playlists.manage');

        $data = $this->validated($request);
        $items = $data['items'] ?? [];
        unset($data['items']);

        $playlist->update($data);
        $this->syncItems($playlist, $items);

        return redirect()->route('admin.playlists.index')->with('success', 'Collection updated.');
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        $this->authorize('playlists.manage');

        $playlist->delete();

        return redirect()->route('admin.playlists.index')->with('success', 'Collection removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'items' => ['nullable', 'array'],
            'items.*' => ['string'], // "song:12" | "audio_asset:5"
        ]);
    }

    private function syncItems(Playlist $playlist, array $items): void
    {
        $playlist->items()->delete();
        foreach (array_values($items) as $position => $item) {
            [$type, $id] = explode(':', $item) + [null, null];
            if (! in_array($type, ['song', 'audio_asset'], true) || ! is_numeric($id)) {
                continue;
            }
            PlaylistItem::query()->create([
                'playlist_id' => $playlist->id,
                'playable_type' => $type,
                'playable_id' => (int) $id,
                'position' => $position,
            ]);
        }
    }

    private function options(): array
    {
        $songs = Song::query()->with('audioAsset')->get()
            ->mapWithKeys(fn (Song $s) => ['song:'.$s->id => '♪ '.($s->audioAsset?->title ?? "Song #{$s->id}")]);

        $assets = AudioAsset::query()->where('status', 'published')
            ->whereNotIn('content_type', ['song'])->orderBy('title')->get()
            ->mapWithKeys(fn (AudioAsset $a) => ['audio_asset:'.$a->id => $a->title]);

        return ['playableOptions' => $songs->merge($assets)];
    }
}
