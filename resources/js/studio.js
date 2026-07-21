/**
 * Audio Studio — the professional visualization & editing engine.
 *
 * Implements the Audio Visualization spec on top of WaveSurfer.js v7 + the
 * Web Audio API: interactive waveform (zoom, click-seek, drag-select),
 * spectrogram, real-time L/R frequency spectrum, loudness meter, playback
 * heatmap overlay, silence/noise detection, speaker/segment strips, AI/manual
 * content markers, non-destructive editing (EDL) and original-vs-edited compare.
 *
 * Config is injected by the Blade page as window.__STUDIO__.
 */
import WaveSurfer from 'wavesurfer.js';
import RegionsPlugin from 'wavesurfer.js/dist/plugins/regions.esm.js';
import TimelinePlugin from 'wavesurfer.js/dist/plugins/timeline.esm.js';
import MinimapPlugin from 'wavesurfer.js/dist/plugins/minimap.esm.js';
import HoverPlugin from 'wavesurfer.js/dist/plugins/hover.esm.js';
import SpectrogramPlugin from 'wavesurfer.js/dist/plugins/spectrogram.esm.js';

const CFG = window.__STUDIO__;

function css(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}
const fmt = (s) => {
    if (!isFinite(s)) return '0:00';
    const m = Math.floor(s / 60), sec = Math.floor(s % 60);
    return `${m}:${sec.toString().padStart(2, '0')}`;
};
const $ = (sel) => document.querySelector(sel);
const audioUrl = (versionId) => CFG.urls.streamTemplate.replace('__V__', versionId);

