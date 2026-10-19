<script setup>
/**
 * Single-choice answer list. Native radios (arrow keys move between options), whole row clickable.
 * API unchanged: question, v-model (option id).
 */
import { CheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);
const letter = (i) => String.fromCharCode(65 + i);
</script>

<template>
    <div role="radiogroup" :aria-label="`Savol javob variantlari`" class="space-y-2.5">
        <label v-for="(option, i) in question.options" :key="option.id" :class="[
            'group relative flex cursor-pointer items-start gap-3 rounded-md border p-3.5 transition-colors duration-150 sm:p-4',
            modelValue === option.id
                ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600'
                : 'border-border bg-surface hover:border-border-strong hover:bg-surface-2/60',
        ]">
            <input type="radio" class="peer sr-only" :name="'question-' + question.id" :value="option.id"
                :checked="modelValue === option.id" @change="emit('update:modelValue', option.id)">
            <span class="pointer-events-none absolute inset-0 rounded-md peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-surface"
                aria-hidden="true" />
            <span :class="[
                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-13 font-semibold transition-colors',
                modelValue === option.id ? 'border-brand-600 bg-brand-600 text-white' : 'border-border-strong bg-surface text-fg-muted',
            ]" aria-hidden="true">
                <CheckIcon v-if="modelValue === option.id" class="h-4 w-4" />
                <template v-else>{{ letter(i) }}</template>
            </span>
            <span class="min-w-0 flex-1 pt-0.5 text-[15px] leading-relaxed text-fg">
                <span class="sr-only">{{ letter(i) }}. </span>{{ option.content }}
            </span>
        </label>
    </div>
</template>
