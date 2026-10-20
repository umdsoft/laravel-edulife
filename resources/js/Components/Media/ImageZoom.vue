<script setup>
/**
 * ImageZoom — full-screen view of a question image (tap an image in RichContent / MathText).
 *   <ImageZoom v-model:src="zoomSrc" :alt="zoomAlt" />
 * Tap the picture to switch between "fit to screen" and its real size (scrollable); Esc or ✕ closes.
 */
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    src: { type: String, default: '' },
    alt: { type: String, default: '' },
});
const emit = defineEmits(['update:src']);

const full = ref(false);
const closeBtn = ref(null);
let lastFocus = null;

const close = () => emit('update:src', '');
const onKey = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();
        close();
    }
};

watch(() => props.src, (src) => {
    full.value = false;
    if (src) {
        lastFocus = document.activeElement;
        document.addEventListener('keydown', onKey);
        nextTick(() => closeBtn.value?.focus());
    } else {
        document.removeEventListener('keydown', onKey);
        lastFocus?.focus?.();
        lastFocus = null;
    }
});
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div v-if="src" class="fixed inset-0 z-[90] flex flex-col bg-overlay/90" role="dialog" aria-modal="true" :aria-label="alt || 'Rasm'"
            @click.self="close">
            <div class="flex items-center justify-between gap-3 px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))] text-white">
                <p class="min-w-0 truncate text-sm">{{ alt }}</p>
                <button ref="closeBtn" type="button" class="focus-ring inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 hover:bg-white/20"
                    aria-label="Yopish" @click="close">
                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
            </div>
            <div :class="['min-h-0 flex-1 px-2 pb-[max(0.75rem,env(safe-area-inset-bottom))]', full ? 'overflow-auto' : 'flex items-center justify-center overflow-hidden']"
                @click.self="close">
                <img :src="src" :alt="alt" :class="[
                    'select-none rounded-md bg-white',
                    full ? 'max-w-none cursor-zoom-out' : 'max-h-full max-w-full cursor-zoom-in object-contain',
                ]" style="touch-action: pinch-zoom" @click="full = !full" />
            </div>
        </div>
    </Teleport>
</template>
