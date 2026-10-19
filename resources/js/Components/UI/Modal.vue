<script setup>
/**
 * Modal — accessible dialog (Teleport to body, Escape / backdrop to close, focus trap,
 * focus restore, body scroll lock).
 *
 * Backward compatible: :show, :closeable, @close, default slot (rendered as-is when no
 * `title` is given, so existing modals that bring their own padding look the same).
 *
 * New:
 *   title / description → standard header with close button; body gets padding.
 *   maxWidth (or `size`): sm | md | lg (default) | xl | 2xl | 3xl | 4xl | full
 *   #footer slot → right-aligned action bar.
 *
 *   <Modal :show="open" title="Kursni o'chirish" @close="open = false">
 *     Rostdan ham o'chirmoqchimisiz?
 *     <template #footer>
 *       <Button variant="secondary" @click="open = false">Bekor qilish</Button>
 *       <Button variant="danger" :loading="form.processing" @click="destroy">O'chirish</Button>
 *     </template>
 *   </Modal>
 */
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    closeable: { type: Boolean, default: true },
    title: { type: String, default: null },
    description: { type: String, default: null },
    maxWidth: { type: String, default: null },
    size: { type: String, default: null },
    hideClose: Boolean,
});

const emit = defineEmits(['close']);

const panel = ref(null);
const uid = useId();
const titleId = `modal-title-${uid}`;
const descId = `modal-desc-${uid}`;
let lastFocused = null;

const widths = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
    '5xl': 'sm:max-w-5xl',
    full: 'sm:max-w-[calc(100vw-4rem)]',
};
const widthClass = computed(() => widths[props.maxWidth || props.size || 'lg'] || widths.lg);
const hasHeader = computed(() => !!props.title);

const close = () => {
    if (props.closeable) emit('close');
};

const FOCUSABLE =
    'a[href],button:not([disabled]),textarea:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),[tabindex]:not([tabindex="-1"])';

const focusables = () => (panel.value ? Array.from(panel.value.querySelectorAll(FOCUSABLE)).filter((el) => el.offsetParent !== null || el === document.activeElement) : []);

const onKeydown = (e) => {
    if (!props.show) return;
    if (e.key === 'Escape') {
        e.stopPropagation();
        close();
        return;
    }
    if (e.key === 'Tab') {
        const items = focusables();
        if (!items.length) {
            e.preventDefault();
            panel.value?.focus();
            return;
        }
        const first = items[0];
        const last = items[items.length - 1];
        if (e.shiftKey && (document.activeElement === first || document.activeElement === panel.value)) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }
};

const lock = () => {
    lastFocused = document.activeElement;
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', onKeydown);
    nextTick(() => {
        if (!panel.value) return;
        const auto = panel.value.querySelector('[autofocus]');
        const target = auto || focusables().find((el) => !el.dataset.modalClose) || panel.value;
        target.focus({ preventScroll: true });
    });
};

const unlock = () => {
    document.body.style.overflow = null;
    document.removeEventListener('keydown', onKeydown);
    if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus({ preventScroll: true });
    }
    lastFocused = null;
};

watch(
    () => props.show,
    (show) => (show ? lock() : unlock()),
    { immediate: typeof document !== 'undefined' && props.show },
);

onBeforeUnmount(() => {
    if (props.show) unlock();
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-modal overflow-y-auto" role="dialog" aria-modal="true"
                :aria-labelledby="hasHeader ? titleId : 'modal-title'"
                :aria-describedby="description ? descId : undefined">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-overlay/40 backdrop-blur-[2px]" aria-hidden="true" @click="close" />

                <div class="flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
                    <div ref="panel" tabindex="-1" :class="[
                        'relative w-full overflow-hidden bg-surface text-left text-fg shadow-lg outline-none',
                        'rounded-t-xl sm:rounded-xl border border-border animate-scale-in',
                        'max-h-[92vh] flex flex-col',
                        widthClass,
                    ]">
                        <div v-if="hasHeader" class="flex items-start justify-between gap-4 px-5 pb-2 pt-5 sm:px-6">
                            <div class="min-w-0">
                                <h2 :id="titleId" class="text-lg font-semibold text-fg">{{ title }}</h2>
                                <p v-if="description" :id="descId" class="mt-1 text-sm text-fg-muted">{{ description }}</p>
                            </div>
                            <button v-if="closeable && !hideClose" type="button" data-modal-close="1"
                                class="focus-ring -mr-2 -mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-fg-subtle hover:bg-surface-2 hover:text-fg"
                                aria-label="Yopish" @click="close">
                                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>

                        <div :class="['min-h-0 flex-1 overflow-y-auto', hasHeader ? 'px-5 py-3 sm:px-6' : '']">
                            <slot />
                        </div>

                        <div v-if="$slots.footer"
                            class="flex flex-col-reverse gap-2 border-t border-border bg-surface-2/50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                            <slot name="footer" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
