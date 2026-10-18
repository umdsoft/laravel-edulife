import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.js';

export default mergeConfig(viteConfig, defineConfig({
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/__tests__/**/*.test.js'],
        setupFiles: ['resources/js/__tests__/setup.js'],
    },
}));
