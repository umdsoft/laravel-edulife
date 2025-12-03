<script setup>
defineProps({
    question: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Boolean,
        default: null
    }
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="grid grid-cols-2 gap-4">
        <button type="button" class="p-6 rounded-lg border-2 text-center transition-all duration-200" :class="modelValue === true
            ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
            : 'border-gray-200 hover:border-green-300 dark:border-gray-700 dark:text-gray-300'"
            @click="$emit('update:modelValue', true)">
            <span class="text-2xl block mb-2">✅</span>
            <span class="font-bold">TO'G'RI</span>
        </button>

        <button type="button" class="p-6 rounded-lg border-2 text-center transition-all duration-200" :class="modelValue === false
            ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'
            : 'border-gray-200 hover:border-red-300 dark:border-gray-700 dark:text-gray-300'"
            @click="$emit('update:modelValue', false)">
            <span class="text-2xl block mb-2">❌</span>
            <span class="font-bold">NOTO'G'RI</span>
        </button>
    </div>
</template>
