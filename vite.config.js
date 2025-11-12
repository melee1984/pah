import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import path from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/merchant.js',
                'resources/js/dashboard.js',
                'resources/sass/app.scss',
                'resources/sass/dashboard.scss',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrlsOptions: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@fonts': '/resources/fonts',
            '@node_modules': path.resolve(__dirname, 'node_modules'),
            'vue': path.resolve(__dirname, 'node_modules/vue/dist/vue.esm.js'),
        },
    },
    optimizeDeps: {
        exclude: ['vue2-google-maps']
    },

    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
                merchant: 'resources/js/merchant.js',
                dashboard: 'resources/js/dashboard.js',
            },
        },
    },
});
