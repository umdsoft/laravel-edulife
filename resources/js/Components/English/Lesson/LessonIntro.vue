<script setup>
import { PlayIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    lesson: Object,
})

const emit = defineEmits(['next'])
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-12 text-center">
        <!-- Lesson Icon -->
        <div class="w-24 h-24 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-4xl shadow-xl">
            {{ lesson?.icon || '📚' }}
        </div>
        
        <!-- Lesson Info -->
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            {{ lesson?.title || 'Lesson' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            {{ lesson?.description }}
        </p>
        
        <!-- Learning Goals -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-8 text-left">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4">What you'll learn:</h2>
            <ul class="space-y-3">
                <li class="flex items-start space-x-3">
                    <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm flex-shrink-0">✓</span>
                    <span class="text-gray-700 dark:text-gray-300">New vocabulary words</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm flex-shrink-0">✓</span>
                    <span class="text-gray-700 dark:text-gray-300">Grammar rules and usage</span>
                </li>
                <li class="flex items-start space-x-3">
                    <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-sm flex-shrink-0">✓</span>
                    <span class="text-gray-700 dark:text-gray-300">Practice exercises</span>
                </li>
            </ul>
        </div>
        
        <!-- Lesson Stats -->
        <div class="flex justify-center space-x-8 mb-8">
            <div class="text-center">
                <span class="block text-2xl font-bold text-gray-900 dark:text-white">{{ lesson?.estimated_minutes || 10 }}</span>
                <span class="text-sm text-gray-500">minutes</span>
            </div>
            <div class="text-center">
                <span class="block text-2xl font-bold text-gray-900 dark:text-white">{{ lesson?.xp_reward || 20 }}</span>
                <span class="text-sm text-gray-500">XP reward</span>
            </div>
        </div>
        
        <!-- Start Button -->
        <button
            @click="$emit('next')"
            class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-2xl font-semibold text-lg hover:shadow-xl transition-all hover:scale-105"
        >
            <PlayIcon class="w-6 h-6" />
            <span>Start Lesson</span>
        </button>
    </div>
</template>
