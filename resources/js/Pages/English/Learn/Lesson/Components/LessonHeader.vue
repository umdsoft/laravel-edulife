<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    lesson: Object,
    module: Object,
    elapsedTime: {
        type: Number,
        default: 0
    },
    streak: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['exit'])

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const levelColorClass = computed(() => {
    const colors = {
        emerald: 'text-emerald-600',
        blue: 'text-blue-600',
        purple: 'text-purple-600',
        amber: 'text-amber-600',
        rose: 'text-rose-600',
        indigo: 'text-indigo-600'
    }
    return colors[props.module?.level_color] || 'text-indigo-600'
})
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-3xl mx-auto px-4">
            <div class="flex items-center justify-between h-14">
                <!-- Exit Button -->
                <button 
                    @click="$emit('exit')"
                    class="p-2 -ml-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <XMarkIcon class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                </button>
                
                <!-- Center: Lesson Info -->
                <div class="flex-1 text-center px-4">
                    <div class="flex items-center justify-center gap-2">
                        <span 
                            class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700"
                            :class="levelColorClass"
                        >
                            {{ module?.level }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[180px]">
                            {{ lesson?.title }}
                        </span>
                    </div>
                </div>
                
                <!-- Right: Timer & Streak -->
                <div class="flex items-center gap-3">
                    <!-- Streak -->
                    <div v-if="streak > 0" class="flex items-center gap-1 text-amber-500">
                        <span class="text-lg">🔥</span>
                        <span class="text-sm font-bold">{{ streak }}</span>
                    </div>
                    
                    <!-- Timer -->
                    <div class="text-sm font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                        ⏱️ {{ formatTime(elapsedTime) }}
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
