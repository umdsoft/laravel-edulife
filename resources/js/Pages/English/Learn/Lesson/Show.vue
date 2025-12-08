<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import LessonHeader from './Components/LessonHeader.vue'
import LessonProgress from './Components/LessonProgress.vue'
import LessonComplete from './Components/LessonComplete.vue'
import uz from '@/Lang/uz'

// Lesson type components
import VocabularyLesson from './Types/VocabularyLesson.vue'
import GrammarLesson from './Types/GrammarLesson.vue'
import PracticeLesson from './Types/PracticeLesson.vue'
import ConversationLesson from './Types/ConversationLesson.vue'
import TestLesson from './Types/TestLesson.vue'

const props = defineProps({
    lesson: Object,
    content: Object,
    module: Object,
    userProgress: Object,
    nextLesson: Object
})

// Translations (O'zbek tili)
const t = uz

// Lesson state
const phase = ref('learn') // learn, practice, quiz, complete, failed
const currentStep = ref(0)
const correctCount = ref(0)
const incorrectCount = ref(0)
const streak = ref(0)
const maxStreak = ref(0)
const startTime = ref(null)
const elapsedTime = ref(0)
const isSubmitting = ref(false)
const completionData = ref(null)

// Timer
let timerInterval = null

// Total steps calculation
const totalSteps = computed(() => {
    const content = props.content || {}
    const wordsCount = content.words?.length || 0
    const exercisesCount = content.exercises?.length || 0
    const quizCount = content.quiz?.length || 0
    return wordsCount + exercisesCount + quizCount || content.totalSteps || 10
})

// Calculate rewards (client-side preview)
const calculateRewards = () => {
    const total = correctCount.value + incorrectCount.value
    const accuracy = total > 0 ? Math.round((correctCount.value / total) * 100) : 0
    const timeInMinutes = elapsedTime.value / 60

    // Base rewards by lesson type
    const baseRewards = {
        vocabulary: { xp: 2, coins: 1 },
        grammar: { xp: 3, coins: 1 },
        practice: { xp: 2, coins: 1 },
        conversation: { xp: 3, coins: 1 },
        standard: { xp: 2, coins: 1 },
        review: { xp: 2, coins: 1 },
        test: { xp: 5, coins: 2 }
    }

    let xp = baseRewards[props.lesson.type]?.xp || 2
    let coins = baseRewards[props.lesson.type]?.coins || 1

    // Accuracy bonus
    if (accuracy >= 90) xp += 1

    // Time bonus (completed quickly)
    const timeLimit = props.lesson.type === 'vocabulary' ? 3 : 5
    if (timeInMinutes < timeLimit) xp += 1

    // Streak bonus
    if (maxStreak.value >= 10) xp += 2
    else if (maxStreak.value >= 5) xp += 1
    else if (maxStreak.value >= 3) xp += 1

    // Perfect lesson bonus
    if (accuracy === 100 && incorrectCount.value === 0) {
        xp += 1
        coins += 1
    }

    // Stars calculation
    const stars = accuracy >= 90 ? 3 : accuracy >= 70 ? 2 : accuracy >= 50 ? 1 : 0

    return {
        xp: Math.min(xp, props.lesson.type === 'test' ? 10 : 6),
        coins: Math.min(coins, props.lesson.type === 'test' ? 4 : 3),
        accuracy,
        stars
    }
}

// Handle answer from exercises
const handleAnswer = (isCorrect) => {
    if (isCorrect) {
        correctCount.value++
        streak.value++
        maxStreak.value = Math.max(maxStreak.value, streak.value)
    } else {
        incorrectCount.value++
        streak.value = 0
    }
}

// Complete lesson
const completeLesson = async () => {
    if (isSubmitting.value) return

    const rewards = calculateRewards()

    // Check if passed (80%+)
    if (rewards.accuracy < 80) {
        phase.value = 'failed'
        return
    }

    isSubmitting.value = true

    try {
        await router.post(route('student.english.lesson.complete', props.lesson.id), {
            score: rewards.accuracy,
            time_spent: elapsedTime.value,
            correct_answers: correctCount.value,
            total_questions: correctCount.value + incorrectCount.value,
            max_streak: maxStreak.value
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                completionData.value = rewards
                phase.value = 'complete'
            },
            onError: (errors) => {
                console.error('Failed to complete lesson:', errors)
            },
            onFinish: () => {
                isSubmitting.value = false
            }
        })
    } catch (e) {
        console.error('Failed to save progress:', e)
        isSubmitting.value = false
        // Still show completion screen
        completionData.value = rewards
        phase.value = 'complete'
    }
}

