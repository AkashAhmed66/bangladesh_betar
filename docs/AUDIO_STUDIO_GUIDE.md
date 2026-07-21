# Audio Ingestion & Audio Studio — Step-by-Step Guide

This guide covers the two features just added:

1. **Audio ingestion by file upload** (M02) — you can now upload a real audio file, one at a
   time, and the system extracts its technical metadata and waveform automatically.
2. **Audio Studio** — the professional visual interface implementing the *Audio Visualization
   Feature* spec: interactive waveform, spectrogram, real-time frequency spectrum, loudness
   meter, playback heat-map, silence/noise detection, speaker/segment navigation, content
   markers, non-destructive editing and original-vs-edited compare.

---

## Part 1 — Uploading audio (ingestion)

### A. Ingest a brand-new asset
1. Sign in to `/admin` (e.g. `admin@betar.gov.bd` / `123456`).
2. Go to **Archive → Audio Assets → Ingest Asset**.
3. In the **Audio File** card at the top, click the drop-zone and choose **one** file
   (WAV, BWF, FLAC, MP3, AAC, M4A, OGG or AIFF; up to 512 MB — configurable in
   `config/audio.php`).
4. Fill in the metadata (title, content type, category, language, station, dates, access
   flags). You do **not** enter duration/format — they're detected from the file.
5. Click **Register Asset**. The system:
   - stores the file as the immutable **preservation master** on the archive disk,
   - derives a default **online streaming** version,
   - extracts duration, sample rate, bit depth, channels, bitrate, **loudness (LUFS)**,
     **peak dBFS**, **silence %** and a real **waveform**,
   - records the SHA-256 checksum and file size.
6. You land on the asset page — click **Open Studio** to visualise it.

### B. Replace / add audio on an existing asset
- On the **asset detail page** or the **Studio** header, use **Replace audio** and pick a file.
  A new master + online version is added; **previous masters are preserved** (never
  overwritten), honouring the immutable-master rule (FR-REP-03).

### How extraction works (ffmpeg vs. fallback)
- **With ffmpeg/ffprobe available** (the Docker image installs them): all formats get full
  extraction, including EBU R128 loudness and ffmpeg-decoded waveform peaks.
- **Without ffmpeg** (e.g. a Windows dev machine): the built-in PHP analyzer parses **WAV/BWF**
  natively (real duration, sample rate, peaks, peak-dB, silence). For compressed formats the
  file is stored and the **browser** computes the waveform on first Studio view and saves it
  back. Either way, uploading works.
- To force full extraction locally, run the app under Docker (`docker compose up -d --build`)
  or install ffmpeg and set `FFMPEG_PATH` in `.env`.

---

## Part 2 — Opening the Audio Studio

From any asset: **Audio Assets → (open an asset) → Open Studio**, or the accent
**Open Studio** button in the asset header. URL: `/admin/assets/{id}/studio`.

The Studio streams the audio to your browser (range-capable, so seeking works) and renders
everything client-side with **WaveSurfer.js v7 + the Web Audio API**. No file leaves the
server except the audio stream itself; the master is never modified by anything you do here.

> If an asset has no real uploaded file yet (e.g. seeded demo data), the Studio transparently
> plays a synthesized demo track so every panel still works. Upload a real file to see real data.

---

## Part 3 — Using each visualization (step by step)

### 1. Interactive waveform
- The waveform is the big graph at the top. **Click anywhere** to seek/start playback there.
- **Zoom**: use the **−/+** buttons (top-right of the waveform card). "fit" shows the whole
  file; higher values (e.g. `120px/s`) zoom in for sample-precise navigation.
- **Hover** shows a time cursor; the **minimap** underneath shows your position in the whole file.
- **Drag** across the waveform to select a region (used for markers and editing, below).
- Transport bar: **Play/Pause**, **Stop**, current/total time, and **Speed** (0.5×–2×).

### 2. Spectrogram
- Click the **Spectrogram** tab (top-left of the waveform card).
- It renders frequency (vertical, mel scale) × time (horizontal) × intensity (colour) — use it
  to spot background noise, hiss, silence, clipping and damaged audio for restoration/QC.
- Switch back with the **Waveform** tab.

### 3. Real-time frequency spectrum (right rail, top)
- **Press Play.** The canvas animates live: **bars = left channel**, **line = right channel**
  (log-spaced low→high). Use it to judge noise, distortion, voice clarity and L/R balance.

