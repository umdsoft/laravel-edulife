<script setup>
/**
 * True / false answer — two large radio cards. API unchanged: question, v-model (Boolean | null).
 */
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Boolean,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const choices = [
    { value: true, label: "To'g'ri", icon: CheckIcon, on: 'border-success-600 bg-success-50 ring-1 ring-success-600', chip: 'bg-success-600 text-white' },
    { value: false, label: "Noto'g'ri", icon: XMarkIcon, on: 'border-danger-600 bg-danger-50 ring-1 ring-danger-600', chip: 'bg-danger-600 text-white' },
];
</script>

<template>
    <div role="radiogroup" aria-label="To'g'ri yoki noto'g'ri" class="grid grid-cols-2 gap-3 sm:gap-4">
        <label v-for="c in choices" :key="String(c.value)" :class="[
            'relative flex cursor-pointer flex-col items-center gap-2 rounded-lg border px-4 py-5 text-center transition-colors duration-150 sm:py-6',
            modelValue === c.value ? c.on : 'border-border bg-surface hover:border-border-strong hover:bg-surface-2/60',
        ]">
            <input type="radio" class="peer sr-only" :name="'question-' + question.id" :checked="modelValue === c.value"
                @change="emit('update:modelValue', c.value)">
            <span class="pointer-events-none absolute inset-0 rounded-lg peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-surface"
                aria-hidden="true" />
            <span :class="[
                'flex h-10 w-10 items-center justify-center rounded-full transition-colors',
                modelValue === c.value ? c.chip : 'bg-surface-2 text-fg-muted',
            ]" aria-hidden="true">
                <component :is="c.icon" class="h-5 w-5" />
            </span>
            <span class="text-sm font-semibold text-fg">{{ c.label }}</span>
        </label>
    </div>
</template>
