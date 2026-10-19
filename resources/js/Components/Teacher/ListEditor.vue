<script setup>
/**
 * ListEditor — edit a list of short strings (requirements, outcomes, skills).
 *   <ListEditor v-model="form.requirements" label="Talablar" placeholder="Talab qo'shish" :error="form.errors.requirements" />
 * Enter adds an item; items can be removed. `variant="chips"` renders tags instead of rows.
 */
import { ref, useId } from 'vue';
import { PlusIcon, XMarkIcon, CheckIcon } from '@heroicons/vue/20/solid';
import FormField from '@/Components/UI/FormField.vue';
import Button from '@/Components/UI/Button.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    placeholder: { type: String, default: 'Qo\'shish…' },
    error: { type: [String, Array], default: null },
    optional: Boolean,
    variant: { type: String, default: 'list', validator: (v) => ['list', 'chips'].includes(v) },
    addLabel: { type: String, default: 'Qo\'shish' },
});

const emit = defineEmits(['update:modelValue']);
const draft = ref('');
const uid = useId();

const add = () => {
    const value = draft.value.trim();
    if (!value) return;
    emit('update:modelValue', [...(props.modelValue || []), value]);
    draft.value = '';
};

const remove = (index) => {
    const next = [...(props.modelValue || [])];
    next.splice(index, 1);
    emit('update:modelValue', next);
};
</script>

<template>
    <FormField :label="label" :hint="hint" :error="error" :optional="optional" v-slot="{ id, describedBy, invalid }">
        <div class="flex gap-2">
            <input :id="id" v-model="draft" type="text" :placeholder="placeholder" :aria-describedby="describedBy"
                :aria-invalid="invalid || undefined" :class="[controlBase, controlState(invalid), controlSize.md, 'min-w-0 flex-1']"
                @keydown.enter.prevent="add" />
            <Button variant="secondary" :icon-left="PlusIcon" :disabled="!draft.trim()" @click="add">
                <span class="sr-only sm:not-sr-only">{{ addLabel }}</span>
            </Button>
        </div>

        <ul v-if="modelValue?.length && variant === 'list'" class="mt-3 divide-y divide-border rounded-md border border-border"
            :aria-label="label || 'Ro\'yxat'">
            <li v-for="(item, index) in modelValue" :key="`${uid}-${index}`" class="flex items-start gap-2.5 px-3 py-2 text-sm">
                <CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                <span class="min-w-0 flex-1 break-words text-fg">{{ item }}</span>
                <button type="button" class="focus-ring -my-1 -mr-1.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-sm text-fg-subtle hover:bg-danger-50 hover:text-danger"
                    :aria-label="`O'chirish: ${item}`" @click="remove(index)">
                    <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                </button>
            </li>
        </ul>

        <ul v-else-if="modelValue?.length" class="mt-3 flex flex-wrap gap-2" :aria-label="label || 'Ro\'yxat'">
            <li v-for="(item, index) in modelValue" :key="`${uid}-${index}`"
                class="inline-flex items-center gap-1 rounded-full bg-brand-50 py-1 pl-3 pr-1 text-13 font-medium text-brand ring-1 ring-inset ring-brand-100">
                {{ item }}
                <button type="button" class="focus-ring inline-flex h-5 w-5 items-center justify-center rounded-full hover:bg-brand-100"
                    :aria-label="`O'chirish: ${item}`" @click="remove(index)">
                    <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </FormField>
</template>
