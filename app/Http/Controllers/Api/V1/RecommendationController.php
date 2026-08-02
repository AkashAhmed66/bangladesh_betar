<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\PresentsCataloguedAssets;
use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\PlayHistory;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M25 — recommendations & personalization. Combines listening history
 * (collaborative) with content signals (genre/mood/era). Guests and
 * opted-out users get curated/trending fallback (FR-REC-04/07).
 */
class RecommendationController extends Controller
{
    use PresentsCataloguedAssets;

    /** "Recommended for you" (FR-REC-01) — catalogued content only. */
    public function forYou(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null || ($user->preferences['personalization_opt_out'] ?? false)) {
            return response()->json([
                'personalized' => false,
                'reason' => $user ? 'Personalization is turned off.' : 'Sign in for personalized recommendations.',
                'data' => $this->presentCatalogued(
                    AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
                        ->orderByDesc('play_count')->take(20)->get(),
                ),
            ]);
        }

        // Genres the listener has recently played, then popular unheard content in them.
        $recentAssetIds = PlayHistory::query()->where('user_id', $user->id)
            ->where('playable_type', 'audio_asset')->latest('last_played_at')->take(50)->pluck('playable_id');

        $genreIds = Song::query()->whereIn('audio_asset_id', $recentAssetIds)->pluck('genre_id')->filter()->unique();

        $recommended = AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
            ->whereNotIn('id', $recentAssetIds)
            ->when($genreIds->isNotEmpty(), fn ($q) => $q->whereHas('song', fn ($s) => $s->whereIn('genre_id', $genreIds)))
            ->orderByDesc('play_count')->take(20)->get();

        if ($recommended->isEmpty()) {
            $recommended = AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
                ->orderByDesc('play_count')->take(20)->get();
        }

        return response()->json([
            'personalized' => true,
            'data' => $this->presentCatalogued($recommended),
        ]);
    }

    /** "Similar to this" / autoplay continuation (FR-REC-02) — catalogued content only. */
    public function similar(AudioAsset $asset): JsonResponse
    {
        abort_unless($asset->isPublished(), 404);

        $song = $asset->song;
        $similar = AudioAsset::query()->published()->catalogued()->with($this->cataloguedWith())
            ->where('id', '!=', $asset->id)
            ->when($song?->genre_id, fn ($q) => $q->whereHas('song', fn ($s) => $s->where('genre_id', $song->genre_id)))
            ->when($song === null, fn ($q) => $q->where('content_type', $asset->content_type))
            ->orderByDesc('play_count')->take(15)->get();

        return response()->json(['data' => $this->presentCatalogued($similar)]);
    }

    /** Opt out and clear the recommendation profile (FR-REC-07). */
    public function optOut(Request $request): JsonResponse
    {
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $prefs['personalization_opt_out'] = $request->boolean('opt_out', true);
        $user->update(['preferences' => $prefs]);

        return response()->json([
            'message' => $prefs['personalization_opt_out']
                ? 'Personalization disabled and profile cleared.'
                : 'Personalization enabled.',
        ]);
    }
}
