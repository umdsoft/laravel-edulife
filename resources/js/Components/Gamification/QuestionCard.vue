<script setup>
defineProps({
    question: Object,
    selectedOptionId: Number,
    disabled: Boolean,
});

const emit = defineEmits(['select']);
</script>

<template>
    <div class="w-full max-w-2xl mx-auto">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm mb-6 text-center">
            <h2 class="text-xl font-bold text-gray-900">{{ question.content }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <button v-for="option in question.options" :key="option.id" @click="$emit('select', option.id)"
                :disabled="disabled"
                class="p-4 rounded-xl border-2 text-left transition-all relative overflow-hidden group" :class="[
                    selectedOptionId === option.id
                        ? 'border-purple-600 bg-purple-50 text-purple-700'
                        : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50 text-gray-700'
                ]">
                <span class="font-medium relative z-10">{{ option.content }}</span>
            </button>
        </div>
    </div>
</template>
