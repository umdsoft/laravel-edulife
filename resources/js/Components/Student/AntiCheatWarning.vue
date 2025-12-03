<script setup>
import { ref } from 'vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    show: Boolean,
    warningsCount: Number,
    maxWarnings: {
        type: Number,
        default: 5
    }
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="show" maxWidth="md" @close="$emit('close')">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Diqqat! Taqiqlangan harakat
            </h3>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Siz test oynasidan chiqib ketdingiz yoki boshqa oynaga o'tdingiz. Bu test qoidalariga zid.
                <br><br>
                <span class="font-bold text-red-600">Ogohlantirish: {{ warningsCount }} / {{ maxWarnings }}</span>
                <br>
                Agar ogohlantirishlar soni limitga yetsa, test avtomatik ravishda yakunlanadi.
            </p>

            <button @click="$emit('close')"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                Tushundim, davom etish
            </button>
        </div>
    </Modal>
</template>
