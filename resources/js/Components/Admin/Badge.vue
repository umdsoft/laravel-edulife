<template>
    <span :class="badgeClasses">
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'gray',
        validator: (value) => ['success', 'warning', 'danger', 'info', 'gray'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md'].includes(value),
    },
});

const badgeClasses = computed(() => {
    const base = 'inline-flex items-center font-medium rounded-full';

    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
    };

    const variants = {
        success: 'bg-green-100 text-green-700',
        warning: 'bg-yellow-100 text-yellow-700',
        danger: 'bg-red-100 text-red-700',
        info: 'bg-blue-100 text-blue-700',
        gray: 'bg-gray-100 text-gray-700',
    };

    return `${base} ${sizes[props.size]} ${variants[props.variant]}`;
});
</script>
