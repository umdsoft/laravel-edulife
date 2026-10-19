import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { pushFlash } from './useToast';

/**
 * Show Inertia flash messages (success / error / warning / info) as toasts.
 * Called once by every layout.
 */
export function useFlashToasts() {
    const page = usePage();
    watch(
        () => page.props.flash,
        (flash) => pushFlash(flash),
        { deep: true, immediate: true },
    );
}
