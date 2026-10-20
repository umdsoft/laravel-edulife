<script setup>
/**
 * ChoiceGroup — a radio group drawn as large tappable tiles (mobile-first forms).
 *   <ChoiceGroup v-model="form.grade" name="grade" label="Sinf" :options="[{ value: '9', label: '9-sinf' }]" />
 * Native radios underneath: arrow keys, form semantics and screen readers work as usual.
 */
import { computed, useId } from 'vue';
import { ExclamationCircleIcon } from '@heroicons/vue/20/solid';
import { labelClass, hintClass, errorClass } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, required: true }, // [{ value, label, hint? }]
    name: { type: String, required: true },
    label: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: [String, Array], default: null },
    columns: { type: Number, default: 2 },
});

const emit = defineEmits(['update:modelValue']);
const uid = useId();
const errorText = computed(() => (Array.isArray(props.error) ? props.error[0] : props.error));
const grid = computed(() => ({
    1: 'grid-cols-1',
    2: 'grid-cols-2',
    3: 'grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-4',
}[props.columns] || 'grid-cols-2'));
</script>

<template>
    <fieldset :aria-describedby="errorText ? `${uid}-err` : hint ? `${uid}-hint` : undefined"
        :aria-invalid="errorText ? 'true' : undefined">
        <legend v-if="label" :class="labelClass">{{ label }}</legend>
        <p v-if="hint" :id="`${uid}-hint`" :class="[hintClass, 'mt-0.5']">{{ hint }}</p>
        <div :class="['mt-2 grid gap-2', grid]">
            <label v-for="opt in options" :key="String(opt.value)"
                :class="[
                    'relative flex min-h-[44px] cursor-pointer flex-col justify-center rounded-md border px-3 py-2 text-sm transition-colors duration-150',
                    'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 has-[:focus-visible]:ring-offset-2 has-[:focus-visible]:ring-offset-surface',
                    modelValue === opt.value
                        ? 'border-brand-200 bg-brand-50 text-brand'
                        : 'border-border-strong bg-surface text-fg hover:bg-surface-2',
                ]">
                <input type="radio" class="sr-only" :name="name" :value="opt.value" :checked="modelValue === opt.value"
                    @change="emit('update:modelValue', opt.value)" />
                <span class="font-medium">{{ opt.label }}</span>
                <span v-if="opt.hint" class="mt-0.5 text-xs" :class="modelValue === opt.value ? 'text-brand' : 'text-fg-subtle'">{{ opt.hint }}</span>
            </label>
        </div>
        <p v-if="errorText" :id="`${uid}-err`" :class="errorClass">
            <ExclamationCircleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <span>{{ errorText }}</span>
        </p>
    </fieldset>
</template>
