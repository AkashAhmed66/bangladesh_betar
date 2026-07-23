/**
 * Broadcast Studio — the broadcaster's "go on air" engine (M27).
 *
 * Captures the microphone and publishes it into the channel's LiveKit room so
 * public listeners can subscribe live. Config is injected by the Blade page as
 * window.__BROADCAST__ (mirrors the studio.js pattern).
 */
import { Room, RoomEvent, createLocalAudioTrack } from 'livekit-client';

const CFG = window.__BROADCAST__;

const $ = (id) => document.getElementById(id);

function boot() {
    const els = {
        goLive: $('go-live-btn'),
        stop: $('stop-btn'),
        statusPill: $('status-pill'),
        statusText: $('status-text'),
        level: $('mic-level'),
        elapsed: $('elapsed'),
        listeners: $('listeners'),
        peak: $('peak-listeners'),
        error: $('broadcast-error'),
        hint: $('mic-hint'),
    };

    const state = {
        room: null,
        track: null,
        live: false,
        startedAt: null,
        elapsedTimer: null,
        statusTimer: null,
        audioCtx: null,
        levelRAF: null,
    };

    if (!CFG.active) {
        els.goLive.disabled = true;
    }

    /* ------------------------------- helpers ------------------------------ */

    const showError = (msg) => {
        els.error.textContent = msg;
        els.error.classList.remove('hidden');
    };
    const clearError = () => els.error.classList.add('hidden');

    const setStatus = (mode) => {
        // mode: 'off' | 'connecting' | 'live'
        const dot = els.statusPill.querySelector('span.rounded-full') || els.statusPill.querySelector('span');
        els.statusPill.classList.remove(
            'bg-slate-100', 'text-slate-600', 'dark:bg-slate-800', 'dark:text-slate-300',
            'bg-rose-100', 'text-rose-700', 'dark:bg-rose-500/15', 'dark:text-rose-300',
            'bg-amber-100', 'text-amber-700',
        );
        if (mode === 'live') {
            els.statusPill.classList.add('bg-rose-100', 'text-rose-700', 'dark:bg-rose-500/15', 'dark:text-rose-300');
            els.statusText.textContent = 'ON AIR';
            if (dot) dot.className = 'size-2.5 rounded-full bg-rose-600 animate-pulse';
        } else if (mode === 'connecting') {
            els.statusPill.classList.add('bg-amber-100', 'text-amber-700');
            els.statusText.textContent = 'Connecting…';
            if (dot) dot.className = 'size-2.5 rounded-full bg-amber-500 animate-pulse';
        } else {
            els.statusPill.classList.add('bg-slate-100', 'text-slate-600', 'dark:bg-slate-800', 'dark:text-slate-300');
            els.statusText.textContent = 'Off air';
            if (dot) dot.className = 'size-2.5 rounded-full bg-slate-400';
        }
    };

    const api = async (url, body) => {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, Accept: 'application/json' },
            body: body ? JSON.stringify(body) : '{}',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || `Request failed (${res.status})`);
        return data;
    };

    const fmtElapsed = (ms) => {
        const s = Math.floor(ms / 1000);
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        const pad = (n) => String(n).padStart(2, '0');
        return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`;
    };

    /* ------------------------------ mic meter ----------------------------- */

    function startLevelMeter(track) {
        try {
            const AC = window.AudioContext || window.webkitAudioContext;
            state.audioCtx = new AC();
            const src = state.audioCtx.createMediaStreamSource(new MediaStream([track.mediaStreamTrack]));
            const analyser = state.audioCtx.createAnalyser();
            analyser.fftSize = 512;
            src.connect(analyser);
            const buf = new Uint8Array(analyser.fftSize);

            const tick = () => {
                analyser.getByteTimeDomainData(buf);
                let peak = 0;
                for (let i = 0; i < buf.length; i++) {
                    const v = Math.abs(buf[i] - 128) / 128;
                    if (v > peak) peak = v;
                }
                const pct = Math.min(100, Math.round(peak * 140));
                els.level.style.width = pct + '%';
                els.level.classList.toggle('bg-rose-500', pct > 90);
                els.level.classList.toggle('bg-emerald-500', pct <= 90);
                state.levelRAF = requestAnimationFrame(tick);
            };
            tick();
        } catch (e) {
            console.warn('Mic meter unavailable:', e);
        }
    }

    function stopLevelMeter() {
        if (state.levelRAF) cancelAnimationFrame(state.levelRAF);
        state.levelRAF = null;
        if (state.audioCtx) { state.audioCtx.close().catch(() => {}); state.audioCtx = null; }
        els.level.style.width = '0%';
    }

    /* ------------------------------ lifecycle ----------------------------- */

    async function goLive() {
        clearError();
        els.goLive.disabled = true;
        setStatus('connecting');

        let creds;
        try {
            creds = await api(CFG.urls.goLive);
        } catch (e) {
            showError(e.message);
            setStatus('off');
            els.goLive.disabled = !CFG.active ? true : false;
            return;
        }

        try {
            state.track = await createLocalAudioTrack({
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            });

            state.room = new Room();
            state.room.on(RoomEvent.Disconnected, () => { if (state.live) endLocally(); });

            await state.room.connect(creds.ws_url, creds.token);
            await state.room.localParticipant.publishTrack(state.track);
        } catch (e) {
            console.error('LiveKit publish failed:', e);
            showError('Could not start the broadcast: ' + (e.message || e) + '. Check microphone permission and that the LiveKit server is reachable.');
            // Roll back the server-side session we just opened.
            await api(CFG.urls.stop).catch(() => {});
            await teardown();
            setStatus('off');
            els.goLive.disabled = false;
            return;
        }

        state.live = true;
        state.startedAt = Date.now();
        setStatus('live');
        els.goLive.classList.add('hidden');
        els.stop.classList.remove('hidden');
        els.hint.textContent = 'You are on air. Speak into your microphone — listeners hear you live.';

        startLevelMeter(state.track);
        state.elapsedTimer = setInterval(() => {
            els.elapsed.textContent = fmtElapsed(Date.now() - state.startedAt);
        }, 500);
        pollStatus();
        state.statusTimer = setInterval(pollStatus, 5000);
    }

    async function stop() {
        els.stop.disabled = true;
        await teardown();
        await api(CFG.urls.stop).catch(() => {});
        endLocally();
        els.stop.disabled = false;
    }

    // Tear down the LiveKit connection + local track (client side only).
    async function teardown() {
        stopLevelMeter();
        if (state.track) { try { state.track.stop(); } catch (_) {} state.track = null; }
        if (state.room) { try { await state.room.disconnect(); } catch (_) {} state.room = null; }
    }

    // Reset the UI to off-air (used after Stop or a dropped connection).
    function endLocally() {
        state.live = false;
        if (state.elapsedTimer) clearInterval(state.elapsedTimer);
        if (state.statusTimer) clearInterval(state.statusTimer);
        state.elapsedTimer = state.statusTimer = null;
        stopLevelMeter();
        setStatus('off');
        els.stop.classList.add('hidden');
        els.goLive.classList.remove('hidden');
        els.goLive.disabled = !CFG.active;
        els.hint.textContent = 'Click Go Live and allow microphone access when your browser prompts you.';
    }

    async function pollStatus() {
        try {
            const res = await fetch(CFG.urls.status, { headers: { Accept: 'application/json' } });
            const s = await res.json();
            els.listeners.textContent = s.listeners ?? 0;
            els.peak.textContent = s.peak_listeners ?? 0;
        } catch (_) { /* transient */ }
    }

    /* ------------------------------- wire up ------------------------------ */

    // Browsers only expose the microphone on a "secure context" — HTTPS or
    // localhost. Opened over http://<ip> the mic API is missing, so surface a
    // clear explanation instead of a cryptic getUserMedia error.
    const canCapture = window.isSecureContext && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    if (CFG.active && !canCapture) {
        els.goLive.disabled = true;
        showError(
            'Your browser blocks microphone access on this address because it is not secure (http://<ip>). '
            + 'To broadcast, open the studio on the server itself at http://localhost:8080/admin, or serve the admin portal over HTTPS. '
            + 'Listeners can still tune in from other machines over the network.',
        );
        els.hint.textContent = 'Broadcasting needs a secure origin (localhost or HTTPS). Listening works from anywhere.';
    }

    els.goLive.addEventListener('click', goLive);
    els.stop.addEventListener('click', stop);

    // Best-effort clean shutdown if the broadcaster closes/navigates away.
    window.addEventListener('pagehide', () => {
        if (!state.live) return;
        try {
            const fd = new FormData();
            fd.append('_token', CFG.csrf);
            navigator.sendBeacon(CFG.urls.stop, fd);
        } catch (_) {}
        if (state.room) { try { state.room.disconnect(); } catch (_) {} }
    });
}

if (CFG) boot();
