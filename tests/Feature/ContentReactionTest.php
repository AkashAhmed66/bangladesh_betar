<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContentReaction;
use App\Models\NewsArticle;
use App\Models\User;
use App\Models\WatchShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ContentReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_counts_but_must_sign_in_to_react(): void
    {
        $article = NewsArticle::factory()->create();

        $this->getJson(route('api.v1.reactions.show', ['type' => 'news_article', 'id' => $article->id]))
            ->assertOk()
            ->assertExactJson([
                'data' => ['likes' => 0, 'dislikes' => 0, 'my_reaction' => null],
            ]);

        $this->putJson(route('api.v1.reactions.update', ['type' => 'news_article', 'id' => $article->id]), [
            'reaction' => 'like',
        ])->assertUnauthorized();
    }

    public function test_a_listener_can_like_switch_to_dislike_and_remove_their_reaction(): void
    {
        $article = NewsArticle::factory()->create();
        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener']));
        $url = route('api.v1.reactions.update', ['type' => 'news_article', 'id' => $article->id]);

        $this->putJson($url, ['reaction' => 'like'])
            ->assertOk()
            ->assertJsonPath('data.likes', 1)
            ->assertJsonPath('data.dislikes', 0)
            ->assertJsonPath('data.my_reaction', 'like');

        $this->putJson($url, ['reaction' => 'dislike'])
            ->assertOk()
            ->assertJsonPath('data.likes', 0)
            ->assertJsonPath('data.dislikes', 1)
            ->assertJsonPath('data.my_reaction', 'dislike');

        $this->putJson($url, ['reaction' => 'dislike'])
            ->assertOk()
            ->assertJsonPath('data.likes', 0)
            ->assertJsonPath('data.dislikes', 0)
            ->assertJsonPath('data.my_reaction', null);

        $this->assertDatabaseCount('content_reactions', 0);
    }

    public function test_counts_include_other_users_but_only_return_the_current_users_selection(): void
    {
        $show = WatchShow::factory()->create();
        $first = User::factory()->create(['user_type' => 'listener']);
        $second = User::factory()->create(['user_type' => 'listener']);

        ContentReaction::query()->create([
            'user_id' => $first->id,
            'reactionable_type' => $show->getMorphClass(),
            'reactionable_id' => $show->id,
            'reaction' => ContentReaction::LIKE,
        ]);
        ContentReaction::query()->create([
            'user_id' => $second->id,
            'reactionable_type' => $show->getMorphClass(),
            'reactionable_id' => $show->id,
            'reaction' => ContentReaction::DISLIKE,
        ]);

        Sanctum::actingAs($first);
        $this->getJson(route('api.v1.reactions.show', ['type' => 'watch_show', 'id' => $show->id]))
            ->assertOk()
            ->assertExactJson([
                'data' => ['likes' => 1, 'dislikes' => 1, 'my_reaction' => 'like'],
            ]);
    }

    public function test_reactions_reject_invalid_values_and_hidden_or_unsupported_content(): void
    {
        $draft = NewsArticle::factory()->create(['is_published' => false]);
        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener']));

        $this->putJson(route('api.v1.reactions.update', ['type' => 'news_article', 'id' => $draft->id]), [
            'reaction' => 'love',
        ])->assertUnprocessable();

        $this->getJson(route('api.v1.reactions.show', ['type' => 'news_article', 'id' => $draft->id]))
            ->assertNotFound();
        $this->getJson(route('api.v1.reactions.show', ['type' => 'unknown_post', 'id' => $draft->id]))
            ->assertNotFound();
    }
}
