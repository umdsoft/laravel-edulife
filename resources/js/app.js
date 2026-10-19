import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { route, ZiggyVue } from '../../vendor/tightenco/ziggy';

// Ziggy: the route list is injected by the @routes Blade directive (globalThis.Ziggy).
// Expose route() globally so it also works inside <script setup> and plain JS modules.
window.route = route;

createInertiaApp({
    title: (title) => title ? `${title} - EDULIFE` : 'EDULIFE - Online Ta\'lim Platformasi',

    // Lazy loading pages for better performance
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            console.error(`Page not found: ${name}`);
            return pages['./Pages/Errors/404.vue']?.() || Promise.resolve({});
        }

        return page();
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // ZiggyVue registers route() for templates (globalProperties) and provide/inject
        app.use(ZiggyVue);

        // Performance: Add error handler
        app.config.errorHandler = (err, vm, info) => {
            console.error('Vue Error:', err, info);
        };

        // Mount the app
        app.use(plugin).mount(el);
    },

    // Progress bar configuration
    progress: {
        color: '#7C3AED',
        showSpinner: true,
    },
});

// Register service worker for PWA (optional)
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Service worker registration failed
        });
    });
}

// Lazy load images with Intersection Observer
document.addEventListener('DOMContentLoaded', () => {
    const lazyImages = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
        });
    }
});