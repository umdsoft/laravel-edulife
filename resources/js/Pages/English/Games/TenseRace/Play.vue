<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Array,
    powerups: Array,
    tenseCategories: Array,
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const gameStarted = ref(false)
const gameOver = ref(false)
const sessionId = ref(null)
const selectedMode = ref('mixed')
const currentQuestion = ref(null)
const isAnswering = ref(false)
const selectedAnswer = ref(null)
const eliminatedOptions = ref([])
const showFeedback = ref(false)
const lastAnswerCorrect = ref(false)
const lastPointsEarned = ref(0)
const lastExplanation = ref('')
const showExitModal = ref(false)
const results = ref({})
const showHint = ref(false)
const hintInfo = ref(null)

// Timers
const gameTimer = ref(null)
const answerStartTime = ref(null)
const answerTimer = ref(null)
const answerProgress = ref(100)

const gameState = ref({
    score: 0,
    streak: 0,
    bestStreak: 0,
    timeRemaining: 60,
    questionsAnswered: 0,
    totalQuestions: 0,
    availablePowerups: {},
    doubleXpActive: false,
    doubleXpRemaining: 0,
})

// Computed
const progressPercentage = computed(() => {
    if (gameState.value.totalQuestions === 0) return 0
    return (gameState.value.questionsAnswered / gameState.value.totalQuestions) * 100
})

// Methods
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = Math.floor(seconds % 60)
    return mins > 0 ? `${mins}:${secs.toString().padStart(2, '0')}` : `${secs}s`
}

const getTenseName = (tenseId) => {
    const tense = props.tenseCategories?.find(t => t.id === tenseId)
    return tense?.name_uz || tense?.name || tenseId
}

const getQuestionTypeName = (type) => {
    const names = {
        'identify': 'Aniqlash',
        'fill_blank': 'Bo\'sh joy',
        'transform': 'O\'zgartirish',
    }
    return names[type] || type
}

function getModeIcon(mode) {
    const icons = {
        'mixed': '🏎️',
        'identify': '🎯',
        'fill_blank': '📝',
        'transform': '🔄'
    }
    return icons[mode] || '🎮'
}

function getModeBgGradient(mode) {
    const gradients = {
        'mixed': 'from-indigo-500 to-purple-600',
        'identify': 'from-blue-500 to-cyan-600',
        'fill_blank': 'from-green-500 to-emerald-600',
        'transform': 'from-orange-500 to-red-600'
    }
    return gradients[mode] || 'from-indigo-500 to-purple-600'
}

