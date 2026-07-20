# User Manual — Bangladesh Betar Audio Archive (Admin Portal)

A practical guide for Bangladesh Betar staff using the back-office Admin Web Portal to
digitize, catalogue, review, curate, moderate and publish the national audio archive.

---

## 1. Signing in

1. Open the portal at **`/admin`** (e.g. `http://your-server/admin`).
2. Enter your Bangladesh Betar staff **e-mail** and **password**.
3. The portal is restricted to active staff accounts. Listener accounts cannot sign in here.

After five failed attempts your login is temporarily locked (security policy). Every login,
logout and failed attempt is recorded in the audit trail.

**Interface basics**
- **Collapsible sidebar** (left): grouped navigation — collapse it with the menu button to
  widen your workspace. Your menu only shows the modules your role can access.
- **Top bar**: page title, light/dark/system theme switch, a notifications bell (pending
  approvals), and your account menu (sign out).
- **Theme**: switch between light and dark from the sun/moon button; your choice is remembered.

---

## 2. Roles & what you can do

The portal uses role-based access control — you see only what your role permits.

| Your role | You mainly work with |
|-----------|----------------------|
| Super Administrator | Everything: users, roles, settings, all content |
| Archive Administrator | Assets, digitization, versions, QC, approvals, backups |
| Archivist / Digitization Operator | Uploads, metadata, digitization register, transcripts |
| Audio Editor | Non-destructive edit sessions, clips |
| Programme Producer | Programmes, episodes, playlists |
| Podcast Manager | Podcast channels, episodes, scheduling |
| Music Library Manager | Songs, albums, artists, playlists |
| Content Curator | Home sections, banners, curated collections |
| Moderator | Comments, reported content, issues, feedback |
| Advertisement Manager | Advertisers, campaigns, ad assets, delivery reports |
| Marketing User | Voice artists, scripts, campaigns |
| Copyright Officer | Rights records, takedowns |
| Approver / Management | Approvals, dashboards, subscription & revenue reports |
| Researcher | Browsing permitted content |

---

## 3. The dashboard

Your landing page summarises what matters to your role: total assets and hours, published
count, storage used, listeners, and — depending on your permissions — your approval queue,
QC failures, rights expiring soon, digitization progress, moderation queue, active
subscribers and monthly revenue. A 14-day listening trend and a content-type breakdown chart
sit alongside "Most Played" and either your approval queue or recent uploads.

---

## 4. Bringing audio into the archive

### Uploading an asset (Archive → Audio Assets → Ingest Asset)
1. Click **Ingest Asset**.
2. Fill in **descriptive metadata** in English and Bangla — title, description, content type,
   category, language, source.
3. Set the **hierarchy** (station, programme) and **dates** (recorded, first broadcast — the
   broadcast date powers the public "On This Day" feature).
4. Set **access & protection**: access level, premium flag, public-service flag (always free,
   no ads), comments, content warning.
5. Save. The asset receives a permanent **archive ID** (e.g. `BB-2026-000123`) and a
   preservation master version. The master is immutable and never modified.

### Digitizing physical media (Archive → Digitization)
Register each reel, cassette, DAT, vinyl or CD with its condition, location and priority, then
move it through the pipeline: **registered → in progress → captured → restored → QC → archived**.
Link the finished digital asset back to its physical source. The dashboard shows progress.

---

## 5. Cataloguing music (Catalogue menu)

- **Songs** — create a song record linked to an audio asset; set genre, mood, album, track
  number, version type (original/live/remastered/instrumental/cover) and its master song for
  version families. Add singer, composer and lyricist credits and lyrics (Bangla + English).
- **Albums** — browsable album entities with artwork, year and ordered tracks.
- **Artists** — reusable profiles (singer, composer, presenter, voice artist…). Mark artists
  as *featured* for homepage spotlights; listeners can follow published artists.
- **Vocabularies** — manage categories, genres, moods, languages and tags without IT help.

---

## 6. Podcasts & event programmes

- **Podcast Channels / Episodes** (Content menu) — organise channels → seasons → episodes,
  schedule future publication, mark premium episodes, and configure the external RSS feed.
- **Event Episodes / Stories** (Bhoot FM-style) — an episode holds several independent
  **stories**, each with its own timestamps, storyteller (with an anonymous option), district,
  category and content warning. **Story Submissions** from listeners arrive here with consent
  records for review.

---

## 7. Getting content published — the workflow

Nothing reaches the public app until it passes review. The typical path:

