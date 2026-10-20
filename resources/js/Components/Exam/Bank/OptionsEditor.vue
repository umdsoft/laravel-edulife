<script setup>
/**
 * OptionsEditor — answer options A–E (4 or 5) of a closed exam question, each with a live
 * KaTeX preview and the teacher's correct mark (radio for single, checkbox for multiple choice).
 *   <OptionsEditor v-model="form.options" :multiple="form.question_type === 'multiple_choice'" />
 * Leaving every option unmarked is allowed (the question then waits in the review queue).
 */
import { computed } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import { OPTION_LABELS } from './bank';
import { hasMath } from '@/Utils/mathText';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    multiple: Boolean,
    errors: { type: Object, default: () => ({}) },
    // review inline edit: texts only, marks are set with one click elsewhere
    hideMarks: Boolean,
});
const emit = defineEmits(['update:modelValue']);

const options = computed(() => props.modelValue || []);
const update = (list) => emit('update:modelValue', list);
const setText = (i, text) => update(options.value.map((o, j) => (j === i ? { ...o, text } : o)));
const toggle = (i) => update(options.value.map((o, j) => {
    if (props.multiple) return j === i ? { ...o, is_correct: o.is_correct !== true } : o;
    return { ...o, is_correct: j === i ? (o.is_correct === true ? null : true) : null };
}));
const add = () => options.value.length < 5 && update([...options.value, { text: '', is_correct: null }]);
const remove = (i) => options.value.length > 4 && update(options.value.filter((_, j) => j !== i));
const err = (i) => props.errors?.[`options.${i}.text`] || null;
</script>

<template>
    <div class="space-y-3">
        <div v-for="(o, i) in options" :key="i" :class="[
            'rounded-md border p-3 transition-colors duration-150',
            o.is_correct === true && !hideMarks ? 'border-success-100 bg-success-50/60' : 'border-border',
        ]">
            <div class="flex items-start gap-3">
                <button v-if="!hideMarks" type="button" :aria-pressed="o.is_correct === true"
                    :aria-label="`${OPTION_LABELS[i]} — to'g'ri javob`" :title="multiple ? 'To\'g\'ri (bir nechtasi mumkin)' : 'To\'g\'ri javob'"
                    :class="[
                        'focus-ring mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-13 font-semibold transition-colors duration-150',
                        o.is_correct === true ? 'border-success-600 bg-success-600 text-white' : 'border-border-strong bg-surface text-fg-muted hover:border-success-600 hover:text-success',
                    ]" @click="toggle(i)">
                    <CheckIcon v-if="o.is_correct === true" class="h-4 w-4" aria-hidden="true" />
                    <span v-else>{{ OPTION_LABELS[i] }}</span>
                </button>
                <span v-else class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-2 text-13 font-semibold text-fg-muted">
                    {{ OPTION_LABELS[i] }}
                </span>
                <div class="min-w-0 flex-1 space-y-2">
                    <Textarea :model-value="o.text" :rows="1" autoresize :aria-label="`${OPTION_LABELS[i]} variant`"
                        :placeholder="`${OPTION_LABELS[i]} variant, masalan $\\frac{1}{2}$`" :error="err(i)" class="font-mono"
                        @update:model-value="setText(i, $event)" />
                    <div v-if="hasMath(o.text)" class="rounded-sm bg-surface-2/60 px-2.5 py-1.5">
                        <RichContent :html="o.text" size="sm" />
                    </div>
                </div>
                <IconButton v-if="options.length > 4" :icon="TrashIcon" label="Variantni olib tashlash" size="sm" class="mt-1"
                    @click="remove(i)" />
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p v-if="!hideMarks" class="text-13 text-fg-subtle">
                {{ multiple ? 'To\'g\'ri variantlarni belgilang (bir nechtasi mumkin).' : 'To\'g\'ri variant harfini bosing.' }}
                Belgilanmasa, savol tekshiruv navbatida kutadi.
            </p>
            <Button v-if="options.length < 5" size="sm" variant="secondary" :icon-left="PlusIcon" @click="add">E variant</Button>
        </div>
    </div>
</template>
