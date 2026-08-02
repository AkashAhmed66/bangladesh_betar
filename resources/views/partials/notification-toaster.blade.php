{{-- Bottom-right notification toasts with sound (M30). Polls for fresh
     notifications; each new one pops up, chimes, and clicks through to its
     target. The topbar bell badge stays in sync between page loads. --}}
<div id="notif-toasts" class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 max-w-[calc(100vw-2rem)] flex-col-reverse gap-2"></div>

<script>
(function () {
    'use strict';
    const KEY = 'betar-notif-since';
    const POLL_MS = 20000;
    const container = document.getElementById('notif-toasts');
    let audioCtx = null;

    // Browsers only allow audio after a user gesture — prime the context on
    // the first interaction so later chimes are audible.
    function primeAudio() {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
        } catch (e) { /* sound is best-effort */ }
    }
    ['click', 'keydown', 'touchstart'].forEach(function (ev) {
        document.addEventListener(ev, primeAudio, { once: true, capture: true });
    });

    // Soft two-tone chime, synthesized — no audio file needed.
    function chime() {
        try {
            primeAudio();
            if (!audioCtx || audioCtx.state !== 'running') return;
            const t0 = audioCtx.currentTime;
            [880, 1174.66].forEach(function (freq, i) {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.0001, t0 + i * 0.13);
                gain.gain.exponentialRampToValueAtTime(0.14, t0 + i * 0.13 + 0.025);
                gain.gain.exponentialRampToValueAtTime(0.0001, t0 + i * 0.13 + 0.5);
                osc.connect(gain).connect(audioCtx.destination);
                osc.start(t0 + i * 0.13);
                osc.stop(t0 + i * 0.13 + 0.55);
            });
        } catch (e) { /* sound is best-effort */ }
    }

    function dismiss(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateX(1rem)';
        setTimeout(function () { el.remove(); }, 250);
    }

    function toast(n) {
        const el = document.createElement('a');
        el.href = n.url;
        el.className = 'pointer-events-auto block rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900';
        el.style.cssText = 'opacity:0;transform:translateX(1rem);transition:opacity .25s ease,transform .25s ease;';
        el.innerHTML =
            '<div style="display:flex;align-items:flex-start;gap:.65rem;">' +
                '<span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>' +
                '</span>' +
                '<span style="min-width:0;flex:1;">' +
                    '<span class="block text-sm font-semibold text-slate-800 dark:text-slate-100"></span>' +
                    '<span class="clamp-2 mt-0.5 block text-xs text-slate-500 dark:text-slate-400"></span>' +
                '</span>' +
                '<button type="button" class="shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Dismiss">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
                '</button>' +
            '</div>';
        el.querySelector('span.text-sm').textContent = n.title;
        el.querySelector('span.clamp-2').textContent = n.message;
        el.querySelector('button').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dismiss(el);
        });
        container.appendChild(el);
        requestAnimationFrame(function () {
            el.style.opacity = '1';
            el.style.transform = 'translateX(0)';
        });
        setTimeout(function () { if (el.isConnected) dismiss(el); }, 9000);
    }

    function updateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.classList.toggle('hidden', count < 1);
    }

    async function poll() {
        try {
            const since = localStorage.getItem(KEY);
            const url = '{{ route('admin.notifications.poll') }}' + (since ? '?since=' + encodeURIComponent(since) : '');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            localStorage.setItem(KEY, data.now);
            updateBadge(data.unread);
            if (Array.isArray(data.new) && data.new.length) {
                data.new.slice().reverse().forEach(toast);
                chime();
            }
        } catch (e) { /* offline / navigating — try again next tick */ }
    }

    poll();
    setInterval(poll, POLL_MS);
})();
</script>
