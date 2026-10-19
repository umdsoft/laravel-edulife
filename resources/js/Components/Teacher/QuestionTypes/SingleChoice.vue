<script setup>
import { ref, useId, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);
const uid = useId();

// An empty array (new question / type switch) gets the default rows
const options = ref(props.modelValue?.length ? props.modelValue.map((o) => ({ ...o, is_correct: !!o.is_correct })) : [
    { option_text: '', is_correct: true },
    { option_text: '', is_correct: false },
]);

const addOption = () => {
    options.value.push({ option_text: '', is_correct: false });
    emit('update:modelValue', options.value);
};

const removeOption = (index) => {
    const wasCorrect = options.value[index]?.is_correct;
    options.value.splice(index, 1);
    if (wasCorrect && options.value.length) options.value[0].is_correct = true;
    emit('update:modelValue', options.value);
};

const setCorrect = (index) => {
    options.value.forEach((opt, i) => {
        opt.is_correct = i === index;
    });
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">Variantlarni yozing va to'g'ri javobni belgilang.</p>

        <div role="radiogroup" aria-label="To'g'ri javob" class="space-y-2">
            <div v-for="(option, index) in options" :key="index" :class="[
                'flex items-center gap-3 rounded-md border px-3 py-2 transition-colors duration-150',
                option.is_correct ? 'border-success-100 bg-success-50' : 'border-border bg-surface',
            ]">
                <input type="radio" :name="`correct-${uid}`" :checked="option.is_correct" :aria-label="`${index + 1}-variant — to'g'ri javob`"
                    class="h-4 w-4 shrink-0 cursor-pointer accent-success-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    @change="setCorrect(index)">
                <input v-model="option.option_text" type="text" :aria-label="`${index + 1}-variant matni`"
                    :class="[controlBase, controlState(false), controlSize.md, 'min-w-0 flex-1']" :placeholder="`${index + 1}-variant`" required>
                <span v-if="option.is_correct" class="hidden text-xs font-medium text-success sm:inline">To'g'ri</span>
                <IconButton v-if="options.length > 2" :icon="TrashIcon" :label="`${index + 1}-variantni o'chirish`" variant="danger" size="sm"
                    @click="removeOption(index)" />
            </div>
        </div>

        <Button variant="ghost" size="sm" :icon-left="PlusIcon" @click="addOption">Variant qo'shish</Button>
    </div>
</template>
