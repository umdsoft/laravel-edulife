<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: {
        type: Object,
        required: true
    },
    config: {
        type: Object,
        default: () => ({})
    },
    gameModes: {
        type: Array,
        default: () => []
    },
    powerups: {
        type: Array,
        default: () => []
    },
    tenses: {
        type: Array,
        default: () => []
    },
    pronouns: {
        type: Array,
        default: () => []
    }
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const gameCompleted = ref(false)

// Mode selection
const showModeSelect = ref(true)
const selectedMode = ref('classic')

// Session state
const gameStarted = ref(false)
const sessionId = ref(null)
const questions = ref([])
const currentQuestionIndex = ref(0)
const answered = ref(false)
const selectedAnswer = ref(null)
const typedAnswer = ref('')
const lastAnswerCorrect = ref(false)
const lastPointsEarned = ref(0)
const questionStartTime = ref(null)
const timeLeft = ref(null)
const timerInterval = ref(null)
const gameResults = ref({})
const powerupsUsed = ref({})
const hintMessage = ref('')
const eliminatedOptions = ref([])

const gameState = ref({
    score: 0,
    streak: 0,
    correctCount: 0,
    incorrectCount: 0
})

// Animation states
const showCelebration = ref(false)
const streakMilestone = ref(null)

const currentQuestion = computed(() => {
    if (!questions.value.length) return null
    return questions.value[currentQuestionIndex.value]
})

const progress = computed(() => {
    return questions.value.length > 0 ? Math.round(((currentQuestionIndex.value + 1) / questions.value.length) * 100) : 0
})

const timerPercentage = computed(() => {
    const limit = props.level.time_limit || 120
    return timeLeft.value !== null ? (timeLeft.value / limit) * 100 : 100
})

const availablePowerups = computed(() => {
    return props.powerups.filter(p => {
        const used = powerupsUsed.value[p.id] || 0
        return used < (p.uses_per_game || 1)
    })
})

function getModeIcon(mode) {
    const icons = {
        'classic': '📝',
        'timed': '⏱️',
        'typing': '⌨️'
    }
    return icons[mode] || '📝'
}

function getModeBgGradient(mode) {
    const gradients = {
        'classic': 'from-emerald-500 to-teal-600',
        'timed': 'from-orange-500 to-red-600',
        'typing': 'from-violet-500 to-purple-600'
    }
    return gradients[mode] || 'from-emerald-500 to-teal-600'
}

function getTenseName(tenseId) {
    const tense = props.tenses.find(t => t.id === tenseId)
    return tense ? tense.name : tenseId
}

function getQuestionTypeName(type) {
    const names = {
        conjugate: 'Tuslamoq',
        negative: 'Inkor shakli',
        question: "So'roq shakli",
        identify: 'Zamonni aniqlash'
    }
    return names[type] || type
}

async function selectMode(mode) {
    selectedMode.value = mode
    showModeSelect.value = false
    await startGame()
}

async function startGame() {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(
            `/student/english/games/verb-conjugator/start/${props.level.level_number}`,
            { mode: selectedMode.value }
        )

        if (response.data.success) {
            sessionId.value = response.data.data.session_id
            questions.value = response.data.data.questions
            gameStarted.value = true
            currentQuestionIndex.value = 0
            questionStartTime.value = Date.now()

            if (response.data.data.time_limit) {
                timeLeft.value = response.data.data.time_limit
                startTimer()
            }
        } else {
            hasError.value = true
            errorMessage.value = response.data.error || 'Failed to start session'
        }
    } catch (error) {
        hasError.value = true
        errorMessage.value = error.response?.data?.message || 'An error occurred'
    } finally {
        isLoading.value = false
    }
}

function startTimer() {
    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--
        } else {
            clearInterval(timerInterval.value)
            completeGame()
        }
    }, 1000)
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

