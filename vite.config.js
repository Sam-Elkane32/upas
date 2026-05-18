import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

function resolveHmrHost(env) {
    if (env.VITE_HMR_HOST) {
        return env.VITE_HMR_HOST;
    }
    try {
        const hostname = new URL(env.APP_URL || 'http://localhost').hostname;
        if (!hostname || hostname === '[::1]') {
            return undefined;
        }
        // Always pin HMR to APP_URL host so public/hot matches how you open the app
        // (avoids broken CSS when the browser URL and hot-file host disagree).
        return hostname;
    } catch {
        //
    }
    return undefined;
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const hmrHost = resolveHmrHost(env);

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: true,
            strictPort: true,
            ...(hmrHost ? { hmr: { host: hmrHost } } : {}),
        },
    };
});
