<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Services\ArtworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Listener playlists (read-only). Shows the playlists end-users created in the
 * public app and the tracks they added, for oversight. Editorial/curated
 * playlists were removed — the admin does not create playlists.
 */
class PlaylistController extends Controller
{
    public function __construct(private readonly ArtworkService $artwork) {}

    public function index(Request $request): View
    {
        $playlists = Playlist::query()
            ->where('is_editorial', false)
            ->with('user')
            ->withCount('items')
            ->when($request->filled('q'), fn ($q) => $q
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->string('q').'%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.playlists.index', compact('playlists'));
    }

    public function show(Playlist $playlist): View
    {
        abort_unless(! $playlist->is_editorial, 404);

        $playlist->load(['user', 'items.playable']);

        return view('admin.playlists.show', compact('playlist'));
    }

    public function updateArtwork(Request $request, Playlist $playlist): RedirectResponse
    {
        abort_unless(! $playlist->is_editorial, 404);

        $request->validate([
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
        ]);

        $playlist->update([
            'artwork_path' => $this->artwork->sync(
                $request,
                'artwork/playlists',
                $playlist->artwork_path,
            ),
        ]);

        return back()->with('success', 'Playlist image updated.');
    }
}
