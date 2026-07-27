/**
 * Broadcast Studio — the broadcaster's on-air console (M27).
 *
 * Captures the microphone through a Web Audio processing graph (device select +
 * input gain + monitoring + live metering), then publishes it into the
 * channel's LiveKit room so public listeners can subscribe live. Config is
 * injected by the Blade page as window.__BROADCAST__.
 *
 * Signal path:  getUserMedia → MediaStreamSource → gain → [analyser]
 *                                                       ├→ MediaStreamDestination → LiveKit publish
 *                                                       └→ monitorGain → speakers (optional)
 */
import { Room, RoomEvent, LocalAudioTrack, ConnectionQuality } from 'livekit-client';

const CFG = window.__BROADCAST__;
const $ = (id) => document.getElementById(id);

function boot() {
    const els = {
        error: $('broadcast-error'),
        statusPill: $('status-pill'),
        statusText: $('status-text'),
        elapsed: $('elapsed'),
        onairClock: $('onair-clock'),
        connDot: $('conn-dot'),
        connText: $('conn-quality-text'),
        connWrap: $('conn-quality'),
        lampRing: $('lamp-ring'),
        goLive: $('go-live-btn'),
        stop: $('stop-btn'),
        mute: $('mute-btn'),
        muteLabel: $('mute-label'),
        monitor: $('monitor-btn'),
        monitorLabel: $('monitor-label'),
        topic: $('topic-input'),
        hint: $('mic-hint'),
        // meters
        meter: $('meter-canvas'),
        spectrum: $('spectrum-canvas'),
        peakDb: $('peak-db'),
        clipLed: $('clip-led'),
        // audience
        listeners: $('listeners'),
        peakListeners: $('peak-listeners'),
        spark: $('spark-canvas'),
        startedAt: $('started-at'),
        // interactive audience (grant/revoke speaking)
        listenersCount: $('listeners-count'),
        listenersEmpty: $('listeners-empty'),
        listenersList: $('listeners-list'),
        // input
        micSelect: $('mic-select'),
        gain: $('gain-range'),
        gainVal: $('gain-val'),
        procEcho: $('proc-echo'),
        procNoise: $('proc-noise'),
        procAgc: $('proc-agc'),
        arm: $('arm-btn'),
        // link
        copyLink: $('copy-link-btn'),
        listenUrl: $('listen-url'),
    };

    const state = {
        room: null,
        lkTrack: null,
        live: false,
        muted: false,
        monitoring: false,
        armed: false,
        // web-audio graph
        stream: null,
        ctx: null,
        source: null,
        gainNode: null,
        analyser: null,
        monitorGain: null,
        dest: null,
        // metering
        rafMeter: null,
        peakHoldDb: -100,
        peakHoldAt: 0,
        lastPeakDb: -100,
        // timers / history
        startedAt: null,
        elapsedTimer: null,
        statusTimer: null,
        participantsTimer: null,
        clockTimer: null,
        listenerHistory: [],
        quality: 'unknown',
    };

    const canCapture = window.isSecureContext && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    /* ------------------------------- helpers ------------------------------ */

    const showError = (msg) => { els.error.textContent = msg; els.error.classList.remove('hidden'); };
    const clearError = () => els.error.classList.add('hidden');

    const gainDb = () => parseFloat(els.gain?.value ?? '0') || 0;
    const linearFromDb = (db) => Math.pow(10, db / 20);

    const procConstraints = () => ({
        echoCancellation: !!els.procEcho?.checked,
        noiseSuppression: !!els.procNoise?.checked,
        autoGainControl: !!els.procAgc?.checked,
    });

    const setStatus = (mode) => {
        const dot = els.statusPill.querySelector('span.rounded-full') || els.statusPill.querySelector('span');
        els.statusPill.classList.remove(
            'bg-slate-100', 'text-slate-600', 'dark:bg-slate-800', 'dark:text-slate-300',
            'bg-rose-100', 'text-rose-700', 'dark:bg-rose-500/15', 'dark:text-rose-300',
            'bg-amber-100', 'text-amber-700',
        );
        if (mode === 'live') {
            els.statusPill.classList.add('bg-rose-100', 'text-rose-700', 'dark:bg-rose-500/15', 'dark:text-rose-300');
            els.statusText.textContent = state.muted ? 'ON AIR · MUTED' : 'ON AIR';
            if (dot) dot.className = 'size-2.5 rounded-full bg-rose-600 animate-pulse';
            els.lampRing?.classList.remove('border-slate-200', 'dark:border-slate-800', 'border-amber-400');
            els.lampRing?.classList.add('border-rose-500', 'shadow-[0_0_28px_2px]', 'shadow-rose-500/40');
        } else if (mode === 'connecting') {
            els.statusPill.classList.add('bg-amber-100', 'text-amber-700');
            els.statusText.textContent = 'Connecting…';
            if (dot) dot.className = 'size-2.5 rounded-full bg-amber-500 animate-pulse';
            els.lampRing?.classList.remove('border-slate-200', 'dark:border-slate-800', 'border-rose-500', 'shadow-rose-500/40');
            els.lampRing?.classList.add('border-amber-400');
        } else {
            els.statusPill.classList.add('bg-slate-100', 'text-slate-600', 'dark:bg-slate-800', 'dark:text-slate-300');
            els.statusText.textContent = state.armed ? 'Armed · Off air' : 'Off air';
            if (dot) dot.className = 'size-2.5 rounded-full ' + (state.armed ? 'bg-emerald-500' : 'bg-slate-400');
            els.lampRing?.classList.remove('border-rose-500', 'shadow-[0_0_28px_2px]', 'shadow-rose-500/40', 'border-amber-400');
            els.lampRing?.classList.add('border-slate-200', 'dark:border-slate-800');
        }
    };

    const setQuality = (q) => {
        state.quality = String(q || 'unknown');
        const map = {
            excellent: ['Excellent', 'bg-emerald-500', 'text-emerald-600 dark:text-emerald-400'],
            good: ['Good', 'bg-emerald-500', 'text-emerald-600 dark:text-emerald-400'],
            poor: ['Poor', 'bg-amber-500', 'text-amber-600 dark:text-amber-400'],
            lost: ['Reconnecting…', 'bg-rose-500 animate-pulse', 'text-rose-600 dark:text-rose-400'],
            unknown: ['—', 'bg-slate-300 dark:bg-slate-600', 'text-slate-500 dark:text-slate-400'],
        };
        const [label, dotCls] = map[state.quality] || map.unknown;
        els.connText.textContent = state.live ? label : (state.armed ? 'Mic ready' : 'Not connected');
        els.connDot.className = 'size-2 rounded-full ' + (state.live ? dotCls : (state.armed ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'));
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
        const s = Math.max(0, Math.floor(ms / 1000));
        const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
        const pad = (n) => String(n).padStart(2, '0');
        return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`;
    };

    /* --------------------------- capture graph ---------------------------- */

    // Build (or rebuild) the mic → gain → destination graph. Used both for the
    // pre-flight "Test microphone" arm and for the live publish source.
    async function buildCaptureChain() {
        await teardownGraph();

        const deviceId = els.micSelect?.value || '';
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: {
                deviceId: deviceId ? { exact: deviceId } : undefined,
                ...procConstraints(),
                channelCount: 1,
            },
            video: false,
        });

        const AC = window.AudioContext || window.webkitAudioContext;
        const ctx = new AC();
        if (ctx.state === 'suspended') { try { await ctx.resume(); } catch (_) {} }

        const source = ctx.createMediaStreamSource(stream);
        const gainNode = ctx.createGain();
        gainNode.gain.value = linearFromDb(gainDb());
        const analyser = ctx.createAnalyser();
        analyser.fftSize = 2048;
        analyser.smoothingTimeConstant = 0.8;
        const monitorGain = ctx.createGain();
        monitorGain.gain.value = state.monitoring ? 1 : 0;
        const dest = ctx.createMediaStreamDestination();

        source.connect(gainNode);
        gainNode.connect(analyser);
        gainNode.connect(dest);            // → published to LiveKit
        gainNode.connect(monitorGain);
        monitorGain.connect(ctx.destination); // → local speakers (monitor)

        Object.assign(state, { stream, ctx, source, gainNode, analyser, monitorGain, dest });
        startMeters();
        await populateDevices();
    }

    async function teardownGraph() {
        stopMeters();
        try { state.stream?.getTracks().forEach((t) => t.stop()); } catch (_) {}
        try { await state.ctx?.close(); } catch (_) {}
        state.stream = state.ctx = state.source = state.gainNode = state.analyser = state.monitorGain = state.dest = null;
    }

    async function populateDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const mics = devices.filter((d) => d.kind === 'audioinput');
            if (!mics.length) return;
            const current = els.micSelect.value;
            els.micSelect.innerHTML = '<option value="">Default device</option>';
            mics.forEach((d, i) => {
                const opt = document.createElement('option');
                opt.value = d.deviceId;
                opt.textContent = d.label || `Microphone ${i + 1}`;
                els.micSelect.appendChild(opt);
            });
            if (current) els.micSelect.value = current;
        } catch (_) { /* enumeration best-effort */ }
    }

    /* ------------------------------- meters ------------------------------- */

    function setupCanvas(canvas) {
        if (!canvas) return null;
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const w = Math.max(1, Math.round(rect.width));
        const h = Math.max(1, canvas.height);
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        const ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return { ctx, w, h };
    }

    function startMeters() {
        stopMeters();
        if (!state.analyser) return;
        const analyser = state.analyser;
        const time = new Uint8Array(analyser.fftSize);
        const freq = new Uint8Array(analyser.frequencyBinCount);

        const meterC = setupCanvas(els.meter);
        const specC = setupCanvas(els.spectrum);

        const draw = () => {
            if (!state.analyser) return;
            analyser.getByteTimeDomainData(time);
            // peak + rms over the frame
            let peak = 0, sumSq = 0;
            for (let i = 0; i < time.length; i++) {
                const v = (time[i] - 128) / 128;
                const a = Math.abs(v);
                if (a > peak) peak = a;
                sumSq += v * v;
            }
            const rms = Math.sqrt(sumSq / time.length);
            const peakDb = peak > 0 ? 20 * Math.log10(peak) : -100;
            const rmsDb = rms > 0 ? 20 * Math.log10(rms) : -100;
            state.lastPeakDb = peakDb;

            // peak hold with 1.2s freeze then decay
            const now = performance.now();
            if (peakDb >= state.peakHoldDb) { state.peakHoldDb = peakDb; state.peakHoldAt = now; }
            else if (now - state.peakHoldAt > 1200) { state.peakHoldDb -= 0.4; }

            // readout + clip LED
            if (els.peakDb) els.peakDb.textContent = peakDb <= -99 ? '−∞ dB' : `${peakDb.toFixed(1)} dB`;
            const clipping = peakDb >= -0.5;
            if (els.clipLed) {
                els.clipLed.classList.toggle('bg-rose-500', clipping);
                els.clipLed.classList.toggle('text-white', clipping);
                els.clipLed.classList.toggle('bg-slate-200', !clipping);
                els.clipLed.classList.toggle('text-slate-400', !clipping);
                els.clipLed.classList.toggle('dark:bg-slate-800', !clipping);
                els.clipLed.classList.toggle('dark:text-slate-500', !clipping);
            }

            drawMeter(meterC, rmsDb, peakDb, state.peakHoldDb);
            drawSpectrum(specC, analyser, freq);
            state.rafMeter = requestAnimationFrame(draw);
        };
        draw();
    }

    function stopMeters() {
        if (state.rafMeter) cancelAnimationFrame(state.rafMeter);
        state.rafMeter = null;
        // reset the bar
        const meterC = setupCanvas(els.meter);
        if (meterC) { meterC.ctx.clearRect(0, 0, meterC.w, meterC.h); }
        if (els.peakDb) els.peakDb.textContent = '−∞ dB';
    }

    // Map a dB value (-60..0) to 0..1 across the meter width.
    const dbToPos = (db) => Math.max(0, Math.min(1, (db + 60) / 60));

    function drawMeter(c, rmsDb, peakDb, holdDb) {
        if (!c) return;
        const { ctx, w, h } = c;
        ctx.clearRect(0, 0, w, h);
        // background segments (subtle scale grid at -18/-6)
        const fill = dbToPos(rmsDb) * w;
        const grad = ctx.createLinearGradient(0, 0, w, 0);
        grad.addColorStop(0, '#10b981');
        grad.addColorStop(dbToPos(-12), '#22c55e');
        grad.addColorStop(dbToPos(-6), '#f59e0b');
        grad.addColorStop(dbToPos(-1.5), '#ef4444');
        grad.addColorStop(1, '#ef4444');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, fill, h);
        // peak-hold marker
        const px = dbToPos(peakDb) * w;
        ctx.fillStyle = 'rgba(255,255,255,0.9)';
        ctx.fillRect(Math.min(w - 2, px - 1), 0, 2, h);
        // held-peak thin marker
        const hx = dbToPos(holdDb) * w;
        ctx.fillStyle = peakDb >= -0.5 ? '#ef4444' : 'rgba(148,163,184,0.9)';
        ctx.fillRect(Math.min(w - 2, hx - 1), 0, 2, h);
        // tick lines
        ctx.fillStyle = 'rgba(100,116,139,0.25)';
        [-48, -36, -24, -18, -12, -6].forEach((d) => { ctx.fillRect(dbToPos(d) * w, 0, 1, h); });
    }

    function drawSpectrum(c, analyser, freq) {
        if (!c) return;
        const { ctx, w, h } = c;
        analyser.getByteFrequencyData(freq);
        ctx.clearRect(0, 0, w, h);
        const bars = 64;
        const bin = Math.floor(freq.length / bars);
        const gap = 2;
        const bw = (w - (bars - 1) * gap) / bars;
        for (let i = 0; i < bars; i++) {
            let max = 0;
            for (let j = 0; j < bin; j++) { const v = freq[i * bin + j]; if (v > max) max = v; }
            const mag = max / 255;
            const bh = Math.max(1, mag * (h - 2));
            const x = i * (bw + gap);
            const hue = 160 - mag * 160; // green→red
            ctx.fillStyle = `hsl(${hue}, 85%, ${45 + mag * 15}%)`;
            ctx.fillRect(x, h - bh, bw, bh);
        }
    }

    /* ---------------------------- listener spark -------------------------- */

    function drawSpark() {
        const c = setupCanvas(els.spark);
        if (!c) return;
        const { ctx, w, h } = c;
        ctx.clearRect(0, 0, w, h);
        const data = state.listenerHistory;
        if (data.length < 2) return;
        const max = Math.max(4, ...data);
        const stepX = w / (Math.max(1, data.length - 1));
        const y = (v) => h - 2 - (v / max) * (h - 4);
        // area
        ctx.beginPath();
        ctx.moveTo(0, h);
        data.forEach((v, i) => ctx.lineTo(i * stepX, y(v)));
        ctx.lineTo((data.length - 1) * stepX, h);
        ctx.closePath();
        ctx.fillStyle = 'rgba(20,184,166,0.15)';
        ctx.fill();
        // line
        ctx.beginPath();
        data.forEach((v, i) => (i ? ctx.lineTo(i * stepX, y(v)) : ctx.moveTo(0, y(v))));
        ctx.strokeStyle = '#14b8a6';
        ctx.lineWidth = 1.5;
        ctx.stroke();
    }

    /* ------------------------------ lifecycle ----------------------------- */

    async function arm() {
        clearError();
        if (!canCapture) return false;
        els.arm.disabled = true;
        try {
            await buildCaptureChain();
            state.armed = true;
            setStatus('off');
            setQuality('unknown');
            els.arm.innerHTML = '<span class="inline-flex items-center gap-1.5">Mic ready · re-test</span>';
            els.hint.textContent = 'Microphone armed — check your levels, then Go Live to broadcast.';
        } catch (e) {
            console.error('Mic arm failed:', e);
            showError('Could not access the microphone: ' + (e.message || e) + '. Check the browser permission and that a mic is connected.');
        } finally {
            els.arm.disabled = false;
        }
        return state.armed;
    }

    async function goLive() {
        clearError();
        els.goLive.disabled = true;
        setStatus('connecting');

        // ensure the capture graph is live
        if (!state.armed || !state.dest) {
            const ok = await arm();
            if (!ok) { setStatus('off'); els.goLive.disabled = !CFG.active; return; }
        }
        if (state.ctx?.state === 'suspended') { try { await state.ctx.resume(); } catch (_) {} }

        let creds;
        try {
            creds = await api(CFG.urls.goLive, { title: (els.topic?.value || '').trim() || null });
        } catch (e) {
            showError(e.message);
            setStatus('off'); els.goLive.disabled = !CFG.active;
            return;
        }

        try {
            const wsUrl = CFG.wsUrl || creds.ws_url;
            const mediaTrack = state.dest.stream.getAudioTracks()[0];
            state.lkTrack = new LocalAudioTrack(mediaTrack, undefined, false, state.ctx);

            state.room = new Room();
            state.room.on(RoomEvent.Disconnected, () => { if (state.live) endLocally(); });
            state.room.on(RoomEvent.ConnectionQualityChanged, (quality, participant) => {
                if (!state.room || participant?.identity === state.room.localParticipant?.identity) setQuality(quality);
            });
            state.room.on(RoomEvent.Reconnecting, () => setQuality('lost'));
            state.room.on(RoomEvent.Reconnected, () => setQuality('good'));

            await state.room.connect(wsUrl, creds.token);
            await state.room.localParticipant.publishTrack(state.lkTrack, { name: 'microphone' });
        } catch (e) {
            console.error('LiveKit publish failed:', e);
            showError('Could not start the broadcast: ' + (e.message || e) + '. Check that the LiveKit server is reachable.');
            await api(CFG.urls.stop).catch(() => {});
            try { await state.room?.disconnect(); } catch (_) {}
            state.room = null; state.lkTrack = null;
            setStatus('off'); els.goLive.disabled = false;
            return;
        }

        state.live = true;
        state.muted = false;
        state.startedAt = Date.now();
        setStatus('live');
        setQuality('good');
        els.goLive.classList.add('hidden');
        els.stop.classList.remove('hidden');
        els.stop.classList.add('flex');
        els.mute.disabled = false;
        els.monitor.disabled = false;
        lockInputs(true);
        els.hint.textContent = 'You are on air — listeners hear you live. Press M or Mute for a cough-mute.';

        els.elapsedTimer = setInterval(() => { els.elapsed.textContent = fmtElapsed(Date.now() - state.startedAt); }, 500);
        pollStatus();
        state.statusTimer = setInterval(pollStatus, 5000);
        pollParticipants();
        state.participantsTimer = setInterval(pollParticipants, 4000);
    }

    async function stop() {
        els.stop.disabled = true;
        try { await state.room?.disconnect(); } catch (_) {}
        state.room = null; state.lkTrack = null;
        await api(CFG.urls.stop).catch(() => {});
        endLocally();
        els.stop.disabled = false;
    }

    function endLocally() {
        state.live = false;
        state.muted = false;
        if (state.elapsedTimer) clearInterval(state.elapsedTimer);
        if (state.statusTimer) clearInterval(state.statusTimer);
        if (state.participantsTimer) clearInterval(state.participantsTimer);
        state.elapsedTimer = state.statusTimer = state.participantsTimer = null;
        renderParticipants([]);
        setStatus('off');
        setQuality('unknown');
        els.stop.classList.add('hidden');
        els.stop.classList.remove('flex');
        els.goLive.classList.remove('hidden');
        els.goLive.disabled = !CFG.active;
        els.mute.disabled = true;
        els.monitor.disabled = true;
        updateMuteUI();
        lockInputs(false);
        els.hint.textContent = 'Off air. Click Go Live to broadcast again.';
    }

    function lockInputs(locked) {
        [els.micSelect, els.procEcho, els.procNoise, els.procAgc, els.arm].forEach((el) => { if (el) el.disabled = locked; });
    }

    /* ---------------------------- transport ------------------------------- */

    async function toggleMute() {
        if (!state.live || !state.lkTrack) return;
        state.muted = !state.muted;
        try { state.muted ? await state.lkTrack.mute() : await state.lkTrack.unmute(); } catch (_) {}
        updateMuteUI();
        setStatus('live');
    }

    function updateMuteUI() {
        const on = state.muted;
        els.muteLabel.textContent = on ? 'Muted' : 'Mute';
        els.mute.classList.toggle('bg-rose-600', on);
        els.mute.classList.toggle('text-white', on);
        els.mute.classList.toggle('border-rose-600', on);
    }

    function toggleMonitor() {
        state.monitoring = !state.monitoring;
        if (state.monitorGain && state.ctx) {
            state.monitorGain.gain.setTargetAtTime(state.monitoring ? 1 : 0, state.ctx.currentTime, 0.01);
        }
        els.monitorLabel.textContent = state.monitoring ? 'Monitoring' : 'Monitor';
        els.monitor.classList.toggle('bg-primary-600', state.monitoring);
        els.monitor.classList.toggle('text-white', state.monitoring);
        els.monitor.classList.toggle('border-primary-600', state.monitoring);
        if (state.monitoring) els.hint.textContent = 'Monitoring on — use headphones to avoid feedback/echo.';
    }

    function applyGain() {
        const db = gainDb();
        els.gainVal.textContent = `${db > 0 ? '+' : ''}${db} dB`;
        if (state.gainNode && state.ctx) {
            state.gainNode.gain.setTargetAtTime(linearFromDb(db), state.ctx.currentTime, 0.01);
        }
    }

    /* ------------------------------ polling ------------------------------- */

    async function pollStatus() {
        try {
            const res = await fetch(CFG.urls.status, { headers: { Accept: 'application/json' } });
            const s = await res.json();
            const n = s.listeners ?? 0;
            els.listeners.textContent = n;
            els.peakListeners.textContent = s.peak_listeners ?? 0;
            if (s.started_at && els.startedAt.textContent === '—') {
                els.startedAt.textContent = new Date(s.started_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            state.listenerHistory.push(n);
            if (state.listenerHistory.length > 60) state.listenerHistory.shift();
            drawSpark();
        } catch (_) { /* transient */ }
    }

    /* -------------------- interactive audience (speaking) ----------------- */

    async function pollParticipants() {
        if (!CFG.urls?.participants) return;
        try {
            const res = await fetch(CFG.urls.participants, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            renderParticipants(Array.isArray(data.participants) ? data.participants : []);
        } catch (_) { /* transient */ }
    }

    function renderParticipants(list) {
        if (!els.listenersList || !els.listenersEmpty) return;
        if (els.listenersCount) els.listenersCount.textContent = list.length;

        if (!list.length) {
            els.listenersList.classList.add('hidden');
            els.listenersList.innerHTML = '';
            els.listenersEmpty.classList.remove('hidden');
            els.listenersEmpty.textContent = state.live
                ? 'No listeners yet — the room is quiet.'
                : "Go live to see who's tuned in — then invite a listener to speak.";
            return;
        }

        els.listenersEmpty.classList.add('hidden');
        els.listenersList.classList.remove('hidden');
        els.listenersList.innerHTML = '';
        list.forEach((p) => els.listenersList.appendChild(participantRow(p)));
    }

    function participantRow(p) {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 dark:border-slate-800 dark:bg-slate-800/40';

        const left = document.createElement('div');
        left.className = 'min-w-0';
        const name = document.createElement('p');
        name.className = 'truncate text-xs font-medium text-slate-700 dark:text-slate-200';
        name.textContent = p.name || 'Listener';
        const badge = document.createElement('p');
        badge.className = 'mt-0.5 text-[10px] font-semibold uppercase tracking-wide ';
        if (p.is_speaking) { badge.textContent = '● Speaking'; badge.className += 'text-rose-500'; }
        else if (p.can_publish) { badge.textContent = 'Can speak'; badge.className += 'text-emerald-600 dark:text-emerald-400'; }
        else if (p.raised_hand) { badge.textContent = '✋ Wants to speak'; badge.className += 'text-amber-600 dark:text-amber-400'; }
        else { badge.textContent = 'Listening'; badge.className += 'text-slate-400'; }
        left.appendChild(name);
        left.appendChild(badge);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'shrink-0 rounded-lg px-2.5 py-1 text-[11px] font-semibold transition disabled:opacity-50 ';
        if (p.can_publish) {
            btn.textContent = 'Revoke';
            btn.className += 'border border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-400 dark:hover:bg-rose-500/10';
            btn.addEventListener('click', () => setSpeak(p.identity, false, btn));
        } else {
            btn.textContent = 'Allow to speak';
            btn.className += 'bg-primary-600 text-white hover:bg-primary-700';
            if (p.raised_hand) btn.className += ' ring-2 ring-amber-400';
            btn.addEventListener('click', () => setSpeak(p.identity, true, btn));
        }

        row.appendChild(left);
        row.appendChild(btn);
        return row;
    }

    async function setSpeak(identity, allow, btn) {
        if (btn) { btn.disabled = true; btn.textContent = '…'; }
        try {
            await api(allow ? CFG.urls.grant : CFG.urls.revoke, { identity });
            await pollParticipants();
        } catch (e) {
            showError(e.message || 'Could not update speaking permission.');
            if (btn) btn.disabled = false;
        }
    }

    function startClock() {
        const tick = () => { els.onairClock.textContent = new Date().toLocaleTimeString([], { hour12: false }); };
        tick();
        state.clockTimer = setInterval(tick, 1000);
    }

    /* ------------------------------- wire up ------------------------------ */

    if (!CFG.active) { els.goLive.disabled = true; els.arm.disabled = true; }

    if (CFG.active && !canCapture) {
        els.goLive.disabled = true;
        els.arm.disabled = true;
        const localUrl = 'http://localhost' + (location.port ? ':' + location.port : '') + '/admin';
        showError(
            'Your browser blocks microphone access on this address because it is not secure (http://<ip>). '
            + 'To broadcast, open the studio on the server itself at ' + localUrl + ', or serve the admin portal over HTTPS. '
            + 'Listeners can still tune in from other machines over the network.',
        );
        els.hint.textContent = 'Broadcasting needs a secure origin (localhost or HTTPS). Listening works from anywhere.';
    }

    els.goLive.addEventListener('click', goLive);
    els.stop.addEventListener('click', stop);
    els.mute.addEventListener('click', toggleMute);
    els.monitor.addEventListener('click', toggleMonitor);
    els.arm.addEventListener('click', arm);
    els.gain.addEventListener('input', applyGain);

    // Re-arm when device/processing change before going live.
    const reArm = () => { if (state.armed && !state.live) arm(); };
    els.micSelect.addEventListener('change', reArm);
    [els.procEcho, els.procNoise, els.procAgc].forEach((el) => el.addEventListener('change', reArm));

    els.copyLink.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(CFG.listenUrl || els.listenUrl.value);
            const orig = els.copyLink.innerHTML;
            els.copyLink.textContent = 'Copied';
            setTimeout(() => { els.copyLink.innerHTML = orig; }, 1400);
        } catch (_) {
            els.listenUrl.select();
            document.execCommand?.('copy');
        }
    });

    // Keyboard: M = cough-mute while live (ignore when typing the topic).
    window.addEventListener('keydown', (e) => {
        if (e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) return;
        if ((e.key === 'm' || e.key === 'M') && state.live) { e.preventDefault(); toggleMute(); }
    });

    window.addEventListener('resize', () => { drawSpark(); });

    window.addEventListener('pagehide', () => {
        if (!state.live) return;
        try {
            const fd = new FormData();
            fd.append('_token', CFG.csrf);
            navigator.sendBeacon(CFG.urls.stop, fd);
        } catch (_) {}
        try { state.room?.disconnect(); } catch (_) {}
    });

    // Debug hook for automated verification.
    window.__bcDebug = () => ({
        live: state.live, armed: state.armed, muted: state.muted, monitoring: state.monitoring,
        connected: state.room?.state || null,
        published: state.room?.localParticipant ? (state.room.localParticipant.getTrackPublications?.() || []).length : 0,
        peakDb: Math.round(state.lastPeakDb * 10) / 10,
        quality: state.quality,
        gainDb: gainDb(),
    });

    setStatus('off');
    setQuality('unknown');
    startClock();
    applyGain();
}

if (CFG) boot();
