<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\WatchEpisode;
use App\Models\WatchlistItem;
use App\Models\WatchShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class WatchEngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_can_toggle_show_and_episode_watchlist_items(): void
    {
        $show = WatchShow::factory()->create();
        $episode = WatchEpisode::factory()->for($show, 'show')->create();
        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener']));

        $this->postJson(route('api.v1.watchlist.toggle'), [
            'watchable_type' => 'watch_show',
            'watchable_id' => $show->id,
        ])->assertCreated()->assertJsonPath('watchlisted', true);

        $this->postJson(route('api.v1.watchlist.toggle'), [
            'watchable_type' => 'watch_episode',
            'watchable_id' => $episode->id,
        ])->assertCreated()->assertJsonPath('watchlisted', true);

        $this->getJson(route('api.v1.watchlist.index'))
            ->assertOk()->assertJsonCount(2, 'data');

        $this->deleteJson(route('api.v1.watchlist.destroy', ['type' => 'watch_show', 'id' => $show->id]))
            ->assertOk()->assertJsonPath('removed', true);
    }

    public function test_listener_can_review_and_rate_a_published_episode(): void
    {
        $show = WatchShow::factory()->create();
        $episode = WatchEpisode::factory()->for($show, 'show')->create();
        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener']));

        $this->postJson(route('api.v1.watch-episodes.reviews.store', $episode), [
            'rating' => 5,
            'body' => 'Excellent episode',
        ])->assertCreated()->assertJsonPath('rating.your_rating', 5);

        $this->getJson(route('api.v1.watch-episodes.reviews.index', $episode))
            ->assertOk()
            ->assertJsonPath('rating.avg_rating', 5)
            ->assertJsonPath('rating.rating_count', 1)
            ->assertJsonPath('rating.your_rating', 5)
            ->assertJsonPath('data.0.body', 'Excellent episode');
    }

    public function test_watchlist_excludes_unpublished_items_and_catalogue_marks_saved_items(): void
    {
        $show = WatchShow::factory()->create();
        $episode = WatchEpisode::factory()->for($show, 'show')->create();
        $hidden = WatchShow::factory()->create(['is_published' => false]);
        $user = User::factory()->create(['user_type' => 'listener']);
        WatchlistItem::query()->create(['user_id' => $user->id, 'watchable_type' => 'watch_show', 'watchable_id' => $show->id]);
        WatchlistItem::query()->create(['user_id' => $user->id, 'watchable_type' => 'watch_episode', 'watchable_id' => $episode->id]);
        WatchlistItem::query()->create(['user_id' => $user->id, 'watchable_type' => 'watch_show', 'watchable_id' => $hidden->id]);
        Sanctum::actingAs($user);

        $watchlist = $this->getJson(route('api.v1.watchlist.index'));
        $watchlist->assertOk()->assertJsonCount(2, 'data');
        $this->assertTrue(collect($watchlist->json('data'))->contains(
            fn (array $item): bool => data_get($item, 'item.show.slug') === (string) $show->slug,
        ));

        $catalogue = $this->getJson(route('api.v1.watch.index'))->assertOk()->json('data');
        $catalogueShow = collect($catalogue)->first(fn (array $item): bool => $item['slug'] === (string) $show->slug);
        $this->assertTrue((bool) data_get($catalogueShow, 'is_in_watchlist'));
        $this->assertTrue((bool) data_get($catalogueShow, 'episodes.0.is_in_watchlist'));
    }

    public function test_rating_distribution_counts_current_rating_once_per_listener(): void
    {
        $episode = WatchEpisode::factory()->create();
        $first = User::factory()->create(['user_type' => 'listener']);
        $second = User::factory()->create(['user_type' => 'listener']);

        Sanctum::actingAs($first);
        $this->postJson(route('api.v1.watch-episodes.rate', $episode), ['rating' => 3])->assertOk();
        $this->postJson(route('api.v1.watch-episodes.rate', $episode), ['rating' => 5])->assertOk();
        Sanctum::actingAs($second);
        $this->postJson(route('api.v1.watch-episodes.rate', $episode), ['rating' => 4])->assertOk();

        $this->getJson(route('api.v1.watch-episodes.reviews.index', $episode))
            ->assertOk()
            ->assertJsonPath('rating.avg_rating', 4.5)
            ->assertJsonPath('rating.rating_count', 2)
            ->assertJsonPath('rating.your_rating', 4)
            ->assertJsonPath('rating.distribution.1', 0)
            ->assertJsonPath('rating.distribution.2', 0)
            ->assertJsonPath('rating.distribution.3', 0)
            ->assertJsonPath('rating.distribution.4', 1)
            ->assertJsonPath('rating.distribution.5', 1);
    }
}
