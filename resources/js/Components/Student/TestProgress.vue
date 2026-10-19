<script setup>
/**
 * Question navigator: answered / current / flagged state for every question.
 * API unchanged: questions [{ id, is_answered, is_flagged }], currentQuestionIndex, emits navigate(index).
 */
import { computed } from 'vue';
import { FlagIcon } from '@heroicons/vue/20/solid';
import ProgressBar from '@/Components/UI/ProgressBar.vue';

const props = defineProps({
    questions: {
        type: Array,
        required: true,
    },
    currentQuestionIndex: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['navigate']);

const answeredCount = computed(() => props.questions.filter(q => q.is_answered).length);
const flaggedCount = computed(() => props.questions.filter(q => q.is_flagged).length);
const progressPercentage = computed(() =>
    props.questions.length ? Math.round((answeredCount.value / props.questions.length) * 100) : 0,
);

const describe = (q, i) => {
    const parts = [`Savol ${i + 1}`, q.is_answered ? 'javob berilgan' : 'javob berilmagan'];
    if (q.is_flagged) parts.push('belgilangan');
    return parts.join(', ');
};
</script>

<template>
    <div>
        <div class="mb-4">
            <div class="mb-1.5 flex items-baseline justify-between text-13">
                <span class="text-fg-muted">Javob berildi</span>
                <span class="font-semibold text-fg tabular-nums">{{ answeredCount }} / {{ questions.length }}</span>
            </div>
            <ProgressBar :value="progressPercentage" size="sm" />
        </div>

        <nav aria-label="Savollar bo'yicha o'tish">
            <ol class="grid grid-cols-5 gap-2 sm:grid-cols-6 lg:grid-cols-5">
                <li v-for="(question, index) in questions" :key="question.id">
                    <button type="button" :aria-label="describe(question, index)"
                        :aria-current="index === currentQuestionIndex ? 'step' : undefined" :class="[
                            'focus-ring relative flex h-10 w-full items-center justify-center rounded-md border text-13 font-semibold tabular-nums transition-colors duration-150',
                            index === currentQuestionIndex
                                ? 'border-brand-600 bg-brand-600 text-white shadow-xs'
                                : question.is_answered
                                    ? 'border-brand-200 bg-brand-50 text-brand hover:border-brand-300'
                                    : 'border-border bg-surface text-fg-muted hover:border-border-strong hover:bg-surface-2',
                        ]" @click="emit('navigate', index)">
                        {{ index + 1 }}
                        <FlagIcon v-if="question.is_flagged"
                            class="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-surface p-px text-warning-600" aria-hidden="true" />
                    </button>
                </li>
            </ol>
        </nav>

        <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-fg-subtle" aria-label="Belgilar">
            <li class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-sm border border-brand-200 bg-brand-50" aria-hidden="true" /> Javob berilgan
            </li>
            <li class="flex items-center gap-1.5">
                <span class="h-3 w-3 rounded-sm border border-border bg-surface" aria-hidden="true" /> Javobsiz
            </li>
            <li class="flex items-center gap-1.5">
                <FlagIcon class="h-3.5 w-3.5 text-warning-600" aria-hidden="true" /> Belgilangan
                <span v-if="flaggedCount" class="tabular-nums">({{ flaggedCount }})</span>
            </li>
        </ul>
    </div>
</template>