const startGame = async () => {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(route('student.english.games.tense-race.start', props.level.level_number), {
            mode: selectedMode.value
        })

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            currentQuestion.value = data.first_question
            gameState.value.timeRemaining = data.time_limit
            gameState.value.totalQuestions = data.total_questions
            gameState.value.availablePowerups = {}

            // Initialize powerups
            props.powerups?.forEach(p => {
                gameState.value.availablePowerups[p.id] = {
                    ...p,
                    available: true,
                    used: false
                }
            })

            gameStarted.value = true
            startTimers()
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

const startTimers = () => {
    // Main game timer
    gameTimer.value = setInterval(() => {
        if (gameState.value.timeRemaining > 0) {
            gameState.value.timeRemaining -= 0.1
            if (gameState.value.timeRemaining <= 0) {
                endGame()
            }
        }
    }, 100)

    // Answer timer for progress bar
    answerStartTime.value = Date.now()
    answerTimer.value = setInterval(() => {
        const elapsed = (Date.now() - answerStartTime.value) / 1000
        answerProgress.value = Math.max(0, 100 - (elapsed / 15) * 100)
    }, 50)
}

const resetAnswerTimer = () => {
    answerStartTime.value = Date.now()
    answerProgress.value = 100
    showHint.value = false
    hintInfo.value = null
}

const selectAnswer = async (index) => {
    if (isAnswering.value || eliminatedOptions.value.includes(index)) return

    isAnswering.value = true
    selectedAnswer.value = index
    const answerTime = (Date.now() - answerStartTime.value) / 1000

    try {
        const response = await axios.post(route('student.english.games.tense-race.check'), {
            session_id: sessionId.value,
            question_id: currentQuestion.value.id,
            answer: index,
            time: answerTime
        })

        if (response.data.success) {
            const data = response.data.data

            lastAnswerCorrect.value = data.is_correct
            lastPointsEarned.value = data.points_earned
            lastExplanation.value = data.explanation

            // Update game state
            gameState.value.score = data.total_score
            gameState.value.streak = data.streak
            gameState.value.bestStreak = data.best_streak
            gameState.value.questionsAnswered = data.questions_answered

            // Handle double XP
            if (gameState.value.doubleXpActive) {
                gameState.value.doubleXpRemaining--
                if (gameState.value.doubleXpRemaining <= 0) {
                    gameState.value.doubleXpActive = false
                }
            }

            // Show feedback
            showFeedback.value = true
            setTimeout(() => {
                showFeedback.value = false
            }, lastAnswerCorrect.value ? 800 : 1500)

            if (data.game_over) {
                setTimeout(() => endGame(), 500)
            } else {
                // Next question
                setTimeout(() => {
                    currentQuestion.value = data.next_question
                    selectedAnswer.value = null
                    eliminatedOptions.value = []
                    isAnswering.value = false
                    resetAnswerTimer()
                }, lastAnswerCorrect.value ? 300 : 1000)
            }
        }
    } catch (error) {
        console.error('Failed to check answer:', error)
        isAnswering.value = false
    }
}

const usePowerup = async (powerupId) => {
    if (!gameState.value.availablePowerups[powerupId]?.available) return

    try {
        const response = await axios.post(route('student.english.games.tense-race.powerup'), {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            const data = response.data.data

            // Mark powerup as used
            gameState.value.availablePowerups[powerupId].available = false
            gameState.value.availablePowerups[powerupId].used = true

            switch (data.effect) {
                case 'time_added':
                    gameState.value.timeRemaining += data.value
                    break

                case 'double_xp_activated':
                    gameState.value.doubleXpActive = true
                    gameState.value.doubleXpRemaining = data.duration
                    break

                case 'question_skipped':
                    if (data.next_question) {
                        currentQuestion.value = data.next_question
                        gameState.value.questionsAnswered++
                        resetAnswerTimer()
                    }
                    break

                case 'options_eliminated':
                    eliminatedOptions.value = data.eliminated_indices
                    break

                case 'hint_shown':
                    showHint.value = true
                    hintInfo.value = data.hint
                    break
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

const endGame = async () => {
    clearInterval(gameTimer.value)
    clearInterval(answerTimer.value)

    try {
        const response = await axios.post(route('student.english.games.tense-race.complete'), {
            session_id: sessionId.value,
            final_time: props.level.time_limit - gameState.value.timeRemaining
        })

        if (response.data.success) {
            results.value = response.data.data
            gameOver.value = true
        }
    } catch (error) {
        console.error('Failed to complete game:', error)
        gameOver.value = true
    }
}

const restartGame = () => {
    gameOver.value = false
    gameStarted.value = false
    gameState.value = {
        score: 0,
        streak: 0,
        bestStreak: 0,
        timeRemaining: 60,
        questionsAnswered: 0,
        totalQuestions: 0,
        availablePowerups: {},
        doubleXpActive: false,
        doubleXpRemaining: 0,
    }
    currentQuestion.value = null
    selectedAnswer.value = null
    eliminatedOptions.value = []
    results.value = {}
    showHint.value = false
    hintInfo.value = null
}

function goBack() {
    clearInterval(gameTimer.value)
    clearInterval(answerTimer.value)
    router.visit(route('student.english.games.tense-race.index'))
}

function nextLevel() {
    clearInterval(gameTimer.value)
    clearInterval(answerTimer.value)
    const nextLevelNumber = props.level.level_number + 1
    if (nextLevelNumber <= 10) {
        router.visit(route('student.english.games.tense-race.play', nextLevelNumber))
    } else {
        router.visit(route('student.english.games.tense-race.index'))
    }
}

const confirmExit = () => {
    if (gameStarted.value && !gameOver.value) {
        showExitModal.value = true
    } else {
        goBack()
    }
}

const getOptionClass = (index) => {
    if (eliminatedOptions.value.includes(index)) {
        return 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed opacity-50'
    }
    if (selectedAnswer.value === index) {
        if (isAnswering.value) {
            return lastAnswerCorrect.value
                ? 'bg-green-500 text-white scale-105 shadow-lg'
                : 'bg-red-500 text-white scale-105 shadow-lg'
        }
        return 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white scale-105 shadow-lg'
    }
    return 'hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-[1.02] hover:shadow-xl'
}

onMounted(() => {
    // Prevent accidental page leave
    window.addEventListener('beforeunload', (e) => {
        if (gameStarted.value && !gameOver.value) {
            e.preventDefault()
            e.returnValue = ''
        }
    })
})

onUnmounted(() => {
    clearInterval(gameTimer.value)
    clearInterval(answerTimer.value)
})
</script>

<template>
    <Head :title="`Tense Race - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-indigo-200 dark:border-indigo-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin"></div>
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
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="!gameStarted" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-indigo-500 via-purple-500 to-violet-600 rounded-3xl mb-6 shadow-2xl shadow-indigo-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🏎️</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.description_uz || level?.description }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.total_questions }} savol</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_limit }}s</span>
                        </div>

                        <!-- Tenses in this level -->
                        <div v-if="level?.tenses?.length > 0" class="mt-6">
                            <p class="text-gray-700 dark:text-gray-300 text-sm mb-3 font-medium">Bu bosqichdagi zamonlar:</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <span v-for="tense in level.tenses" :key="tense"
                                      class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-3 py-1.5 rounded-lg text-sm border border-indigo-200 dark:border-indigo-800/50 font-medium">
                                    {{ getTenseName(tense) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectedMode = mode.id"
                                :class="[
                                    'group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 shadow-lg border-2 overflow-hidden',
                                    selectedMode === mode.id
                                        ? 'border-indigo-500 ring-4 ring-indigo-300 dark:ring-indigo-700 scale-[1.02]'
                                        : 'border-gray-100 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:scale-[1.02] hover:shadow-2xl'
                                ]">
                            <div class="relative z-10 p-6">
                                <!-- Icon -->
                                <div :class="['w-16 h-16 rounded-2xl flex items-center justify-center text-4xl mb-4 bg-gradient-to-br shadow-xl transition-transform duration-300', selectedMode === mode.id ? 'scale-110' : 'group-hover:scale-110', getModeBgGradient(mode.id)]">
                                    {{ mode.icon }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">{{ mode.name }}</h3>

                                <!-- Description -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed mb-3">{{ mode.description_uz || mode.description }}</p>

                                <!-- Multiplier -->
                                <div class="inline-flex items-center gap-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-3 py-1 rounded-full text-xs font-semibold">
                                    <span>⚡</span>
                                    <span>{{ mode.point_multiplier }}x ball</span>
                                </div>
                            </div>

                            <!-- Selected indicator -->
                            <div v-if="selectedMode === mode.id" class="absolute top-3 right-3 w-7 h-7 bg-indigo-500 rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button @click="goBack" class="flex-1 px-6 py-3.5 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all font-semibold rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            Orqaga qaytish
                        </button>
                        <button @click="startGame" class="flex-1 px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105 text-lg">
                            Boshlash! 🏎️
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameOver && results" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ results.stars >= 3 ? '🏆' : results.stars >= 2 ? '🎉' : results.stars >= 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= results.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ results.stars >= 3 ? 'Ajoyib!' : results.stars >= 2 ? 'Yaxshi!' : results.stars >= 1 ? 'Yomonmas!' : 'Qayta urinib ko\'ring!' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ results.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl p-5 border border-indigo-100 dark:border-indigo-800/50">
                            <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ results.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ results.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ results.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ results.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Tense Stats -->
                    <div v-if="Object.keys(results.tense_stats || {}).length > 0" class="mb-8 bg-gray-50 dark:bg-gray-700/30 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-gray-700 dark:text-gray-300 text-sm font-bold mb-3">Zamon natijalari:</h3>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            <div v-for="(stats, tense) in results.tense_stats" :key="tense"
                                 class="flex justify-between items-center bg-white dark:bg-gray-800 px-3 py-2 rounded-xl border border-gray-100 dark:border-gray-700">
                                <span class="text-gray-800 dark:text-gray-200 text-sm font-medium">{{ getTenseName(tense) }}</span>
                                <span class="text-gray-600 dark:text-gray-400 text-sm font-bold">{{ stats.correct }}/{{ stats.total }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="results.new_achievements?.length > 0" class="mb-8">
                        <h3 class="text-gray-700 dark:text-gray-300 text-sm font-bold mb-3">Yangi yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-2">
                            <div v-for="achievement in results.new_achievements" :key="achievement.id"
                                 class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800/50 rounded-xl px-4 py-2 flex items-center space-x-2 shadow-md">
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-gray-800 dark:text-yellow-300 text-sm font-semibold">{{ achievement.name_uz }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="results.stars > 0 && level.level_number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
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
                        <button @click="confirmExit" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg">
                            <span class="text-2xl">🏎️</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="w-24"></div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ gameState.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ gameState.questionsAnswered }}/{{ gameState.totalQuestions }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savollar</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            gameState.timeRemaining < 10
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="gameState.timeRemaining < 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ formatTime(gameState.timeRemaining) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ gameState.questionsAnswered }}/{{ gameState.totalQuestions }} savol</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-violet-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progressPercentage + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Question Card -->
                <div v-if="currentQuestion" class="space-y-6">
                    <!-- Question Type & Tense Info -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 px-4 py-2 rounded-xl text-sm border border-indigo-200 dark:border-indigo-800/50 font-semibold shadow-md">
                                {{ getQuestionTypeName(currentQuestion?.type) }}
                            </span>
                            <span class="bg-white dark:bg-gray-800 text-purple-700 dark:text-purple-300 px-4 py-2 rounded-xl text-sm border border-purple-200 dark:border-purple-800/50 font-semibold shadow-md">
                                {{ getTenseName(currentQuestion?.tense) }}
                            </span>
                        </div>
                    </div>

                    <!-- Question -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">
                        <!-- Question Type Label -->
                        <div class="text-center mb-4">
                            <span v-if="currentQuestion?.type === 'identify'" class="text-gray-600 dark:text-gray-400 text-sm">
                                Qaysi zamon ishlatilgan?
                            </span>
                            <span v-else-if="currentQuestion?.type === 'fill_blank'" class="text-gray-600 dark:text-gray-400 text-sm">
                                Bo'sh joyni to'g'ri shakl bilan to'ldiring
                            </span>
                            <span v-else-if="currentQuestion?.type === 'transform'" class="text-gray-600 dark:text-gray-400 text-sm">
                                Gapni {{ getTenseName(currentQuestion?.target_tense) }} zamonga o'zgartiring
                            </span>
                        </div>

                        <h2 class="text-gray-900 dark:text-white text-2xl font-bold text-center mb-2">
                            {{ currentQuestion?.sentence }}
                        </h2>

                        <!-- Answer timer indicator -->
                        <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full mt-4 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-100"
                                 :style="{ width: answerProgress + '%' }"></div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <button v-for="(option, index) in currentQuestion?.options" :key="index"
                                @click="selectAnswer(index)"
                                :disabled="isAnswering || eliminatedOptions.includes(index)"
                                :class="[
                                    'p-6 rounded-xl text-left transition-all duration-300 transform bg-white dark:bg-gray-800 shadow-lg border-2',
                                    getOptionClass(index),
                                    'disabled:cursor-not-allowed'
                                ]">
                            <div class="flex items-center space-x-3">
                                <span class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-sm text-gray-700 dark:text-gray-300">
                                    {{ ['A', 'B', 'C', 'D'][index] }}
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ option }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Double XP Indicator -->
                    <div v-if="gameState.doubleXpActive"
                         class="text-center bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800/50 rounded-xl py-3 px-4 shadow-md">
                        <span class="text-yellow-700 dark:text-yellow-300 font-medium">
                            ⚡ 2x XP faol! {{ gameState.doubleXpRemaining }} savol qoldi
                        </span>
                    </div>

                    <!-- Hint Display -->
                    <div v-if="showHint && hintInfo"
                         class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800/50 rounded-xl p-5 shadow-md">
                        <h4 class="text-indigo-700 dark:text-indigo-300 font-bold mb-2">{{ hintInfo.name_uz }} zamoni haqida:</h4>
                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ hintInfo.description }}</p>
                        <p class="text-indigo-600 dark:text-indigo-400 text-xs mt-2 font-medium">
                            <strong>Formula:</strong> {{ hintInfo.formula }}
                        </p>
                    </div>

                    <!-- Powerups -->
                    <div class="flex justify-center flex-wrap gap-3 mt-6">
                        <button v-for="(powerup, id) in gameState.availablePowerups" :key="id"
                                @click="usePowerup(id)"
                                :disabled="!powerup.available || isAnswering"
                                :class="[
                                    'flex flex-col items-center p-4 rounded-xl transition-all bg-white dark:bg-gray-800 shadow-md border-2',
                                    powerup.available
                                        ? 'hover:shadow-lg text-gray-900 dark:text-white cursor-pointer border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:scale-105'
                                        : 'text-gray-400 cursor-not-allowed opacity-50 border-gray-200 dark:border-gray-700'
                                ]">
                            <span class="text-2xl mb-1">{{ powerup.icon }}</span>
                            <span class="text-xs font-medium">{{ powerup.name_uz || powerup.name }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feedback Toast -->
            <Transition name="slide-up">
                <div v-if="showFeedback"
                     class="fixed bottom-8 left-1/2 -translate-x-1/2 px-8 py-4 rounded-xl text-white font-bold text-lg shadow-2xl z-50"
                     :class="lastAnswerCorrect ? 'bg-green-500' : 'bg-red-500'">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">{{ lastAnswerCorrect ? '✓' : '✗' }}</span>
                        <div>
                            <span>{{ lastAnswerCorrect ? `+${lastPointsEarned} ball` : 'Noto\'g\'ri!' }}</span>
                            <p v-if="!lastAnswerCorrect && lastExplanation" class="text-xs mt-1 font-normal">
                                {{ lastExplanation }}
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Exit Confirmation Modal -->
            <Teleport to="body">
                <div v-if="showExitModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 max-w-sm w-full p-6">
                        <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4">O'yindan chiqish?</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">O'yinni tugatmasdan chiqsangiz, progress saqlanmaydi.</p>
                        <div class="flex space-x-3">
                            <button @click="showExitModal = false"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-colors font-semibold">
                                Davom etish
                            </button>
                            <button @click="goBack"
                                    class="flex-1 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors font-semibold">
                                Chiqish
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </StudentLayout>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translate(-50%, 20px);
}
</style>
