<script setup>
import { ref, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ArrowRightIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

// An empty array (new question / type switch) gets the default rows
const options = ref(props.modelValue?.length ? props.modelValue : [
    { option_text: '', match_text: '', is_correct: true },
    { option_text: '', match_text: '', is_correct: true },
]);

const addPair = () => {
    options.value.push({ option_text: '', match_text: '', is_correct: true });
    emit('update:modelValue', options.value);
};

const removePair = (index) => {
    options.value.splice(index, 1);
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });

const inputClass = [controlBase, controlState(false), controlSize.md, 'min-w-0'];
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">Har bir juftlikni yozing — o'quvchiga o'ng tomon aralashtirilgan holda ko'rsatiladi.</p>

        <div class="hidden grid-cols-[minmax(0,1fr)_1.25rem_minmax(0,1fr)_2rem] gap-2 px-1 text-xs font-medium uppercase tracking-wide text-fg-subtle sm:grid">
            <span>Chap tomon</span><span /><span>Mos javob</span><span />
        </div>

        <ol class="space-y-2">
            <li v-for="(option, index) in options" :key="index"
                class="grid grid-cols-[minmax(0,1fr)_2rem] items-center gap-2 rounded-md border border-border bg-surface p-2 sm:grid-cols-[minmax(0,1fr)_1.25rem_minmax(0,1fr)_2rem] sm:border-0 sm:bg-transparent sm:p-0">
                <input v-model="option.option_text" type="text" :class="inputClass" :placeholder="`${index + 1}. Savol tomoni`"
                    :aria-label="`${index + 1}-juftlik: chap tomon`" required>
                <ArrowRightIcon class="hidden h-5 w-5 justify-self-center text-fg-subtle sm:block" aria-hidden="true" />
                <input v-model="option.match_text" type="text" :class="[...inputClass, 'col-start-1 sm:col-start-auto']" placeholder="Mos javob"
                    :aria-label="`${index + 1}-juftlik: mos javob`" required>
                <IconButton v-if="options.length > 2" :icon="TrashIcon" :label="`${index + 1}-juftlikni o'chirish`" variant="danger" size="sm"
                    class="col-start-2 row-span-2 row-start-1 sm:col-start-auto sm:row-span-1 sm:row-start-auto" @click="removePair(index)" />
            </li>
        </ol>

        <Button variant="ghost" size="sm" :icon-left="PlusIcon" @click="addPair">Juftlik qo'shish</Button>
    </div>
</template>
