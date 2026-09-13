<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\BroadcastChannel;
use App\Models\ContentReaction;
use App\Models\Episode;
use App\Models\NewsArticle;
use App\Models\Playlist;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use App\Models\Song;
use App\Models\User;
use App\Models\WatchShow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ContentReactionService
{
    /** Resolve only content that is currently visible in the public portal. */
    public function findPublicContent(string $type, int $id): Model
    {
        return match ($type) {
            'news_article' => NewsArticle::query()->published()->findOrFail($id),
            'watch_show' => WatchShow::query()->published()->findOrFail($id),
            'audio_asset' => AudioAsset::query()->published()->findOrFail($id),
            'song' => Song::query()->published()->findOrFail($id),
            'album' => Album::query()->published()->findOrFail($id),
            'artist' => Artist::query()->published()->findOrFail($id),
            'programme' => Programme::query()->published()->findOrFail($id),
            'episode' => Episode::query()->published()->findOrFail($id),
            'podcast_channel' => PodcastChannel::query()->published()->findOrFail($id),
            'podcast_episode' => PodcastEpisode::query()->published()->findOrFail($id),
            'audio_book' => AudioBook::query()->published()->findOrFail($id),
            'playlist' => Playlist::query()->where('is_public', true)->findOrFail($id),
            'broadcast_channel' => BroadcastChannel::query()->where('is_active', true)->findOrFail($id),
            'watch_clip' => \App\Models\WatchClip::query()->published()->findOrFail($id),
            default => abort(404),
        };
    }

    /** @return array{likes: int, dislikes: int, my_reaction: string|null} */
    public function summary(Model $content, ?User $user): array
    {
        $base = ContentReaction::query()
            ->where('reactionable_type', $content->getMorphClass())
            ->where('reactionable_id', $content->getKey());

        $counts = (clone $base)
            ->selectRaw('reaction, COUNT(*) as aggregate')
            ->groupBy('reaction')
            ->pluck('aggregate', 'reaction');

        $mine = $user
            ? (clone $base)->where('user_id', $user->id)->value('reaction')
            : null;

        return [
            'likes' => (int) ($counts[ContentReaction::LIKE] ?? 0),
            'dislikes' => (int) ($counts[ContentReaction::DISLIKE] ?? 0),
            'my_reaction' => is_string($mine) ? $mine : null,
        ];
    }

    /** Clicking the selected reaction removes it; the other selection switches it. */
    public function toggle(Model $content, User $user, string $reaction): array
    {
        DB::transaction(function () use ($content, $user, $reaction): void {
            $existing = ContentReaction::query()
                ->where('user_id', $user->id)
                ->where('reactionable_type', $content->getMorphClass())
                ->where('reactionable_id', $content->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing?->reaction === $reaction) {
                $existing->delete();

                return;
            }

            if ($existing) {
                $existing->update(['reaction' => $reaction]);

                return;
            }

            ContentReaction::query()->create([
                'user_id' => $user->id,
                'reactionable_type' => $content->getMorphClass(),
                'reactionable_id' => $content->getKey(),
                'reaction' => $reaction,
            ]);
        });

        return $this->summary($content, $user);
    }
}
