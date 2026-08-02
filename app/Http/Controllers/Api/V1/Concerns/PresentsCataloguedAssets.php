<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Http\Resources\EpisodeResource;
use App\Http\Resources\PodcastEpisodeResource;
use App\Http\Resources\SongResource;
use App\Models\AudioAsset;
use Illuminate\Support\Collection;

/**
 * The public app shows CATALOGUE entities (songs, podcast episodes,
 * programme episodes) — never raw archive recordings. This maps a set of
 * published assets to their catalogue representation and drops anything
 * that has no public catalogue entry.
 */
trait PresentsCataloguedAssets
{
    /** @param  iterable<AudioAsset>  $assets */
    private function presentCatalogued(iterable $assets): Collection
    {
        return collect($assets)->map(function (AudioAsset $asset): ?array {
            if ($asset->song) {
                return (new SongResource($asset->song->setRelation('audioAsset', $asset)))->resolve();
            }
            if ($asset->podcastEpisode?->status === 'published') {
                return (new PodcastEpisodeResource($asset->podcastEpisode->setRelation('audioAsset', $asset)))->resolve();
            }
            if ($asset->episode?->is_published) {
                return (new EpisodeResource($asset->episode->setRelation('audioAsset', $asset)))->resolve();
            }

            return null;
        })->filter()->values();
    }

    /** Eager loads the presenter needs. */
    private function cataloguedWith(): array
    {
        return ['song.artists', 'song.genre', 'podcastEpisode.channel', 'episode.programme'];
    }
}
