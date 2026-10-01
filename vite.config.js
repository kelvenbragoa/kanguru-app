import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devHost = env.VITE_DEV_HOST || '192.168.18.195';

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.js'],
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
            Components({
                resolvers: [
                    PrimeVueResolver()
                ]
            }),
        ],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./resources/js', import.meta.url))
            }
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true,
            origin: `http://${devHost}:5173`,
            hmr: {
                host: devHost,
            },
        },
    };
});
