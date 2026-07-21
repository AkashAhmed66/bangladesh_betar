# Development Guide — Bangladesh Betar Audio Archive Platform

Technical guide for developers working on the platform: architecture, setup, conventions,
and how the codebase maps to the FRS modules (M01–M27).

---

## 1. Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 (PHP 8.2+) |
| Admin UI | Blade + Tailwind CSS 4 + Vite + Alpine.js + Chart.js |
| Auth (portal) | Session auth, Spatie Laravel-Permission (RBAC) |
| Auth (API) | Laravel Sanctum (bearer tokens) |
| Database | MySQL 8 (utf8mb4 — full Bangla Unicode) |
| Queue / cache | database driver by default (Redis-ready) |

The platform is **one Laravel application with two faces**: the server-rendered **Admin
Portal** (`/admin/*`, `routes/web.php`) and the **Public Portal API** (`/api/v1/*`,
`routes/api.php`) that a separate Spotify-style web/mobile client consumes.

---

## 2. Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`, `intl`, `exif`
- Composer 2
- Node.js 18+ and npm
- MySQL 8 (or MariaDB 10.6+). XAMPP/standalone both fine.
- (Optional) Docker + Docker Compose

---

## 3. Setup — manual run

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env          # (already present in this repo)
php artisan key:generate

# 3. Configure the database in .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=betar_archive
#    DB_USERNAME=root
#    DB_PASSWORD=
#    (create the database first: CREATE DATABASE betar_archive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;)

# 4. Migrate + seed (roles, demo staff & listeners, full sample catalogue)
php artisan migrate --seed

# 5. Build front-end assets
npm run build          # production build
# or, during development, run the dev server:
npm run dev

# 6. Serve
php artisan serve      # http://127.0.0.1:8000
```

Then open **http://127.0.0.1:8000/admin** and sign in (see [Accounts](#7-seeded-accounts)).

### One-shot dev (server + queue + logs + vite)
```bash
composer run dev
```

---

## 4. Setup — Docker

```bash
# Build and start the full stack (app + MySQL + Redis)
docker compose up -d --build

# The entrypoint auto-generates APP_KEY, waits for MySQL, migrates and seeds
# (RUN_MIGRATIONS / RUN_SEEDERS are "true" by default in docker-compose.yml).

# App:   http://localhost:8080/admin
# MySQL: localhost:33061  (user betar / pass secret)
```

Override any value via environment or a `.env` alongside `docker-compose.yml`
(`APP_PORT`, `DB_PASSWORD`, `RUN_SEEDERS`, …). The image bundles PHP-FPM, Nginx, a queue
worker and the scheduler under Supervisor.

To rebuild after code changes: `docker compose up -d --build`.

---

## 5. Project structure

```
app/
  Http/
    Controllers/
      Admin/            # ~40 admin-portal controllers (one per module/entity)
      Api/V1/           # public API controllers (Auth, Browse, Catalogue, Search,
                        #   Playback, Library, Engagement, Recommendation, Subscription)
    Middleware/
      EnsureStaffUser   # portal is staff-only
      OptionalAuth      # attaches user if a token is present (guest mode)
    Resources/          # API JSON transformers (AudioAssetResource, SongResource, …)
  Models/               # 65 Eloquent models (+ Concerns\Auditable trait)
  Services/
    EntitlementService  # freemium plan resolution (M18)
    StreamingService    # signed, expiring stream URLs (M07)
    AdService           # server-side ad selection for free tier (M27)
  Support/Theme.php     # resolves config/theme.php + settings into CSS variables
config/theme.php        # CENTRAL theme & colour configuration
database/
  migrations/           # 21 module migration files (~55 tables)
  seeders/              # 22 seeders, orchestrated by DatabaseSeeder (file by file)
resources/
  css/app.css           # Tailwind 4 design system (semi-flat, light+dark)
  js/app.js             # Alpine stores, colour-mode switch, Chart.js
  views/
    layouts/            # admin.blade.php (app shell), guest.blade.php (login)
    partials/           # sidebar, topbar
    components/         # icon, stat-card, status-badge, form.*, waveform, …
    admin/<module>/     # index/form/show per module
routes/
  web.php               # admin portal (permission-gated)
  api.php               # public API v1
