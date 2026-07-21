# Bangladesh Betar Audio Archive — Public Portal API (v1)

REST API that powers the Spotify-style public listening application (web + Android/iOS).
It exposes **only** published, rights-cleared content; preservation masters and restricted
material are never reachable (FR-PUB-03 / FR-PLY-10).

- **Base URL:** `{APP_URL}/api/v1`
- **Format:** JSON. Send `Accept: application/json` on every request.
- **Auth:** Bearer tokens issued by Laravel Sanctum. Send `Authorization: Bearer <token>`.
- **Localization:** Content carries both English and Bangla fields (`*_bn`). Send
  `Accept-Language: bn|en` for future localized system strings.
- **Rate limiting:** 120 requests/min per user or IP (FR-API-06). Auth endpoints: 10/min per IP.
- **Guest mode:** Most discovery, catalogue, search and streaming endpoints work without a
  token (FR-USR-11). Sign-in is required only for library, engagement writes, and subscription.

---

## Table of contents

1. [Conventions](#conventions)
2. [Authentication](#authentication)
3. [Discovery & Home](#discovery--home)
4. [Search](#search)
5. [Catalogue](#catalogue)
6. [Playback & Streaming](#playback--streaming)
7. [Recommendations](#recommendations)
8. [Library (auth)](#library-auth)
9. [Engagement](#engagement)
10. [Subscriptions & Payments](#subscriptions--payments)
11. [Object schemas](#object-schemas)
12. [Error format](#error-format)

---

## Conventions

- **Pagination:** list endpoints accept `?page=` and `?per_page=` (default 20). Paginated
  responses follow Laravel's resource shape: `{ "data": [...], "links": {...}, "meta": {...} }`.
- **Polymorphic types:** playable/followable objects carry a `type` string
  (`song`, `audio_asset`, `album`, `artist`, `programme`, `episode`, `story`,
  `podcast_channel`, `podcast_episode`, `playlist`). Use `type` + `id` when referencing them.
- **Timestamps:** ISO-8601 (`2026-07-20T09:30:00+00:00`).
- **IDs** are integers unless noted.

---

## Authentication

Public listeners register with e-mail, phone (OTP) or (future) social login (FR-USR-02).

### `POST /auth/register`
Register a new listener account.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | yes | |
| `email` | string | yes | unique |
| `phone` | string | no | |
| `password` | string | yes | min 6, must be `confirmed` |
| `password_confirmation` | string | yes | |
| `locale` | `en`\|`bn` | no | default `bn` |
| `accept_terms` | boolean | yes | must be truthy (FR-USR-12) |

**201** →
```json
{
  "message": "Registration successful.",
  "token": "1|abcdef...",
  "token_type": "Bearer",
  "user": { "id": 21, "name": "Arif", "email": "arif@example.com", "locale": "bn", "is_premium": false }
}
```

### `POST /auth/login`
| Field | Type | Required |
|-------|------|----------|
| `email` | string | yes |
| `password` | string | yes |
| `device_name` | string | no |

**200** → same token payload as register. **422** on invalid credentials.

### `POST /auth/otp/request`
Body: `{ "phone": "01700000000" }` → **200** `{ "message": "OTP sent (demo: use 123456).", "expires_in": 300 }`

### `POST /auth/otp/verify`
Body: `{ "phone": "01700000000", "otp": "123456", "name": "Optional" }` → token payload.
Creates the account on first verification.

### `POST /auth/logout` 🔒
Revokes the current access token. **200**.

### `GET /auth/me` 🔒
Returns the profile plus resolved plan entitlements.
```json
{ "data": {
  "id": 21, "name": "Arif", "email": "...", "phone": null, "locale": "bn",
  "avatar_url": null, "preferences": null,
  "entitlements": { "plan": "free", "is_premium": false, "ads_enabled": true,
    "max_quality_kbps": 128, "skips_per_hour": 6, "offline_downloads": false,
    "equalizer": false, "premium_content_access": "preview", "preview_seconds": 90 }
}}
```

### `PUT /auth/profile` 🔒
Update `name`, `phone`, `locale`, `preferences` (incl. `preferences.personalization_opt_out`,
`preferences.notifications`) — FR-USR-09.

---

## Discovery & Home

### `GET /home`
Assembles the public home screen: promotional banners + curated/dynamic sections managed
by the Content Curator (M24). Sections resolve their items server-side (trending, new
releases, On This Day, featured artists/albums, curated playlists, or manually curated).

```json
{
  "banners": [ { "id": 1, "title": "...", "title_bn": "...", "subtitle": "...", "image_url": null, "target_type": "url", "target_value": "/browse" } ],
  "sections": [
    { "id": 1, "title": "Trending Now", "title_bn": "এখন ট্রেন্ডিং", "type": "trending", "layout": "row", "items": [ /* objects */ ] }
  ]
}
```

### `GET /categories` · `GET /genres`
Flat lists of active content categories / genres (`id`, `name`, `name_bn`, `slug`).

### `GET /trending` · `GET /top-played` · `GET /new-releases`
Collections of audio assets ordered by popularity / recency (FR-ANL-06). `{ "data": [ AudioAsset ] }`.

### `GET /on-this-day`
Content whose original broadcast date matches today (FR-CUR-04):
`{ "date": "2026-07-20", "data": [ AudioAsset ] }`.

### `GET /featured-artists`
`{ "data": [ Artist ] }`.

### `GET /editorial-playlists`
Curated collections published by staff (FR-CUR-02): `{ "data": [ Playlist ] }`.

---

## Search

### `GET /search?q={query}&type={optional}`
Full search across the published catalogue (M06). `type` narrows to one of
`song|artist|album|podcast|programme|audio`. Bangla and English titles are matched.
```json
{ "query": "liberation", "results": {
  "songs":   { "data": [ Song ] },
  "artists": { "data": [ Artist ] },
  "albums":  { "data": [ Album ] },
  "podcasts":{ "data": [ PodcastChannel ] },
  "audio":   { "data": [ AudioAsset ] }
}}
```

### `GET /search/suggest?q={query}`
Type-ahead suggestions (min 2 chars) across titles, artists and albums (FR-SRC-01):
`{ "data": [ { "text": "Nodir Naam Modhumoti", "type": "title" } ] }`.

### `GET /search/semantic?q={natural language}`
Natural-language semantic search (FR-SRC-09). Without an embeddings backend configured
this degrades to keyword matching over metadata and **verified** transcripts; the response
shape is stable so the client integration does not change when embeddings are enabled.

---

## Catalogue

All catalogue endpoints return only published, rights-cleared content. Detail endpoints
include `waveform` data for the player. When a token is supplied, item objects include
`is_favorited` / `is_following`.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/songs` | List songs. Filters: `genre`, `album`, `sort=popular`. |
| GET | `/songs/{song}` | Song detail incl. lyrics (en/bn), waveform, version family. |
| GET | `/albums` | List albums. Filter: `q`. |
| GET | `/albums/{album}` | Album detail with ordered track list. |
| GET | `/artists` | List artists. Filters: `type`, `q`. |
| GET | `/artists/{artist}` | Artist page: bio, songs, albums (FR-PUB-12). |
| GET | `/programmes` | List programmes. Filter: `type`. |
| GET | `/programmes/{programme}` | Programme + paginated episodes. |
| GET | `/episodes/{episode}` | Episode with its stories (Bhoot FM, M10). |
| GET | `/stories/{story}` | Single story (honours anonymity, FR-EVT-04). |
| GET | `/podcasts` | List podcast channels. |
| GET | `/podcasts/{podcastChannel}` | Channel + paginated episodes. |
| GET | `/podcast-episodes/{podcastEpisode}` | Podcast episode detail with chapters. |
| GET | `/assets/{asset}` | Generic audio asset detail. |
| GET | `/playlists/{playlist}` | Public playlist detail (editorial or user-shared) with resolved items. |

---

## Playback & Streaming

### `GET /assets/{asset}/stream`
Resolves a **signed, expiring** streaming URL respecting the listener's plan (M07/M18).
Premium-flagged content returns a preview version for free/guest listeners; a pre-roll ad
is attached for the free tier (never for premium or public-service content).

```json
{
  "asset_id": 30,
  "title": "Nodir Naam Modhumoti",
  "stream": {
    "version": "online",
    "url": "https://.../api/v1/stream/30/play/88?expires=...&signature=...",
    "expires_at": "2026-07-20T10:00:00+00:00",
    "duration_seconds": 240,
    "is_preview": false,
    "bitrate_kbps": 128
  },
  "ad": { "id": 4, "title": "Rupali Soap 30s Spot", "type": "commercial", "duration_seconds": 30, "slot": "pre_roll", "audio_url": "https://.../api/v1/ads/4/audio" },
  "requires_login_for_full": false
}
```
The signed `stream.url` is the only way to reach audio bytes and expires after
`stream_url_ttl_minutes` (default 30). It streams the derived media file with
HTTP `Range` support (seekable; **206 Partial Content**). Master versions are
rejected with **403**. In demo environments without archive media on disk, a
synthesized fallback track is streamed (`php artisan demo:audio` generates them).

### `GET /ads/{ad}/audio`
Streams the ad creative referenced by the `ad.audio_url` field above (public,
range-capable). Clients play it as a pre-roll before the main stream and then
log the impression via `POST /ads/impression`.

### `POST /assets/{asset}/events`
Emit a playback event for analytics + Continue Listening (FR-ANL-01 / FR-PLY-09).

| Field | Type | Notes |
|-------|------|-------|
| `event_type` | enum | `play`\|`pause`\|`seek`\|`replay`\|`skip`\|`progress`\|`complete` |
| `position_seconds` | int | current position |
| `platform` | enum | `web`\|`android`\|`ios` |
| `anonymous_id` | string | for guests (pseudonymized, FR-ANL-07) |

**202 Accepted.** Signed-in `progress`/`pause`/`complete` events update resume position across devices.

### `POST /ads/impression`
Log an ad impression/completion (FR-ADV-06). Body: `ad_asset_id`, `slot`, `platform`,
`completed`, `anonymous_id`. **202**.

---

## Recommendations

### `GET /recommendations/for-you`
Personalized rows for signed-in listeners built from listening history + content signals
(M25). Guests / opted-out users get a trending fallback (FR-REC-04). Response includes a
`personalized` boolean.

### `GET /assets/{asset}/similar`
"Similar to this" / autoplay continuation using genre + content type (FR-REC-02).

### `POST /me/personalization/opt-out` 🔒
Body `{ "opt_out": true|false }` — opt out of personalization and clear the profile (FR-REC-07).

---

## Library (auth) 🔒

All require a listener token.

### Playlists (FR-PUB-04)
| Method | Path | Body |
|--------|------|------|
| GET | `/me/playlists` | — |
| POST | `/me/playlists` | `title`, `description?`, `is_public?` |
| GET | `/me/playlists/{playlist}` | — |
| PUT | `/me/playlists/{playlist}` | `title?`, `description?`, `is_public?` |
| DELETE | `/me/playlists/{playlist}` | — |
| POST | `/me/playlists/{playlist}/items` | `playable_type`, `playable_id` |
| DELETE | `/me/playlists/{playlist}/items/{item}` | — |
| PUT | `/me/playlists/{playlist}/reorder` | `order`: array of item IDs in new order |

### Favourites
- `GET /me/favorites` — paginated favourited assets.
- `POST /me/favorites/toggle` — `{ favoritable_type, favoritable_id }` → `{ "favorited": true|false }`.

### Follows (FR-PUB-12)
- `GET /me/follows` — grouped by type.
- `POST /me/follows/toggle` — `{ followable_type: artist|programme|podcast_channel|playlist, followable_id }`.

### History & Continue Listening (FR-PUB-14)
- `GET /me/history` — paginated listening history with resume position.
- `GET /me/continue-listening` — unfinished items to resume.

### Queue (FR-PLY-11)
- `GET /me/queue` → `{ items, repeat_mode, shuffle }`.
- `PUT /me/queue` — persist `{ items:[{type,id}], repeat_mode: off|all|one, shuffle }`.

---

## Engagement

### `GET /assets/{asset}/comments`
Public list of **approved** comments and ratings (FR-ENG-01/02). Paginated. Each entry
carries the star rating (`rating`, 1–5 or `null`) the listener gave at the time they
posted, alongside the comment `body`.

### `POST /assets/{asset}/comments` 🔒
Unified **Comments & Ratings** submission. Body: `{ "body"?: "...", "rating"?: 1..5 }` —
at least one of `body` / `rating` is required, both may be sent together in one request.

- If `rating` is present, it is upserted into the listener's per-asset rating (one per
  user, changeable — FR-ENG-02) and the asset's `avg_rating` / `rating_count` are
  recomputed immediately.
- If `body` is present (non-empty), a comment is created — subject to the moderation
  policy (pre/post) and profanity filter (FR-ENG-03); the response indicates whether it
  is live or pending. Comments require `allow_comments` on the asset; a rating-only
  submission does not.

**200/201** →
```json
{
  "message": "Rating saved. Comment posted.",
  "data": { "id": 12, "body": "...", "rating": 4, "status": "approved", "...": "..." },
  "rating": { "avg_rating": 4.32, "rating_count": 89, "your_rating": 4 }
}
```
`data` is `null` for a rating-only submission (no comment row is created); `rating` is
`null` when no rating was included in the request.

### `DELETE /comments/{comment}` 🔒
Delete your own comment. (The rating you gave, if any, is unaffected — ratings are
managed independently of the comment that displayed them.)

### `POST /assets/{asset}/rate` 🔒
Standalone rating endpoint, kept for API completeness. Body `{ "rating": 1..5 }`. One
rating per user per item, changeable (FR-ENG-02). Returns updated `avg_rating` +
`rating_count`. Prefer `POST /assets/{asset}/comments` with a `rating` field so a rating
can be given alongside a comment in one request — that is what the web portal uses.

### `POST /reports` 🔒
Report a comment or content item (FR-ENG-04): `{ reportable_type: comment|audio_asset, reportable_id, reason, details? }`.

### `POST /issue-reports`
Report a broken/incorrect item (FR-ENG-07), guest-allowed:
`{ audio_asset_id?, issue_type: broken_audio|wrong_metadata|inappropriate|other, description? }`.

### `POST /feedback`
General feedback (FR-ENG-09), guest-allowed: `{ category, subject?, message }`.

---

## Subscriptions & Payments

### `GET /plans`
Public plan comparison (FR-SUB-01/02): `{ "data": [ Plan ] }`.

### `GET /me/subscription` 🔒
Current entitlements + active subscription state.

### `POST /me/subscription/subscribe` 🔒
Activate/upgrade Premium (FR-SUB-03/05). Simulates a gateway charge.

| Field | Type | Notes |
|-------|------|-------|
| `plan_code` | `premium` | |
| `billing_cycle` | `monthly`\|`annual` | |
| `method` | `bkash`\|`nagad`\|`rocket`\|`card` | |
| `promo_code` | string | optional (e.g. `BETAR50`) |
| `start_trial` | boolean | begins the configured free trial |

**201** → subscription state + invoice + refreshed entitlements.

### `POST /me/subscription/cancel` 🔒
Cancels auto-renewal; Premium stays active until period end, then downgrades (FR-SUB-08).

### `GET /me/payments` 🔒
Payment/transaction history (FR-SUB-04).

---

## Object schemas

### AudioAsset
```json
{ "id": 30, "type": "audio_asset", "content_type": "song", "archive_no": "BB-2026-000030",
  "title": "...", "title_bn": "...", "slug": "...", "description": "...", "duration_seconds": 240,
  "artwork_url": null, "category": "Songs", "language": "Bangla", "station": "Bangladesh Betar Dhaka",
  "is_premium": false, "is_public_service": false, "content_warning": null,
  "first_broadcast_on": "1978-05-12", "play_count": 12345, "favorite_count": 210,
  "avg_rating": 4.3, "rating_count": 88, "allow_comments": true,
  "waveform": [0.3, 0.6, ...] /* detail only */, "is_favorited": false /* when authed */,
  "my_rating": null /* 1..5 when authed and previously rated, else omitted */ }
```

### Song
`id, type, title, title_bn, archive_no, audio_asset_id, duration_seconds, genre, mood,
version_type, release_year, is_premium, play_count, avg_rating, singers[], album, lyrics{en,bn} (detail), waveform (detail)`

### Album
`id, type, title, title_bn, album_type, year, artwork_url, description, artists[], tracks_count, tracks[] (detail)`

### Artist
`id, type, name, name_bn, artist_type, photo_url, is_featured, followers_count, bio/bio_bn (detail), is_following (authed)`

### PodcastChannel / PodcastEpisode / Programme / Episode / Story / Playlist / Plan
See inline examples above; every object carries a `type` discriminator.

---

## Error format

Errors use standard HTTP status codes with a JSON body:
```json
{ "message": "The given data was invalid.", "errors": { "email": ["The email field is required."] } }
```

| Status | Meaning |
|--------|---------|
| 401 | Missing/invalid token on a protected route |
| 403 | Not allowed (e.g. master file, expired signed URL, foreign playlist) |
| 404 | Not found or not published |
| 422 | Validation failed |
| 429 | Rate limit exceeded |

🔒 = requires `Authorization: Bearer <token>`.