### 4. Loudness meter (right rail)
- While playing, **Momentary RMS** and **Peak** meters move in real time.
- A **⚠ Clipping / over-level** warning appears if the peak approaches 0 dBFS.
- The lower tiles show the **Integrated LUFS**, **Peak dBFS** and **Silence %** measured at
  ingest, against the **−23 LUFS** EBU R128 broadcast target (LUFS turns amber if out of range).

### 5. Playback heat-map (below the waveform)
- The teal strip shows **most-played vs least-played** sections (darker = more listens),
  from real analytics. **Click any bar to jump** to that moment. Great for finding highlights.

### 6. Silence & noise detection
- Click **Silence** (top-right of the waveform card). The Studio scans the decoded audio and
  overlays grey regions on every silent/low gap, with a count (e.g. "4 silent gaps").
- Use these to review or trim dead air. (Threshold ≈ −34 dBFS, gaps > 0.4 s.)

### 7. Speakers & segments (right rail, bottom)
- If the asset has a transcript, each line becomes a **clickable speaker/segment row** and a
  labelled band on the waveform. **Click a row to jump** straight to that speaker/segment.

### 8. Content markers (right rail)
- Existing **AI-detected markers** (intro, music, keyword, applause, emotion, chapters, outro…)
  and manual markers are listed and drawn on the timeline; AI ones are tagged **· AI** (draft).
- **Add a marker**: pick a **type**, type a **label**, then click **+**. It's placed at the
  playhead — or, if you dragged a selection, across that region. **Click a marker** to jump to
  it; the **✕** deletes it. This covers chapter markers and annotation.

### 9. Non-destructive editing (needs the *Audio Editor* permission)
1. Click **Enable edit mode** (Editing card). A toolbar appears.
2. **Drag** on the waveform to select a region.
3. Apply an operation: **Trim to selection**, **Cut selection**, **Split at cursor**,
   **Fade in**, **Fade out**, or **Gain +3 dB**. Each adds a coloured step to the **Edit
   Decision List (EDL)**.
4. **Undo/Redo** any step.
5. Name the version and click **Save as new version**. This writes the EDL to an **edit
   session** and creates a new **edited (clip) version** — the **preservation master is never
   touched** (FR-EDT-01/05). (With ffmpeg the EDL is rendered into real audio bytes.)

### 10. Compare original vs edited
- In **Compare Versions**, pick another version from the dropdown. A second waveform loads
  below the main one so you can **A/B the original against an edited version** visually.

---

## Spec → implementation map

| Audio Visualization spec | Where it lives |
|--------------------------|----------------|
| Interactive Waveform (zoom, click, drag, markers) | Studio waveform (WaveSurfer + Regions/Zoom/Minimap/Hover) |
| Frequency Spectrum (real-time, L/R) | Studio right rail — `#spectrum` (Web Audio AnalyserNode + ChannelSplitter) |
| Spectrogram | Studio Spectrogram tab (WaveSurfer Spectrogram plugin) |
| Playback Heatmap | Studio heat strip (from `asset_stats_dailies`) |
| Audio Loudness Meter (peak/RMS/LUFS, clipping) | Studio Loudness Meter (live RMS/peak + ingest LUFS/peak) |
| Silence & Noise Detection | Studio **Silence** button (decoded-audio scan) + ingest `silence %` |
| Speaker & Segment Visualization | Studio Speakers & Segments (from transcripts) |
| Editing Visualization (cut/trim/fade, undo/redo, master-preserving, compare) | Studio Editing + Compare (EDL → `edit_sessions` + new version) |
| AI-Based Content Markers (chapters, keywords, applause…) | Studio Content Markers (`audio_markers`, `is_ai`) |

## Key files (for developers)

- Ingestion: `app/Services/AudioProcessor.php`, `AudioAssetController@store/uploadMaster/ingestFile`,
  `resources/views/admin/assets/form.blade.php`, `config/audio.php`.
- Studio backend: `app/Http/Controllers/Admin/AudioStudioController.php`, routes in
  `routes/web.php` (`admin.assets.studio|stream|markers|edit-session|peaks`),
  `app/Models/AudioMarker.php`, migration `..._create_audio_markers_table.php`.
