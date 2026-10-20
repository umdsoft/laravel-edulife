<script setup>
/**
 * Multiple-choice answer list (native checkboxes, whole row clickable).
 * API unchanged: question, v-model (array of option ids). A null model is treated as [].
 * Options with a picture or a graph become a 2-column card grid (1 column under 360 px).
 */
import MathText from '@/Components/MathText.vue';
import QuestionImage from '@/Components/Media/QuestionImage.vue';
import { OPTION_GRID, optionHasMedia, optionsHaveMedia } from '@/Components/Media/optionMedia';
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

const toggleOption = (optionId) => {
    const newValue = [...selected.value];
    const index = newValue.indexOf(optionId);

    if (index === -1) {
        newValue.push(optionId);
    } else {
        newValue.splice(index, 1);
    }

    emit('update:modelValue', newValue);
};

const letter = (i) => String.fromCharCode(65 + i);
const grid = computed(() => optionsHaveMedia(props.question.options));
</script>

<template>
    <fieldset :class="grid ? OPTION_GRID : 'space-y-2.5'">
        <legend class="mb-1 text-13 text-fg-subtle">Bir nechta to'g'ri javobni belgilang</legend>
        <label v-for="(option, i) in question.options" :key="option.id" :class="[
            'relative flex cursor-pointer gap-3 rounded-md border p-3.5 transition-colors duration-150 sm:p-4',
            grid ? 'flex-col items-stretch' : 'items-start',
            selected.includes(option.id)
                ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600'
                : 'border-border bg-surface hover:border-border-strong hover:bg-surface-2/60',
        ]">
            <input type="checkbox" class="peer sr-only" :value="option.id" :checked="selected.includes(option.id)"
                @change="toggleOption(option.id)">
            <span class="pointer-events-none absolute inset-0 rounded-md peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-surface"
                aria-hidden="true" />
            <span :class="[
                'flex h-6 w-6 shrink-0 items-center justify-center rounded-sm border transition-colors',
                selected.includes(option.id) ? 'border-brand-600 bg-brand-600 text-white' : 'border-border-strong bg-surface text-transparent',
            ]" aria-hidden="true">
                <CheckIcon class="h-4 w-4" />
            </span>
            <span class="min-w-0 flex-1 text-[15px] leading-relaxed text-fg">
                <span class="mr-1.5 font-semibold text-fg-subtle">{{ letter(i) }}.</span><MathText :text="option.content" :zoom="optionHasMedia(option) ? 'badge' : 'tap'" />
                <QuestionImage v-if="option.image" :src="option.image" :alt="`${letter(i)} variant rasmi`" class="mt-2" />
            </span>
        </label>
    </fieldset>
</template>
