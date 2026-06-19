import tailwindcss from '@tailwindcss/vite';

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-07-15',
    // Universal (SSR) rendering is on by default; kept explicit.
    ssr: true,
    devtools: { enabled: true },

    // The Nuxt app sources live under resources/ (Laravel keeps app/, routes/, etc.).
    srcDir: 'resources',
    // Avoid clashing with Laravel's public/ web root.
    dir: {
        public: 'resources/public',
    },

    css: ['~/assets/css/main.css'],

    app: {
        // Smooth out-in transition between routes (paired with .page-* CSS).
        pageTransition: { name: 'page', mode: 'out-in' },
    },

    vite: {
        plugins: [tailwindcss()],
    },

    runtimeConfig: {
        // SSR (Nuxt Node server -> Laravel). Absolute loopback through nginx, so
        // it never hardcodes the public domain. nginx must answer the API for the
        // 127.0.0.1 host (see server_name in the nginx config).
        apiBase: process.env.NUXT_API_BASE || 'http://127.0.0.1/api',
        public: {
            // Browser. Relative by default -> same origin as the page, so the app
            // works on ANY domain it is served from with no rebuild. Override with
            // NUXT_PUBLIC_API_BASE only if the API lives on a different origin.
            apiBase: process.env.NUXT_PUBLIC_API_BASE || '/api',
        },
    },

    nitro: {
        // In `npm run dev` the browser hits the Nuxt dev server (:3000); forward
        // the relative /api calls to Laravel (served by nginx) so the same
        // relative base also works in development, same-origin, no CORS.
        devProxy: {
            '/api': {
                target: process.env.NUXT_DEV_API_ORIGIN || 'http://127.0.0.1',
                changeOrigin: true,
            },
        },
    },
});
