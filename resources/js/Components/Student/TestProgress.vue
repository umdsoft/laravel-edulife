<script setup>
import { computed } from 'vue';

const props = defineProps({
    questions: {
        type: Array,
        required: true
    },
    currentQuestionIndex: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['navigate']);

const progressPercentage = computed(() => {
    const answered = props.questions.filter(q => q.is_answered).length;
    return Math.round((answered / props.questions.length) * 100);
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-500 dark:text-gray-400">Progress</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ progressPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-purple-600 h-2.5 rounded-full transition-all duration-300"
                    :style="{ width: `${progressPercentage}%` }"></div>
            </div>
        </div>

        <div class="grid grid-cols-5 gap-2">
            <button v-for="(question, index) in questions" :key="question.id" @click="$emit('navigate', index)"
                class="w-full aspect-square flex items-center justify-center rounded text-sm font-medium transition-colors relative"
                :class="[
                    index === currentQuestionIndex
                        ? 'ring-2 ring-purple-500 ring-offset-2 dark:ring-offset-gray-800'
                        : '',
                    question.is_answered
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]">
                {{ index + 1 }}
                <span v-if="question.is_flagged" class="absolute top-0 right-0 -mt-1 -mr-1 text-xs">🚩</span>
            </button>
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400 justify-center">
            <div class="flex items-center gap-1">
                <div class="w-3 h-3 bg-green-100 border border-green-200 rounded"></div> Answered
            </div>
            <div class="flex items-center gap-1">
                <div class="w-3 h-3 bg-gray-100 border border-gray-200 rounded"></div> Unanswered
            </div>
            <div class="flex items-center gap-1">
                <span class="text-xs">🚩</span> Flagged
            </div>
        </div>
    </div>
</template>
