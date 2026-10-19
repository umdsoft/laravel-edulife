import { reactive } from 'vue';

/**
 * Global toast store. Every layout renders <ToastViewport />, which shows these.
 *
 *   import { useToast } from '@/Composables/useToast';
 *   const toast = useToast();
 *   toast.success("Saqlandi");
 *   toast.error("Xatolik yuz berdi", { title: 'Diqqat', duration: 8000 });
 *
 * Flash messages (page.props.flash.success|error|warning|info) are pushed
 * automatically by the layouts via `pushFlash()`.
 */
const state = reactive({ items: [] });
let seq = 0;

const TYPES = ['success', 'error', 'warning', 'info'];

export function pushToast({ type = 'info', message, title = null, duration = 5000 } = {}) {
    if (!message) return null;
    const safeType = TYPES.includes(type) ? type : 'info';
    // Avoid stacking the exact same message twice at once
    const dup = state.items.find((t) => t.type === safeType && t.message === message);
    if (dup) return dup.id;
    const id = ++seq;
    state.items.push({ id, type: safeType, message: String(message), title, duration });
    // keep the stack short
    if (state.items.length > 4) state.items.shift();
    return id;
}

export function dismissToast(id) {
    const i = state.items.findIndex((t) => t.id === id);
    if (i !== -1) state.items.splice(i, 1);
}

export function clearToasts() {
    state.items.splice(0, state.items.length);
}

/** Push every message present in an Inertia `flash` object. */
export function pushFlash(flash) {
    if (!flash || typeof flash !== 'object') return;
    for (const type of TYPES) {
        if (flash[type]) pushToast({ type, message: flash[type] });
    }
}

export function useToast() {
    return {
        toasts: state.items,
        push: pushToast,
        dismiss: dismissToast,
        clear: clearToasts,
        success: (message, opts = {}) => pushToast({ ...opts, type: 'success', message }),
        error: (message, opts = {}) => pushToast({ ...opts, type: 'error', message }),
        warning: (message, opts = {}) => pushToast({ ...opts, type: 'warning', message }),
        info: (message, opts = {}) => pushToast({ ...opts, type: 'info', message }),
    };
}
