import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        // Code splitting for better caching
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunk - rarely changes
                    'vendor': ['vue', '@inertiajs/vue3'],
                    // UI components chunk
                    'ui': [
                        '/resources/js/Components/UI/Button.vue',
                        '/resources/js/Components/UI/Input.vue',
                        '/resources/js/Components/UI/Modal.vue',
                    ],
                },
                // Asset naming for better caching
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    if (/\.(woff2?|ttf|eot|otf)$/.test(assetInfo.name)) {
                        return 'fonts/[name]-[hash][extname]';
                    }
                    if (/\.(png|jpg|jpeg|gif|svg|webp|avif)$/.test(assetInfo.name)) {
                        return 'images/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
        // Minification settings
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Source maps only in development
        sourcemap: false,
        // Target modern browsers
        target: 'es2020',
    },
    // Optimization for development
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3'],
    },
});