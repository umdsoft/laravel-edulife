<script setup>
import { computed } from 'vue';

const props = defineProps({
    answer: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const question = computed(() => props.answer.question);
const isCorrect = computed(() => props.answer.is_correct);

const formatAnswer = (val) => {
    if (Array.isArray(val)) return val.join(', ');
    if (typeof val === 'boolean') return val ? "To'g'ri" : "Noto'g'ri";
    return val;
};

// Helper to get option label if available
const getOptionLabel = (id) => {
    const opt = question.value.options?.find(o => o.id === id);
    return opt ? opt.content : id;
};

const formattedUserAnswer = computed(() => {
    const val = props.answer.user_answer;
    if (['single_choice', 'multiple_choice'].includes(question.value.type)) {
        if (Array.isArray(val)) return val.map(getOptionLabel).join(', ');
        return getOptionLabel(val);
    }
    return formatAnswer(val);
});

const formattedCorrectAnswer = computed(() => {
    const val = props.answer.correct_answer;
    if (['single_choice', 'multiple_choice'].includes(question.value.type)) {
        if (Array.isArray(val)) return val.map(getOptionLabel).join(', ');
        return getOptionLabel(val);
    }
    return formatAnswer(val);
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-4 border-l-4"
        :class="isCorrect ? 'border-green-500' : 'border-red-500'">

        <div class="flex justify-between items-start mb-4">
            <h3 class="font-medium text-gray-900 dark:text-white">
                <span class="text-gray-500 mr-2">{{ index + 1 }}.</span>
                {{ question.content }}
            </h3>
            <span class="px-2 py-1 text-xs font-bold rounded"
                :class="isCorrect ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ isCorrect ? "To'g'ri" : "Noto'g'ri" }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="p-3 rounded bg-gray-50 dark:bg-gray-700">
                <span class="block text-xs text-gray-500 mb-1">Sizning javobingiz:</span>
                <span class="font-medium" :class="isCorrect ? 'text-green-600' : 'text-red-600'">
                    {{ formattedUserAnswer || '(Javob berilmagan)' }}
                </span>
            </div>

            <div v-if="!isCorrect" class="p-3 rounded bg-green-50 dark:bg-green-900/20">
                <span class="block text-xs text-gray-500 mb-1">To'g'ri javob:</span>
                <span class="font-medium text-green-600">
                    {{ formattedCorrectAnswer }}
                </span>
            </div>
        </div>

        <div v-if="question.explanation"
            class="mt-4 text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded">
            <strong class="block text-blue-700 dark:text-blue-300 mb-1">💡 Tushuntirish:</strong>
            {{ question.explanation }}
        </div>
    </div>
</template>
