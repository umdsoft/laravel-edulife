import { ref, readonly } from 'vue';

// Same key the old StudentLayout used, so existing preferences are kept.
export const THEME_STORAGE_KEY = 'edulife_dark_mode';

const isDark = ref(false);

function hasDom() {
    return typeof document !== 'undefined';
}

function readSaved() {
    try {
        return typeof localStorage !== 'undefined' && localStorage.getItem(THEME_STORAGE_KEY) === 'true';
    } catch {
        return false;
    }
}

function apply(value) {
    isDark.value = !!value;
    if (hasDom()) {
        document.documentElement.classList.toggle('dark', isDark.value);
    }
}

/** Read the saved preference and apply it to <html>. Safe to call many times. */
export function initTheme() {
    apply(readSaved());
}

/**
 * Light/dark theme state shared by every layout.
 *   const { isDark, toggle, setDark } = useTheme();
 */
export function useTheme() {
    const setDark = (value) => {
        apply(value);
        try {
            localStorage.setItem(THEME_STORAGE_KEY, String(isDark.value));
        } catch {
            // storage unavailable (private mode) — keep in-memory state only
        }
    };

    const toggle = () => setDark(!isDark.value);

    return { isDark: readonly(isDark), toggle, setDark };
}