function getOptionClass(option) {
    if (!answered.value) {
        if (eliminatedOptions.value.includes(option)) {
            return 'bg-gray-200 dark:bg-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed border-2 border-gray-300 dark:border-gray-600 opacity-50'
        }
        return 'bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white hover:bg-emerald-50 dark:hover:bg-emerald-900/20 border-2 border-gray-200 dark:border-gray-600 hover:border-emerald-400 hover:shadow-xl hover:scale-[1.02] cursor-pointer'
    }

    const correctAnswer = currentQuestion.value?.correct_answer

    if (option === correctAnswer) {
        return 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-2 border-green-400'
    }

    if (option === selectedAnswer.value && option !== correctAnswer) {
        return 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 border-2 border-red-400 animate-shake'
    }

    return 'bg-gray-50 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500 border-2 border-gray-200 dark:border-gray-600'
}

async function submitAnswer(answer) {
    if (answered.value || !answer) return

    answered.value = true
    selectedAnswer.value = answer

    const timeSpent = (Date.now() - questionStartTime.value) / 1000

    try {
        const response = await axios.post('/student/english/games/verb-conjugator/answer', {
            session_id: sessionId.value,
            question_index: currentQuestionIndex.value,
            answer: answer,
            time_spent: timeSpent
        })

        if (response.data.success) {
            const result = response.data.data
            lastAnswerCorrect.value = result.correct
            lastPointsEarned.value = result.points_earned
            gameState.value.score = result.total_score
            gameState.value.streak = result.streak
            gameState.value.correctCount = result.progress.correct
            gameState.value.incorrectCount = result.progress.incorrect

            if (result.correct) {
                showCelebration.value = true
                setTimeout(() => { showCelebration.value = false }, 600)
            }
        }
    } catch (error) {
        console.error('Error submitting answer:', error)
    }
}

function nextQuestion() {
    if (currentQuestionIndex.value < questions.value.length - 1) {
        currentQuestionIndex.value++
        answered.value = false
        selectedAnswer.value = null
        typedAnswer.value = ''
        questionStartTime.value = Date.now()
        hintMessage.value = ''
        eliminatedOptions.value = []
    } else {
        completeGame()
    }
}

async function completeGame() {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }

    try {
        const response = await axios.post('/student/english/games/verb-conjugator/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            gameResults.value = response.data.data
            gameCompleted.value = true
            gameStarted.value = false
        }
    } catch (error) {
        console.error('Error completing game:', error)
    }
}

function canUsePowerup(powerupId) {
    const used = powerupsUsed.value[powerupId] || 0
    const powerup = props.powerups.find(p => p.id === powerupId)
    return used < (powerup?.uses_per_game || 1) && !answered.value
}

async function usePowerup(powerupId) {
    if (!canUsePowerup(powerupId)) return

    try {
        const response = await axios.post('/student/english/games/verb-conjugator/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1

            const effect = response.data.data.effect

            if (effect.type === 'hint') {
                hintMessage.value = effect.message
            } else if (effect.type === 'eliminate') {
                eliminatedOptions.value = effect.eliminated_options
            } else if (effect.type === 'skip') {
                nextQuestion()
            }
        }
    } catch (error) {
        console.error('Error using powerup:', error)
    }
}

function playAgain() {
    gameCompleted.value = false
    gameStarted.value = false
    sessionId.value = null
    questions.value = []
    currentQuestionIndex.value = 0
    gameState.value = {
        score: 0,
        streak: 0,
        correctCount: 0,
        incorrectCount: 0
    }
    powerupsUsed.value = {}
    timeLeft.value = null
    answered.value = false
    selectedAnswer.value = null
    typedAnswer.value = ''
    hintMessage.value = ''
    eliminatedOptions.value = []
    showModeSelect.value = true
}

function goBack() {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
    router.visit('/student/english/games/verb-conjugator')
}

