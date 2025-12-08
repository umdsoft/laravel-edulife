<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'
import { CheckCircleIcon, XCircleIcon, ArrowRightIcon, ArrowLeftIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    exercises: { type: Array, default: () => [] },
})

const emit = defineEmits(['next', 'back'])
const audio = useAudio()

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const isAnswered = ref(false)
const correctCount = ref(0)

const currentExercise = computed(() => props.exercises[currentIndex.value])
const isLast = computed(() => currentIndex.value === props.exercises.length - 1)
const isCorrect = computed(() => selectedAnswer.value === currentExercise.value?.correct_answer)

const selectAnswer = (answer) => {
    if (isAnswered.value) return
    
    selectedAnswer.value = answer
    isAnswered.value = true
    
    if (answer === currentExercise.value?.correct_answer) {
        correctCount.value++
        audio.playCorrect()
    } else {
        audio.playWrong()
    }
}

const next = () => {
    if (isLast.value) {
        emit('next')
    } else {
        currentIndex.value++
        selectedAnswer.value = null
        isAnswered.value = false
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Practice</h2>
            <p class="text-gray-600 dark:text-gray-400">{{ currentIndex + 1 }} / {{ exercises.length }}</p>
        </div>
        
        <!-- Progress -->
        <div class="mb-8">
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div 
                    class="h-full bg-blue-500 rounded-full transition-all duration-300"
                    :style="{ width: `${((currentIndex + 1) / exercises.length) * 100}%` }"
                ></div>
            </div>
        </div>
        
        <!-- Question -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-6">
            <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                {{ currentExercise?.question }}
            </p>
            <p v-if="currentExercise?.hint" class="text-sm text-gray-500">
                Hint: {{ currentExercise.hint }}
            </p>
        </div>
        
        <!-- Options -->
        <div class="space-y-3 mb-8">
            <button
                v-for="option in currentExercise?.options || []"
                :key="option"
                @click="selectAnswer(option)"
                :disabled="isAnswered"
                class="w-full p-4 rounded-xl border-2 text-left transition-all"
                :class="{
                    'border-gray-200 dark:border-gray-700 hover:border-blue-500': !isAnswered,
                    'border-green-500 bg-green-50 dark:bg-green-900/20': isAnswered && option === currentExercise?.correct_answer,
                    'border-red-500 bg-red-50 dark:bg-red-900/20': isAnswered && selectedAnswer === option && !isCorrect,
                    'border-gray-200 dark:border-gray-700 opacity-50': isAnswered && option !== selectedAnswer && option !== currentExercise?.correct_answer,
                }"
            >
                <div class="flex items-center justify-between">
                    <span class="text-gray-900 dark:text-white">{{ option }}</span>
                    <CheckCircleIcon v-if="isAnswered && option === currentExercise?.correct_answer" class="w-6 h-6 text-green-500" />
                    <XCircleIcon v-if="isAnswered && selectedAnswer === option && !isCorrect" class="w-6 h-6 text-red-500" />
                </div>
            </button>
        </div>
        
        <!-- Feedback -->
        <div v-if="isAnswered" class="mb-8">
            <div 
                class="p-4 rounded-xl"
                :class="isCorrect ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'"
            >
                <p class="font-medium" :class="isCorrect ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                    {{ isCorrect ? '✅ Correct!' : '❌ Not quite right' }}
                </p>
                <p v-if="currentExercise?.explanation" class="text-sm mt-1" :class="isCorrect ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                    {{ currentExercise.explanation }}
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
            
            <button
                v-if="isAnswered"
                @click="next"
                class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-medium hover:shadow-lg transition-all"
            >
                <span>{{ isLast ? 'Continue' : 'Next' }}</span>
                <ArrowRightIcon class="w-5 h-5" />
            </button>
        </div>
    </div>
</template>
