<script setup>
import { computed, onMounted } from 'vue'
import confetti from 'canvas-confetti'
import { StarIcon, SparklesIcon, ClockIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    lesson: Object,
    results: Object,
})

const emit = defineEmits(['finish'])

const stars = computed(() => {
    const percentage = props.results?.percentage || 0
    if (percentage >= 95) return 3
    if (percentage >= 80) return 2
    if (percentage >= 60) return 1
    return 0
})

const xpEarned = computed(() => {
    const baseXp = props.lesson?.xp_reward || 20
    const multiplier = stars.value === 3 ? 1.5 : stars.value === 2 ? 1.2 : 1.0
    return Math.round(baseXp * multiplier)
})

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const starColors = ['text-gray-300', 'text-yellow-400', 'text-yellow-400', 'text-yellow-400']

onMounted(() => {
    if (stars.value >= 2) {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        })
    }
})
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-12 text-center">
        <!-- Celebration Icon -->
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white text-4xl animate-bounce">
            🎉
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            {{ stars >= 2 ? 'Excellent!' : stars >= 1 ? 'Good job!' : 'Lesson Complete!' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            You've completed {{ lesson?.title }}
        </p>
        
        <!-- Stars -->
        <div class="flex justify-center space-x-2 mb-8">
            <StarIcon 
                v-for="i in 3" 
                :key="i"
                class="w-12 h-12 transition-all duration-500"
                :class="i <= stars ? 'text-yellow-400 scale-110' : 'text-gray-300 dark:text-gray-600'"
            />
        </div>
        
        <!-- Results Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-2">
                        <span class="text-xl font-bold text-green-600">{{ results?.correct_count || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500">Correct</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-2">
                        <SparklesIcon class="w-6 h-6 text-purple-600" />
                    </div>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">+{{ xpEarned }}</p>
                    <p class="text-sm text-gray-500">XP Earned</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-2">
                        <ClockIcon class="w-6 h-6 text-blue-600" />
                    </div>
                    <p class="text-sm text-gray-900 dark:text-white font-medium">{{ formatTime(results?.time_seconds || 0) }}</p>
                    <p class="text-sm text-gray-500">Time</p>
                </div>
            </div>
            
            <!-- Accuracy -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Accuracy</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ results?.percentage || 0 }}%</span>
                </div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div 
                        class="h-full rounded-full transition-all duration-1000"
                        :class="results?.percentage >= 80 ? 'bg-green-500' : results?.percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500'"
                        :style="{ width: `${results?.percentage || 0}%` }"
                    ></div>
                </div>
            </div>
        </div>
        
        <!-- Action Button -->
        <button
            @click="$emit('finish')"
            class="px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-2xl font-semibold text-lg hover:shadow-xl transition-all hover:scale-105"
        >
            Continue
        </button>
    </div>
</template>
