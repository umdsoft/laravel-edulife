<template>
    <div class="relative flex items-center justify-center">
        <svg :width="size" :height="size" class="transform -rotate-90">
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="(size - stroke) / 2"
                fill="none"
                stroke="currentColor"
                :stroke-width="stroke"
                class="text-gray-200 dark:text-gray-700"
            />
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="(size - stroke) / 2"
                fill="none"
                :stroke="color"
                :stroke-width="stroke"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
                class="transition-all duration-1000 ease-out"
            />
        </svg>
        <div v-if="showText" class="absolute text-xs font-bold text-gray-700 dark:text-gray-300">
            {{ Math.round(progress) }}%
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    progress: {
        type: Number,
        default: 0
    },
    size: {
        type: Number,
        default: 40
    },
    stroke: {
        type: Number,
        default: 4
    },
    color: {
        type: String,
        default: 'currentColor'
    },
    showText: {
        type: Boolean,
        default: true
    }
})

const circumference = computed(() => 2 * Math.PI * ((props.size - props.stroke) / 2))
const dashOffset = computed(() => {
    const p = Math.min(Math.max(props.progress, 0), 100)
    return props.circumference - (p / 100) * props.circumference
})
</script>
