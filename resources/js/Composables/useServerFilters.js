import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Drop empty values so the URL only carries active filters
 * ({ search: '', status: 'active', page: 3 } → { status: 'active' }).
 * The page number is always dropped: a new filter starts from page 1.
 */
export function cleanFilters(filters) {
    const out = {};
    for (const [key, value] of Object.entries(filters || {})) {
        if (key === 'page') continue;
        if (value === null || value === undefined) continue;
        const v = typeof value === 'string' ? value.trim() : value;
        if (v === '' || v === false) continue;
        out[key] = v;
    }
    return out;
}

/**
 * Filters that are applied by the backend: changing a value reloads the
 * current Inertia page with the filters as query parameters (text fields are
 * debounced).
 *
 * @param {object} initial   filter values from the server (props.filters)
 * @param {string} url       page URL, e.g. route('teacher.students.index')
 * @param {object} options   { debounce: ms, only: [prop names to reload] }
 */
export function useServerFilters(initial, url, { debounce = 350, only } = {}) {
    const filters = reactive({ ...initial });
    let timer = null;

    const apply = () => {
        clearTimeout(timer);
        router.get(url, cleanFilters(filters), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            ...(only ? { only } : {}),
        });
    };

    watch(
        () => ({ ...filters }),
        (now, before) => {
            clearTimeout(timer);
            if (now.search !== before.search) {
                timer = setTimeout(apply, debounce);
            } else {
                apply();
            }
        },
    );

    const reset = () => {
        for (const key of Object.keys(filters)) filters[key] = '';
    };

    return { filters, apply, reset };
}
