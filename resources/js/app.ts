import { createInertiaApp } from '@inertiajs/vue3';
import Aura from '@primevue/themes/aura';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import type { Config } from 'ziggy-js';

// 1. IMPORT LAYOUT
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';

// 2. IMPORT PRIMEVUE & TEMA
import 'primeicons/primeicons.css';

import { Ziggy } from './ziggy.js'; // Ini file fisik peta rute kita

const appName = import.meta.env.VITE_APP_NAME || 'EduChem';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        // Pemetaan folder 'Pages' (huruf besar untuk kompatibilitas Linux)
        const pages = import.meta.glob('./pages/**/*.vue');
        const page: any = await resolvePageComponent(
            `./pages/${name}.vue`,
            pages,
        );

        // PASANG LAYOUT SECARA OTOMATIS
        if (page.default.layout === undefined) {
            const lowerName = name.toLowerCase();

            if (lowerName.startsWith('auth/')) {
                page.default.layout = AuthLayout;
            } else {
                page.default.layout = AppLayout; // Sidebar dirender di sini
            }
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        // SOLUSI SSR 1: Hapus dark mode HANYA jika berjalan di sisi Client (Browser)
        if (typeof document !== 'undefined') {
            document.documentElement.classList.remove('dark');
        }

        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);

        // Konfigurasi PrimeVue (Force Light Mode)
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: false,
                },
            },
        });

        // Gunakan konfigurasi URL dari server di browser dan peta statis saat SSR.
        // Ini mencegah URL localhost dari file hasil generate terbawa ke production.
        const ziggyConfig =
            typeof window !== 'undefined' && window.Ziggy
                ? window.Ziggy
                : (Ziggy as Config);
        app.use(ZiggyVue, ziggyConfig);

        // Mount aplikasi HANYA jika elemen DOM (el) tersedia di browser
        if (el) {
            app.mount(el);
        }

        // WAJIB UNTUK SSR: Return instance aplikasi
        return app;
    },
    progress: { color: '#4F8CFF' }, // Warna loading biru EduChem
});
