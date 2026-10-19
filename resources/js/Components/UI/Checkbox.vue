<script setup>
/**
 * Checkbox — boolean or array v-model.
 *   <Checkbox v-model="form.remember" label="Meni eslab qol" />
 *   <Checkbox v-model="selectedIds" :value="row.id" label="Tanlash" />   (array mode)
 * Props: label, description, disabled, indeterminate, error, id, name, value.
 */
import { computed, onMounted, ref, useId, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [Boolean, Array], default: false },
    value: { type: [String, Number, Boolean, Object], default: true },
    label: { type: String, default: null },
    description: { type: String, default: null },
    disabled: Boolean,
    indeterminate: Boolean,
    error: { type: [String, Array], default: null },
    id: { type: String, default: null },
    name: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'change']);
const el = ref(null);
const autoId = useId();
const inputId = computed(() => props.id || `cb-${autoId}`);
const errorText = computed(() => (Array.isArray(props.error) ? props.error[0] : props.error));

const checked = computed(() =>
    Array.isArray(props.modelValue) ? props.modelValue.includes(props.value) : !!props.modelValue,
);

const onChange = (e) => {
    const on = e.target.checked;
    let next;
    if (Array.isArray(props.modelValue)) {
        next = on ? [...props.modelValue, props.value] : props.modelValue.filter((v) => v !== props.value);
    } else {
        next = on;
    }
    emit('update:modelValue', next);
    emit('change', next);
};

const syncIndeterminate = () => {
    if (el.value) el.value.indeterminate = props.indeterminate;
};
onMounted(syncIndeterminate);
watch(() => props.indeterminate, syncIndeterminate);
</script>

<template>
    <div :class="['flex items-start gap-3', $attrs.class]">
        <span class="flex h-5 items-center">
            <input :id="inputId" ref="el" type="checkbox" :name="name" :checked="checked" :disabled="disabled"
                :aria-invalid="errorText ? true : undefined"
                :aria-describedby="description || errorText ? `${inputId}-desc` : undefined" :class="[
                    'h-4 w-4 cursor-pointer rounded-[4px] border bg-surface accent-brand-600',
                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-surface',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    errorText ? 'border-danger-600' : 'border-border-strong',
                ]" @change="onChange" />
        </span>
        <span v-if="label || description || $slots.default || errorText" class="min-w-0 text-sm leading-5">
            <label :for="inputId" :class="['font-medium text-fg', disabled ? 'opacity-60' : 'cursor-pointer']">
                <slot>{{ label }}</slot>
            </label>
            <span v-if="description || errorText" :id="`${inputId}-desc`" class="block">
                <span v-if="description" class="block text-13 text-fg-subtle">{{ description }}</span>
                <span v-if="errorText" class="block text-13 text-danger">{{ errorText }}</span>
            </span>
        </span>
    </div>
</template>
