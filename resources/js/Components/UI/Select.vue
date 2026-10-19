<script setup>
/**
 * Select — native <select> styled to match Input (best on mobile & for a11y).
 *   <Select v-model="form.grade" label="Sinf" placeholder="Tanlang"
 *           :options="[{ value: 9, label: '9-sinf' }, { value: 10, label: '10-sinf' }]" />
 * `options` accepts objects ({ value, label, disabled }) or plain strings/numbers.
 * Custom <option>/<optgroup> markup can be passed in the default slot instead.
 */
import { computed, useAttrs, useId } from 'vue';
import { ChevronUpDownIcon } from '@heroicons/vue/20/solid';
import FormField from './FormField.vue';
import { controlBase, controlState, controlSize } from './fieldStyles';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, null], default: null },
    options: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'value' },
    labelKey: { type: String, default: 'label' },
    placeholder: { type: String, default: null },
    label: String,
    hint: String,
    error: [String, Array],
    disabled: Boolean,
    required: Boolean,
    optional: Boolean,
    id: { type: String, default: null },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
});

const emit = defineEmits(['update:modelValue', 'change']);
const attrs = useAttrs();
const autoId = useId();
const fieldId = computed(() => props.id || attrs.id || `sel-${autoId}`);
const wrapperAttrs = computed(() => ({ class: attrs.class, style: attrs.style }));
const selectAttrs = computed(() => {
    const { class: _c, style: _s, id: _i, onChange: _o, ...rest } = attrs;
    return rest;
});

const normalized = computed(() =>
    props.options.map((o) =>
        o !== null && typeof o === 'object'
            ? { value: o[props.valueKey], label: o[props.labelKey], disabled: !!o.disabled }
            : { value: o, label: String(o), disabled: false },
    ),
);

// Native <select> gives strings; map back to the original option value (keeps numbers numbers)
const onChange = (e) => {
    const raw = e.target.value;
    const match = normalized.value.find((o) => String(o.value) === raw);
    const value = raw === '' && props.placeholder !== null ? null : match ? match.value : raw;
    emit('update:modelValue', value);
    emit('change', value);
};

const current = computed(() => (props.modelValue === null || props.modelValue === undefined ? '' : String(props.modelValue)));
</script>

<template>
    <FormField v-bind="wrapperAttrs" :id="fieldId" :label="label" :hint="hint" :error="error" :required="required"
        :optional="optional" v-slot="{ id, describedBy, invalid }">
        <div class="relative">
            <select v-bind="selectAttrs" :id="id" :value="current" :disabled="disabled" :required="required || undefined"
                :aria-invalid="invalid || undefined" :aria-describedby="describedBy"
                :class="[controlBase, controlState(invalid), controlSize[size], 'appearance-none bg-none pr-9 cursor-pointer']"
                @change="onChange">
                <option v-if="placeholder !== null" value="" :disabled="required">{{ placeholder }}</option>
                <slot>
                    <option v-for="opt in normalized" :key="String(opt.value)" :value="String(opt.value)"
                        :disabled="opt.disabled">
                        {{ opt.label }}
                    </option>
                </slot>
            </select>
            <ChevronUpDownIcon class="pointer-events-none absolute right-2.5 top-1/2 h-5 w-5 -translate-y-1/2 text-fg-subtle"
                aria-hidden="true" />
        </div>
    </FormField>
</template>