docs/                   # this file, API_DOCUMENTATION.md, USER_MANUAL.md
docker/                 # nginx.conf, supervisord.conf, entrypoint.sh
```

---

## 6. Module → code map (FRS traceability)

| Module | Area | Key code |
|--------|------|----------|
| M01 | Users, roles, RBAC | `RolePermissionSeeder`, `UserController`, `RoleController`, `Api\V1\AuthController` |
| M02 | Ingestion / upload | `AudioAssetController@store`, `audio_versions` |
| M03 | Digitization | `MediaItemController`, `media_items` |
| M04 | Repository / versions | `AudioAsset`, `AudioVersion`, `Programme`, `Season` |
| M05 | Metadata / vocabularies | `VocabularyController`, `Category/Genre/Mood/Language/Tag` |
| M06 | Search | `Api\V1\SearchController` |
| M07 | Player / visualization | `Api\V1\PlaybackController`, `StreamingService`, `x-waveform` |
| M08 | Music library | `SongController`, `AlbumController`, `ArtistController`, `PlaylistController` |
| M09 | Podcasts | `PodcastChannelController`, `PodcastEpisodeController` |
| M10 | Event programmes (Bhoot FM) | `EpisodeController`, `StoryController`, `StorySubmissionController` |
| M11 | Marketing production | *removed* |
| M12 | Editing | `EditSessionController`, `edit_sessions` |
| M13 | Workflow / approval | `WorkflowController`, `ApprovalController`, `Workflow`/`Approval` models |
| M14 | Rights | `RightsHolderController`, `RightsRecordController` |
| M15 | QC | `QcReportController` |
| M16 | AI features | `TranscriptController`, `AiSuggestionController` |
| M17 | Public portal | `Api\V1\BrowseController`, `CatalogueController`, `LibraryController` |
| M18 | Freemium / payments | `PlanController`, `SubscriptionController`, `PaymentController`, `Api\V1\SubscriptionController`, `EntitlementService` |
| M19/M20 | Play events / dashboards | `Api\V1\PlaybackController@event`, `DashboardController` |
| M21 | Audit trail | `AuditLogController`, `Concerns\Auditable`, `AuditLog` |
| M22 | Backup / preservation | `BackupController`, `BackupRun`, `IntegrityCheck` |
| M23 | Integration / API | `routes/api.php`, Sanctum, `docs/API_DOCUMENTATION.md` |
| M24 | Curation / homepage | `HomeSectionController`, `BannerController`, `Api\V1\BrowseController@home` |
| M25 | Recommendations | `Api\V1\RecommendationController` |
| M26 | Engagement / moderation | `CommentModerationController`, `ContentReportController`, `TakedownRequestController`, `Api\V1\EngagementController` |
| M27 | Advertising | `AdvertiserController`, `AdCampaignController`, `AdAssetController`, `AdService` |

---

## 7. Seeded accounts

All seeded accounts use password **`123456`**.

**Staff (Admin Portal)** — one per role:

| Role | E-mail |
|------|--------|
| Super Administrator | `admin@betar.gov.bd` |
| Archive Administrator | `archive.admin@betar.gov.bd` |
| Archivist | `archivist@betar.gov.bd` |
| Audio Editor | `editor@betar.gov.bd` |
| Programme Producer | `producer@betar.gov.bd` |
| Podcast Manager | `podcast@betar.gov.bd` |
| Music Library Manager | `music@betar.gov.bd` |
| Content Curator | `curator@betar.gov.bd` |
| Moderator | `moderator@betar.gov.bd` |
| Advertisement Manager | `ads@betar.gov.bd` |
| Copyright Officer | `copyright@betar.gov.bd` |
| Approver / Management | `approver@betar.gov.bd` |
| Researcher | `researcher@betar.gov.bd` |

**Listeners (Public API):** `listener1@example.com` … `listener6@example.com`.

---

## 8. Central theme configuration

The whole portal re-skins from one place — `config/theme.php` — plus live overrides in the
`settings` table (Admin → Settings → Theme). `App\Support\Theme::cssVariables()` emits a full
50–950 palette as CSS variables into the page `<head>`; Tailwind utilities (`bg-primary-600`,
`text-accent-500`, …) consume them. To change the brand colour, edit `theme.primary` /
`theme.accent` (or set `theme_primary` / `theme_accent` in Settings) — no recompile needed.
Light/dark mode is a `.dark` class on `<html>` toggled by `resources/js/app.js` and persisted
in `localStorage` (light | dark | system).

---

## 9. Conventions

- **Controllers**: `declare(strict_types=1)`, typed signatures, thin controllers. Write
  actions call `$this->authorize('<permission>')`; routes are additionally permission-gated.
- **Models**: `$guarded = []` with explicit `$casts`; relationships typed; the `Auditable`
  trait auto-logs create/update/delete to the audit trail.
- **Permissions**: named `group.action` (e.g. `assets.publish`). Defined in
  `RolePermissionSeeder::PERMISSIONS`; roles map to them in `::ROLES`.
- **Polymorphism**: a stable morph map (`AppServiceProvider`) aliases models to short strings
  (`song`, `album`, …) used in DB and API.
- **API resources** transform every model; each carries a `type` discriminator.
- **Blade**: reusable components in `resources/views/components`; never inline raw colours —
  use the theme utilities.

---

## 10. Common commands

```bash
php artisan migrate:fresh --seed     # rebuild DB from scratch with sample data
php artisan route:list --path=api    # inspect API routes
php artisan route:list --path=admin  # inspect admin routes
php artisan optimize:clear           # clear config/route/view caches
npm run build                        # rebuild assets after adding Blade classes
./vendor/bin/pint                    # PSR-12 formatting
php artisan test                     # run the test suite
```

> **Note on assets:** Tailwind scans Blade files at build time. After adding new views or
> utility classes, re-run `npm run build` (or keep `npm run dev` running).

---

## 11. Extending

- **New admin module**: add migration → model (with `Auditable`) → permissions in
  `RolePermissionSeeder` → controller in `app/Http/Controllers/Admin` → routes in `web.php`
  (permission-gated) → views under `resources/views/admin/<module>` → a sidebar entry in
  `resources/views/partials/sidebar.blade.php`.
- **New API endpoint**: controller in `app/Http/Controllers/Api/V1` → resource(s) →
  route in `api.php` (choose `optional.auth` for guest-friendly or `auth:sanctum`) →
  document it in `docs/API_DOCUMENTATION.md`.
- **Real audio pipeline**: `AudioAssetController@store` and the seeders currently register
  version metadata without moving bytes. Wire a queued job (M02) to virus-scan, extract
  technical metadata, transcode proxies and compute waveforms, then populate `audio_versions`.
