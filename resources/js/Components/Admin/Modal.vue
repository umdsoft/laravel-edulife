<script setup>
/**
 * Admin Modal — kept for backward compatibility; renders the UI kit Modal
 * (focus trap, Escape / backdrop close, scroll lock, bottom sheet on mobile).
 *   <Modal :show="open" title="Yangi paket" size="lg" @close="open = false">
 *     …form…
 *     <template #footer>…buttons…</template>
 *   </Modal>
 * size: sm | md (default) | lg | xl  (old scale; any UI Modal size such as "2xl" also works)
 */
import { computed } from 'vue';
import UiModal from '@/Components/UI/Modal.vue';

const props = defineProps({
    show: Boolean,
    title: String,
    description: { type: String, default: null },
    size: { type: String, default: 'md' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

// old Admin scale → UI Modal widths (sm 384, md 512, lg 672, xl 896)
const SIZE_MAP = { sm: 'sm', md: 'lg', lg: '2xl', xl: '4xl' };
const uiSize = computed(() => SIZE_MAP[props.size] || props.size || 'lg');
</script>

<template>
    <UiModal :show="show" :title="title || null" :description="description" :size="uiSize" :closeable="closeable"
        @close="emit('close')">
        <slot v-if="title" />
        <div v-else class="px-5 py-5 sm:px-6">
            <slot />
        </div>
        <template v-if="$slots.footer" #footer>
            <slot name="footer" />
        </template>
    </UiModal>
</template>
