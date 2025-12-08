<script setup>
import { ref, computed, onMounted } from 'vue'
import QuizExercise from '../Components/QuizExercise.vue'
import WordCard from '../Components/WordCard.vue'
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

// All exercises combined (exercises + quiz)
const allExercises = computed(() => {
    return [...exercises.value, ...quiz.value]
})

const currentIndex = ref(0)
const currentWordIndex = ref(0)
const exerciseRef = ref(null)

// Handle word learned
const nextWord = () => {
    if (currentWordIndex.value < words.value.length - 1) {
        currentWordIndex.value++
        emit('next')
    } else {
        // Move to practice phase
        emit('phase-change', 'practice')
    }
}

// Handle answer (just track, don't auto-advance)
const handleAnswer = (isCorrect) => {
    emit('answer', isCorrect)
}

// Handle next (user clicked continue button)
const handleNext = () => {
    emit('next') // Update global progress
    if (currentIndex.value < allExercises.value.length - 1) {
        currentIndex.value++
    }
}

// Handle finish (user clicked finish button on last question)
const handleFinish = () => {
    emit('complete')
}

// Check if current is last
const isLastExercise = computed(() => currentIndex.value >= allExercises.value.length - 1)

// Auto-start logic
onMounted(() => {
    // Only skip learn phase if there are no words
    if (props.phase === 'learn' && words.value.length === 0) {
        emit('phase-change', 'practice')
    }
})
</script>

<template>
    <div>
        <!-- Phase 1: Learn Words -->
        <div v-if="phase === 'learn'">
            <!-- Word Card -->
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
                    @click="emit('phase-change', 'practice')"
                    class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    {{ t.lesson.continue }}
                </button>
            </div>
        </div>

        <!-- Phase 2: Practice -->
        <div v-else class="space-y-6">
            <!-- Phase Header (O'zbek tilida) -->
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>✏️</span>
                    <span>{{ t.lesson.practice }}</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    {{ t.lesson.exercise }} {{ currentIndex + 1 }}{{ t.lesson.exerciseOf }} {{ allExercises.length }}
                </p>
            </div>
            
            <!-- Exercise -->
            <QuizExercise
                v-if="allExercises.length > 0"
                ref="exerciseRef"
                :key="currentIndex"
                :question="allExercises[currentIndex]"
                :questionIndex="currentIndex"
                :totalQuestions="allExercises.length"
                :isLastQuestion="isLastExercise"
                @answer="handleAnswer"
                @next="handleNext"
                @finish="handleFinish"
            />
            
            <!-- Fallback -->
            <div v-else class="text-center py-12">
                <p class="text-gray-500 mb-4">Mashqlar mavjud emas.</p>
                <button 
                    @click="emit('complete')"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    {{ t.lesson.complete }}
                </button>
            </div>
            
            <!-- Progress dots -->
            <div class="flex justify-center gap-1.5 flex-wrap max-w-xs mx-auto">
                <span 
                    v-for="(_, i) in allExercises" 
                    :key="i"
                    :class="[
                        'w-2 h-2 rounded-full transition-all duration-300',
                        i < currentIndex ? 'bg-emerald-500' :
                        i === currentIndex ? 'bg-indigo-500 scale-125' : 'bg-gray-300 dark:bg-gray-600'
                    ]"
                ></span>
            </div>
        </div>
    </div>
</template>
