<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    question: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

// Parse matching pairs from question options or content
// Assuming options structure: { left: [...], right: [...] } or similar
// For simplicity, let's assume question.options contains the pairs but shuffled
// Or we receive left items and right items separately.
// Let's assume question.options has 'left' and 'right' arrays for this demo.
// If not, we'll adapt. Based on typical structure, options might be a flat list with 'column' attribute.

// Let's assume options are flat and have 'column' ('left' or 'right').
const leftItems = props.question.options.filter(o => o.column === 'left' || !o.column || o.is_left);
const rightItems = props.question.options.filter(o => o.column === 'right' || o.is_right);

const pairs = ref({ ...props.modelValue });

const updatePair = (leftId, rightId) => {
    pairs.value[leftId] = rightId;
    emit('update:modelValue', pairs.value);
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="space-y-4">
            <h4 class="font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">Savollar</h4>
            <div v-for="item in leftItems" :key="item.id"
                class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm flex items-center justify-between">
                <span class="text-sm font-medium">{{ item.content }}</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </div>
        </div>

        <div class="space-y-4">
            <h4 class="font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wider">Javoblar</h4>
            <div v-for="item in leftItems" :key="'select-' + item.id" class="h-[58px] flex items-center">
                <select :value="pairs[item.id]" @change="updatePair(item.id, $event.target.value)"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                    <option value="">Tanlang...</option>
                    <option v-for="right in rightItems" :key="right.id" :value="right.id">
                        {{ right.content }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>