**Upload → Technical QC → Editorial Review → Copyright Review → Management Approval → Publish**

1. **Submit for approval** from the asset page. It enters the workflow configured for its
   content type.
2. **Reviewers** (Governance → Approvals, or your "My Approvals" queue) can **approve**,
   **reject** (comment required) or **request changes** (returns it to the submitter).
3. Once fully approved, an authorized user **Publishes** the asset. Publication is blocked if
   rights are not cleared or no online streaming version exists.
4. Every decision is kept in the asset's **approval history**.

Administrators configure the stages per content type under **Governance → Workflows**.

---

## 8. Rights & quality control

- **Rights Records** (Governance → Rights) — record the rights holder, rights types
  (broadcast/streaming/download/commercial), territory, validity dates and royalties. The
  system blocks publication of unauthorized or expired content and flags rights **expiring
  soon** (90/30/7 days). Copyright Officers also handle **takedown** requests.
- **Quality Control** (Governance → Quality Control) — every file is auto-analysed for
  silence, clipping, volume, noise, channel faults and loudness. Failed items go to a
  reviewer who inspects the waveform and **approves / rejects / requests re-processing**.
- **Transcripts & AI Review** — AI-generated transcripts, tags, genre/mood and speaker
  suggestions appear as **drafts** and only become live metadata after a human **verifies**
  them.

---

## 9. Curating the public app (Curation menu)

Content Curators shape what listeners see:
- **Home Sections** — create, order and schedule the rows on the public home screen
  (Trending, New Releases, On This Day, Featured Artists, curated collections, seasonal
  specials that appear/disappear on set dates).
- **Banners** — promotional banners with scheduling.
- **Editorial Playlists / Collections** (Catalogue → Playlists) — build curated collections
  like "Songs of 1971" from published, rights-cleared content and publish them to the app.

---

## 10. Community & moderation (Community menu)

- **Comments** — approve, hide or remove listener comments; the profanity filter and
  pre/post-moderation policy are configurable in Settings.
- **Reported Content** — a queue of user-reported comments/content to action or dismiss.
- **Issue Reports** — listeners flagging broken audio or wrong metadata; triage and resolve.
- **Takedowns** — external rights-holder complaints; investigate and, if warranted,
  temporarily unpublish the item.
- **Feedback** — general messages from listeners.

---

## 11. Freemium, subscriptions & advertising (Business menu)

- **Plans** — configure the Free and Premium plans: pricing (monthly/annual), free-trial
  length, and Premium features (ad-free, high quality, offline, equalizer). **Promo Codes**
  offer discounts.
- **Subscriptions** — view subscriber status; cancel where needed.
- **Payments** — transaction history; authorized staff can issue **full or partial refunds**
  (reason required — all audit-logged).
- **Advertising** — manage advertisers, **campaigns** (flight dates, budget, targeting,
  frequency cap) and **ad assets** (commercial spots, house announcements, PSAs). Ads serve
  only to free-tier listeners; premium and public-service content never carry ads. Delivery
  reports show impressions and completion.

---

## 12. Insights & protection (Insights menu)

- **Analytics** — plays, unique listeners, completion/skip/replay rates, per-asset
  second-by-second **heat maps** (most-replayed sections), platform and regional breakdowns,
  and trending rankings.
- **Audit Trail** — an immutable, searchable log of every significant action (who, what,
  when, before/after). Filter by user, action and date.
- **Backups** — status of scheduled backups (three-copy rule) and file **integrity checks**;
  corrupt copies are flagged for restore.

---

## 13. Settings (System menu, Super Administrator)

- **General** — application name, tagline, support e-mail, default language.
- **Theme** — primary/accent colours, default mode and sidebar style. Changing the colours
  re-skins the entire portal instantly.
- **Streaming** — free vs premium quality caps, preview length, signed-URL validity, skip
  limits.
- **Moderation / Rights / Workflow / Ads / Backup** — policy defaults (moderation mode,
  profanity filter, rights expiry alert intervals, approval escalation time, ad frequency cap,
  backup schedule, integrity-check interval).

---

## 14. Tips

- **Bangla everywhere** — titles, names and metadata accept full Bangla (Unicode). Fill both
  language fields where available so listeners can search and read in either language.
- **Your queue** — the bell in the top bar and the dashboard both surface items awaiting your
  approval.
- **Nothing is lost** — assets are soft-deleted and metadata is versioned; the audit trail
  reconstructs any change.
- **Restricted content stays internal** — items marked restricted or without cleared rights
  are never exposed to the public app.
