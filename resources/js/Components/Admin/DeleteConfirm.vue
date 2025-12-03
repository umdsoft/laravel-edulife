<template>
    <Modal :show="show" :title="title || 'O\'chirishni tasdiqlang'" size="sm" @close="cancel">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-700">
                        {{ message || 'Bu amalni bekor qilish mumkin emas. Davom etishni xohlaysizmi?' }}
                    </p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3">
                <Button variant="secondary" @click="cancel" :disabled="loading">
                    Bekor qilish
                </Button>
                <Button variant="danger" @click="confirm" :loading="loading">
                    O'chirish
                </Button>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import Modal from './Modal.vue';
import Button from '../UI/Button.vue';

defineProps({
    show: Boolean,
    title: String,
    message: String,
    loading: Boolean,
});

const emit = defineEmits(['confirm', 'cancel']);

const confirm = () => {
    emit('confirm');
};

const cancel = () => {
    emit('cancel');
};
</script>
