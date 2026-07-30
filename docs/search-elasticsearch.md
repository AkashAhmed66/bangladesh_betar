# Search — Elasticsearch (M06)

Public search is powered by **Elasticsearch** in a **hybrid** design:

- **Indexing / sync** is handled automatically by **Laravel Scout** (the
  `elastic` driver). Whenever a searchable model is created, updated, published,
  unpublished or deleted, Scout pushes the change to Elasticsearch — queued onto
  the database queue worker (`SCOUT_QUEUE=true`) so requests stay fast.
- **Querying** is done by `App\Services\SearchService`, which talks to the
  Elasticsearch client **directly**, so we control multilingual analysis, field
  boosting, fuzziness and popularity ranking. It returns only ranked
  `{type, id, score}` hits; `SearchController` then re-hydrates the real models
  from MySQL through the existing API Resources, so the JSON contract is
  unchanged.
- If Elasticsearch is ever unreachable, search **degrades gracefully** to the
  original SQL `LIKE` query — the endpoints never 500.

## What is searchable

Seven entity types, each in its own index (`betar_<table>`), all sharing an
identical mapping so a single query ranks them together:

| Type | Index | Model | Published rule |
|------|-------|-------|----------------|
| `song` | `betar_songs` | `Song` | underlying asset published + cleared |
| `artist` | `betar_artists` | `Artist` | `is_published` |
| `programme` | `betar_programmes` | `Programme` | `is_published` |
| `episode` | `betar_episodes` | `Episode` | `is_published` + has audio |
| `podcast` | `betar_podcast_channels` | `PodcastChannel` | `is_published` |
| `podcast_episode` | `betar_podcast_episodes` | `PodcastEpisode` | `status=published` + has audio |
| `live_radio` | `betar_broadcast_channels` | `BroadcastChannel` | `is_active` |

Each document carries: `title` (English analyzer), `title_bn` (Bangla analyzer),
`people` (artist/host names), `body`/`body_bn` (descriptions, lyrics, bios),
`transcript` (spoken text), `popularity` and `published_at`. Every human-facing
text field also has an edge-ngram `.autocomplete` subfield used by type-ahead.

Analyzers: **`text_en`** (lowercase + asciifolding + English stop-words +
stemmer), the built-in **`bengali`** analyzer for `*_bn`, a name analyzer, and an
edge-ngram autocomplete pair.

## Endpoints (unchanged contract, new buckets)

- `GET /api/v1/search?q=…[&type=…]` → `{ query, results: { songs, artists,
  programmes, episodes, podcasts, podcast_episodes, live_radios } }`
- `GET /api/v1/search/suggest?q=…` → `{ data: [{ text, type }] }`
- `GET /api/v1/search/semantic?q=…` → unchanged (keyword over transcripts)

## Configuration (env)

```dotenv
SCOUT_DRIVER=elastic        # set to null/database to disable ES (SQL fallback)
SCOUT_QUEUE=true            # queue index writes (prod). false = synchronous
SCOUT_PREFIX=betar_         # index namespace → betar_songs, betar_artists, …
ELASTIC_HOST=http://elasticsearch:9200
RUN_SEARCH_SETUP=true       # entrypoint auto-provisions indices on boot
```

The Elasticsearch **client major must match the server major** (this project
uses PHP client v9 ↔ Elasticsearch **9.x**).

## Deploy

The admin `docker-compose.yml` already includes an `elasticsearch` service and
the app is wired to it.

1. **Admin API (betar_app):** rebuild so the new Composer deps + code are baked
   in, then bring the stack up:
   ```bash
   cd bangladesh_betar
   docker compose build app
   docker compose up -d            # starts elasticsearch too
   ```
   On boot the entrypoint waits for ES, runs `elastic:migrate` (creates the
   indices) and imports the catalogue **once** (only empty indices) — no manual
   step needed on a fresh deploy.

2. **Public app (betar_public):** the search UI gained Artists + Live-radio
   results, so rebuild the Next.js image:
   ```bash
   cd bangladesh_betar_public
   docker compose up -d --build
   ```

### Resource note
Elasticsearch is heavyweight. The compose file caps the JVM heap at 512 MB
(`ES_JAVA_OPTS`); raise it on a box with more RAM for better performance. Ensure
the host has the memory headroom (≈1 GB free for ES).

## Operations

```bash
# Full manual rebuild of all indices (flush + reimport)
php artisan search:index --fresh

# Refresh all indices (idempotent upsert) — what the nightly job runs at 03:15
php artisan search:index

# Import a single model
php artisan scout:import "App\Models\Song"

# Index status
curl -s http://localhost:9201/_cat/indices/betar_*?v

# Re-run index mapping migrations (e.g. after changing analyzers)
php artisan elastic:migrate            # apply new
php artisan elastic:migrate:refresh    # drop + recreate (then reimport)
```

Real-time sync is automatic via the Scout observers; the nightly
`search:index` reconciles any drift. If you change `toSearchableArray()` or the
mapping, run `elastic:migrate:refresh` (if the mapping changed) followed by
`php artisan search:index --fresh`.

## Files

- `docker-compose.yml` — `elasticsearch` service + app env/deps
- `config/scout.php`, `config/elastic.client.php`, `config/elastic.migrations.php`
- `elastic/migrations/2026_07_29_105323_create_search_indices.php` — index mappings
- `app/Models/{Song,Artist,Programme,Episode,PodcastChannel,PodcastEpisode,BroadcastChannel}.php` — `Searchable`
- `app/Services/SearchService.php` — the ES query builder (hybrid read side)
- `app/Http/Controllers/Api/V1/SearchController.php` — hydration + SQL fallback
- `app/Console/Commands/SearchIndexCommand.php` — `search:index`
- `routes/console.php` — nightly reconcile schedule
- `docker/entrypoint.sh` — boot-time provisioning
