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
        'student.english.games.index': '/student/english/games',
        'student.english.games.play': (id) => `/student/english/games/${id}/play`,
        'student.english.battle': '/student/english/battle',
        'student.english.battle.arena': (id) => `/student/english/battle/${id}`,
        'student.english.achievements': '/student/english/achievements',
        'student.english.leaderboard': '/student/english/leaderboard',
        'student.english.profile': '/student/english/profile',

        // Grammar Quiz Routes
        'student.english.games.grammar-quiz.index': '/student/english/games/grammar-quiz',
        'student.english.games.grammar-quiz.play': (params) => `/student/english/games/grammar-quiz/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.grammar-quiz.start': (params) => `/student/english/games/grammar-quiz/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.grammar-quiz.check': '/student/english/games/grammar-quiz/check',
        'student.english.games.grammar-quiz.hint': '/student/english/games/grammar-quiz/hint',
        'student.english.games.grammar-quiz.skip': '/student/english/games/grammar-quiz/skip',
        'student.english.games.grammar-quiz.fifty-fifty': '/student/english/games/grammar-quiz/fifty-fifty',
        'student.english.games.grammar-quiz.complete': '/student/english/games/grammar-quiz/complete',

        // Rapid Fire Routes
        'student.english.games.rapid-fire.index': '/student/english/games/rapid-fire',
        'student.english.games.rapid-fire.play': (params) => `/student/english/games/rapid-fire/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.rapid-fire.start': (params) => `/student/english/games/rapid-fire/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.rapid-fire.check': '/student/english/games/rapid-fire/check',
        'student.english.games.rapid-fire.complete': '/student/english/games/rapid-fire/complete',

        // Tense Race Routes
        'student.english.games.tense-race.index': '/student/english/games/tense-race',
        'student.english.games.tense-race.play': (params) => `/student/english/games/tense-race/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.tense-race.start': (params) => `/student/english/games/tense-race/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.tense-race.check': '/student/english/games/tense-race/check',
        'student.english.games.tense-race.complete': '/student/english/games/tense-race/complete',

        // Listen Choose Routes
        'student.english.games.listen-choose.index': '/student/english/games/listen-choose',
        'student.english.games.listen-choose.play': (params) => `/student/english/games/listen-choose/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.listen-choose.start': (params) => `/student/english/games/listen-choose/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.listen-choose.check': '/student/english/games/listen-choose/check',
        'student.english.games.listen-choose.complete': '/student/english/games/listen-choose/complete',

        // Sequence Recall Routes
        'student.english.games.sequence-recall.index': '/student/english/games/sequence-recall',
        'student.english.games.sequence-recall.play': (params) => `/student/english/games/sequence-recall/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.sequence-recall.start': (params) => `/student/english/games/sequence-recall/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.sequence-recall.check': '/student/english/games/sequence-recall/check',
        'student.english.games.sequence-recall.complete': '/student/english/games/sequence-recall/complete',

        // True/False Routes
        'student.english.games.true-false.index': '/student/english/games/true-false',
        'student.english.games.true-false.play': (params) => `/student/english/games/true-false/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.true-false.start': (params) => `/student/english/games/true-false/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.true-false.check': '/student/english/games/true-false/check',
        'student.english.games.true-false.complete': '/student/english/games/true-false/complete',

        // Hangman Routes
        'student.english.games.hangman.index': '/student/english/games/hangman',
        'student.english.games.hangman.play': (params) => `/student/english/games/hangman/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.hangman.start': (params) => `/student/english/games/hangman/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.hangman.guess': '/student/english/games/hangman/guess',
        'student.english.games.hangman.complete': '/student/english/games/hangman/complete',

        // Number Listener Routes
        'student.english.games.number-listener.index': '/student/english/games/number-listener',
        'student.english.games.number-listener.play': (params) => `/student/english/games/number-listener/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.number-listener.start': (params) => `/student/english/games/number-listener/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.number-listener.check': '/student/english/games/number-listener/check',
        'student.english.games.number-listener.complete': '/student/english/games/number-listener/complete',

        // Spelling Bee Routes
        'student.english.games.spelling-bee.index': '/student/english/games/spelling-bee',
        'student.english.games.spelling-bee.play': (params) => `/student/english/games/spelling-bee/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.spelling-bee.start': (params) => `/student/english/games/spelling-bee/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.spelling-bee.check': '/student/english/games/spelling-bee/check',
        'student.english.games.spelling-bee.complete': '/student/english/games/spelling-bee/complete',

        // Word Recall Routes
        'student.english.games.word-recall.index': '/student/english/games/word-recall',
        'student.english.games.word-recall.play': (params) => `/student/english/games/word-recall/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.word-recall.start': (params) => `/student/english/games/word-recall/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.word-recall.check-round': '/student/english/games/word-recall/check-round',
        'student.english.games.word-recall.complete': '/student/english/games/word-recall/complete',

        // Typing Race Routes
        'student.english.games.typing-race.index': '/student/english/games/typing-race',
        'student.english.games.typing-race.play': (params) => `/student/english/games/typing-race/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.typing-race.start': (params) => `/student/english/games/typing-race/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.typing-race.submit': '/student/english/games/typing-race/submit',
        'student.english.games.typing-race.complete': '/student/english/games/typing-race/complete',

        // Fill the Gap Routes  
        'student.english.games.fill-the-gap.index': '/student/english/games/fill-the-gap',
        'student.english.games.fill-the-gap.play': (params) => `/student/english/games/fill-the-gap/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.fill-the-gap.start': (params) => `/student/english/games/fill-the-gap/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.fill-the-gap.check': '/student/english/games/fill-the-gap/check',
        'student.english.games.fill-the-gap.complete': '/student/english/games/fill-the-gap/complete',

        // Verb Conjugator Routes
        'student.english.games.verb-conjugator.index': '/student/english/games/verb-conjugator',
        'student.english.games.verb-conjugator.play': (params) => `/student/english/games/verb-conjugator/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.verb-conjugator.start-session': (params) => `/student/english/games/verb-conjugator/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.verb-conjugator.submit-answer': '/student/english/games/verb-conjugator/answer',
        'student.english.games.verb-conjugator.complete-session': '/student/english/games/verb-conjugator/complete',

        // Beat the Clock Routes
        'student.english.games.beat-the-clock.index': '/student/english/games/beat-the-clock',
        'student.english.games.beat-the-clock.play': (params) => `/student/english/games/beat-the-clock/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.beat-the-clock.start-session': (params) => `/student/english/games/beat-the-clock/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.beat-the-clock.submit-answer': '/student/english/games/beat-the-clock/answer',
        'student.english.games.beat-the-clock.complete-session': '/student/english/games/beat-the-clock/complete',

        // Conversation Catcher Routes
        'student.english.games.conversation-catcher.index': '/student/english/games/conversation-catcher',
        'student.english.games.conversation-catcher.play': (params) => `/student/english/games/conversation-catcher/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.conversation-catcher.start-session': (params) => `/student/english/games/conversation-catcher/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.conversation-catcher.submit-answer': '/student/english/games/conversation-catcher/answer',
        'student.english.games.conversation-catcher.complete-session': '/student/english/games/conversation-catcher/complete',

        // Daily Challenge Routes
        'student.english.games.daily-challenge.index': '/student/english/games/daily-challenge',
        'student.english.games.daily-challenge.play': (params) => `/student/english/games/daily-challenge/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.daily-challenge.start-session': (params) => `/student/english/games/daily-challenge/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.daily-challenge.submit-answer': '/student/english/games/daily-challenge/answer',
        'student.english.games.daily-challenge.complete-session': '/student/english/games/daily-challenge/complete',

        // Word Search Routes
        'student.english.games.word-search.index': '/student/english/games/word-search',
        'student.english.games.word-search.play': (params) => `/student/english/games/word-search/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.word-search.start-session': (params) => `/student/english/games/word-search/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.word-search.check-word': '/student/english/games/word-search/check-word',
        'student.english.games.word-search.complete-session': '/student/english/games/word-search/complete',

        // Crossword Puzzle Routes
        'student.english.games.crossword-puzzle.index': '/student/english/games/crossword-puzzle',
        'student.english.games.crossword-puzzle.play': (params) => `/student/english/games/crossword-puzzle/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.crossword-puzzle.start-session': (params) => `/student/english/games/crossword-puzzle/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.crossword-puzzle.submit-word': '/student/english/games/crossword-puzzle/submit-word',
        'student.english.games.crossword-puzzle.complete-session': '/student/english/games/crossword-puzzle/complete',

        // Article Master Routes
        'student.english.games.article-master.index': '/student/english/games/article-master',
        'student.english.games.article-master.play': (params) => `/student/english/games/article-master/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.article-master.start-session': (params) => `/student/english/games/article-master/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.article-master.submit-answer': '/student/english/games/article-master/answer',
        'student.english.games.article-master.complete-session': '/student/english/games/article-master/complete',

        // Word Chain Routes
        'student.english.games.word-chain.index': '/student/english/games/word-chain',
        'student.english.games.word-chain.play': (params) => `/student/english/games/word-chain/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.word-chain.start': (params) => `/student/english/games/word-chain/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.word-chain.submit': '/student/english/games/word-chain/submit',
        'student.english.games.word-chain.complete': '/student/english/games/word-chain/complete',

        // Anagram Solver Routes
        'student.english.games.anagram-solver.index': '/student/english/games/anagram-solver',
        'student.english.games.anagram-solver.play': (params) => `/student/english/games/anagram-solver/play/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.anagram-solver.start': (params) => `/student/english/games/anagram-solver/start/${typeof params === 'object' ? params.level : params}`,
        'student.english.games.anagram-solver.answer': '/student/english/games/anagram-solver/answer',
        'student.english.games.anagram-solver.complete': '/student/english/games/anagram-solver/complete',

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