<script setup>
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { PlusIcon, TrashIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/24/outline';
import { Bars2Icon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

// An empty array (new question / type switch) gets the default rows; saved rows come back in DB order → sort
const options = ref(props.modelValue?.length
    ? [...props.modelValue].sort((a, b) => (a.correct_position || 0) - (b.correct_position || 0))
    : [
        { option_text: '', correct_position: 1, is_correct: true },
        { option_text: '', correct_position: 2, is_correct: true },
        { option_text: '', correct_position: 3, is_correct: true },
    ]);

const updatePositions = () => {
    options.value.forEach((opt, index) => {
        opt.correct_position = index + 1;
    });
    emit('update:modelValue', options.value);
};

const addOption = () => {
    options.value.push({
        option_text: '',
        correct_position: options.value.length + 1,
        is_correct: true
    });
    emit('update:modelValue', options.value);
};

const removeOption = (index) => {
    options.value.splice(index, 1);
    updatePositions();
};

const move = (from, dir) => {
    const to = from + dir;
    if (to < 0 || to >= options.value.length) return;
    const [item] = options.value.splice(from, 1);
    options.value.splice(to, 0, item);
    updatePositions();
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });

// Stable keys while dragging (correct_position changes on every move)
let seq = 0;
const keyOf = (opt) => {
    if (!opt.__key) Object.defineProperty(opt, '__key', { value: `o${++seq}`, enumerable: false });
    return opt.__key;
};
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">Elementlarni <span class="font-medium text-fg">to'g'ri tartibda</span> joylashtiring — o'quvchiga aralashtirilgan holda ko'rsatiladi.</p>

        <draggable v-model="options" :item-key="keyOf" handle=".drag-handle" :animation="150" ghost-class="opacity-40" tag="ol"
            class="space-y-2" @end="updatePositions">
            <template #item="{ element: option, index }">
                <li class="flex items-center gap-2 rounded-md border border-border bg-surface px-2 py-2">
                    <span class="drag-handle flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded-sm text-fg-subtle hover:bg-surface-2 hover:text-fg active:cursor-grabbing"
                        title="Sudrab tartiblang" aria-hidden="true">
                        <Bars2Icon class="h-4 w-4" />
                    </span>
                    <span class="w-6 shrink-0 text-center text-13 font-semibold tabular-nums text-fg-subtle">{{ index + 1 }}</span>
                    <input v-model="option.option_text" type="text" :aria-label="`${index + 1}-element`"
                        :class="[controlBase, controlState(false), controlSize.md, 'min-w-0 flex-1']" placeholder="Element matni" required>
                    <div class="flex shrink-0 items-center">
                        <IconButton :icon="ArrowUpIcon" :label="`${index + 1}-elementni yuqoriga`" size="sm" :disabled="index === 0" @click="move(index, -1)" />
                        <IconButton :icon="ArrowDownIcon" :label="`${index + 1}-elementni pastga`" size="sm" :disabled="index === options.length - 1"
                            @click="move(index, 1)" />
                        <IconButton v-if="options.length > 2" :icon="TrashIcon" :label="`${index + 1}-elementni o'chirish`" variant="danger" size="sm"
                            @click="removeOption(index)" />
                    </div>
                </li>
            </template>
        </draggable>

        <Button variant="ghost" size="sm" :icon-left="PlusIcon" @click="addOption">Element qo'shish</Button>
    </div>
</template>