// Component mapping
const lessonComponents = {
    vocabulary: VocabularyLesson,
    grammar: GrammarLesson,
    practice: PracticeLesson,
    conversation: ConversationLesson,
    standard: PracticeLesson,
    review: PracticeLesson,
    test: TestLesson
}

const currentComponent = computed(() => {
    return lessonComponents[props.lesson.type] || PracticeLesson
})

// Format time display
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

// Start timer on mount
onMounted(() => {
    startTime.value = Date.now()
    timerInterval = setInterval(() => {
        elapsedTime.value = Math.floor((Date.now() - startTime.value) / 1000)
    }, 1000)
})

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})

// Darslarga qaytish (to'g'ridan-to'g'ri)
const goBackToLessons = () => {
    // Force hard reload to ensure clean state
    window.location.href = '/student/english/levels'
}

// Exit lesson (with confirmation)
const exitLesson = () => {
    // If completed or failed, just go back
    if (phase.value === 'complete' || phase.value === 'failed') {
        goBackToLessons()
        return
    }

    // In progress: ask for confirmation
    if (confirm(t.lesson.exitConfirm || 'Darsni tugatmasdan chiqmoqchimisiz?')) {
        goBackToLessons()
    }
}

// Next lesson
const goToNextLesson = () => {
    console.log('goToNextLesson called')
    if (props.nextLesson) {
        try {
            router.visit(route('student.english.lesson', props.nextLesson.id), {
                preserveScroll: false
            })
        } catch (error) {
            console.error('Router error:', error)
            window.location.href = `/student/english/lesson/${props.nextLesson.id}`
        }
    } else {
        goBackToLessons()
    }
}

// Retry lesson
const retryLesson = () => {
    console.log('retryLesson called')
    correctCount.value = 0
    incorrectCount.value = 0
    streak.value = 0
    maxStreak.value = 0
    currentStep.value = 0
    phase.value = 'learn'
    startTime.value = Date.now()
    elapsedTime.value = 0
}
</script>

<template>

    <Head :title="`Ingliz tili - ${lesson?.title || 'Dars'}`" />

    <!-- Lesson uses minimal layout without full StudentLayout nav -->
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">

        <!-- Lesson Header -->
        <LessonHeader :lesson="lesson" :module="module" :elapsedTime="elapsedTime" :streak="streak"
            @exit="exitLesson" />

        <!-- Progress Bar -->
        <LessonProgress v-if="phase !== 'complete' && phase !== 'failed'" :current="currentStep" :total="totalSteps"
            :correct="correctCount" :incorrect="incorrectCount" />

        <!-- Main Content -->
        <main class="max-w-3xl mx-auto px-4 py-6 pt-20">

            <!-- Active Lesson -->
            <component v-if="phase !== 'complete' && phase !== 'failed'" :is="currentComponent" :content="content"
                :phase="phase" :currentStep="currentStep" @answer="handleAnswer" @next="currentStep++"
                @phase-change="(p) => phase = p" @complete="completeLesson" />

            <!-- Completion Screen -->
            <LessonComplete v-else-if="phase === 'complete'" :lesson="lesson"
                :rewards="completionData || calculateRewards()" :stats="{
                    correct: correctCount,
                    incorrect: incorrectCount,
                    maxStreak: maxStreak,
                    time: elapsedTime
                }" :nextLesson="nextLesson" @next="goToNextLesson" @retry="retryLesson" @exit="exitLesson" />

            <!-- ✅ MUVAFFAQIYATSIZ EKRANI (O'zbek tilida) -->
            <div v-else-if="phase === 'failed'" class="text-center py-12">
                <div class="text-7xl mb-6">😔</div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ t.lesson.notPassed }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    {{ t.lesson.needPass }}
                </p>
                <p class="text-2xl font-bold text-red-500 mb-4">
                    {{ t.lesson.accuracy }}: {{ calculateRewards().accuracy }}%
                </p>
                <p class="text-gray-500 dark:text-gray-400 mb-8">
                    {{ t.lesson.tryAgainMessage }}
                </p>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm mx-auto mb-8">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-emerald-600">{{ correctCount }}</div>
                            <div class="text-sm text-gray-500">{{ t.lesson.correctAnswers }}</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-red-500">{{ incorrectCount }}</div>
                            <div class="text-sm text-gray-500">{{ t.lesson.wrongAnswers }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button @click="retryLesson" type="button"
                        class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98]">
                        <span>{{ t.lesson.tryAgain }}</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button @click="goBackToLessons" type="button"
                        class="px-6 py-4 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-semibold text-lg transition-all duration-200 flex items-center justify-center gap-2 border-2 border-gray-200 dark:border-gray-600 transform hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>{{ t.lesson.backToLessons }}</span>
                    </button>
                </div>
            </div>

        </main>
    </div>
</template>
