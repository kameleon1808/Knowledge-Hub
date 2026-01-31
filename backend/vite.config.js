import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    // URL the browser uses to load Vite assets (hot file + script src).
    // In Docker, set VITE_DEV_SERVER_URL=http://localhost:5173 so the hot file
    // never contains 0.0.0.0 or [::], which cause ERR_ADDRESS_INVALID in browsers.
    const viteDevServerUrl =
        env.VITE_DEV_SERVER_URL || 'http://localhost:5173';

    // Allow requests from the app origin (nginx at :8080) to Vite dev server.
    const appUrl = env.APP_URL || 'http://localhost:8080';

    return {
        plugins: [
            tailwindcss(),
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        server: {
            // Ensures public/hot contains a browser-usable URL (e.g. localhost:5173),
            // not 0.0.0.0 or [::], which cause ERR_ADDRESS_INVALID when opening from Docker.
            origin: viteDevServerUrl,
            // Fix CORS for http://localhost:8080 -> http://localhost:5173 requests.
            cors: {
                origin: appUrl,
            },
        },
    };
});
