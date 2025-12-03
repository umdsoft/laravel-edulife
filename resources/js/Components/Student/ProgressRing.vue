<script setup>
import { computed } from 'vue';

const props = defineProps({
    progress: {
        type: Number,
        default: 0
    },
    size: {
        type: Number,
        default: 60
    },
    strokeWidth: {
        type: Number,
        default: 4
    },
    color: {
        type: String,
        default: 'text-purple-600'
    }
});

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value - (props.progress / 100) * circumference.value);
</script>

<template>
    <div class="relative flex items-center justify-center" :style="{ width: size + 'px', height: size + 'px' }">
        <svg class="transform -rotate-90 w-full h-full">
            <!-- Background circle -->
            <circle class="text-gray-200" stroke-width="strokeWidth" stroke="currentColor" fill="transparent"
                :r="radius" :cx="size / 2" :cy="size / 2" />
            <!-- Progress circle -->
            <circle :class="color" stroke-width="strokeWidth" stroke-dasharray="circumference"
                :stroke-dashoffset="offset" stroke-linecap="round" stroke="currentColor" fill="transparent" :r="radius"
                :cx="size / 2" :cy="size / 2" class="transition-all duration-1000 ease-out" />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
            <slot>
                <span class="text-xs font-bold text-gray-700">{{ progress }}%</span>
            </slot>
        </div>
    </div>
</template>
