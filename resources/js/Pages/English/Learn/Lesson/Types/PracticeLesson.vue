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

// All exercises combined
const allExercises = computed(() => {
    const exercises = props.content?.exercises || []
    const quiz = props.content?.quiz || []
    return [...exercises, ...quiz]
})

const currentIndex = ref(0)
const exerciseRef = ref(null)

// Handle answer (just track, don't auto-advance)
const handleAnswer = (isCorrect) => {
    emit('answer', isCorrect)
    emit('next')
}

// Handle next (user clicked continue button)
const handleNext = () => {
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

// Auto-start in practice mode
onMounted(() => {
    if (props.phase === 'learn') {
        emit('phase-change', 'practice')
    }
})
</script>

<template>
    <div class="space-y-6">
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
</template>
