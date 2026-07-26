import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import {
    Chart,
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, ArcElement,
    CategoryScale, LinearScale, Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement,
    BarController, BarElement,
    DoughnutController, ArcElement,
    CategoryScale, LinearScale, Tooltip, Legend, Filler,
);
window.Chart = Chart;

/* ------------------------------------------------------------------ */
/* Colour mode: light | dark | system — persisted in localStorage      */
/* ------------------------------------------------------------------ */

const applyMode = (mode) => {
    const dark =
        mode === 'dark' ||
        (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
};

const storedMode = () =>
    localStorage.getItem('color-mode') || document.documentElement.dataset.defaultMode || 'system';

applyMode(storedMode());

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (storedMode() === 'system') applyMode('system');
});

window.setColorMode = (mode) => {
    localStorage.setItem('color-mode', mode);
    applyMode(mode);
};

/* ------------------------------------------------------------------ */
/* Alpine stores & helpers                                             */
/* ------------------------------------------------------------------ */

Alpine.store('ui', {
    sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === '1',
    sidebarOpenMobile: false,
    mode: storedMode(),

    toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
    },

    setMode(mode) {
        this.mode = mode;
        window.setColorMode(mode);
    },
});

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

/* ------------------------------------------------------------------ */
/* Keep the active sidebar item in view                                */
/* On load, the nav may be scrolled to the top while the active item   */
/* sits below the fold — center it in the nav's own scroll viewport    */
/* (never scrolls the page, only the sidebar).                         */
/* ------------------------------------------------------------------ */

function scrollActiveNavIntoView() {
    const active = document.querySelector('.nav-item.active');
    const nav = active?.closest('nav');
    if (!active || !nav) return;

    const navRect = nav.getBoundingClientRect();
    const actRect = active.getBoundingClientRect();

    // Already fully visible — leave the scroll position alone.
    if (actRect.top >= navRect.top && actRect.bottom <= navRect.bottom) return;

    nav.scrollTop += (actRect.top - navRect.top) - nav.clientHeight / 2 + actRect.height / 2;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scrollActiveNavIntoView);
} else {
    scrollActiveNavIntoView();
}
