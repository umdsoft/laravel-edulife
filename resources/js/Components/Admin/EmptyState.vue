<template>
    <div class="flex flex-col items-center justify-center text-center py-12 px-4">
        <!-- Icon -->
        <div class="text-6xl mb-4">
            {{ icon }}
        </div>

        <!-- Title -->
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
            {{ title }}
        </h3>

        <!-- Description -->
        <p class="text-sm text-gray-500 mb-6 max-w-sm">
            {{ description }}
        </p>

        <!-- Action Slot -->
        <div v-if="$slots.default">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: {
        type: String,
        default: '📭',
    },
    title: {
        type: String,
        required: true,
    },
    description: String,
});
</script>
