<script setup>
import { ref, computed } from 'vue'
import WordCard from '../Components/WordCard.vue'
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

// Content sections
const words = computed(() => props.content?.words || [])
const exercises = computed(() => props.content?.exercises || [])
const quiz = computed(() => props.content?.quiz || [])

// Local state for each phase
const currentWordIndex = ref(0)
const currentExerciseIndex = ref(0)
const currentQuizIndex = ref(0)

// Quiz ref for reset
const exerciseRef = ref(null)
const quizRef = ref(null)

// Handle word learned
const nextWord = () => {
    if (currentWordIndex.value < words.value.length - 1) {
        currentWordIndex.value++
        emit('next')
    } else {
        // Move to practice phase if there are exercises, otherwise to quiz
        if (exercises.value.length > 0) {
            emit('phase-change', 'practice')
        } else if (quiz.value.length > 0) {
            emit('phase-change', 'quiz')
        } else {
            emit('complete')
        }
    }
}

// Handle exercise answer (just track, don't auto-advance)
const handleExerciseAnswer = (isCorrect) => {
    emit('answer', isCorrect)
}

// Handle exercise next (user clicked continue button)
const handleExerciseNext = () => {
    emit('next') // Update global progress
    if (currentExerciseIndex.value < exercises.value.length - 1) {
        currentExerciseIndex.value++
    } else if (quiz.value.length > 0) {
        emit('phase-change', 'quiz')
    } else {
        emit('complete')
    }
}

// Handle exercise finish
const handleExerciseFinish = () => {
    if (quiz.value.length > 0) {
        emit('phase-change', 'quiz')
    } else {
        emit('complete')
    }
}

// Handle quiz answer (just track, don't auto-advance)
const handleQuizAnswer = (isCorrect) => {
    emit('answer', isCorrect)
}

// Handle quiz next (user clicked continue button)
const handleQuizNext = () => {
    emit('next') // Update global progress
    if (currentQuizIndex.value < quiz.value.length - 1) {
        currentQuizIndex.value++
    }
}

// Handle quiz finish (user clicked finish button on last question)
const handleQuizFinish = () => {
    emit('complete')
}

// Check if current is last
const isLastExercise = computed(() => currentExerciseIndex.value >= exercises.value.length - 1)
const isLastQuiz = computed(() => currentQuizIndex.value >= quiz.value.length - 1)
</script>

<template>
    <div>
        <!-- Phase 1: Learn Words -->
        <div v-if="phase === 'learn'">
            <!-- Word Card with integrated progress -->
            <WordCard
                v-if="words.length > 0"
                :key="currentWordIndex"
                :word="words[currentWordIndex]"
                :currentIndex="currentWordIndex"
                :totalWords="words.length"
                @next="nextWord"
            />
            
            <!-- Fallback if no words -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500">O'rganish uchun so'zlar yo'q. Mashqqa o'tilmoqda...</p>
                <button 
                    @click="emit('phase-change', 'quiz')"
                    class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    {{ t.lesson.continue }}
                </button>
            </div>
        </div>
        
        <!-- Phase 2: Practice Exercises (O'zbek tilida) -->
        <div v-else-if="phase === 'practice'" class="space-y-6">
            <!-- Phase Header -->
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>💪</span>
                    <span>{{ t.lesson.practice }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    {{ t.lesson.exercise }} {{ currentExerciseIndex + 1 }}{{ t.lesson.exerciseOf }} {{ exercises.length }}
                </p>
            </div>
            
            <!-- Exercise (using quiz format) -->
            <QuizExercise
                v-if="exercises.length > 0"
                ref="exerciseRef"
                :key="currentExerciseIndex"
                :question="exercises[currentExerciseIndex]"
                :questionIndex="currentExerciseIndex"
                :totalQuestions="exercises.length"
                :isLastQuestion="isLastExercise"
                @answer="handleExerciseAnswer"
                @next="handleExerciseNext"
                @finish="handleExerciseFinish"
            />
            
            <!-- Fallback -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500">Mashqlar yo'q. Testga o'tilmoqda...</p>
                <button 
                    @click="emit('phase-change', 'quiz')"
                    class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    {{ t.lesson.continue }}
                </button>
            </div>
        </div>
        
        <!-- Phase 3: Quiz (O'zbek tilida) -->
        <div v-else-if="phase === 'quiz'" class="space-y-6">
            <!-- Phase Header -->
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>📝</span>
                    <span>{{ t.quiz.finalQuiz }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    {{ t.lesson.question }} {{ currentQuizIndex + 1 }}{{ t.lesson.questionOf }} {{ quiz.length }}
                </p>
            </div>
            
            <!-- Quiz Question -->
            <QuizExercise
                v-if="quiz.length > 0"
                ref="quizRef"
                :key="currentQuizIndex"
                :question="quiz[currentQuizIndex]"
                :questionIndex="currentQuizIndex"
                :totalQuestions="quiz.length"
                :isLastQuestion="isLastQuiz"
                @answer="handleQuizAnswer"
                @next="handleQuizNext"
                @finish="handleQuizFinish"
            />
            
            <!-- Fallback -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500">Test savollari yo'q.</p>
                <button 
                    @click="emit('complete')"
                    class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    {{ t.lesson.complete }}
                </button>
            </div>
        </div>
    </div>
</template>
