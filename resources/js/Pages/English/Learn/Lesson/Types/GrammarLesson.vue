<script setup>
import { ref, computed } from 'vue'
import QuizExercise from '../Components/QuizExercise.vue'

const props = defineProps({
    content: Object,
    phase: String,
    currentStep: Number
})

const emit = defineEmits(['answer', 'next', 'phase-change', 'complete'])

// Content sections
const explanation = computed(() => props.content?.explanation || {})
const examples = computed(() => props.content?.examples || [])
const exercises = computed(() => props.content?.exercises || [])
const quiz = computed(() => props.content?.quiz || [])

// Local state
const currentExplanationPage = ref(0)
const currentExerciseIndex = ref(0)
const currentQuizIndex = ref(0)

// Explanation pages (can be multiple slides)
const explanationPages = computed(() => {
    if (Array.isArray(explanation.value)) {
        return explanation.value
    }
    return [explanation.value]
})

// Next explanation page
const nextExplanationPage = () => {
    if (currentExplanationPage.value < explanationPages.value.length - 1) {
        currentExplanationPage.value++
    } else {
        emit('phase-change', 'practice')
    }
}

// Handle exercise answer
const handleExerciseAnswer = (isCorrect) => {
    emit('answer', isCorrect)
    emit('next')
    
    setTimeout(() => {
        if (currentExerciseIndex.value < exercises.value.length - 1) {
            currentExerciseIndex.value++
        } else if (quiz.value.length > 0) {
            emit('phase-change', 'quiz')
        } else {
            emit('complete')
        }
    }, 1500)
}

// Handle quiz answer
const handleQuizAnswer = (isCorrect) => {
    emit('answer', isCorrect)
    emit('next')
    
    setTimeout(() => {
        if (currentQuizIndex.value < quiz.value.length - 1) {
            currentQuizIndex.value++
        } else {
            emit('complete')
        }
    }, 1500)
}
</script>

<template>
    <div>
        <!-- Phase 1: Learn Grammar -->
        <div v-if="phase === 'learn'" class="space-y-6">
            <!-- Phase Header -->
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>📝</span>
                    <span>Grammar Explanation</span>
                </div>
            </div>
            
            <!-- Explanation Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <!-- Title -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">
                    {{ explanationPages[currentExplanationPage]?.title || 'Grammar Rule' }}
                </h3>
                
                <!-- Content -->
                <div class="prose dark:prose-invert max-w-none mb-6">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ explanationPages[currentExplanationPage]?.content || 'Learn this grammar rule...' }}
                    </p>
                </div>
                
                <!-- Table if provided -->
                <div 
                    v-if="explanationPages[currentExplanationPage]?.table"
                    class="overflow-x-auto mb-6"
                >
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th 
                                    v-for="(header, i) in explanationPages[currentExplanationPage].table.headers" 
                                    :key="i"
                                    class="px-4 py-2 text-left font-semibold text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600"
                                >
                                    {{ header }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr 
                                v-for="(row, i) in explanationPages[currentExplanationPage].table.rows" 
                                :key="i"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td 
                                    v-for="(cell, j) in row" 
                                    :key="j"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600"
                                >
                                    {{ cell }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Tip -->
                <div 
                    v-if="explanationPages[currentExplanationPage]?.tip"
                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4"
                >
                    <p class="text-amber-800 dark:text-amber-200 text-sm">
                        💡 <strong>Tip:</strong> {{ explanationPages[currentExplanationPage].tip }}
                    </p>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex justify-center">
                <button
                    @click="nextExplanationPage"
                    class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition-all hover:scale-105 flex items-center gap-2"
                >
                    {{ currentExplanationPage < explanationPages.length - 1 ? 'Next' : 'Start Practice' }}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            
            <!-- Page dots -->
            <div v-if="explanationPages.length > 1" class="flex justify-center gap-2">
                <span 
                    v-for="(_, i) in explanationPages" 
                    :key="i"
                    :class="[
                        'w-2 h-2 rounded-full transition-colors',
                        i === currentExplanationPage ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-600'
                    ]"
                ></span>
            </div>
        </div>
        
        <!-- Phase 2: Practice -->
        <div v-else-if="phase === 'practice'" class="space-y-6">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>✏️</span>
                    <span>Practice</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Exercise {{ currentExerciseIndex + 1 }} of {{ exercises.length }}
                </p>
            </div>
            
            <QuizExercise
                v-if="exercises.length > 0"
                :question="exercises[currentExerciseIndex]"
                @answer="handleExerciseAnswer"
            />
            
            <div v-else class="text-center py-12">
                <button 
                    @click="emit('phase-change', 'quiz')"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    Continue to Quiz
                </button>
            </div>
        </div>
        
        <!-- Phase 3: Quiz -->
        <div v-else-if="phase === 'quiz'" class="space-y-6">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>📝</span>
                    <span>Final Quiz</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Question {{ currentQuizIndex + 1 }} of {{ quiz.length }}
                </p>
            </div>
            
            <QuizExercise
                v-if="quiz.length > 0"
                :question="quiz[currentQuizIndex]"
                @answer="handleQuizAnswer"
            />
            
            <div v-else class="text-center py-12">
                <button 
                    @click="emit('complete')"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg"
                >
                    Complete Lesson
                </button>
            </div>
        </div>
    </div>
</template>
