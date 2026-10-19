import { onBeforeUnmount, ref } from 'vue';

/**
 * Reactive media query. Value is correct on first render (read synchronously).
 *   const isDesktop = useMediaQuery('(min-width: 1024px)');
 */
export function useMediaQuery(query) {
    const supported = typeof window !== 'undefined' && typeof window.matchMedia === 'function';
    const mql = supported ? window.matchMedia(query) : null;
    const matches = ref(mql ? mql.matches : false);

    if (mql) {
        const onChange = (e) => (matches.value = e.matches);
        if (mql.addEventListener) mql.addEventListener('change', onChange);
        else mql.addListener(onChange);
        onBeforeUnmount(() => {
            if (mql.removeEventListener) mql.removeEventListener('change', onChange);
            else mql.removeListener(onChange);
        });
    }

    return matches;
}

// Tailwind breakpoints
export const BREAKPOINTS = { sm: 640, md: 768, lg: 1024, xl: 1280 };
