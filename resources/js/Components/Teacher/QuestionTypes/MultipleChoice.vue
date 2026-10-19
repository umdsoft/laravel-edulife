<script setup>
import { computed, ref, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

// An empty array (new question / type switch) gets the default rows
// (DB rows may carry is_correct as 0/1 → normalise to booleans for the checkboxes)
const options = ref(props.modelValue?.length ? props.modelValue.map((o) => ({ ...o, is_correct: !!o.is_correct })) : [
    { option_text: '', is_correct: false },
    { option_text: '', is_correct: false },
]);

const correctCount = computed(() => options.value.filter((o) => o.is_correct).length);

const addOption = () => {
    options.value.push({ option_text: '', is_correct: false });
    emit('update:modelValue', options.value);
};

const removeOption = (index) => {
    options.value.splice(index, 1);
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">
            Bir nechta to'g'ri javobni belgilash mumkin.
            <span :class="correctCount ? 'text-success' : 'text-warning'" class="font-medium tabular-nums">
                Belgilangan: {{ correctCount }}
            </span>
        </p>

        <div class="space-y-2">
            <div v-for="(option, index) in options" :key="index" :class="[
                'flex items-center gap-3 rounded-md border px-3 py-2 transition-colors duration-150',
                option.is_correct ? 'border-success-100 bg-success-50' : 'border-border bg-surface',
            ]">
                <input v-model="option.is_correct" type="checkbox" :aria-label="`${index + 1}-variant — to'g'ri javob`"
                    class="h-4 w-4 shrink-0 cursor-pointer rounded-[4px] accent-success-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
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
