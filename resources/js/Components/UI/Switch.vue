<script setup>
/**
 * Switch — on/off toggle (role="switch"). Use for settings that apply immediately.
 *   <Switch v-model="form.email_notifications" label="Email xabarnomalar" description="Haftalik hisobot" />
 */
import { computed, useId } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: null },
    description: { type: String, default: null },
    disabled: Boolean,
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
    // label on the left (settings rows) or right
    labelPosition: { type: String, default: 'right', validator: (v) => ['left', 'right'].includes(v) },
    id: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'change']);
const autoId = useId();
const switchId = computed(() => props.id || `sw-${autoId}`);

const toggle = () => {
    if (props.disabled) return;
    emit('update:modelValue', !props.modelValue);
    emit('change', !props.modelValue);
};

const track = { sm: 'h-5 w-9', md: 'h-6 w-11' };
const thumb = { sm: 'h-4 w-4', md: 'h-5 w-5' };
const shift = { sm: 'translate-x-4', md: 'translate-x-5' };
</script>

<template>
    <div :class="['flex items-start gap-3', labelPosition === 'left' ? 'flex-row-reverse justify-between' : '']">
        <button :id="switchId" type="button" role="switch" :aria-checked="modelValue" :disabled="disabled"
            :aria-labelledby="label ? `${switchId}-label` : undefined"
            :aria-describedby="description ? `${switchId}-desc` : undefined" :class="[
                'focus-ring relative inline-flex shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-150',
                track[size],
                modelValue ? 'bg-brand-600' : 'bg-surface-3',
                disabled ? 'cursor-not-allowed opacity-50' : '',
            ]" @click="toggle">
            <span aria-hidden="true" :class="[
                'pointer-events-none inline-block rounded-full bg-white shadow-sm transition-transform duration-150 ease-out',
                thumb[size],
                modelValue ? shift[size] : 'translate-x-0',
            ]" />
        </button>
        <span v-if="label || description" class="min-w-0 text-sm leading-5">
            <span :id="`${switchId}-label`" :class="['block font-medium text-fg', disabled ? 'opacity-60' : 'cursor-pointer']"
                @click="toggle">{{ label }}</span>
            <span v-if="description" :id="`${switchId}-desc`" class="block text-13 text-fg-subtle">{{ description }}</span>
        </span>
    </div>
</template>
