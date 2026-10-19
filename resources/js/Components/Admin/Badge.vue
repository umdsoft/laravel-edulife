<script setup>
/**
 * Admin Badge — kept for backward compatibility; renders the UI kit Badge.
 *   <Badge variant="success">Faol</Badge>
 * variant: success | warning | danger | info | gray (+ any UI Badge variant: neutral, brand, math, physics …)
 * size: sm | md.  Extra UI Badge props (dot, icon, solid) pass through.
 */
import UiBadge from '@/Components/UI/Badge.vue';

defineProps({
    variant: { type: String, default: 'gray' },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md'].includes(value),
    },
});
</script>

<template>
    <UiBadge :variant="variant" :size="size">
        <slot />
    </UiBadge>
</template>
