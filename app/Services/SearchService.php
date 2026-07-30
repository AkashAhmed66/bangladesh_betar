<?php

declare(strict_types=1);

namespace App\Services;

use Elastic\Client\ClientBuilderInterface;
use Elastic\Elasticsearch\Client;

/**
 * M06 — the query side of the hybrid Elasticsearch search.
 *
 * Model → index sync is handled automatically by Laravel Scout (the elastic
 * driver). This service owns the *reading* half: it talks to Elasticsearch
 * directly so we can control multilingual analysis, field boosting, fuzziness
 * and popularity ranking that Scout's thin query API can't express.
 *
 * It deliberately returns only lightweight hit descriptors ({type, id, score});
 * the controller re-hydrates the real Eloquent models through the existing API
 * Resources, so the JSON contract is identical to the old SQL search and no
 * Resource field has to be duplicated into the index.
 */
class SearchService
{
    /** Public search type => Elasticsearch base index name (table name). */
    public const INDEX_BY_TYPE = [
        'song' => 'songs',
        'artist' => 'artists',
        'programme' => 'programmes',
        'episode' => 'episodes',
        'podcast' => 'podcast_channels',
        'podcast_episode' => 'podcast_episodes',
        'live_radio' => 'broadcast_channels',
    ];

    private Client $client;

    public function __construct(ClientBuilderInterface $clientBuilder)
    {
        $this->client = $clientBuilder->default();
    }

    /** Search is only active when the Elasticsearch Scout driver is selected. */
    public function enabled(): bool
    {
        return config('scout.driver') === 'elastic';
    }

    /**
     * Run one ranked query per requested type in a single _msearch round trip.
     *
     * @return array<string, array<int, array{id:int, score:float}>>
     *         type => hits (already sorted best-first by Elasticsearch)
     */
    public function search(string $query, ?string $type = null, int $perType = 20): array
    {
        $types = $this->typesFor($type);
        if ($types === []) {
            return [];
        }

        $body = [];
        foreach ($types as $t) {
            $body[] = ['index' => $this->indexFor($t)];
            $body[] = [
                'size' => $perType,
                '_source' => false, // only need _id + _score; the DB hydrates the rest
                'query' => $this->relevanceQuery($query),
            ];
        }

        $responses = $this->client->msearch(['body' => $body])->asArray()['responses'] ?? [];

        $results = [];
        foreach ($types as $i => $t) {
            $hits = $responses[$i]['hits']['hits'] ?? [];
            $results[$t] = array_map(static fn (array $h): array => [
                'id' => (int) $h['_id'],
                'score' => (float) ($h['_score'] ?? 0),
            ], $hits);
        }

        return $results;
    }

    /**
     * Type-ahead suggestions across every index using the edge-ngram
     * autocomplete fields. A Bangla (non-ASCII) query prefers the Bangla label.
     *
     * @return array<int, array{text:string, type:string}>
     */
    public function suggest(string $query, int $limit = 10): array
    {
        $isBangla = (bool) preg_match('/[^\x00-\x7F]/', $query);

        $response = $this->client->search([
            'index' => $this->allIndices(),
            'body' => [
                'size' => $limit * 2,
                '_source' => ['type', 'title', 'title_bn'],
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'operator' => 'and',
                        'fields' => [
                            'title.autocomplete^2',
                            'title_bn.autocomplete^2',
                            'people.autocomplete',
                        ],
                    ],
                ],
            ],
        ])->asArray();

        $seen = [];
        $out = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $src = $hit['_source'] ?? [];
            $text = $isBangla && ! empty($src['title_bn']) ? $src['title_bn'] : ($src['title'] ?? null);
            if (! $text || isset($seen[$text])) {
                continue;
            }
            $seen[$text] = true;
            $out[] = ['text' => $text, 'type' => $src['type'] ?? null];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /** The relevance query: fuzzy full-text OR prefix match, nudged by popularity. */
    private function relevanceQuery(string $query): array
    {
        return [
            'function_score' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            // Typo-tolerant full-text match across titles, people and body.
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'type' => 'best_fields',
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 1,
                                    'fields' => [
                                        'title^6',
                                        'title_bn^6',
                                        'people^4',
                                        'body^1.5',
                                        'body_bn^1.5',
                                        'transcript^0.5',
                                    ],
                                ],
                            ],
                            // "Starts-with" boost so partial titles rank near the top.
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'type' => 'phrase_prefix',
                                    'fields' => ['title^4', 'title_bn^4', 'people^3'],
                                ],
                            ],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
                // Gentle, bounded popularity boost — breaks ties toward what people
                // actually listen to without ever letting popularity beat relevance.
                'functions' => [
                    [
                        'field_value_factor' => [
                            'field' => 'popularity',
                            'modifier' => 'ln2p',
                            'factor' => 1,
                            'missing' => 0,
                        ],
                        'weight' => 0.3,
                    ],
                ],
                'boost_mode' => 'sum',
                'max_boost' => 6,
            ],
        ];
    }

    /** @return array<int, string> the types to query (all, or the single filter). */
    private function typesFor(?string $type): array
    {
        if ($type !== null && $type !== '') {
            return isset(self::INDEX_BY_TYPE[$type]) ? [$type] : [];
        }

        return array_keys(self::INDEX_BY_TYPE);
    }

    private function indexFor(string $type): string
    {
        return config('scout.prefix').self::INDEX_BY_TYPE[$type];
    }

    private function allIndices(): string
    {
        $prefix = config('scout.prefix');

        return implode(',', array_map(
            static fn (string $base): string => $prefix.$base,
            array_values(self::INDEX_BY_TYPE),
        ));
    }
}
