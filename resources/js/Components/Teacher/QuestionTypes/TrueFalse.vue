<script setup>
import { ref, useId, watch } from 'vue';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);
const uid = useId();

// Needs exactly the two rows (Rost / Yolg'on); anything else gets the defaults
const options = ref(props.modelValue?.length === 2 ? props.modelValue.map((o) => ({ ...o, is_correct: !!o.is_correct })) : [
    { option_text: 'Rost', is_correct: true },
    { option_text: 'Yolg\'on', is_correct: false },
]);

const setCorrect = (index) => {
    options.value.forEach((opt, i) => {
        opt.is_correct = i === index;
    });
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });

const choices = [
    { index: 0, label: 'Rost', icon: CheckCircleIcon },
    { index: 1, label: 'Yolg\'on', icon: XCircleIcon },
];
</script>

<template>
    <fieldset class="space-y-3">
        <legend class="mb-3 text-13 text-fg-subtle">Fikr to'g'rimi yoki noto'g'ri — to'g'ri javobni tanlang.</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            <label v-for="c in choices" :key="c.index" :class="[
                'flex cursor-pointer items-center gap-3 rounded-md border p-3 transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-2 focus-within:ring-offset-surface',
                options[c.index]?.is_correct ? 'border-success-100 bg-success-50' : 'border-border-strong bg-surface hover:bg-surface-2',
            ]">
                <input type="radio" :name="`tf-${uid}`" :checked="options[c.index]?.is_correct" class="sr-only" @change="setCorrect(c.index)">
                <component :is="c.icon" :class="['h-5 w-5 shrink-0', options[c.index]?.is_correct ? 'text-success' : 'text-fg-subtle']" aria-hidden="true" />
                <span class="text-sm font-medium text-fg">{{ c.label }}</span>
                <span v-if="options[c.index]?.is_correct" class="ml-auto text-xs font-medium text-success">To'g'ri javob</span>
            </label>
        </div>
    </fieldset>
</template>
