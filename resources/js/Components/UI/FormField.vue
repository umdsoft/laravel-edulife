<script setup>
/**
 * FormField — label + control + hint + error, wired for accessibility.
 * The default slot receives { id, describedBy, invalid } to bind on the control:
 *
 *   <FormField label="Kurs nomi" :error="form.errors.title" hint="60 belgigacha" required v-slot="{ id, describedBy, invalid }">
 *     <input :id="id" :aria-describedby="describedBy" :aria-invalid="invalid" v-model="form.title" />
 *   </FormField>
 *
 * UI controls (Input/Textarea/Select) already include label/hint/error props, so FormField is
 * mainly for custom controls (radio groups, uploaders, editors).
 */
import { computed, useId } from 'vue';
import { ExclamationCircleIcon } from '@heroicons/vue/20/solid';
import { labelClass, hintClass, errorClass } from './fieldStyles';

const props = defineProps({
    label: { type: String, default: null },
    id: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: [String, Array], default: null },
    required: Boolean,
    optional: Boolean,
    // "fieldset" for groups of radios/checkboxes (label becomes <legend>)
    as: { type: String, default: 'div' },
});

const autoId = useId();
const fieldId = computed(() => props.id || `f-${autoId}`);
const errorText = computed(() => (Array.isArray(props.error) ? props.error[0] : props.error));
const describedBy = computed(() => {
    const ids = [];
    if (props.hint && !errorText.value) ids.push(`${fieldId.value}-hint`);
    if (errorText.value) ids.push(`${fieldId.value}-error`);
    return ids.length ? ids.join(' ') : undefined;
});
</script>

<template>
    <component :is="as" class="min-w-0">
        <component :is="as === 'fieldset' ? 'legend' : 'label'" v-if="label || $slots.label"
            :for="as === 'fieldset' ? undefined : fieldId" :class="[labelClass, 'mb-1.5']">
            <slot name="label">{{ label }}</slot>
            <span v-if="required" class="ml-0.5 text-danger" aria-hidden="true">*</span>
            <span v-else-if="optional" class="ml-1 font-normal text-fg-subtle">(ixtiyoriy)</span>
        </component>

        <slot :id="fieldId" :describedBy="describedBy" :invalid="!!errorText" />

        <p v-if="hint && !errorText" :id="`${fieldId}-hint`" :class="hintClass">{{ hint }}</p>
        <p v-if="errorText" :id="`${fieldId}-error`" :class="errorClass" role="alert">
            <ExclamationCircleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <span>{{ errorText }}</span>
        </p>
    </component>
</template>
