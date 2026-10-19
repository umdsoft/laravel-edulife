<template>
    <transition enter-active-class="transition ease-out duration-200 transform"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="show" :role="type === 'error' || type === 'warning' ? 'alert' : 'status'"
            :aria-live="type === 'error' ? 'assertive' : 'polite'" :class="[
                inline ? 'relative' : 'fixed right-4 top-4 z-toast',
                'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-md border border-border bg-surface p-4 text-fg shadow-lg',
            ]" @mouseenter="pause" @mouseleave="resume">
            <!-- Icon -->
            <component :is="icons[type] || icons.info" :class="['mt-0.5 h-5 w-5 shrink-0', iconColor[type] || iconColor.info]"
                aria-hidden="true" />

            <!-- Message -->
            <div class="min-w-0 flex-1">
                <p v-if="title" class="text-sm font-semibold text-fg">{{ title }}</p>
                <p :class="['text-sm', title ? 'mt-0.5 text-fg-muted' : 'font-medium text-fg']">{{ message }}</p>
            </div>

            <!-- Close button -->
            <button type="button" aria-label="Yopish"
                class="focus-ring -m-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-sm text-fg-subtle hover:bg-surface-2 hover:text-fg"
                @click="close">
                <XMarkIcon class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </transition>
</template>

<script setup>
/**
 * Toast — transient notification. Backward compatible props: type, message, duration; emits close.
 * New: title, inline (no fixed positioning — used inside <ToastViewport>).
 * Prefer the store: `useToast().success('Saqlandi')` — layouts render the viewport.
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
    },
    message: {
        type: String,
        required: true,
    },
    duration: {
        type: Number,
        default: 5000,
    },
    title: { type: String, default: null },
    inline: Boolean,
});

const emit = defineEmits(['close']);

const icons = { success: CheckCircleIcon, error: XCircleIcon, warning: ExclamationTriangleIcon, info: InformationCircleIcon };
const iconColor = { success: 'text-success-600', error: 'text-danger-600', warning: 'text-warning-600', info: 'text-info-600' };

const show = ref(false);
let timeout = null;
let remaining = props.duration;
let startedAt = 0;

const clear = () => {
    if (timeout) clearTimeout(timeout);
    timeout = null;
};

const close = () => {
    show.value = false;
    clear();
    setTimeout(() => emit('close'), 200);
};

const start = () => {
    if (props.duration <= 0) return;
    startedAt = Date.now();
    timeout = setTimeout(close, remaining);
};
const pause = () => {
    if (!timeout) return;
    clear();
    remaining = Math.max(1000, remaining - (Date.now() - startedAt));
};
const resume = () => {
    if (show.value && !timeout) start();
};

onMounted(() => {
    show.value = true;
    start();
});
onBeforeUnmount(clear);
</script>
