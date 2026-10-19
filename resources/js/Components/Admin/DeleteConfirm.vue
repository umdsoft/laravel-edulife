<script setup>
/**
 * DeleteConfirm — confirmation dialog for destructive actions.
 *   <DeleteConfirm :show="open" :loading="busy" message="… o'chirilsinmi?" @confirm="destroy" @cancel="open = false" />
 * Props: show, title, message, loading, confirmText (default "O'chirish").  Emits: confirm, cancel.
 */
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    show: Boolean,
    title: String,
    message: String,
    loading: Boolean,
    confirmText: { type: String, default: 'O\'chirish' },
});

const emit = defineEmits(['confirm', 'cancel']);

const confirm = () => emit('confirm');
const cancel = () => {
    if (!props.loading) emit('cancel');
};
</script>

<template>
    <Modal :show="show" size="md" :closeable="!loading" @close="cancel">
        <div class="flex items-start gap-4 px-5 pb-2 pt-6 sm:px-6">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-50 text-danger">
                <ExclamationTriangleIcon class="h-5 w-5" aria-hidden="true" />
            </span>
            <div class="min-w-0 pt-0.5">
                <h2 id="modal-title" class="text-base font-semibold text-fg">
                    {{ title || 'O\'chirishni tasdiqlang' }}
                </h2>
                <p class="mt-1.5 text-sm text-fg-muted">
                    {{ message || 'Bu amalni bekor qilib bo\'lmaydi. Davom etasizmi?' }}
                </p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 px-5 pb-5 pt-4 sm:flex-row sm:justify-end sm:px-6">
            <Button variant="secondary" :disabled="loading" @click="cancel">Bekor qilish</Button>
            <Button variant="danger" :loading="loading" @click="confirm">{{ confirmText }}</Button>
        </div>
    </Modal>
</template>