- Studio frontend: `resources/views/admin/assets/studio.blade.php`, `resources/js/studio.js`
  (Vite entry), `wavesurfer.js` dependency.
- Docker: `ffmpeg` added to `Dockerfile` for full server-side extraction/transcoding.

## Part 4 — Editor · Enhancement · Restoration (real ffmpeg rendering)

The Studio's **Editor · Enhancement · Restoration** panel (needs the *Audio Editor*
permission) applies real audio processing and renders a **new version** — the preservation
master is never modified (FR-EDT-01). Processing runs server-side with **ffmpeg** in a
background queue job.

### Hearing your changes (all in the main waveform)
- **Instant (Web Audio):** with **Live preview** on (default), **EQ, gain, de-hum, compressor,
  limiter and tempo** change in real time on the main player — press play and move the sliders.
- **Rendered into the waveform (ffmpeg):** **denoise, pitch, de-click, de-clip, normalize** and
  exciter are rendered into the **main waveform** itself about a second after you change them
  (with a brief "Rendering preview…" overlay), so the waveform, spectrogram and playback all
  reflect the processed audio — and the instant Web Audio effects still play on top of it. Clear
  those effects and the waveform reverts to the original. Toggle **auto** off to render on demand
  with the **Preview** button.
- **Applied on save only:** structural edits — **trim, cut, reverse, fades, silence removal,
  channel/sample-rate/format changes** — apply when you **save** (they change the
  timeline/output, so previewing them in place would misalign markers).
- **Save** offers two targets: **Save as new version** (default) or **Update this version**
  (overwrite the loaded version in place). The **preservation master is always locked** — you
  can never overwrite it, only create a new version from it.
- You can load **any version** of the family into the Studio (header **Version** switcher, or the
  per-version **Open** button on the asset page) and edit/listen to each individually.

### How to use it
1. Open an asset → **Open Studio** → scroll to **Editor · Enhancement · Restoration**.
2. Set any combination of operations across the sections:
   - **Trim & Cut** — trim to a range, or add one/more cut ranges. Click **⤓ sel** to fill
     the range from a waveform drag-selection.
   - **Volume & Dynamics** — gain, loudness normalize (target LUFS), compressor, limiter.
   - **Equalizer** — bass / mid / treble, with presets (Voice clarity, Warm, Bright, Flat).
   - **Restoration** — noise reduction (strength), de-hum (50/60 Hz), de-click, de-clip/pop.
   - **Pitch & Tempo** — pitch shift (semitones, keeps length) and tempo (keeps pitch).
   - **Time FX** — reverse, fade in/out, remove silence.
   - **Export Format** — WAV/FLAC/MP3/AAC/OGG, bit depth, sample rate, channels, bitrate.
3. The **Processing Chain** on the right lists, in order, exactly what will be applied.
4. Name the output (optional) and click **Render new version**. A progress bar polls the job;
   when done the page reloads and the new version appears in the **version family** and the
   **Compare Versions** picker (play it, or A/B it against the original).

### How it works (for developers)
- UI builds an operation list → `POST /admin/assets/{asset}/render` → creates an
  `edit_sessions` row (`render_status=queued`) → dispatches `App\Jobs\RenderEditSession`.
- `App\Services\AudioRenderService` compiles the ops into one deterministic ffmpeg
  filtergraph (see the mapping in `docs/AUDIO_EDITING_ROADMAP.md`), runs it, and writes a new
  `audio_versions` row (`edited` / `enhanced` / `restored`), analysed for real metadata.
- The Studio polls `GET /admin/assets/{asset}/render/{editSession}/status` for progress.
- Requires **ffmpeg** — present in the Docker image (and the supervisor runs a `queue:work`
  worker, so renders process automatically). On a host without ffmpeg the render fails with a
  clear message instead of silently breaking.

> **AI features** (transcription, diarization, source separation, restoration-by-ML, moderation)
> are the separate Python microservice described in `docs/AUDIO_EDITING_ROADMAP.md` (Phase 5),
> not part of this ffmpeg pipeline.

## Notes & limits
- Uploads are **one file at a time** by design for now (bulk/resumable is a later step).
- Without ffmpeg, edit operations are recorded as an EDL and a version record is created, but
  the audio isn't re-rendered into new bytes (the EDL is the source of truth); run under Docker
  for real rendering/transcoding.
- The Studio is same-origin, so the browser's Web Audio analysis is not CORS-blocked.
