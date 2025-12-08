<script setup>
import { computed } from 'vue'

const props = defineProps({
    xpProgress: { type: Number, default: 0 },
    xpGoal: { type: Number, default: 50 },
    tasksCompleted: { type: Number, default: 0 },
    tasksTotal: { type: Number, default: 3 },
    size: { type: Number, default: 120 },
})

const xpPercentage = computed(() => Math.min(100, Math.round((props.xpProgress / props.xpGoal) * 100)))
const taskPercentage = computed(() => Math.min(100, Math.round((props.tasksCompleted / props.tasksTotal) * 100)))

const outerRadius = computed(() => (props.size - 8) / 2)
const innerRadius = computed(() => outerRadius.value - 12)
const circumferenceOuter = computed(() => 2 * Math.PI * outerRadius.value)
const circumferenceInner = computed(() => 2 * Math.PI * innerRadius.value)

const outerOffset = computed(() => circumferenceOuter.value * (1 - xpPercentage.value / 100))
const innerOffset = computed(() => circumferenceInner.value * (1 - taskPercentage.value / 100))
</script>

<template>
    <div class="relative" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" class="transform -rotate-90">
            <!-- Outer ring background -->
            <circle :cx="size / 2" :cy="size / 2" :r="outerRadius" fill="none" stroke-width="8"
                class="stroke-gray-200 dark:stroke-gray-700" />
            <!-- Outer ring progress (XP) -->
            <circle :cx="size / 2" :cy="size / 2" :r="outerRadius" fill="none" stroke-width="8" stroke-linecap="round"
                class="stroke-purple-500 transition-all duration-500"
                :stroke-dasharray="circumferenceOuter" :stroke-dashoffset="outerOffset" />
            <!-- Inner ring background -->
            <circle :cx="size / 2" :cy="size / 2" :r="innerRadius" fill="none" stroke-width="8"
                class="stroke-gray-200 dark:stroke-gray-700" />
            <!-- Inner ring progress (Tasks) -->
            <circle :cx="size / 2" :cy="size / 2" :r="innerRadius" fill="none" stroke-width="8" stroke-linecap="round"
                class="stroke-blue-500 transition-all duration-500"
                :stroke-dasharray="circumferenceInner" :stroke-dashoffset="innerOffset" />
        </svg>
        <!-- Center text -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ xpPercentage }}%</span>
            <span class="text-xs text-gray-500">complete</span>
        </div>
    </div>
</template>