function boot() {
    const dark = document.documentElement.classList.contains('dark');
    const primary = css('--primary-500') || '#14b8a6';
    const accent = css('--accent-500') || '#f59e0b';

    const regions = RegionsPlugin.create();

    // Let WaveSurfer download + decode the real audio: the waveform is
    // sample-accurate AND the Spectrogram plugin has the decoded buffer it
    // needs. Streaming always returns audio (demo fallback), so decode
    // succeeds; failures surface via the error banner below.
    const ws = WaveSurfer.create({
        container: '#waveform',
        height: 140,
        waveColor: dark ? '#334155' : '#cbd5e1',
        progressColor: primary,
        cursorColor: accent,
        cursorWidth: 2,
        barWidth: 2,
        barGap: 1,
        barRadius: 2,
        url: audioUrl(CFG.defaultVersionId),
        plugins: [
            regions,
            TimelinePlugin.create({ container: '#timeline' }),
            MinimapPlugin.create({ container: '#minimap', height: 28,
                waveColor: dark ? '#475569' : '#e2e8f0', progressColor: primary }),
            HoverPlugin.create({ lineColor: accent, lineWidth: 1, labelBackground: '#0f172a', labelColor: '#fff' }),
        ],
    });

    const state = {
        ws, regions, zoom: 0, edl: [], redo: [], compareWs: null,
        ctx: null, analyserMain: null, analyserL: null, analyserR: null,
        fx: null, previewSettings: null,
        buf: null, running: false, frame: 0, holdL: 0, holdR: 0, eqHold: null,
        overlays: { heatmap: true, segments: true, silence: false },
    };
    window.studio = state; // handy for debugging

    /* ---------------------------- lifecycle --------------------------- */

    // Draw segment + marker overlays once the waveform is ready.
    let dataDrawn = false;
    const drawStaticData = () => {
        if (dataDrawn) return;
        dataDrawn = true;
        const duration = ws.getDuration() || CFG.duration || 0;
        $('#total-time').textContent = fmt(duration);
        if (state.overlays.segments) drawSegments();
        drawMarkers();
    };
    ws.on('ready', drawStaticData);
    ws.on('decode', () => {
        drawStaticData();
        // Persist browser-computed peaks when the server had none (no ffmpeg).
        if (CFG.needsPeaks) exportAndSavePeaks(ws);
    });
    // Fallback: if neither event fires within 3s (audio stalled), draw anyway.
    setTimeout(drawStaticData, 3000);

    // Surface audio-load failures instead of silently breaking.
    ws.on('error', (err) => {
        console.error('WaveSurfer error:', err);
        const banner = $('#audio-error');
        if (banner) {
            banner.textContent = 'Audio could not be loaded for playback (the visualizers need audio). '
                + 'The waveform and data are shown from stored analysis. Details in the console.';
            banner.classList.remove('hidden');
        }
        drawStaticData();
    });

    ws.on('timeupdate', (t) => {
        $('#cur-time').textContent = fmt(t);
        updateSpectrogramPlayhead(t);
    });

    // Move the spectrogram playhead to the current time (percentage of duration,
    // matching the spectrogram which spans the container width).
    function updateSpectrogramPlayhead(t) {
        const ph = $('#spectrogram-playhead');
        if (!ph) return;
        const dur = ws.getDuration() || CFG.duration || 0;
        if (!dur) return;
        ph.style.left = Math.max(0, Math.min(100, (t / dur) * 100)) + '%';
    }
    ws.on('play', () => { setPlayIcon(true); ensureAudio().then(startLoop); });
    ws.on('pause', () => { setPlayIcon(false); state.running = false; });
    ws.on('finish', () => { setPlayIcon(false); state.running = false; });

    /* --------------------------- transport ---------------------------- */

    // Build the Web Audio graph lazily on the first play gesture and resume
    // the AudioContext — creating/resuming it inside a user gesture is what
    // makes audio actually play (autoplay policy suspends contexts otherwise).
    async function ensureAudio() {
        if (!state.ctx) initAudioEngine();
        if (state.ctx && state.ctx.state === 'suspended') {
            try { await state.ctx.resume(); } catch (_) {}
        }
    }

    $('#btn-play')?.addEventListener('click', async () => { await ensureAudio(); ws.playPause(); });
    $('#btn-stop')?.addEventListener('click', () => { ws.stop(); setPlayIcon(false); });
    $('#btn-zoom-in')?.addEventListener('click', () => setZoom(state.zoom + 40));
    $('#btn-zoom-out')?.addEventListener('click', () => setZoom(Math.max(0, state.zoom - 40)));
    $('#speed')?.addEventListener('change', (e) => ws.setPlaybackRate(parseFloat(e.target.value), true));

    function setZoom(px) { state.zoom = px; try { ws.zoom(px); } catch (_) {} $('#zoom-val').textContent = px ? `${px}px/s` : 'fit'; }

    /* --------------------------- view toggles ------------------------- */

    let spectrogram = null;
    $('#tab-waveform')?.addEventListener('click', () => switchView('waveform'));
    $('#tab-spectrogram')?.addEventListener('click', () => switchView('spectrogram'));

    function switchView(view) {
        const showSpec = view === 'spectrogram';
        // Use the width-preserving hide (not `hidden`/display:none) so the
        // WaveSurfer wrapper never collapses to 0 width — which would fire a
        // redraw that re-renders the spectrogram at zero size and blanks it.
        $('#waveform-view')?.classList.toggle('viz-hidden', showSpec);
        $('#spectrogram-view')?.classList.toggle('viz-hidden', !showSpec);
        $('#tab-waveform')?.classList.toggle('tab-active', !showSpec);
        $('#tab-spectrogram')?.classList.toggle('tab-active', showSpec);

        if (showSpec && !spectrogram) {
            // Register lazily now that the container is visible (correct width).
            // The plugin auto-renders if the audio is decoded; otherwise it
            // renders on the next redraw (i.e. when decoding finishes).
            spectrogram = ws.registerPlugin(SpectrogramPlugin.create({
                container: '#spectrogram', labels: true, height: 220, scale: 'mel', fftSamples: 512,
            }));
        }
        // Show/track the playhead only while the spectrogram tab is active.
        const ph = $('#spectrogram-playhead');
        if (ph) {
            ph.style.display = showSpec ? 'block' : 'none';
            if (showSpec) updateSpectrogramPlayhead(ws.getCurrentTime());
        }
    }

    // Click the spectrogram to seek (maps click X → time, honouring scroll).
    $('#spectrogram')?.addEventListener('click', (e) => {
        const el = e.currentTarget, dur = ws.getDuration() || CFG.duration || 0;
        if (!dur) return;
        const rect = el.getBoundingClientRect();
        const frac = Math.max(0, Math.min(1, (e.clientX - rect.left + el.scrollLeft) / (el.scrollWidth || el.clientWidth)));
        ws.setTime(frac * dur);
        updateSpectrogramPlayhead(frac * dur);
    });

    /* ---------------- real-time audio engine + visualizers ------------ */

    // One media source fans out to a main analyser (spectrum / EQ / circular)
    // and a per-channel L/R split (goniometer / level meters). Audio is routed
    // to the destination first so sound is guaranteed even if analysis fails.
    function initAudioEngine() {
        try {
            const media = ws.getMediaElement();
            const AC = window.AudioContext || window.webkitAudioContext;
            const ctx = new AC();
            state.ctx = ctx;
            const source = ctx.createMediaElementSource(media);

            // Live effect chain (Layer-1 preview). Each node is neutral by
            // default; the editor updates them via window.studioPreview so
            // gain / EQ / de-hum / compressor / limiter / tempo are heard live.
            const biquad = (type, freq, q) => {
                const n = ctx.createBiquadFilter();
                n.type = type;
                n.frequency.value = freq;           // AudioParams: set .value, never reassign
                if (q != null) n.Q.value = q;
                return n;
            };
            const fx = {
                gain: ctx.createGain(),
                bass: biquad('lowshelf', 120),
                mid: biquad('peaking', 1500, 1),
                treble: biquad('highshelf', 3500),
                notch: biquad('allpass', 60, 6),    // de-hum (allpass = off)
                comp: ctx.createDynamicsCompressor(),
                limit: ctx.createDynamicsCompressor(),
                out: ctx.createGain(),
            };
            fx.comp.ratio.value = 1; fx.comp.threshold.value = 0;   // bypassed
            fx.limit.ratio.value = 1; fx.limit.threshold.value = 0; // bypassed
            state.fx = fx;

            // source → gain → bass → mid → treble → notch → comp → limit → out → destination
            source.connect(fx.gain); fx.gain.connect(fx.bass); fx.bass.connect(fx.mid);
            fx.mid.connect(fx.treble); fx.treble.connect(fx.notch); fx.notch.connect(fx.comp);
            fx.comp.connect(fx.limit); fx.limit.connect(fx.out);
            fx.out.connect(ctx.destination); // audible

            const channels = ws.getDecodedData()?.numberOfChannels || 2;

            // Analysers tap the PROCESSED signal so the visualizers reflect the
            // effects you hear.
            state.analyserMain = ctx.createAnalyser();
            state.analyserMain.fftSize = 2048;
            state.analyserMain.smoothingTimeConstant = 0.82;
            fx.out.connect(state.analyserMain);

            const splitter = ctx.createChannelSplitter(2);
            fx.out.connect(splitter);
            state.analyserL = ctx.createAnalyser();
            state.analyserR = ctx.createAnalyser();
            [state.analyserL, state.analyserR].forEach((a) => { a.fftSize = 2048; a.smoothingTimeConstant = 0.8; });
            splitter.connect(state.analyserL, 0);
            splitter.connect(state.analyserR, channels > 1 ? 1 : 0); // mono → mirror

            const N = state.analyserMain.frequencyBinCount;
            state.buf = {
                freq: new Uint8Array(N),
                timeL: new Float32Array(state.analyserL.fftSize),
                timeR: new Float32Array(state.analyserR.fftSize),
            };

            applyPreview(state.previewSettings); // apply any settings set before playback
        } catch (e) {
            console.warn('Audio engine unavailable:', e);
        }
    }

    // Apply live-previewable effect settings to the Web Audio chain.
    function applyPreview(s) {
        state.previewSettings = s || state.previewSettings;
        const fx = state.fx;
        if (!fx || !state.previewSettings) return;
        const p = state.previewSettings;
        const on = p.enabled !== false;
        const dbToGain = (db) => Math.pow(10, (db || 0) / 20);

        fx.gain.gain.value = on ? dbToGain(p.gain) : 1;
        fx.bass.gain.value = on ? (p.bass || 0) : 0;   // shelf/peaking gains are in dB
        fx.mid.gain.value = on ? (p.mid || 0) : 0;
        fx.treble.gain.value = on ? (p.treble || 0) : 0;

        if (on && p.dehum) { fx.notch.type = 'notch'; fx.notch.frequency.value = p.dehumFreq || 50; fx.notch.Q.value = 6; }
        else { fx.notch.type = 'allpass'; }

        if (on && p.compress) { fx.comp.threshold.value = -18; fx.comp.ratio.value = 3; fx.comp.knee.value = 6; fx.comp.attack.value = 0.02; fx.comp.release.value = 0.25; }
        else { fx.comp.threshold.value = 0; fx.comp.ratio.value = 1; }

        if (on && p.limit) { fx.limit.threshold.value = -1; fx.limit.ratio.value = 20; fx.limit.knee.value = 0; fx.limit.attack.value = 0.001; fx.limit.release.value = 0.05; }
        else { fx.limit.threshold.value = 0; fx.limit.ratio.value = 1; }

        const media = ws.getMediaElement();
        if (media) { media.preservesPitch = true; media.playbackRate = on ? (p.tempo || 1) : 1; }
    }

    // Exposed so the Alpine editor can push live settings.
    window.studioPreview = (settings) => applyPreview(settings);

    function startLoop() {
        if (state.running || !state.analyserMain) return;
        state.running = true;
        renderLoop();
    }

    function renderLoop() {
        if (!state.running) return;
        const { analyserMain, analyserL, analyserR, buf } = state;
        analyserMain.getByteFrequencyData(buf.freq);
        analyserL.getFloatTimeDomainData(buf.timeL);
        analyserR.getFloatTimeDomainData(buf.timeR);

        const l = channelStats(buf.timeL);
        const r = channelStats(buf.timeR);

        drawSpectrum(buf.freq);
        drawEqualizer(buf.freq);
        drawCircular(buf.freq, (l.rms + r.rms) / 2);
        drawGoniometer(buf.timeL, buf.timeR);
        drawLevels(l, r);
        updateLoudness(l, r);

        state.frame++;
        requestAnimationFrame(renderLoop);
    }

    // --- audio math helpers ---
    function channelStats(time) {
        let sum = 0, peak = 0;
        for (let i = 0; i < time.length; i++) { const v = time[i]; sum += v * v; if (Math.abs(v) > peak) peak = Math.abs(v); }
        return { rms: Math.sqrt(sum / time.length), peak };
    }
    const ampDb = (a) => (a > 0 ? 20 * Math.log10(a) : -60);
    const dbFrac = (db) => Math.max(0, Math.min(1, (db + 60) / 60));

    // --- 1. Frequency spectrum (log-spaced bars, primary→accent gradient) ---
    function drawSpectrum(freq) {
        const c = $('#spectrum'); if (!c) return;
        const x = c.getContext('2d'), w = c.width, h = c.height, N = freq.length, bars = 80;
        x.clearRect(0, 0, w, h);
        const grad = x.createLinearGradient(0, h, 0, 0);
        grad.addColorStop(0, primary); grad.addColorStop(1, accent);
        x.fillStyle = grad;
        const bw = w / bars;
        for (let i = 0; i < bars; i++) {
            const idx = Math.floor(Math.pow(i / bars, 2) * N);
            const bh = (freq[idx] / 255) * h;
            x.fillRect(i * bw, h - bh, bw - 1, bh);
        }
    }

    // --- 2. Equalizer bars (24 log bands with falling peak-hold caps) ---
    function drawEqualizer(freq) {
        const c = $('#equalizer'); if (!c) return;
        const x = c.getContext('2d'), w = c.width, h = c.height, N = freq.length, bands = 24;
        if (!state.eqHold) state.eqHold = new Array(bands).fill(0);
        x.clearRect(0, 0, w, h);
        const bw = w / bands;
        for (let i = 0; i < bands; i++) {
            const lo = Math.floor(Math.pow(i / bands, 2) * N);
            const hi = Math.max(lo + 1, Math.floor(Math.pow((i + 1) / bands, 2) * N));
            let s = 0; for (let k = lo; k < hi; k++) s += freq[k];
            const v = (s / (hi - lo)) / 255;
            state.eqHold[i] = Math.max(v, state.eqHold[i] - 0.02);
            const bh = v * h;
            x.fillStyle = v > 0.85 ? '#ef4444' : v > 0.6 ? accent : primary;
            x.fillRect(i * bw + 1, h - bh, bw - 2, bh);
            x.fillStyle = '#e2e8f0';
            x.fillRect(i * bw + 1, h - state.eqHold[i] * h - 1, bw - 2, 2); // peak cap
        }
    }

    // --- 3. Circular visualizer (radial spectrum + level-pulsed core) ---
    function drawCircular(freq, rms) {
        const c = $('#circular'); if (!c) return;
        const x = c.getContext('2d'), w = c.width, h = c.height, cx = w / 2, cy = h / 2;
        const N = freq.length, count = 96, baseR = Math.min(w, h) * 0.2, rot = state.frame * 0.004;
        x.clearRect(0, 0, w, h);
        x.lineWidth = 2;
        for (let i = 0; i < count; i++) {
            const idx = Math.floor(Math.pow(i / count, 1.6) * N * 0.7);
            const v = freq[idx] / 255;
            const ang = (i / count) * Math.PI * 2 + rot;
            const r2 = baseR + v * Math.min(w, h) * 0.28;
            x.strokeStyle = `hsl(${168 + v * 70}, 72%, ${45 + v * 25}%)`;
            x.beginPath();
            x.moveTo(cx + Math.cos(ang) * baseR, cy + Math.sin(ang) * baseR);
            x.lineTo(cx + Math.cos(ang) * r2, cy + Math.sin(ang) * r2);
            x.stroke();
        }
        x.beginPath(); x.arc(cx, cy, baseR * (0.55 + Math.min(1, rms * 1.4)), 0, Math.PI * 2);
        x.fillStyle = primary + '44'; x.fill();
        x.beginPath(); x.arc(cx, cy, baseR * 0.45, 0, Math.PI * 2); x.fillStyle = primary; x.fill();
    }

    // --- 4. Stereo goniometer (Lissajous: mid vertical, side horizontal) ---
    function drawGoniometer(tL, tR) {
        const c = $('#goniometer'); if (!c) return;
        const x = c.getContext('2d'), w = c.width, h = c.height, cx = w / 2, cy = h / 2, s = Math.min(w, h) * 0.62;
        x.fillStyle = 'rgba(2,6,23,0.3)'; x.fillRect(0, 0, w, h); // fade → trails
        x.strokeStyle = 'rgba(148,163,184,0.15)';
        x.beginPath(); x.moveTo(cx, 0); x.lineTo(cx, h); x.moveTo(0, cy); x.lineTo(w, cy); x.stroke();
        x.fillStyle = accent;
        const step = Math.max(1, Math.floor(tL.length / 300));
        for (let i = 0; i < tL.length; i += step) {
            const mid = (tL[i] + tR[i]) * 0.5, side = (tL[i] - tR[i]) * 0.5;
            x.fillRect(cx + side * s, cy - mid * s, 1.6, 1.6);
        }
    }

    // --- 5. Peak / level meters, per channel, with peak-hold + clip ---
    function drawLevels(l, r) {
        const c = $('#levels'); if (!c) return;
        const x = c.getContext('2d'), w = c.width, h = c.height, top = 6, bottom = h - 16;
        x.clearRect(0, 0, w, h);
        state.holdL = Math.max(l.peak, state.holdL - 0.008);
        state.holdR = Math.max(r.peak, state.holdR - 0.008);
        [['L', l, state.holdL], ['R', r, state.holdR]].forEach(([label, st, hold], i) => {
            const colW = w / 2, bw = colW * 0.46, bx = i * colW + (colW - bw) / 2;
            x.fillStyle = 'rgba(148,163,184,0.15)'; x.fillRect(bx, top, bw, bottom - top);
            const fillH = (bottom - top) * dbFrac(ampDb(st.rms));
            const g = x.createLinearGradient(0, bottom, 0, top);
            g.addColorStop(0, '#10b981'); g.addColorStop(0.7, accent); g.addColorStop(1, '#ef4444');
            x.fillStyle = g; x.fillRect(bx, bottom - fillH, bw, fillH);
            const peakY = bottom - (bottom - top) * dbFrac(ampDb(hold));
            x.fillStyle = hold >= 0.99 ? '#ef4444' : '#e2e8f0';
            x.fillRect(bx, peakY - 1, bw, 2);
            x.fillStyle = '#94a3b8'; x.font = '10px sans-serif'; x.textAlign = 'center';
            x.fillText(label, bx + bw / 2, h - 3);
        });
    }

    // --- 6. Loudness meter (DOM, right rail) ---
    function updateLoudness(l, r) {
        const rms = (l.rms + r.rms) / 2, peak = Math.max(l.peak, r.peak);
        const rmsDb = ampDb(rms), peakDb = ampDb(peak);
        const set = (id, v) => { const e = $(id); if (e) e.textContent = v; };
        set('#meter-rms', `${rmsDb.toFixed(1)} dB`);
        set('#meter-peak', `${peakDb.toFixed(1)} dB`);
        const rb = $('#bar-rms'), pb = $('#bar-peak');
        if (rb) rb.style.width = dbFrac(rmsDb) * 100 + '%';
        if (pb) pb.style.width = dbFrac(peakDb) * 100 + '%';
        const clip = $('#clip-warn'); if (clip) clip.classList.toggle('hidden', peak < 0.99);
    }

    /* --------------------------- heatmap ------------------------------ */

    function drawHeatmap(duration) {
        const el = $('#heatmap');
        if (!el) return;
        el.innerHTML = '';
        const data = CFG.heatmap || [];
        if (!data.length) { el.innerHTML = '<span class="text-xs text-slate-400 px-2">No listening data yet</span>'; return; }
        const max = Math.max(1, ...data);
        data.forEach((v, i) => {
            const bar = document.createElement('div');
            const intensity = v / max;
            bar.className = 'flex-1 rounded-sm cursor-pointer';
            bar.style.height = '100%';
            bar.style.background = `rgba(20,184,166,${0.15 + intensity * 0.85})`;
            bar.title = `${fmt((i / data.length) * duration)} · ${v} listens`;
            bar.addEventListener('click', () => ws.setTime((i / data.length) * duration));
            el.appendChild(bar);
        });
    }

    /* --------------------- segments / speakers ------------------------ */

    function drawSegments() {
        (CFG.segments || []).forEach((seg, i) => {
            if (seg.end <= seg.start) return;
            regions.addRegion({
                start: seg.start, end: seg.end, drag: false, resize: false,
                color: i % 2 ? 'rgba(13,148,136,0.08)' : 'rgba(100,116,139,0.08)',
                content: seg.speaker,
                id: 'seg-' + i,
            });
        });
        const list = $('#segment-list');
        if (list) {
            list.innerHTML = '';
            (CFG.segments || []).forEach((seg) => {
                const row = document.createElement('button');
                row.className = 'w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800';
                row.innerHTML = `<span class="font-medium text-primary-700 dark:text-primary-300">${seg.speaker}</span>
                    <span class="text-xs text-slate-400 ml-1">${fmt(seg.start)}</span>
                    <span class="block truncate text-xs text-slate-500 dark:text-slate-400">${seg.text}</span>`;
                row.addEventListener('click', () => { ws.setTime(seg.start); ws.play(); });
                list.appendChild(row);
            });
        }
    }

    /* --------------------- silence / noise ---------------------------- */

    // Return the decoded AudioBuffer — from WaveSurfer if it decoded, else
    // fetch + decode the stream on demand (waveform may be from stored peaks).
    async function getDecoded() {
        const d = ws.getDecodedData();
        if (d) return d;
        try {
            const res = await fetch(audioUrl(CFG.defaultVersionId));
            if (!res.ok) return null;
            const AC = window.AudioContext || window.webkitAudioContext;
            const ctx = state.ctx || new AC();
            return await ctx.decodeAudioData(await res.arrayBuffer());
        } catch (_) { return null; }
    }

    $('#btn-detect-silence')?.addEventListener('click', async () => {
        $('#silence-count').textContent = 'analysing…';
        const decoded = await getDecoded();
        if (!decoded) { $('#silence-count').textContent = 'audio unavailable'; return; }
        const ch = decoded.getChannelData(0);
        const sr = decoded.sampleRate;
        const win = Math.floor(sr * 0.2);           // 200ms windows
        const thresh = 0.02;                         // ~ -34 dBFS
        let start = null, count = 0;
        // clear previous silence regions
        regions.getRegions().filter(r => r.id?.startsWith('sil-')).forEach(r => r.remove());
        for (let i = 0; i < ch.length; i += win) {
            let peak = 0;
            for (let j = i; j < i + win && j < ch.length; j++) peak = Math.max(peak, Math.abs(ch[j]));
            const t = i / sr;
            if (peak < thresh) { if (start === null) start = t; }
            else if (start !== null) {
                if (t - start > 0.4) { regions.addRegion({ start, end: t, color: 'rgba(148,163,184,0.35)', drag: false, resize: false, id: 'sil-' + (count++), content: '🔇' }); }
                start = null;
            }
        }
        $('#silence-count').textContent = `${count} silent gaps`;
    });

    /* ----------------------- markers ---------------------------------- */

    function drawMarkers() {
        (CFG.markers || []).forEach(addMarkerRegion);
        renderMarkerList();
    }
    function addMarkerRegion(m) {
        regions.addRegion({
            start: m.start, end: m.end ?? undefined,
            color: hexToRgba(m.color, m.end ? 0.18 : 1),
            content: `${iconFor(m.type)} ${m.label}`,
            drag: false, resize: false, id: 'mrk-' + m.id,
        });
    }
    function renderMarkerList() {
        const list = $('#marker-list');
        if (!list) return;
        list.innerHTML = '';
        (CFG.markers || []).sort((a, b) => a.start - b.start).forEach((m) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-100 dark:hover:bg-slate-800';
            row.innerHTML = `<span class="size-2.5 rounded-full shrink-0" style="background:${m.color}"></span>
                <button class="flex-1 text-left truncate" data-seek="${m.start}">${m.label}
                  <span class="text-xs text-slate-400">${fmt(m.start)}${m.is_ai ? ' · AI' : ''}</span></button>`;
            row.querySelector('[data-seek]').addEventListener('click', () => { ws.setTime(m.start); });
            if (CFG.canEdit) {
                const del = document.createElement('button');
                del.className = 'text-slate-400 hover:text-rose-500';
                del.innerHTML = '✕';
                del.addEventListener('click', () => deleteMarker(m));
                row.appendChild(del);
            }
            list.appendChild(row);
        });
    }

    $('#btn-add-marker')?.addEventListener('click', async () => {
        const type = $('#marker-type').value;
        const label = $('#marker-label').value.trim() || type;
        const sel = activeSelection();
        const payload = { marker_type: type, label, start_seconds: sel ? sel.start : ws.getCurrentTime(), end_seconds: sel ? sel.end : null };
        const res = await api('POST', CFG.urls.markerStore, payload);
        if (res?.data) { CFG.markers.push(res.data); addMarkerRegion(res.data); renderMarkerList(); $('#marker-label').value = ''; }
    });

    async function deleteMarker(m) {
        await api('DELETE', CFG.urls.markerDelete.replace('__ID__', m.id));
        CFG.markers = CFG.markers.filter(x => x.id !== m.id);
        regions.getRegions().find(r => r.id === 'mrk-' + m.id)?.remove();
        renderMarkerList();
    }

    /* ------------------------- editing (EDL) -------------------------- */

    let dragDisable = null;
    $('#btn-edit-mode')?.addEventListener('click', (e) => {
        const on = e.currentTarget.getAttribute('aria-pressed') !== 'true';
        e.currentTarget.setAttribute('aria-pressed', on);
        e.currentTarget.classList.toggle('btn-primary', on);
        e.currentTarget.classList.toggle('btn-secondary', !on);
        $('#edit-toolbar').classList.toggle('hidden', !on);
        if (on) dragDisable = regions.enableDragSelection({ color: 'rgba(217,119,6,0.20)' });
        else if (dragDisable) { dragDisable(); dragDisable = null; }
    });

    document.querySelectorAll('[data-op]').forEach((btn) => {
        btn.addEventListener('click', () => applyOp(btn.dataset.op));
    });
    $('#btn-undo')?.addEventListener('click', undo);
    $('#btn-redo')?.addEventListener('click', redo);
    $('#btn-save-edit')?.addEventListener('click', saveEdit);

    function applyOp(op) {
        const sel = activeSelection();
        const isPoint = op === 'split';
        if (!isPoint && !sel) { toast('Drag on the waveform to select a region first.'); return; }
        const entry = {
            op,
            start: isPoint ? ws.getCurrentTime() : sel.start,
            end: isPoint ? null : sel.end,
            params: op === 'gain' ? { db: 3 } : {},
        };
        state.edl.push(entry);
        state.redo = [];
        const colors = { trim: 'rgba(16,185,129,0.18)', cut: 'rgba(239,68,68,0.20)', split: 'rgba(99,102,241,0.9)', fade_in: 'rgba(14,165,233,0.18)', fade_out: 'rgba(14,165,233,0.18)', gain: 'rgba(236,72,153,0.18)' };
        entry._region = regions.addRegion({
            start: entry.start, end: entry.end ?? undefined,
            color: colors[op], content: op.replace('_', ' '), drag: false, resize: false,
            id: 'edl-' + (state.edl.length - 1),
        });
        if (sel?._region) sel._region.remove();
        renderEdl();
    }
    function undo() { const e = state.edl.pop(); if (e) { e._region?.remove(); state.redo.push(e); renderEdl(); } }
    function redo() { const e = state.redo.pop(); if (e) { state.edl.push(e); e._region = regions.addRegion({ start: e.start, end: e.end ?? undefined, content: e.op.replace('_', ' '), drag: false, resize: false }); renderEdl(); } }

    function renderEdl() {
        const box = $('#edl-list');
        if (box) box.innerHTML = state.edl.map((e, i) =>
            `<li class="flex justify-between"><span>${i + 1}. ${e.op.replace('_', ' ')}</span><span class="text-slate-400">${fmt(e.start)}${e.end ? '–' + fmt(e.end) : ''}</span></li>`).join('') || '<li class="text-slate-400">No operations yet.</li>';
        const c = $('#edl-count');
        if (c) c.textContent = state.edl.length;
    }

    async function saveEdit() {
        if (!state.edl.length) { toast('Add at least one edit operation.'); return; }
        const payload = { title: $('#edit-title')?.value || null, edl: state.edl.map(({ op, start, end, params }) => ({ op, start, end, params })) };
        const res = await api('POST', CFG.urls.edit, payload);
        if (res) { toast('Saved as a new version — master preserved. Version #' + res.output_version_id); location.reload(); }
    }

    /* -------------------------- compare ------------------------------- */

    $('#compare-version')?.addEventListener('change', (e) => {
        const vid = e.target.value;
        $('#compare-wrap').classList.toggle('hidden', !vid);
        if (!vid) { state.compareWs?.destroy(); state.compareWs = null; return; }
        state.compareWs?.destroy();
        state.compareWs = WaveSurfer.create({
            container: '#compare-waveform', height: 90, waveColor: dark ? '#334155' : '#cbd5e1',
            progressColor: accent, barWidth: 2, barGap: 1, url: audioUrl(vid),
            plugins: [TimelinePlugin.create({ container: '#compare-timeline' })],
        });
    });

    /* -------------------------- helpers ------------------------------- */

    function activeSelection() {
        const r = regions.getRegions().filter(r => !r.id || (!r.id.startsWith('seg-') && !r.id.startsWith('mrk-') && !r.id.startsWith('sil-') && !r.id.startsWith('edl-')));
        const last = r[r.length - 1];
        return last ? { start: last.start, end: last.end, _region: last } : null;
    }
    function setPlayIcon(playing) {
        $('#icon-play')?.classList.toggle('hidden', playing);
        $('#icon-pause')?.classList.toggle('hidden', !playing);
    }
    function iconFor(t) { return ({ chapter: '📑', keyword: '🔖', speaker: '🎙️', applause: '👏', laughter: '😄', music: '🎵', speech: '🗣️', silence: '🔇', ad: '📢', intro: '⏮️', outro: '⏭️', emotion: '❤️', note: '📝', segment: '▮' }[t] || '•'); }
    function hexToRgba(hex, a) {
        if (!hex || hex[0] !== '#') return `rgba(100,116,139,${a})`;
        const n = parseInt(hex.slice(1), 16);
        return `rgba(${(n >> 16) & 255},${(n >> 8) & 255},${n & 255},${a})`;
    }
    function toast(msg) {
        const t = $('#studio-toast'); if (!t) { alert(msg); return; }
        t.textContent = msg; t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 3500);
    }
    async function api(method, url, body) {
        try {
            const res = await fetch(url, {
                method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, 'Accept': 'application/json' },
                body: body ? JSON.stringify(body) : undefined,
            });
            if (!res.ok) { toast('Request failed (' + res.status + ')'); return null; }
            return res.status === 204 ? true : await res.json();
        } catch (e) { toast('Network error'); return null; }
    }
    async function exportAndSavePeaks(ws) {
        try {
            const peaks = ws.exportPeaks({ maxLength: 200 })[0];
            const norm = Array.from(peaks).map(p => Math.min(1, Math.abs(p)));
            await api('POST', CFG.urls.peaks, { peaks: norm });
        } catch (_) {}
    }

    /* -------------------- render pipeline (ffmpeg) -------------------- */

    // Submit an edit/enhancement op-chain to be rendered into a new version,
    // then poll status. Exposed globally so the Alpine editor panel can call it.
    window.studioRender = async (ops, title, cb = {}, target = 'new') => {
        // Render FROM the version currently loaded in the Studio.
        const res = await api('POST', CFG.urls.render, { title, ops, source_version_id: CFG.defaultVersionId, target });
        if (!res || !res.edit_session_id) { cb.onError?.('Could not queue the render.'); return; }
        if (res.ffmpeg_available === false) {
            cb.onProgress?.(5, 'queued — note: ffmpeg must be available on the server (Docker) to render');
        }
        const statusUrl = CFG.urls.renderStatus.replace('__ID__', res.edit_session_id);
        const poll = async () => {
            let s = null;
            try { s = await fetch(statusUrl, { headers: { Accept: 'application/json' } }).then((r) => r.json()); } catch (_) {}
            if (!s) { cb.onError?.('Status check failed.'); return; }
            cb.onProgress?.(s.progress ?? 0, s.render_status);
            if (s.render_status === 'done') { cb.onDone?.(s.version); return; }
            if (s.render_status === 'failed') { cb.onError?.(s.error || 'Render failed.'); return; }
            setTimeout(poll, 1500);
        };
        setTimeout(poll, 1200);
    };

    // Auto-preview render (heavy sound effects, full file) → returns {url}|{error}.
    window.studioPreviewRender = async (ops, start) => {
        try {
            const res = await fetch(CFG.urls.preview, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, Accept: 'application/json' },
                body: JSON.stringify({ ops, start, source_version_id: CFG.defaultVersionId }),
            });
            return await res.json();
        } catch (e) { return { error: 'Preview request failed.' }; }
    };

    // Swap the MAIN waveform's audio (heavy-effects preview or back to original),
    // preserving position + playing state and re-drawing the overlays. The media
    // element is reused, so the live Web Audio (cheap) effect chain persists.
    window.studioOriginalUrl = () => audioUrl(CFG.defaultVersionId);
    window.studioSetBusy = (on) => { $('#waveform-busy')?.classList.toggle('hidden', !on); };
    window.studioLoadAudio = async (url) => {
        if (!url) return;
        window.studioSetBusy(true);
        const wasPlaying = ws.isPlaying();
        const pos = ws.getCurrentTime();
        try { await ws.load(url); } catch (e) { console.warn('Studio load failed:', e); }
        try { regions.getRegions().forEach((r) => r.remove()); } catch (_) {}
        dataDrawn = false;
        drawStaticData();                 // re-draw segment + marker overlays
        try { if (pos) ws.setTime(Math.min(pos, ws.getDuration() || pos)); } catch (_) {}
        if (wasPlaying) { try { await ws.play(); } catch (_) {} }
        window.studioSetBusy(false);
    };

    // Let the editor panel read the current waveform selection / cursor.
    window.studioSelection = () => {
        const sel = activeSelection();
        return sel ? { start: +sel.start.toFixed(2), end: +sel.end.toFixed(2) } : null;
    };
    window.studioCursor = () => +ws.getCurrentTime().toFixed(2);
    window.studioEnableSelect = (on) => {
        if (on && !dragDisable) dragDisable = regions.enableDragSelection({ color: 'rgba(217,119,6,0.20)' });
        else if (!on && dragDisable) { dragDisable(); dragDisable = null; }
    };

    // Enable drag-to-select on the waveform (feeds the editor's trim/cut).
    window.studioEnableSelect(true);

    // Initialise EDL/marker lists on load
    renderEdl();
}

// Start once all module-level helpers above are initialized (avoids a
// temporal-dead-zone error from calling boot() before the const helpers exist).
if (CFG) boot();
