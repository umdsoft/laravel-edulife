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
}

// Handle exercise next
const handleExerciseNext = () => {
    emit('next')
    if (currentExerciseIndex.value < exercises.value.length - 1) {
        currentExerciseIndex.value++
    } else if (quiz.value.length > 0) {
        emit('phase-change', 'quiz')
    } else {
        emit('complete')
    }
}

// Handle quiz answer
const handleQuizAnswer = (isCorrect) => {
    emit('answer', isCorrect)
}

// Handle quiz next
const handleQuizNext = () => {
    emit('next')
    if (currentQuizIndex.value < quiz.value.length - 1) {
        currentQuizIndex.value++
    } else {
        emit('complete')
    }
}
</script>

<template>
    <div>
        <!-- Phase 1: Learn Grammar -->
        <div v-if="phase === 'learn'" class="space-y-6">
            <!-- Phase Header -->
            <div class="text-center">
                <div
                    class="inline-flex items-center gap-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>📝</span>
                    <span>Grammar Explanation</span>
                </div>
            </div>

            <!-- Explanation Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 min-h-[400px]">
                
                <!-- Title -->
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center border-b border-gray-100 dark:border-gray-700 pb-4">
                    {{ explanationPages[currentExplanationPage]?.title || 'Grammar Rule' }}
                </h3>

                <!-- Dynamic Content Sections -->
                <div class="space-y-8">
                    <template v-for="(section, idx) in explanationPages[currentExplanationPage]?.sections" :key="idx">
                        
                        <!-- Text Section -->
                        <div v-if="section.type === 'text'" class="prose dark:prose-invert max-w-none">
                            <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed" v-html="section.content"></p>
                        </div>

                        <!-- Table Section -->
                        <div v-else-if="section.type === 'table'" class="overflow-x-auto bg-gray-50 dark:bg-gray-700/30 rounded-xl p-2 border border-gray-100 dark:border-gray-700">
                            <h4 v-if="section.title" class="font-bold text-gray-900 dark:text-white mb-2 ml-2">{{ section.title }}</h4>
                            <table class="w-full text-sm border-collapse bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                                <thead>
                                    <tr class="bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-100 dark:border-indigo-800">
                                        <th v-for="(header, i) in section.headers" :key="i"
                                            class="px-4 py-3 text-left font-bold text-indigo-900 dark:text-indigo-200">
                                            {{ header }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in section.rows" :key="i"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b last:border-0 border-gray-100 dark:border-gray-700">
                                        <td v-for="(cell, j) in row" :key="j"
                                            class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            <span v-html="cell"></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Examples List Section -->
                        <div v-else-if="section.type === 'examples'" class="space-y-3">
                            <h4 v-if="section.title" class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-green-500">✅</span> {{ section.title }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="(item, i) in section.items" :key="i" 
                                    class="bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-xl p-4 flex items-start gap-3 transition-transform hover:scale-[1.02]">
                                    <div class="mt-1 text-green-600 dark:text-green-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white text-lg">{{ item.en }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 text-sm">{{ item.uz }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Common Mistakes Section -->
                        <div v-else-if="section.type === 'mistakes'" class="space-y-3">
                             <h4 v-if="section.title" class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-red-500">⚠️</span> {{ section.title }}
                            </h4>
                            <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-xl p-4 space-y-4">
                                <div v-for="(item, i) in section.items" :key="i" class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="flex-1 flex items-center gap-2 text-red-600 dark:text-red-400 line-through decoration-red-400">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ item.bad }}
                                    </div>
                                    <div class="hidden md:block text-gray-400">→</div>
                                    <div class="flex-1 flex items-center gap-2 text-green-600 dark:text-green-400 font-bold">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ item.good }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tip Section -->
                         <div v-else-if="section.type === 'tip'"
                            class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 rounded-r-xl p-4 flex gap-4 shadow-sm">
                            <div class="text-2xl">💡</div>
                            <div>
                                <h4 class="font-bold text-amber-900 dark:text-amber-100 mb-1">Eslab qoling!</h4>
                                <p class="text-amber-800 dark:text-amber-200 text-sm leading-relaxed">
                                    {{ section.content }}
                                </p>
                            </div>
                        </div>

                    </template>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl">
                 <div class="text-sm text-gray-500">
                    Page {{ currentExplanationPage + 1 }} of {{ explanationPages.length }}
                 </div>
                 
                <button @click="nextExplanationPage"
                    class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all hover:scale-105 flex items-center gap-2 shadow-lg shadow-indigo-600/20">
                    {{ currentExplanationPage < explanationPages.length - 1 ? 'Keyingisi →' : 'Mashqlarni Boshlash 🚀' }}
                </button>
            </div>

            <!-- Custom Dots -->
            <div v-if="explanationPages.length > 1" class="flex justify-center gap-2 mt-4">
                <button v-for="(_, i) in explanationPages" :key="i"
                    @click="currentExplanationPage = i" 
                    :class="[
                    'w-3 h-3 rounded-full transition-all duration-300',
                    i === currentExplanationPage ? 'bg-indigo-600 w-8' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400'
                ]"></button>
            </div>
        </div>

        <!-- Phase 2: Practice -->
        <div v-else-if="phase === 'practice'" class="space-y-6">
            <div class="text-center">
                <div
                    class="inline-flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>✏️</span>
                    <span>Practice</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Exercise {{ currentExerciseIndex + 1 }} of {{ exercises.length }}
                </p>
            </div>

            <QuizExercise v-if="exercises.length > 0" :question="exercises[currentExerciseIndex]"
                @answer="handleExerciseAnswer" @next="handleExerciseNext" />

            <div v-else class="text-center py-12">
                <button @click="emit('phase-change', 'quiz')" class="px-6 py-2 bg-indigo-600 text-white rounded-lg">
                    Continue to Quiz
                </button>
            </div>
        </div>

        <!-- Phase 3: Quiz -->
        <div v-else-if="phase === 'quiz'" class="space-y-6">
            <div class="text-center">
                <div
                    class="inline-flex items-center gap-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-4 py-2 rounded-full text-sm font-medium mb-4">
                    <span>📝</span>
                    <span>Final Quiz</span>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Question {{ currentQuizIndex + 1 }} of {{ quiz.length }}
                </p>
            </div>

            <QuizExercise v-if="quiz.length > 0" :question="quiz[currentQuizIndex]" @answer="handleQuizAnswer"
                @next="handleQuizNext" />

            <div v-else class="text-center py-12">
                <button @click="emit('complete')" class="px-6 py-2 bg-indigo-600 text-white rounded-lg">
                    Complete Lesson
                </button>
            </div>
        </div>
    </div>
</template>
