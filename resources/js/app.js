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
