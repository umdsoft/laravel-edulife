<script setup>
/**
 * Matching answer: pick a right-hand item for every left-hand item.
 * API unchanged: question (options with column / is_left / is_right), v-model ({ leftId: rightId }).
 * Values are emitted as strings (as the native <select> did before).
 */
import { computed, ref } from 'vue';
import { ArrowRightIcon } from '@heroicons/vue/20/solid';
import Select from '@/Components/UI/Select.vue';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue']);

// Options are flat and carry a 'column' ('left' | 'right') or is_left / is_right flag.
const leftItems = props.question.options.filter(o => o.column === 'left' || !o.column || o.is_left);
const rightItems = props.question.options.filter(o => o.column === 'right' || o.is_right);

const pairs = ref({ ...(props.modelValue || {}) });

const rightOptions = computed(() => rightItems.map((r) => ({ value: String(r.id), label: r.content })));

const updatePair = (leftId, rightId) => {
    pairs.value[leftId] = rightId === null || rightId === undefined ? '' : String(rightId);
    emit('update:modelValue', pairs.value);
};
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">Har bir chap tomondagi bandga mos javobni tanlang.</p>
        <ol class="space-y-2.5">
            <li v-for="(item, i) in leftItems" :key="item.id"
                class="grid gap-2 rounded-md border border-border bg-surface p-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:items-center sm:gap-3">
                <span class="flex min-w-0 items-start gap-2.5 text-[15px] leading-relaxed text-fg">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-2 text-xs font-semibold text-fg-muted tabular-nums">
                        {{ i + 1 }}
                    </span>
                    <span :id="`match-${question.id}-${item.id}`">{{ item.content }}</span>
                </span>
                <ArrowRightIcon class="hidden h-4 w-4 text-fg-subtle sm:block" aria-hidden="true" />
                <Select :model-value="pairs[item.id] ? String(pairs[item.id]) : null" :options="rightOptions"
                    placeholder="Tanlang…" :aria-labelledby="`match-${question.id}-${item.id}`"
                    @update:model-value="updatePair(item.id, $event)" />
            </li>
        </ol>
    </div>
</template>
