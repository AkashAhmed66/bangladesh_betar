import { Room, RoomEvent, Track, createLocalTracks } from 'livekit-client';

const CFG = window.__WATCH_LIVE__;

if (CFG) {
    const byId = (id) => document.getElementById(id);
    const els = {
        error: byId('watch-live-error'),
        preview: byId('watch-preview'),
        empty: byId('watch-preview-empty'),
        prepare: byId('watch-prepare'),
        goLive: byId('watch-go-live'),
        stop: byId('watch-stop'),
        mic: byId('watch-toggle-mic'),
        camera: byId('watch-toggle-camera'),
        cameraSelect: byId('watch-camera'),
        micSelect: byId('watch-microphone'),
        topic: byId('watch-topic'),
        status: byId('watch-status'),
        statusDot: byId('watch-status-dot'),
        viewers: byId('watch-viewers'),
        elapsed: byId('watch-elapsed'),
        badge: byId('watch-live-badge'),
        copy: byId('watch-copy-link'),
        publicUrl: byId('watch-public-url'),
    };

    const state = {
        room: null,
        tracks: [],
        prepared: false,
        live: false,
        micMuted: false,
        cameraMuted: false,
        startedAt: null,
        elapsedTimer: null,
        statusTimer: null,
    };

    const showError = (message) => {
        els.error.textContent = message;
        els.error.classList.remove('hidden');
    };

    const clearError = () => {
        els.error.textContent = '';
        els.error.classList.add('hidden');
    };

    async function api(url, body = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CFG.csrf,
            },
            body: JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || `Request failed (${response.status})`);
        return data;
    }

    const formatElapsed = (milliseconds) => {
        const seconds = Math.max(0, Math.floor(milliseconds / 1000));
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainder = seconds % 60;
        return hours > 0
            ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
            : `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
    };

    const setStatus = (status) => {
        const live = status === 'live';
        els.status.textContent = live ? 'On air' : state.prepared ? 'Preview ready' : 'Preview offline';
        els.statusDot.className = `size-2.5 rounded-full ${live ? 'animate-pulse bg-rose-500' : state.prepared ? 'bg-emerald-400' : 'bg-slate-500'}`;
        els.badge.classList.toggle('hidden', !live);
        els.badge.classList.toggle('inline-flex', live);
    };

    const updateTransport = () => {
        els.prepare.disabled = state.live;
        els.goLive.disabled = !CFG.active || !state.prepared || state.live;
        els.mic.disabled = !state.prepared;
        els.camera.disabled = !state.prepared;
        els.cameraSelect.disabled = state.live;
        els.micSelect.disabled = state.live;
        els.stop.classList.toggle('hidden', !state.live);
        els.goLive.classList.toggle('hidden', state.live);
        els.mic.querySelector('span').textContent = state.micMuted ? 'Unmute' : 'Mute';
        els.camera.querySelector('span').textContent = state.cameraMuted ? 'Camera on' : 'Camera off';
    };

    const stopTimers = () => {
        if (state.elapsedTimer) window.clearInterval(state.elapsedTimer);
        if (state.statusTimer) window.clearInterval(state.statusTimer);
        state.elapsedTimer = null;
        state.statusTimer = null;
    };

    const startTimers = () => {
        stopTimers();
        const tick = () => {
            els.elapsed.textContent = state.live && state.startedAt
                ? formatElapsed(Date.now() - state.startedAt)
                : '00:00';
        };
        tick();
        state.elapsedTimer = window.setInterval(tick, 500);
        state.statusTimer = window.setInterval(pollStatus, 5000);
    };

    const releaseTracks = () => {
        state.tracks.forEach((track) => {
            try { track.detach(); } catch (_) { /* no-op */ }
            try { track.stop(); } catch (_) { /* no-op */ }
        });
        state.tracks = [];
        els.preview.srcObject = null;
        els.empty.classList.remove('hidden');
        state.prepared = false;
        state.micMuted = false;
        state.cameraMuted = false;
    };

    async function listDevices() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const fill = (select, kind, fallback) => {
            const selected = select.value;
            select.innerHTML = `<option value="">${fallback}</option>`;
            devices.filter((device) => device.kind === kind).forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.textContent = device.label || `${fallback} ${index + 1}`;
                option.selected = device.deviceId === selected;
                select.appendChild(option);
            });
        };
        fill(els.cameraSelect, 'videoinput', 'Default camera');
        fill(els.micSelect, 'audioinput', 'Default microphone');
    }

    async function prepare() {
        if (state.live) return;
        clearError();
        els.prepare.disabled = true;
        els.prepare.textContent = 'Preparing…';
        releaseTracks();

        try {
            state.tracks = await createLocalTracks({
                audio: {
                    ...(els.micSelect.value ? { deviceId: els.micSelect.value } : {}),
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                },
                video: {
                    ...(els.cameraSelect.value ? { deviceId: els.cameraSelect.value } : {}),
                    resolution: { width: 1280, height: 720, frameRate: 30 },
                },
            });

            const videoTrack = state.tracks.find((track) => track.kind === Track.Kind.Video);
            if (!videoTrack) throw new Error('No camera video was created.');
            videoTrack.attach(els.preview);
            els.empty.classList.add('hidden');
            state.prepared = true;
            await listDevices();
            setStatus('preview');
        } catch (error) {
            releaseTracks();
            showError(`Camera preparation failed: ${error.message || error}. Check browser permissions and select another device.`);
        } finally {
            els.prepare.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg> Prepare Camera';
            updateTransport();
        }
    }

    async function goLive() {
        if (!state.prepared) await prepare();
        if (!state.prepared || state.live) return;
        clearError();
        els.goLive.disabled = true;
        els.goLive.textContent = 'Connecting…';

        let sessionCreated = false;
        try {
            const credentials = await api(CFG.urls.goLive, { title: els.topic.value.trim() });
            sessionCreated = true;
            const room = new Room({ adaptiveStream: true, dynacast: true });
            state.room = room;
            room.on(RoomEvent.Disconnected, () => {
                if (state.live) resetLocal(false);
            });
            await room.connect(CFG.wsUrl || credentials.ws_url, credentials.token);
            for (const track of state.tracks) {
                await room.localParticipant.publishTrack(track, {
                    simulcast: track.kind === Track.Kind.Video,
                    videoCodec: track.kind === Track.Kind.Video ? 'vp8' : undefined,
                });
            }
            state.live = true;
            state.startedAt = Date.now();
            setStatus('live');
            updateTransport();
            startTimers();
            await pollStatus();
        } catch (error) {
            if (sessionCreated) void api(CFG.urls.stop).catch(() => undefined);
            try { state.room?.disconnect(); } catch (_) { /* no-op */ }
            state.room = null;
            showError(`Could not start the live video: ${error.message || error}`);
            updateTransport();
        } finally {
            els.goLive.innerHTML = '<span class="size-2 rounded-full bg-white"></span> Go Live';
        }
    }

    function resetLocal(release = true) {
        state.live = false;
        state.startedAt = null;
        stopTimers();
        try { state.room?.disconnect(); } catch (_) { /* no-op */ }
        state.room = null;
        if (release) releaseTracks();
        els.elapsed.textContent = '00:00';
        els.viewers.textContent = '0';
        setStatus(state.prepared ? 'preview' : 'off');
        updateTransport();
    }

    async function stop() {
        if (!state.live) return;
        els.stop.disabled = true;
        try {
            await api(CFG.urls.stop);
        } catch (error) {
            showError(error.message || 'The server could not fully stop the video room.');
        } finally {
            resetLocal(true);
            els.stop.disabled = false;
        }
    }

    async function pollStatus() {
        try {
            const response = await fetch(CFG.urls.status, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const status = await response.json();
            els.viewers.textContent = String(status.viewers ?? 0);
            if (state.live && status.is_live === false) resetLocal(true);
        } catch (_) { /* transient polling error */ }
    }

    async function toggleMic() {
        const track = state.tracks.find((item) => item.kind === Track.Kind.Audio);
        if (!track) return;
        state.micMuted = !state.micMuted;
        if (state.micMuted) await track.mute(); else await track.unmute();
        updateTransport();
    }

    async function toggleCamera() {
        const track = state.tracks.find((item) => item.kind === Track.Kind.Video);
        if (!track) return;
        state.cameraMuted = !state.cameraMuted;
        if (state.cameraMuted) await track.mute(); else await track.unmute();
        updateTransport();
    }

    const secureCapture = window.isSecureContext || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
    if (!secureCapture) {
        showError('Camera broadcasting requires HTTPS or localhost. Open this studio through a secure address before going live.');
        els.prepare.disabled = true;
    } else if (!CFG.active) {
        els.prepare.disabled = true;
    }

    els.prepare.addEventListener('click', prepare);
    els.goLive.addEventListener('click', goLive);
    els.stop.addEventListener('click', stop);
    els.mic.addEventListener('click', () => void toggleMic());
    els.camera.addEventListener('click', () => void toggleCamera());
    els.cameraSelect.addEventListener('change', () => { if (state.prepared && !state.live) void prepare(); });
    els.micSelect.addEventListener('change', () => { if (state.prepared && !state.live) void prepare(); });
    els.copy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(CFG.publicUrl);
            els.copy.textContent = 'Copied';
            window.setTimeout(() => { els.copy.textContent = 'Copy'; }, 1400);
        } catch (_) {
            els.publicUrl.select();
            document.execCommand?.('copy');
        }
    });
    window.addEventListener('pagehide', () => {
        if (!state.live) return;
        const data = new FormData();
        data.append('_token', CFG.csrf);
        navigator.sendBeacon(CFG.urls.stop, data);
    });

    setStatus('off');
    updateTransport();
    void pollStatus();
}
