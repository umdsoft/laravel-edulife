<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    question: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

// Initialize with shuffled options if modelValue is empty
const items = ref(props.modelValue.length > 0
    ? props.modelValue.map(id => props.question.options.find(o => o.id === id))
    : [...props.question.options]);

const moveUp = (index) => {
    if (index === 0) return;
    const newItems = [...items.value];
    [newItems[index - 1], newItems[index]] = [newItems[index], newItems[index - 1]];
    items.value = newItems;
    emit('update:modelValue', newItems.map(i => i.id));
};

const moveDown = (index) => {
    if (index === items.value.length - 1) return;
    const newItems = [...items.value];
    [newItems[index + 1], newItems[index]] = [newItems[index], newItems[index + 1]];
    items.value = newItems;
    emit('update:modelValue', newItems.map(i => i.id));
};
</script>

<template>
    <div class="space-y-2">
        <div v-for="(item, index) in items" :key="item.id"
            class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm transition-all hover:shadow-md">
            <div class="flex flex-col gap-1">
                <button @click="moveUp(index)" :disabled="index === 0"
                    class="p-1 text-gray-400 hover:text-purple-600 disabled:opacity-30 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
                <button @click="moveDown(index)" :disabled="index === items.length - 1"
                    class="p-1 text-gray-400 hover:text-purple-600 disabled:opacity-30 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <div class="flex-1">
                <span
                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 text-purple-800 text-xs font-bold mr-3">
                    {{ index + 1 }}
                </span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ item.content }}</span>
            </div>

            <div class="text-gray-400 cursor-move">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </div>
        </div>
    </div>
</template>
