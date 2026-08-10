<?php

declare(strict_types=1);

use Elastic\Migrations\Facades\Index;
use Elastic\Migrations\MigrationInterface;

/** Add the audiobook index to the public multilingual catalogue search. */
final class CreateAudioBookSearchIndex implements MigrationInterface
{
    public function up(): void
    {
        $settings = [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            'analysis' => [
                'filter' => [
                    'autocomplete_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 1,
                        'max_gram' => 20,
                    ],
                    'english_stop' => ['type' => 'stop', 'stopwords' => '_english_'],
                    'english_stemmer' => ['type' => 'stemmer', 'language' => 'english'],
                ],
                'analyzer' => [
                    'text_en' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding', 'english_stop', 'english_stemmer'],
                    ],
                    'name_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                    'autocomplete_index' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding', 'autocomplete_filter'],
                    ],
                    'autocomplete_search' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
            ],
        ];

        $autocomplete = [
            'type' => 'text',
            'analyzer' => 'autocomplete_index',
            'search_analyzer' => 'autocomplete_search',
        ];

        $mapping = [
            'properties' => [
                'type' => ['type' => 'keyword'],
                'entity_id' => ['type' => 'long'],
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'text_en',
                    'fields' => [
                        'autocomplete' => $autocomplete,
                        'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                    ],
                ],
                'title_bn' => [
                    'type' => 'text',
                    'analyzer' => 'bengali',
                    'fields' => ['autocomplete' => $autocomplete],
                ],
                'people' => [
                    'type' => 'text',
                    'analyzer' => 'name_analyzer',
                    'fields' => ['autocomplete' => $autocomplete],
                ],
                'body' => ['type' => 'text', 'analyzer' => 'text_en'],
                'body_bn' => ['type' => 'text', 'analyzer' => 'bengali'],
                'transcript' => ['type' => 'text', 'analyzer' => 'text_en'],
                'popularity' => ['type' => 'long'],
                'published_at' => ['type' => 'date'],
            ],
        ];

        Index::createRaw('audio_books', $mapping, $settings);
    }

    public function down(): void
    {
        Index::dropIfExists('audio_books');
    }
}
