<script setup>
import Modal from '@/Components/UI/Modal.vue';

defineProps({
    show: Boolean,
    unansweredCount: Number
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" maxWidth="sm" @close="$emit('close')">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 mb-4">
                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Testni topshirish
            </h3>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Testni haqiqatan ham topshirmoqchimisiz?
                <span v-if="unansweredCount > 0" class="block mt-2 text-yellow-600 font-medium">
                    ⚠️ Sizda {{ unansweredCount }} ta javoblanmagan savol qoldi.
                </span>
            </p>

            <div class="flex gap-3 justify-center">
                <button @click="$emit('close')"
                    class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    Bekor qilish
                </button>
                <button @click="$emit('confirm')"
                    class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:text-sm">
                    Ha, topshirish
                </button>
            </div>
        </div>
    </Modal>
</template>
