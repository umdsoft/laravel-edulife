<template>
    <button :type="type" :disabled="disabled || loading" :class="buttonClasses" @click="$emit('click', $event)">
        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>

        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'ghost', 'danger'].includes(value),
    },
    loading: Boolean,
    disabled: Boolean,
});

defineEmits(['click']);

const buttonClasses = computed(() => {
    const base = 'inline-flex items-center justify-center px-6 py-3 rounded-xl font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed';

    const variants = {
        primary: 'bg-primary text-white hover:bg-primary/90 shadow-sm',
        secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200',
        ghost: 'text-gray-600 hover:bg-gray-100',
        danger: 'bg-red-500 text-white hover:bg-red-600 shadow-sm',
    };

    return `${base} ${variants[props.variant]}`;
});
</script>
