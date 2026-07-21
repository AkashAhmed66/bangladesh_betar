# Bangladesh Betar Audio Archive Platform

A centralized digital system to **preserve, manage, catalogue, govern and publish**
Bangladesh Betar's complete audio collection — songs, radio programmes, podcasts, Bhoot FM
event stories, news, dramas, historic recordings and advertisements.

The solution is **one Laravel application with two faces**:

- **Admin Web Portal** (`/admin`) — the back office where staff upload, digitize, catalogue
  (Bangla + English), edit, run quality control, clear rights, approve, curate, moderate and
  publish content, and manage subscriptions and advertising.
- **Public Portal API** (`/api/v1`) — a secured REST API that powers a Spotify-style public
  listening app (web + Android/iOS) with browsing, search, streaming, playlists, favourites,
  follows, recommendations, comments/ratings and a freemium subscription model.

Built to the *FRS — Bangladesh Betar Audio Archive Platform v1.1*, covering all 27 modules
(M01–M27).

---

## Highlights

- **RBAC everywhere** — Spatie Laravel-Permission; 13 staff roles + Listener, ~40
  permission groups, every action gated.
- **Professional UI** — Tailwind CSS 4 + Blade + Vite + Alpine, collapsible sidebar,
  light/dark/system theme, semi-flat responsive design, Bangla-aware fonts.
- **Central theming** — one config file (`config/theme.php`) + live Settings overrides
  re-skin the whole portal; full 50–950 palette emitted as CSS variables.
- **Preservation-grade model** — immutable masters, derived version families, workflow &
  approval, rights enforcement, QC, audit trail, backups & integrity checks.
- **Freemium streaming** — signed expiring stream URLs, plan entitlements, server-side ad
  insertion for the free tier, bKash/Nagad/Rocket/card payment flows.
- **Runs manually or via Docker**, on MySQL.

---

## Quick start

### Manual
```bash
composer install && npm install
php artisan key:generate
# create MySQL db "betar_archive", set DB_* in .env, then:
php artisan migrate --seed
npm run build
php artisan serve      # http://127.0.0.1:8000/admin
```

### Docker
```bash
docker compose up -d --build   # http://localhost:8080/admin  (auto-migrates & seeds)
```

**Sign in** at `/admin` with `admin@betar.gov.bd` — password **`123456`** (all seeded
accounts use this password; see the full list in `docs/DEVELOPMENT.md`).

### Public portal (Next.js)

The Spotify-style listener app lives in the sibling project
[`../bangladesh_betar_public`](../bangladesh_betar_public) and consumes `/api/v1`.

```bash
php artisan demo:audio                    # one-time: synthesize demo streaming media
php artisan serve --port=8000             # API (APP_URL must match: http://localhost:8000)
cd ../bangladesh_betar_public && npm run dev   # portal on http://localhost:9000
```

Local/demo environments have no real archive media on disk; the signed streaming
endpoint transparently falls back to the synthesized tracks from `demo:audio`
(stored on the local disk under `demo-audio/`), so playback, ads, previews and
seeking all work end-to-end.

---

## Documentation

| Doc | Purpose |
|-----|---------|
| [`docs/USER_MANUAL.md`](docs/USER_MANUAL.md) | Guide for Bangladesh Betar staff using the Admin Portal |
| [`docs/DEVELOPMENT.md`](docs/DEVELOPMENT.md) | Developer setup, architecture, FRS→code map, conventions |
| [`docs/API_DOCUMENTATION.md`](docs/API_DOCUMENTATION.md) | Full Public Portal API (v1) reference for the client app |

---

## Tech stack

Laravel 12 · PHP 8.2+ · MySQL 8 · Tailwind CSS 4 · Vite · Alpine.js · Chart.js ·
Spatie Laravel-Permission · Laravel Sanctum.

Requirements documents live in `public/requirments/` (FRS, system overview, BRD).
