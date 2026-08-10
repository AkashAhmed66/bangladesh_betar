<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AudioBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioBookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_audio_books_are_searchable_by_title_and_read_along_text(): void
    {
        config(['scout.driver' => 'null']);

        $author = User::factory()->create();
        $book = AudioBook::withoutSyncingToSearch(fn () => AudioBook::query()->create([
            'user_id' => $author->id,
            'title' => 'The River Beyond Dawn',
            'language' => 'en',
            'source_type' => 'text',
            'text' => 'A traveller discovered the celestial orchard beside the river.',
            'characters' => 67,
            'status' => 'published',
            'published_at' => now(),
        ]));
        AudioBook::withoutSyncingToSearch(fn () => AudioBook::query()->create([
            'user_id' => $author->id,
            'title' => 'Hidden Draft',
            'language' => 'en',
            'source_type' => 'text',
            'text' => 'The celestial orchard is mentioned here too.',
            'characters' => 45,
            'status' => 'ready',
        ]));

        $this->getJson('/api/v1/search?q=celestial%20orchard')
            ->assertOk()
            ->assertJsonPath('results.audiobooks.data.0.id', $book->id)
            ->assertJsonPath('results.audiobooks.data.0.title', 'The River Beyond Dawn')
            ->assertJsonMissing(['title' => 'Hidden Draft']);

        $this->getJson('/api/v1/search?q=River%20Beyond')
            ->assertOk()
            ->assertJsonPath('results.audiobooks.data.0.id', $book->id);

        $this->getJson('/api/v1/search/suggest?q=The%20R')
            ->assertOk()
            ->assertJsonFragment([
                'text' => 'The River Beyond Dawn',
                'type' => 'audio_book',
            ]);

        $book->load('user');
        $this->assertTrue($book->shouldBeSearchable());
        $this->assertSame('The River Beyond Dawn', $book->toSearchableArray()['title']);
        $this->assertSame($book->text, $book->toSearchableArray()['body']);
        $this->assertSame([$author->name], $book->toSearchableArray()['people']);
    }
}
