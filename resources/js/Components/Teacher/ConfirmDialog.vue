<script setup>
/**
 * ConfirmDialog — standard "are you sure?" modal for destructive teacher actions.
 *   <ConfirmDialog :show="!!toDelete" title="Darsni o'chirish" description="Bu amalni qaytarib bo'lmaydi."
 *                  confirm-label="O'chirish" :loading="busy" @confirm="destroy" @close="toDelete = null" />
 */
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

defineProps({
    show: Boolean,
    title: { type: String, default: 'Ishonchingiz komilmi?' },
    description: { type: String, default: null },
    confirmLabel: { type: String, default: 'O\'chirish' },
    cancelLabel: { type: String, default: 'Bekor qilish' },
    // danger (default) | primary
    tone: { type: String, default: 'danger' },
    loading: Boolean,
});

const emit = defineEmits(['confirm', 'close']);
</script>

<template>
    <Modal :show="show" size="md" :closeable="!loading" @close="emit('close')">
        <div class="flex gap-4 px-5 pb-2 pt-6 sm:px-6">
            <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                tone === 'danger' ? 'bg-danger-50 text-danger' : 'bg-brand-50 text-brand']">
                <ExclamationTriangleIcon class="h-5 w-5" aria-hidden="true" />
            </span>
            <div class="min-w-0 pt-1.5">
                <h2 id="modal-title" class="text-base font-semibold text-fg">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-fg-muted">{{ description }}</p>
                <slot />
            </div>
        </div>
        <div class="mt-4 flex flex-col-reverse gap-2 border-t border-border bg-surface-2/50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
            <Button variant="secondary" :disabled="loading" autofocus @click="emit('close')">{{ cancelLabel }}</Button>
            <Button :variant="tone === 'danger' ? 'danger' : 'primary'" :loading="loading" @click="emit('confirm')">
                {{ confirmLabel }}
            </Button>
        </div>
    </Modal>
</template>