function nextLevel() {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
    const nextLevelNumber = props.level.level_number + 1
    router.visit(`/student/english/games/verb-conjugator/play/${nextLevelNumber}`)
}

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<template>
    <Head :title="`Verb Conjugator - ${level?.name || 'Game'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-emerald-200 dark:border-emerald-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-emerald-600 border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-gray-800 dark:text-white text-2xl font-bold mb-2">O'yin yuklanmoqda...</p>
                    <p class="text-gray-500 dark:text-gray-400">Iltimos kuting</p>
                </div>
            </div>

            <!-- Error Screen -->
            <div v-else-if="hasError" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-10 max-w-md shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-5xl">😞</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-3">Xatolik yuz berdi</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">{{ errorMessage }}</p>
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="showModeSelect" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 rounded-3xl mb-6 shadow-2xl shadow-emerald-500/30">
                            <span class="text-5xl filter drop-shadow-lg">📝</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.questions_count }} savol</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_limit || 'Cheksiz' }}s</span>
                        </div>
                    </div>

                    <!-- Tenses Info -->
                    <div v-if="level?.tenses?.length" class="text-center mb-8">
                        <h3 class="text-gray-700 dark:text-gray-300 font-medium mb-3">Bu darajada o'rganiladigan zamonlar:</h3>
                        <div class="flex flex-wrap justify-center gap-2">
                            <span
                                v-for="tense in level.tenses"
                                :key="tense"
                                class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-full text-sm border border-emerald-200 dark:border-emerald-800/50"
                            >
                                {{ getTenseName(tense) }}
                            </span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in (gameModes || [])" :key="mode.id"
                                @click="selectMode(mode.id)"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-500 overflow-hidden">

                            <div class="relative z-10 p-8">
                                <!-- Icon -->
                                <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center text-5xl mb-5 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300', getModeBgGradient(mode.id)]">
                                    {{ getModeIcon(mode.id) }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 transition-colors">{{ mode.name }}</h3>

                                <!-- Description -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ mode.description }}</p>

                                <!-- Play indicator -->
                                <div class="mt-5 flex items-center justify-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                                    <span>O'ynash</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-10 text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameCompleted && gameResults" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ gameResults.stars === 3 ? '🏆' : gameResults.stars === 2 ? '🎉' : gameResults.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= gameResults.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ gameResults.stars === 3 ? 'Mukammal!' : gameResults.stars === 2 ? 'Ajoyib!' : gameResults.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ gameResults.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-5 border border-emerald-100 dark:border-emerald-800/50">
                            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ gameResults.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-teal-50 dark:bg-teal-900/20 rounded-2xl p-5 border border-teal-100 dark:border-teal-800/50">
                            <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">+{{ gameResults.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ gameResults.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameResults.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="playAgain" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="gameResults.stars > 0 && gameResults.level_unlocked" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else class="max-w-6xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-lg">
                            <span class="text-2xl">{{ getModeIcon(selectedMode) }}</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-100 dark:border-emerald-800/50">
                                <span class="text-emerald-500">✨</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ gameState.score }} ball</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ currentQuestionIndex + 1 }}/{{ questions.length }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savol</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ gameState.correctCount }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">To'g'ri</div>
                        </div>
                        <div v-if="timeLeft !== null" :class="[
                            'rounded-xl p-4 border',
                            timeLeft < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 border-cyan-100 dark:border-cyan-800/50'
                        ]">
                            <div :class="timeLeft < 30 ? 'text-red-600 dark:text-red-400' : 'text-cyan-600 dark:text-cyan-400'" class="text-3xl font-bold">{{ formatTime(timeLeft) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                        <div v-else class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">∞</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentQuestionIndex + 1 }}/{{ questions.length }} savol</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Question Card -->
                <div v-if="currentQuestion" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 md:p-8 mb-5">
                    <!-- Question Type Badge -->
                    <div class="flex items-center justify-between mb-6">
                        <span class="px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-xl text-sm font-semibold border border-emerald-200 dark:border-emerald-800/50">
                            {{ getQuestionTypeName(currentQuestion.type) }}
                        </span>
                        <span class="px-4 py-2 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400 rounded-xl text-sm font-semibold border border-teal-200 dark:border-teal-800/50">
                            {{ currentQuestion.tense_name }}
                        </span>
                    </div>

                    <!-- Verb Info -->
                    <div class="text-center mb-6">
                        <div class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-3">
                            {{ currentQuestion.verb.base }}
                        </div>
                        <div class="text-emerald-600 dark:text-emerald-400 text-lg md:text-xl font-medium">
                            {{ currentQuestion.verb.meaning_uz }}
                        </div>
                    </div>

                    <!-- Prompt -->
                    <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-2xl p-5 mb-6 text-center border border-gray-100 dark:border-gray-600/50">
                        <p class="text-gray-900 dark:text-white text-lg md:text-xl mb-2 font-medium">{{ currentQuestion.prompt }}</p>
                        <p class="text-emerald-600 dark:text-emerald-400 text-sm md:text-base">{{ currentQuestion.prompt_uz }}</p>
                    </div>

                    <!-- Pronoun -->
                    <div class="text-center mb-6">
                        <span class="inline-block px-6 py-3 bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/30 dark:to-cyan-900/30 text-gray-900 dark:text-white rounded-xl text-xl font-bold border-2 border-teal-200 dark:border-teal-800/50 shadow-lg">
                            {{ currentQuestion.pronoun.name }}
                        </span>
                    </div>

                    <!-- Hint Message -->
                    <div v-if="hintMessage" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl">
                        <p class="text-blue-800 dark:text-blue-300 text-center">💡 {{ hintMessage }}</p>
                    </div>

                    <!-- Answer Options (Multiple Choice) -->
                    <div v-if="selectedMode !== 'typing'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <button
                            v-for="(option, index) in currentQuestion.options"
                            :key="index"
                            :class="getOptionClass(option)"
                            class="p-5 rounded-xl text-lg font-semibold transition-all duration-300 shadow-lg"
                            :disabled="answered || eliminatedOptions.includes(option)"
                            @click="submitAnswer(option)"
                        >
                            {{ option }}
                        </button>
                    </div>

                    <!-- Typing Input (for typing mode) -->
                    <div v-else class="space-y-4 mb-6">
                        <input
                            v-model="typedAnswer"
                            type="text"
                            class="w-full px-6 py-4 bg-white dark:bg-gray-700 border-2 rounded-xl text-gray-900 dark:text-white text-lg text-center focus:outline-none transition-all shadow-lg"
                            :class="{
                                'border-gray-300 dark:border-gray-600 focus:border-emerald-400': !answered,
                                'border-green-500 dark:border-green-400': answered && lastAnswerCorrect,
                                'border-red-500 dark:border-red-400': answered && !lastAnswerCorrect
                            }"
                            placeholder="Javobni yozing..."
                            :disabled="answered"
                            @keyup.enter="submitAnswer(typedAnswer)"
                        />
                        <button
                            v-if="!answered"
                            @click="submitAnswer(typedAnswer)"
                            class="w-full px-6 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02]"
                        >
                            Tasdiqlash
                        </button>
                    </div>

                    <!-- Feedback -->
                    <div v-if="answered" class="text-center">
                        <div v-if="lastAnswerCorrect" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-xl p-5 mb-4">
                            <div class="text-green-600 dark:text-green-400 text-2xl font-bold mb-1">
                                ✅ To'g'ri! +{{ lastPointsEarned }} ball
                            </div>
                        </div>
                        <div v-else class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl p-5 mb-4">
                            <div class="text-red-600 dark:text-red-400 text-2xl font-bold mb-2">
                                ❌ Noto'g'ri!
                            </div>
                            <div class="text-gray-900 dark:text-white text-lg">
                                To'g'ri javob: <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ currentQuestion.correct_answer }}</span>
                            </div>
                        </div>
                        <button
                            @click="nextQuestion"
                            class="px-8 py-4 bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-700 dark:to-gray-600 hover:from-gray-200 hover:to-slate-200 dark:hover:from-gray-600 dark:hover:to-gray-500 text-gray-900 dark:text-white rounded-xl transition-all font-semibold shadow-lg hover:shadow-xl hover:scale-105 border border-gray-200 dark:border-gray-600"
                        >
                            Keyingi savol →
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="availablePowerups.length && !answered" class="flex flex-wrap justify-center gap-4">
                    <button
                        v-for="powerup in availablePowerups"
                        :key="powerup.id"
                        class="px-5 py-3 bg-white dark:bg-gray-800 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 dark:hover:from-purple-900/20 dark:hover:to-pink-900/20 rounded-xl text-gray-900 dark:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500 hover:scale-105 font-medium"
                        :disabled="!canUsePowerup(powerup.id)"
                        @click="usePowerup(powerup.id)"
                    >
                        {{ powerup.icon }} {{ powerup.name }}
                    </button>
                </div>

                <!-- Celebration Effect -->
                <div v-if="showCelebration" class="fixed inset-0 pointer-events-none z-40">
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <div class="text-8xl animate-bounce">✨</div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.animate-shake {
    animation: shake 0.3s ease-in-out;
}
</style>
