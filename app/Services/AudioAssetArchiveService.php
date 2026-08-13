<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AudioAsset;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class AudioAssetArchiveService
{
    public function archive(AudioAsset $asset, User $user): bool
    {
        if ($asset->isArchived()) {
            return false;
        }

        $asset->loadMissing(['song', 'episode', 'podcastEpisode']);

        $old = [
            'status' => $asset->status,
            'access_level' => $asset->access_level,
            'published_at' => $asset->published_at?->toIso8601String(),
            'archived_at' => null,
            'archived_by' => null,
            'song' => $asset->song ? ['id' => $asset->song->id, 'deleted_at' => null] : null,
            'programme_episode' => $asset->episode ? [
                'id' => $asset->episode->id,
                'is_published' => $asset->episode->is_published,
                'published_at' => $asset->episode->published_at?->toIso8601String(),
                'deleted_at' => null,
            ] : null,
            'podcast_episode' => $asset->podcastEpisode ? [
                'id' => $asset->podcastEpisode->id,
                'status' => $asset->podcastEpisode->status,
                'published_at' => $asset->podcastEpisode->published_at?->toIso8601String(),
                'scheduled_at' => $asset->podcastEpisode->scheduled_at?->toIso8601String(),
                'deleted_at' => null,
            ] : null,
        ];

        DB::transaction(function () use ($asset, $user, $old): void {
            $asset->update([
                // A published asset must become a real unpublished record,
                // not merely a published record hidden by archived_at.
                'status' => $asset->status === 'published' ? 'unpublished' : $asset->status,
                'access_level' => 'internal',
                'published_at' => null,
                'archived_at' => now(),
                'archived_by' => $user->id,
            ]);

            // Catalogue publication is deliberately one-way during archive:
            // unarchiving restores management visibility, never public state.
            $asset->episode?->update([
                'is_published' => false,
                'published_at' => null,
            ]);

            if ($asset->podcastEpisode && in_array($asset->podcastEpisode->status, ['published', 'scheduled'], true)) {
                $asset->podcastEpisode->update([
                    'status' => 'unpublished',
                    'published_at' => null,
                    'scheduled_at' => null,
                ]);
            }

            // Removing an asset from the archive catalogue also removes the
            // module-specific catalogue rows. They are soft-deleted for audit
            // recovery, but unarchive deliberately never restores them.
            $asset->song?->delete();
            $asset->episode?->delete();
            $asset->podcastEpisode?->delete();

            AuditLog::record(
                'asset_archived',
                $asset,
                $old,
                [
                    'status' => $asset->status,
                    'access_level' => $asset->access_level,
                    'published_at' => null,
                    'archived_at' => $asset->archived_at?->toIso8601String(),
                    'archived_by' => $user->id,
                    'song' => $asset->song ? [
                        'id' => $asset->song->id,
                        'deleted_at' => $asset->song->deleted_at?->toIso8601String(),
                    ] : null,
                    'programme_episode' => $asset->episode ? [
                        'id' => $asset->episode->id,
                        'is_published' => false,
                        'published_at' => null,
                        'deleted_at' => $asset->episode->deleted_at?->toIso8601String(),
                    ] : null,
                    'podcast_episode' => $asset->podcastEpisode ? [
                        'id' => $asset->podcastEpisode->id,
                        'status' => $asset->podcastEpisode->status,
                        'published_at' => $asset->podcastEpisode->published_at?->toIso8601String(),
                        'scheduled_at' => $asset->podcastEpisode->scheduled_at?->toIso8601String(),
                        'deleted_at' => $asset->podcastEpisode->deleted_at?->toIso8601String(),
                    ] : null,
                ],
                "Asset {$asset->archive_no} archived by {$user->name}",
            );
        });

        $this->syncCatalogueSearch($asset);

        return true;
    }

    public function unarchive(AudioAsset $asset, User $user): bool
    {
        if (! $asset->isArchived()) {
            return false;
        }

        $old = [
            'archived_at' => $asset->archived_at?->toIso8601String(),
            'archived_by' => $asset->archived_by,
        ];

        DB::transaction(function () use ($asset, $user, $old): void {
            $asset->update([
                'archived_at' => null,
                'archived_by' => null,
            ]);

            AuditLog::record(
                'asset_unarchived',
                $asset,
                $old,
                ['archived_at' => null, 'archived_by' => null],
                "Asset {$asset->archive_no} restored from archive by {$user->name}",
            );
        });

        $this->syncCatalogueSearch($asset);

        return true;
    }

    /**
     * Audio assets are not Scout documents themselves; their song/episode
     * catalogue records are. Keep those documents aligned immediately so an
     * archived recording cannot remain in autocomplete suggestions.
     */
    private function syncCatalogueSearch(AudioAsset $asset): void
    {
        $asset->loadMissing(['song.audioAsset', 'podcastEpisode.audioAsset', 'episode.audioAsset']);

        collect([$asset->song, $asset->podcastEpisode, $asset->episode])
            ->filter(fn (?Model $model): bool => $model !== null)
            ->each(function (Model $model) use ($asset): void {
                if ($asset->isArchived() || ! $model->shouldBeSearchable()) {
                    $model->unsearchable();

                    return;
                }

                $model->searchable();
            });
    }
}
