import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

// Global route helper (simple implementation)
const route = function (name, params = {}) {
    // Simple route URL generator
    const routes = {
        // Admin Users
        'admin.users.index': '/admin/users',
        'admin.users.create': '/admin/users/create',
        'admin.users.store': '/admin/users',
        'admin.users.show': (id) => `/admin/users/${id}`,
        'admin.users.edit': (id) => `/admin/users/${id}/edit`,
        'admin.users.update': (id) => `/admin/users/${id}`,
        'admin.users.destroy': (id) => `/admin/users/${id}`,

        // Admin routes
        'admin.dashboard': '/admin/dashboard',
        'admin.courses.index': '/admin/courses',
        'admin.teachers.index': '/admin/teachers',

        // Student routes
        'student.courses.index': '/student/courses',
        'student.courses.show': (slug) => `/student/courses/${slug}`,
        'student.dashboard': '/student/dashboard',

        // Student English Learning routes  
        'student.english.dashboard': '/student/english',
        'student.english.levels': '/student/english/levels',
        'student.english.lesson': (id) => `/student/english/lesson/${id}`,
        'student.english.lesson.complete': (id) => `/student/english/lesson/${id}/complete`,
        'student.english.vocabulary.review': '/student/english/vocabulary/review',
        'student.english.games': '/student/english/games',
        'student.english.games.play': (id) => `/student/english/games/${id}/play`,
        'student.english.battle': '/student/english/battle',
        'student.english.battle.arena': (id) => `/student/english/battle/${id}`,
        'student.english.achievements': '/student/english/achievements',
        'student.english.leaderboard': '/student/english/leaderboard',
        'student.english.profile': '/student/english/profile',

        // Old English Learning routes (for backwards compatibility)
        'english.dashboard': '/english',
        'english.levels': '/english/levels',
        'english.lesson': (id) => `/english/lesson/${id}`,
        'english.vocabulary.review': '/english/vocabulary/review',
        'english.games.index': '/english/games',
        'english.games.play': (id) => `/english/games/${id}/play`,
        'english.battle.lobby': '/english/battle',
        'english.battle.arena': (id) => `/english/battle/${id}`,
        'english.achievements': '/english/achievements',
        'english.leaderboard': '/english/leaderboard',
        'english.profile': '/english/profile',

        // Auth routes
        'login': '/login',
        'register': '/register',
        'logout': '/logout',
    };

    const routeUrl = routes[name];
    if (typeof routeUrl === 'function') {
        return routeUrl(params);
    }

    // Handle query params
    if (typeof params === 'object' && Object.keys(params).length > 0 && !Array.isArray(params)) {
        const queryString = new URLSearchParams(params).toString();
        return `${routeUrl}?${queryString}`;
    }

    return routeUrl || '#';
};

// Make route available globally
window.route = route;

// Create Pinia instance
const pinia = createPinia();

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

        // Add route as global property for Vue templates
        app.config.globalProperties.route = route;

        // Performance: Add error handler
        app.config.errorHandler = (err, vm, info) => {
            console.error('Vue Error:', err, info);
        };

        // Mount the app with Pinia and Inertia
        app.use(pinia).use(plugin).mount(el);
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