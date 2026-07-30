<?php

declare(strict_types=1);

use Elastic\Migrations\Facades\Index;
use Elastic\Migrations\MigrationInterface;

/**
 * M06 — public search indices (Elasticsearch).
 *
 * Every searchable catalogue entity gets its own index, but all seven share an
 * IDENTICAL mapping + analyzer set so a single cross-index query ranks results
 * together and the SearchController can treat every hit uniformly (each doc
 * carries a `type` discriminator). Index names are the model table names; the
 * `betar_` SCOUT_PREFIX is applied automatically by elastic-migrations, so the
 * physical indices are betar_songs, betar_artists, … matching Scout's
 * searchableAs() (config('scout.prefix') . table).
 *
 * Analyzers:
 *   text_en       — English: standard tokenizer + lowercase + asciifolding +
 *                   English stop-words + stemmer (so "recordings" ~ "record").
 *   bengali       — ES built-in Bangla analyzer (normalization + stemming),
 *                   applied to the *_bn fields.
 *   name_analyzer — people/artist names: lowercase + fold, no stemming.
 *   autocomplete  — edge-ngram at index time, plain lowercase+fold at search
 *                   time (search_analyzer) so type-ahead matches prefixes
 *                   without ngram-ing the query itself.
 */
final class CreateSearchIndices implements MigrationInterface
{
    /** The seven catalogue indices (table names; prefixed to betar_* at runtime). */
    private const INDICES = [
        'songs',
        'artists',
        'programmes',
        'episodes',
        'podcast_channels',
        'podcast_episodes',
        'broadcast_channels',
    ];

    public function up(): void
    {
        $settings = [
            'number_of_shards' => 1,
            // Single-node cluster: 0 replicas keeps health green (a replica has
            // nowhere to be allocated on one node and would sit unassigned).
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

        // Shared subfield used on every human-facing text field for type-ahead.
        $autocomplete = [
            'type' => 'text',
            'analyzer' => 'autocomplete_index',
            'search_analyzer' => 'autocomplete_search',
        ];

        $mapping = [
            'properties' => [
                'type' => ['type' => 'keyword'],
                'entity_id' => ['type' => 'long'],
                // Primary English/Latin title.
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'text_en',
                    'fields' => [
                        'autocomplete' => $autocomplete,
                        'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                    ],
                ],
                // Bangla title (bengali analyzer).
                'title_bn' => [
                    'type' => 'text',
                    'analyzer' => 'bengali',
                    'fields' => ['autocomplete' => $autocomplete],
                ],
                // Associated people: singers, hosts, guests, artist names (both scripts).
                'people' => [
                    'type' => 'text',
                    'analyzer' => 'name_analyzer',
                    'fields' => ['autocomplete' => $autocomplete],
                ],
                // Free text — descriptions / lyrics / bios.
                'body' => ['type' => 'text', 'analyzer' => 'text_en'],
                'body_bn' => ['type' => 'text', 'analyzer' => 'bengali'],
                // Spoken-word transcript (lower weight at query time).
                'transcript' => ['type' => 'text', 'analyzer' => 'text_en'],
                // Signals for relevance / recency boosting and sorting.
                'popularity' => ['type' => 'long'],
                'published_at' => ['type' => 'date'],
            ],
        ];

        foreach (self::INDICES as $index) {
            Index::createRaw($index, $mapping, $settings);
        }
    }

    public function down(): void
    {
        foreach (self::INDICES as $index) {
            Index::dropIfExists($index);
        }
    }
}
