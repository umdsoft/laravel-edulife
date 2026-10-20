<script setup>
import { ref, useId, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { controlBase, controlState } from '@/Components/UI/fieldStyles';
import MathText from '@/Components/MathText.vue';
import MediaTools from '@/Components/Media/MediaTools.vue';
import { hasMedia } from '@/Utils/richText';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);
// option text fields and their "Rasm" / "Grafik" tools (pictures and graphs as options)
const inputs = [];
const tools = [];
const rowsFor = (t) => Math.min(12, String(t || '').split('\n').length);
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
                'flex flex-wrap items-center gap-x-3 gap-y-2 rounded-md border px-3 py-2 transition-colors duration-150',
                option.is_correct ? 'border-success-100 bg-success-50' : 'border-border bg-surface',
            ]">
                <input type="radio" :name="`correct-${uid}`" :checked="option.is_correct" :aria-label="`${index + 1}-variant — to'g'ri javob`"
                    class="h-4 w-4 shrink-0 cursor-pointer accent-success-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    @change="setCorrect(index)">
                <textarea :ref="(el) => (inputs[index] = el)" v-model="option.option_text" :rows="rowsFor(option.option_text)" :aria-label="`${index + 1}-variant matni`"
                    :class="[controlBase, controlState(false), 'min-h-10 min-w-0 flex-1 resize-none px-3 py-2 text-sm leading-6']" :placeholder="`${index + 1}-variant`" required
                    @paste="tools[index]?.onPaste($event)" @drop="tools[index]?.onDrop($event)" @dragover="tools[index]?.onDragOver($event)" />
                <MediaTools :ref="(c) => (tools[index] = c)" v-model="option.option_text" compact inline :target="() => inputs[index]" />
                <span v-if="option.is_correct" class="hidden text-xs font-medium text-success sm:inline">To'g'ri</span>
                <IconButton v-if="options.length > 2" :icon="TrashIcon" :label="`${index + 1}-variantni o'chirish`" variant="danger" size="sm"
                    @click="removeOption(index)" />
                <div v-if="hasMedia(option.option_text)" class="basis-full pl-7">
                    <MathText :text="option.option_text" tag="div" zoom="badge" class="text-sm text-fg" />
                </div>
            </div>
        </div>

        <Button variant="ghost" size="sm" :icon-left="PlusIcon" @click="addOption">Variant qo'shish</Button>
    </div>
</template>
