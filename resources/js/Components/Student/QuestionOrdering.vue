<script setup>
/**
 * Ordering answer: move items up/down into the right order.
 * API unchanged: question, v-model (array of option ids, in order). A null model starts from the given order.
 */
import MathText from '@/Components/MathText.vue';
import { ref } from 'vue';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/20/solid';

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

const initial = Array.isArray(props.modelValue) && props.modelValue.length > 0
    ? props.modelValue.map(id => props.question.options.find(o => o.id === id)).filter(Boolean)
    : [...props.question.options];

const items = ref(initial.length ? initial : [...props.question.options]);
const announcement = ref('');

const move = (index, delta) => {
    const target = index + delta;
    if (target < 0 || target >= items.value.length) return;
    const newItems = [...items.value];
    [newItems[target], newItems[index]] = [newItems[index], newItems[target]];
    items.value = newItems;
    announcement.value = `"${newItems[target].content}" ${target + 1}-o'ringa ko'chirildi`;
    emit('update:modelValue', newItems.map(i => i.id));
};

const moveUp = (index) => move(index, -1);
const moveDown = (index) => move(index, 1);
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">Bandlarni tugmalar yordamida to'g'ri tartibga keltiring.</p>
        <ol class="space-y-2">
            <li v-for="(item, index) in items" :key="item.id"
                class="flex items-center gap-3 rounded-md border border-border bg-surface p-2.5 pl-3 shadow-xs">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-13 font-semibold text-brand tabular-nums"
                    aria-hidden="true">
                    {{ index + 1 }}
                </span>
                <MathText :text="item.content" class="min-w-0 flex-1 text-[15px] leading-relaxed text-fg" />
                <span class="flex shrink-0 gap-1">
                    <button type="button" :disabled="index === 0" @click="moveUp(index)"
                        class="focus-ring flex h-10 w-10 items-center justify-center rounded-md border border-border text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg disabled:cursor-not-allowed disabled:opacity-40"
                        :aria-label="`${item.content} — yuqoriga`">
                        <ChevronUpIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                    <button type="button" :disabled="index === items.length - 1" @click="moveDown(index)"
                        class="focus-ring flex h-10 w-10 items-center justify-center rounded-md border border-border text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg disabled:cursor-not-allowed disabled:opacity-40"
                        :aria-label="`${item.content} — pastga`">
                        <ChevronDownIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                </span>
            </li>
        </ol>
        <p class="sr-only" aria-live="polite">{{ announcement }}</p>
    </div>
</template>
