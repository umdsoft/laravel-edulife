import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

// Global route helper (simple implementation)
const route = function(name, params = {}) {
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
        
        // Add more admin routes as needed
        'admin.dashboard': '/admin/dashboard',
        'admin.courses.index': '/admin/courses',
        'admin.teachers.index': '/admin/teachers',
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

createInertiaApp({
    title: (title) => title ? `${title} - EDULIFE` : 'EDULIFE',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Add route as global property for Vue templates
        app.config.globalProperties.route = route;
        
        app.use(plugin).mount(el);
    },
});