<script setup>
import { ref, computed, onMounted } from 'vue'
import QuizExercise from '../Components/QuizExercise.vue'
import uz from '@/Lang/uz'

const props = defineProps({
    content: Object,
    phase: String,
    currentStep: Number
})

const emit = defineEmits(['answer', 'next', 'phase-change', 'complete'])

// Translations (O'zbek tili)
const t = uz

// All quiz questions
const allQuestions = computed(() => {
    const exercises = props.content?.exercises || []
    const quiz = props.content?.quiz || []
    return [...exercises, ...quiz]
})

const currentIndex = ref(0)
const quizRef = ref(null)

// Handle answer (just track, don't auto-advance)
const handleAnswer = (isCorrect) => {
    emit('answer', isCorrect)
    emit('next')
}

// Handle next (user clicked continue button)
const handleNext = () => {
    if (currentIndex.value < allQuestions.value.length - 1) {
        currentIndex.value++
    }
}

// Handle finish (user clicked finish button on last question)
const handleFinish = () => {
    emit('complete')
}

// Check if current is last
const isLastQuestion = computed(() => currentIndex.value >= allQuestions.value.length - 1)

// Auto-start in quiz mode (tests skip learn phase)
onMounted(() => {
    if (props.phase === 'learn') {
        emit('phase-change', 'quiz')
    }
})
</script>

<template>
    <div class="space-y-6">
        <!-- Phase Header (O'zbek tilida) -->
        <div class="text-center">
            <div class="inline-flex items-center gap-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                <span>📋</span>
                <span>Modul testi</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                {{ t.lesson.question }} {{ currentIndex + 1 }}{{ t.lesson.questionOf }} {{ allQuestions.length }}
            </p>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-6">
            <div 
                class="bg-gradient-to-r from-red-400 to-red-600 h-2 rounded-full transition-all duration-500"
                :style="{ width: `${((currentIndex + 1) / allQuestions.length) * 100}%` }"
            ></div>
        </div>
        
        <!-- Quiz Question -->
        <QuizExercise
            v-if="allQuestions.length > 0"
            ref="quizRef"
            :key="currentIndex"
            :question="allQuestions[currentIndex]"
            :questionIndex="currentIndex"
            :totalQuestions="allQuestions.length"
            :isLastQuestion="isLastQuestion"
            @answer="handleAnswer"
            @next="handleNext"
            @finish="handleFinish"
        />
        
        <!-- Fallback -->
        <div v-else class="text-center py-12">
            <p class="text-gray-500 mb-4">Test savollari mavjud emas.</p>
            <button 
                @click="emit('complete')"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg"
            >
                {{ t.lesson.complete }}
            </button>
        </div>
    </div>
</template>
