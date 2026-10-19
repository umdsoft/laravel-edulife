/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');

// Token colour helper: CSS variables hold "R G B" triplets (resources/css/app.css)
const v = (name) => `rgb(var(--${name}) / <alpha-value>)`;

// 50/100/600/700 + DEFAULT (theme-aware text colour) for semantic scales
const semantic = (name) => ({
    DEFAULT: v(`${name}-fg`),
    fg: v(`${name}-fg`),
    50: v(`${name}-50`),
    100: v(`${name}-100`),
    600: v(`${name}-600`),
    700: v(`${name}-700`),
});

module.exports = {
    darkMode: 'class',
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Neutrals
                bg: v('bg'),
                surface: {
                    DEFAULT: v('surface'),
                    2: v('surface-2'),
                    3: v('surface-3'),
                },
                border: {
                    DEFAULT: v('border'),
                    strong: v('border-strong'),
                },
                fg: {
                    DEFAULT: v('fg'),
                    muted: v('fg-muted'),
                    subtle: v('fg-subtle'),
                },
                overlay: v('overlay'),

                // Brand
                brand: {
                    DEFAULT: v('brand-fg'),
                    fg: v('brand-fg'),
                    50: v('brand-50'),
                    100: v('brand-100'),
                    200: v('brand-200'),
                    300: v('brand-300'),
                    400: v('brand-400'),
                    500: v('brand-500'),
                    600: v('brand-600'),
                    700: v('brand-700'),
                    800: v('brand-800'),
                    900: v('brand-900'),
                },

                // Semantic
                success: semantic('success'),
                warning: semantic('warning'),
                danger: semantic('danger'),
                info: semantic('info'),

                // Subject accents (tags / charts only)
                math: semantic('math'),
                physics: semantic('physics'),

                // Legacy aliases kept for existing pages (bg-primary/10, ring-primary/20 …)
                primary: v('brand-600'),
                admin: '#DC2626',
                teacher: '#059669',
                student: '#3B82F6',
            },
            // Plain `border` / `divide-y` use the token colour (theme aware)
            borderColor: {
                DEFAULT: v('border'),
            },
            fontFamily: {
                sans: ['"Inter Variable"', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            fontSize: {
                13: ['0.8125rem', { lineHeight: '1.25rem' }],
            },
            borderRadius: {
                sm: '6px',
                md: '10px',
                lg: '14px',
                xl: '20px',
            },
            boxShadow: {
                xs: 'var(--shadow-xs)',
                sm: 'var(--shadow-sm)',
                md: 'var(--shadow-md)',
                lg: 'var(--shadow-lg)',
            },
            maxWidth: {
                content: 'var(--content-max)',
            },
            spacing: {
                sidebar: 'var(--sidebar-w)',
                rail: 'var(--sidebar-w-rail)',
                topbar: 'var(--topbar-h)',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
            keyframes: {
                'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
                'scale-in': {
                    from: { opacity: '0', transform: 'scale(0.97) translateY(4px)' },
                    to: { opacity: '1', transform: 'scale(1) translateY(0)' },
                },
                'progress-indeterminate': {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(250%)' },
                },
            },
            animation: {
                'fade-in': 'fade-in 150ms ease-out',
                'scale-in': 'scale-in 180ms cubic-bezier(0.16, 1, 0.3, 1)',
                'progress-indeterminate': 'progress-indeterminate 1.4s ease-in-out infinite',
            },
            zIndex: {
                sidebar: '40',
                topbar: '30',
                drawer: '60',
                modal: '70',
                toast: '80',
                tooltip: '90',
            },
        },
    },
    plugins: [],
};
