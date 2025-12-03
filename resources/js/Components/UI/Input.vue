<template>
    <div>
        <label v-if="label" class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
            {{ label }}
        </label>

        <input :type="type" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
            :placeholder="placeholder" :disabled="disabled" :class="[
                'w-full bg-gray-50 border-0 rounded-xl px-4 py-3.5 text-gray-900 placeholder-gray-400',
                'focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all duration-200',
                error ? 'ring-2 ring-red-500/50 bg-red-50' : '',
                disabled ? 'opacity-50 cursor-not-allowed' : '',
            ]" />

        <p v-if="error" class="mt-2 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    modelValue: [String, Number],
    type: {
        type: String,
        default: 'text',
    },
    label: String,
    placeholder: String,
    error: String,
    disabled: Boolean,
});

defineEmits(['update:modelValue']);
</script>
