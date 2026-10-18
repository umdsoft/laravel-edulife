import { config } from '@vue/test-utils';
import { route } from '../../../vendor/tightenco/ziggy';

// Minimal Ziggy route table mirroring `php artisan route:list` for the routes the tests touch.
export const Ziggy = {
    url: 'http://localhost',
    port: null,
    defaults: {},
    routes: {
        'teacher.lessons.edit': { uri: 'teacher/lessons/{lesson}/edit', methods: ['GET', 'HEAD'], parameters: ['lesson'] },
        'teacher.lessons.destroy': { uri: 'teacher/lessons/{lesson}', methods: ['DELETE'], parameters: ['lesson'] },
        'teacher.courses.tests.create': { uri: 'teacher/courses/{course}/tests/create', methods: ['GET', 'HEAD'], parameters: ['course'] },
        'teacher.courses.edit': { uri: 'teacher/courses/{course}/edit', methods: ['GET', 'HEAD'], parameters: ['course'] },
        'student.tournaments.register': { uri: 'student/tournaments/{tournament}/register', methods: ['POST'], parameters: ['tournament'] },
    },
};

// Same globals that `@routes` + ZiggyVue provide in the browser:
// `route` on window (used inside <script setup>) and as a template global.
const boundRoute = (name, params, absolute) => route(name, params, absolute, Ziggy);
globalThis.Ziggy = Ziggy;
globalThis.route = boundRoute;
config.global.mocks = { ...config.global.mocks, route: boundRoute };
