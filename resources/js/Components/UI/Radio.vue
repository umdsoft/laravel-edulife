<script setup>
/**
 * Radio — one option of a group; share `name` and v-model across the group.
 * Wrap a group in <FormField as="fieldset" label="..."> for a proper legend.
 *   <Radio v-model="form.level" value="easy" name="level" label="Oson" />
 */
import { computed, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number, Boolean, null], default: null },
    value: { type: [String, Number, Boolean], required: true },
    name: { type: String, default: null },
    label: { type: String, default: null },
    description: { type: String, default: null },
    disabled: Boolean,
    id: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'change']);
const autoId = useId();
const inputId = computed(() => props.id || `rd-${autoId}`);
const checked = computed(() => props.modelValue === props.value);

const onChange = () => {
    emit('update:modelValue', props.value);
    emit('change', props.value);
};
</script>

<template>
    <div :class="['flex items-start gap-3', $attrs.class]">
        <span class="flex h-5 items-center">
            <input :id="inputId" type="radio" :name="name" :value="value" :checked="checked" :disabled="disabled"
                :aria-describedby="description ? `${inputId}-desc` : undefined"
                class="h-4 w-4 cursor-pointer border-border-strong accent-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:cursor-not-allowed disabled:opacity-50"
                @change="onChange" />
        </span>
        <span v-if="label || description || $slots.default" class="min-w-0 text-sm leading-5">
            <label :for="inputId" :class="['font-medium text-fg', disabled ? 'opacity-60' : 'cursor-pointer']">
                <slot>{{ label }}</slot>
            </label>
            <span v-if="description" :id="`${inputId}-desc`" class="block text-13 text-fg-subtle">{{ description }}</span>
        </span>
    </div>
</template>
