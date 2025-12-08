<script setup>
import { ref, computed } from 'vue'
import { ArrowRightIcon, ArrowLeftIcon, LightBulbIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    grammarPoints: { type: Array, default: () => [] },
})

const emit = defineEmits(['next', 'back'])

const currentIndex = ref(0)
const currentPoint = computed(() => props.grammarPoints[currentIndex.value])
const isLast = computed(() => currentIndex.value === props.grammarPoints.length - 1)

const next = () => {
    if (isLast.value) {
        emit('next')
    } else {
        currentIndex.value++
    }
}

const previous = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Grammar</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ currentIndex + 1 }} / {{ grammarPoints.length }}</p>
        </div>
        
        <!-- Grammar Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8">
            <!-- Rule Title -->
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                    <LightBulbIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ currentPoint?.title }}</h3>
            </div>
            
            <!-- Explanation -->
            <p class="text-gray-700 dark:text-gray-300 mb-6">{{ currentPoint?.explanation }}</p>
            
            <!-- Formula/Structure -->
            <div v-if="currentPoint?.formula" class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 mb-6">
                <p class="text-center font-mono text-lg text-gray-900 dark:text-white">
                    {{ currentPoint.formula }}
                </p>
            </div>
            
            <!-- Examples -->
            <div class="space-y-3">
                <h4 class="font-medium text-gray-900 dark:text-white">Examples:</h4>
                <div
                    v-for="(example, index) in currentPoint?.examples || []"
                    :key="index"
                    class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4"
                >
                    <p class="text-blue-900 dark:text-blue-100 font-medium">{{ example.sentence }}</p>
                    <p class="text-blue-700 dark:text-blue-300 text-sm mt-1">{{ example.translation }}</p>
                </div>
            </div>
            
            <!-- Tips -->
            <div v-if="currentPoint?.tips" class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4">
                <p class="text-yellow-800 dark:text-yellow-100 text-sm">
                    💡 <strong>Tip:</strong> {{ currentPoint.tips }}
                </p>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="flex items-center justify-between">
            <button 
                @click="$emit('back')"
                class="flex items-center space-x-2 px-4 py-2 text-gray-600 dark:text-gray-400"
            >
                <ArrowLeftIcon class="w-5 h-5" />
                <span>Back</span>
            </button>
            
            <div class="flex items-center space-x-3">
                <button
                    v-if="currentIndex > 0"
                    @click="previous"
                    class="p-3 rounded-xl border border-gray-300 dark:border-gray-600"
                >
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </button>
                
                <button
                    @click="next"
                    class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl font-medium hover:shadow-lg transition-all"
                >
                    <span>{{ isLast ? 'Continue' : 'Next' }}</span>
                    <ArrowRightIcon class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
