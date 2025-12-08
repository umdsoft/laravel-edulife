<script setup>
import { computed } from 'vue'

const props = defineProps({
    current: {
        type: Number,
        default: 0
    },
    total: {
        type: Number,
        default: 10
    },
    correct: {
        type: Number,
        default: 0
    },
    incorrect: {
        type: Number,
        default: 0
    }
})

const progressPercent = computed(() => {
    if (props.total === 0) return 0
    return Math.min(100, Math.round((props.current / props.total) * 100))
})
</script>

<template>
    <div class="fixed top-14 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
        <div class="max-w-3xl mx-auto px-4 py-2">
            <div class="flex items-center gap-4">
                <!-- Progress Bar -->
                <div class="flex-1">
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full transition-all duration-500 ease-out"
                            :style="{ width: `${progressPercent}%` }"
                        ></div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="flex items-center gap-3 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600">
                        <span class="font-bold">{{ correct }}</span>
                        <span class="text-emerald-500">✓</span>
                    </span>
                    <span class="flex items-center gap-1 text-red-500">
                        <span class="font-bold">{{ incorrect }}</span>
                        <span class="text-red-400">✗</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
