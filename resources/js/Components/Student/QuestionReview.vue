<script setup>
import MathText from '@/Components/MathText.vue';
import { computed } from 'vue';
import { CheckCircleIcon, XCircleIcon, LightBulbIcon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    answer: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
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

// choice answers may be pictures / graphs: each chosen option is rendered with MathText
const isChoice = computed(() => ['single_choice', 'multiple_choice'].includes(question.value.type));
const optionTexts = (val) => (Array.isArray(val) ? val : (val === null || val === undefined || val === '' ? [] : [val])).map(getOptionLabel).map(String);
const userAnswerTexts = computed(() => optionTexts(props.answer.user_answer));
const correctAnswerTexts = computed(() => optionTexts(props.answer.correct_answer));

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
    <article class="overflow-hidden rounded-lg border border-border bg-surface shadow-xs">
        <header class="flex items-start gap-3 border-b border-border px-5 py-4">
            <span :class="[
                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-13 font-semibold tabular-nums',
                isCorrect ? 'bg-success-50 text-success' : 'bg-danger-50 text-danger',
            ]">{{ index + 1 }}</span>
            <MathText :text="question.content" tag="h3" class="min-w-0 flex-1 pt-0.5 text-[15px] font-medium leading-relaxed text-fg" />
            <Badge :variant="isCorrect ? 'success' : 'danger'" size="sm" :icon="isCorrect ? CheckCircleIcon : XCircleIcon"
                class="shrink-0">
                {{ isCorrect ? "To'g'ri" : "Noto'g'ri" }}
            </Badge>
        </header>

        <div class="grid gap-3 p-5 text-sm sm:grid-cols-2">
            <div :class="['rounded-md border p-3', isCorrect ? 'border-success-100 bg-success-50' : 'border-danger-100 bg-danger-50']">
                <span class="block text-xs font-medium text-fg-subtle">Sizning javobingiz</span>
                <span v-if="isChoice && userAnswerTexts.length" class="mt-1 block space-y-1 font-medium" :class="isCorrect ? 'text-success' : 'text-danger'">
                    <MathText v-for="(t, i) in userAnswerTexts" :key="i" :text="t" tag="span" class="block" />
                </span>
                <span v-else class="mt-1 block font-medium" :class="isCorrect ? 'text-success' : 'text-danger'">
                    {{ formattedUserAnswer || '(Javob berilmagan)' }}
                </span>
            </div>

            <div v-if="!isCorrect" class="rounded-md border border-success-100 bg-success-50 p-3">
                <span class="block text-xs font-medium text-fg-subtle">To'g'ri javob</span>
                <span v-if="isChoice && correctAnswerTexts.length" class="mt-1 block space-y-1 font-medium text-success">
                    <MathText v-for="(t, i) in correctAnswerTexts" :key="i" :text="t" tag="span" class="block" />
                </span>
                <span v-else class="mt-1 block font-medium text-success">{{ formattedCorrectAnswer }}</span>
            </div>
        </div>

        <div v-if="question.explanation" class="mx-5 mb-5 flex gap-2.5 rounded-md bg-info-50 p-3 text-sm text-fg-muted">
            <LightBulbIcon class="mt-0.5 h-4 w-4 shrink-0 text-info" aria-hidden="true" />
            <div>
                <strong class="block font-semibold text-fg">Tushuntirish</strong>
                <MathText :text="question.explanation" tag="p" class="mt-0.5 leading-relaxed" />
            </div>
        </div>
    </article>
</template>
