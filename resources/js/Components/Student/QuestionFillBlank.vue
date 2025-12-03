<script setup>
defineProps({
    question: {
        type: Object,
        required: true
    },
    modelValue: {
        type: String,
        default: ''
    }
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-4">
        <div class="relative">
            <input type="text" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm p-4"
                placeholder="Javobingizni yozing..." />
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Javobni aniq va xatosiz yozishga harakat qiling. Katta-kichik harflar farq qilmaydi.
        </p>
    </div>
</template>
