<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'
import {
    XMarkIcon,
    StarIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Array,
    powerups: Array,
    categories: Array,
})

// Game state
const isLoading = ref(false)
const gameStarted = ref(false)
const gameOver = ref(false)
const sessionId = ref(null)
const selectedMode = ref('classic')
const currentQuestion = ref(null)
const isAnswering = ref(false)
const selectedAnswer = ref(null)
const eliminatedOptions = ref([])
const showFeedback = ref(false)
const lastAnswerCorrect = ref(false)
const lastPointsEarned = ref(0)
const showExitModal = ref(false)
const results = ref({})

// Timers
const gameTimer = ref(null)
const answerStartTime = ref(null)
const answerTimer = ref(null)
const answerProgress = ref(100)

const gameState = ref({
    score: 0,
    streak: 0,
    bestStreak: 0,
    lives: null,
    timeRemaining: 60,
    questionsAnswered: 0,
    totalQuestions: 0,
    availablePowerups: {},
    doublePointsActive: false,
    doublePointsRemaining: 0,
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

const getCategoryName = (categoryId) => {
    const category = props.categories?.find(c => c.id === categoryId)
    return category?.name || categoryId
}

const getModeIcon = (modeId) => {
    const mode = props.gameModes?.find(m => m.id === modeId)
    return mode?.icon || '🔥'
}

const startGame = async () => {
    isLoading.value = true
    try {
        const response = await axios.post(route('student.english.games.rapid-fire.start', props.level.level_number), {
            mode: selectedMode.value
        })

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            currentQuestion.value = data.first_question
            gameState.value.timeRemaining = data.time_limit
            gameState.value.lives = data.lives
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
        }
    } catch (error) {
        console.error('Failed to start game:', error)
        alert('O\'yinni boshlashda xatolik yuz berdi')
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
        answerProgress.value = Math.max(0, 100 - (elapsed / 10) * 100)
    }, 50)
}

const resetAnswerTimer = () => {
    answerStartTime.value = Date.now()
    answerProgress.value = 100
}

const selectAnswer = async (index) => {
    if (isAnswering.value || eliminatedOptions.value.includes(index)) return

    isAnswering.value = true
    selectedAnswer.value = index
    const answerTime = (Date.now() - answerStartTime.value) / 1000

    try {
        const response = await axios.post(route('student.english.games.rapid-fire.check'), {
            session_id: sessionId.value,
            question_id: currentQuestion.value.id,
            answer: index,
            time: answerTime
        })

        if (response.data.success) {
            const data = response.data.data

            lastAnswerCorrect.value = data.is_correct
            lastPointsEarned.value = data.points_earned

            // Update game state
            gameState.value.score = data.total_score
            gameState.value.streak = data.streak
            gameState.value.bestStreak = data.best_streak
            gameState.value.lives = data.lives
            gameState.value.questionsAnswered = data.questions_answered

            // Handle double points
            if (gameState.value.doublePointsActive) {
                gameState.value.doublePointsRemaining--
                if (gameState.value.doublePointsRemaining <= 0) {
                    gameState.value.doublePointsActive = false
                }
            }

            // Show feedback
            showFeedback.value = true
            setTimeout(() => {
                showFeedback.value = false
            }, 800)

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
                }, 300)
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
        const response = await axios.post(route('student.english.games.rapid-fire.powerup'), {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            const data = response.data.data

            // Mark powerup as used
            gameState.value.availablePowerups[powerupId].available = false
            gameState.value.availablePowerups[powerupId].used = true

            switch (data.effect) {
                case 'time_frozen':
                    // Pause timer briefly
                    clearInterval(gameTimer.value)
                    setTimeout(() => startTimers(), data.duration * 1000)
                    break

                case 'double_points_activated':
                    gameState.value.doublePointsActive = true
                    gameState.value.doublePointsRemaining = data.questions
                    break

                case 'life_added':
                    gameState.value.lives = data.lives
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
        const response = await axios.post(route('student.english.games.rapid-fire.complete'), {
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
        lives: null,
        timeRemaining: 60,
        questionsAnswered: 0,
        totalQuestions: 0,
        availablePowerups: {},
        doublePointsActive: false,
        doublePointsRemaining: 0,
    }
    currentQuestion.value = null
    selectedAnswer.value = null
    eliminatedOptions.value = []
    results.value = {}
}

const confirmExit = () => {
    if (gameStarted.value && !gameOver.value) {
        showExitModal.value = true
    } else {
        router.visit(route('student.english.games.rapid-fire.index'))
    }
}

const goBack = () => {
    router.visit(route('student.english.games.rapid-fire.index'))
}

const getOptionClass = (index) => {
    if (eliminatedOptions.value.includes(index)) {
        return 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-50 border-2 border-gray-200 dark:border-gray-600'
    }
    if (selectedAnswer.value === index) {
        if (isAnswering.value) {
            return lastAnswerCorrect.value
                ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white scale-105 border-2 border-green-400 shadow-xl shadow-green-500/30'
                : 'bg-gradient-to-r from-red-500 to-rose-600 text-white scale-105 border-2 border-red-400 shadow-xl shadow-red-500/30 animate-shake'
        }
        return 'bg-gradient-to-r from-orange-500 to-red-600 text-white scale-105 border-2 border-transparent shadow-xl shadow-orange-500/30'
    }
    return 'bg-white dark:bg-gray-800 text-gray-800 dark:text-white hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:scale-[1.02] border-2 border-gray-200 dark:border-gray-700 hover:border-orange-400 shadow-lg hover:shadow-xl'
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
    <Head :title="`Rapid Fire - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-orange-100 to-red-100 dark:from-orange-900/30 dark:to-red-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-orange-200 dark:border-orange-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-orange-600 border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-gray-800 dark:text-white text-2xl font-bold mb-2">O'yin yuklanmoqda...</p>
                    <p class="text-gray-500 dark:text-gray-400">Iltimos kuting</p>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="!gameStarted" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-500 via-red-500 to-orange-600 rounded-3xl mb-6 shadow-2xl shadow-orange-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🔥</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.description_uz || level?.description }}</p>
                    </div>

                    <!-- Mode Selection -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 mb-6 shadow-xl border border-gray-100 dark:border-gray-700">
                        <label class="text-gray-700 dark:text-gray-300 text-sm font-semibold block mb-4">O'yin rejimini tanlang:</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button v-for="mode in gameModes" :key="mode.id"
                                    @click="selectedMode = mode.id"
                                    class="group p-6 rounded-xl text-left transition-all duration-300 hover:scale-[1.02] shadow-lg border-2"
                                    :class="selectedMode === mode.id
                                        ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white border-transparent shadow-xl shadow-orange-500/30'
                                        : 'bg-white dark:bg-gray-700/50 text-gray-900 dark:text-white border-gray-200 dark:border-gray-600 hover:border-orange-400'">
                                <div class="flex items-center space-x-4">
                                    <span class="text-3xl">{{ mode.icon }}</span>
                                    <div class="flex-1">
                                        <div class="font-bold text-lg mb-1">{{ mode.name }}</div>
                                        <div class="text-sm opacity-90">{{ mode.point_multiplier }}x ball</div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Start Button -->
                    <div class="text-center">
                        <button @click="startGame"
                                class="px-12 py-4 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl font-bold text-lg hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Boshlash! 🔥
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-8 text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
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
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ results.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">+{{ results.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ results.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-5 border border-red-100 dark:border-red-800/50">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ results.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Additional Stats -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-5 mb-8 space-y-3 border border-gray-100 dark:border-gray-600">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">To'g'ri javoblar</span>
                            <span class="text-green-600 dark:text-green-400 font-bold">{{ results.correct }}/{{ results.total_questions }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tezlik (QPM)</span>
                            <span class="text-gray-900 dark:text-white font-bold">{{ results.qpm }}</span>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="results.new_achievements?.length > 0" class="mb-6">
                        <h3 class="text-gray-600 dark:text-gray-400 text-sm font-semibold mb-3">Yangi yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-2">
                            <div v-for="achievement in results.new_achievements" :key="achievement.id"
                                 class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/50 rounded-lg px-3 py-2 flex items-center space-x-2">
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-gray-900 dark:text-white text-sm font-medium">{{ achievement.name_uz }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash 🔥
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
                            <XMarkIcon class="w-5 h-5" />
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-lg">
                            <span class="text-2xl">{{ getModeIcon(selectedMode) }}</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 text-sm">
                            Savol {{ gameState.questionsAnswered + 1 }}
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center mb-5">
                        <div class="bg-gradient-to-br from-orange-50 to-red-100 dark:from-orange-900/30 dark:to-red-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/50">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ gameState.streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div v-if="gameState.lives !== null" class="bg-gradient-to-br from-pink-50 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-pink-100 dark:border-pink-800/50">
                            <div class="flex items-center justify-center space-x-1 mb-1">
                                <HeartIcon v-for="i in 3" :key="i"
                                           class="w-6 h-6"
                                           :class="i <= gameState.lives ? 'text-red-400 fill-red-400' : 'text-gray-300 dark:text-gray-600'" />
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Jonlar</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            gameState.timeRemaining < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50',
                            gameState.lives === null ? 'col-span-2' : ''
                        ]">
                            <div :class="gameState.timeRemaining < 30 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ formatTime(gameState.timeRemaining) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div>
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ gameState.questionsAnswered }}/{{ gameState.totalQuestions }} savol</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-orange-500 via-red-500 to-orange-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progressPercentage + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Question Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 mb-6 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <span class="bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-4 py-2 rounded-full text-sm font-semibold border border-orange-200 dark:border-orange-800/50">
                            {{ getCategoryName(currentQuestion?.category) }}
                        </span>
                    </div>

                    <h2 class="text-gray-900 dark:text-white text-2xl md:text-3xl font-bold text-center mb-6">
                        {{ currentQuestion?.question }}
                    </h2>

                    <!-- Answer timer indicator -->
                    <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-green-500 to-orange-500 rounded-full transition-all duration-100"
                             :style="{ width: `${answerProgress}%` }"></div>
                    </div>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <button v-for="(option, index) in currentQuestion?.options" :key="index"
                            @click="selectAnswer(index)"
                            :disabled="isAnswering || eliminatedOptions.includes(index)"
                            class="p-6 rounded-xl text-left transition-all duration-300 transform"
                            :class="getOptionClass(index)">
                        <div class="flex items-center space-x-3">
                            <span class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-gray-700 dark:text-gray-300"
                                  :class="selectedAnswer === index && isAnswering ? (lastAnswerCorrect ? 'bg-white/30' : 'bg-white/30') : ''">
                                {{ ['A', 'B', 'C', 'D'][index] }}
                            </span>
                            <span class="font-semibold text-lg">{{ option }}</span>
                        </div>
                    </button>
                </div>

                <!-- Powerups -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
                    <h3 class="text-gray-700 dark:text-gray-300 font-semibold mb-4 text-center">Poweruplar</h3>
                    <div class="flex justify-center flex-wrap gap-4">
                        <button v-for="(powerup, id) in gameState.availablePowerups" :key="id"
                                @click="usePowerup(id)"
                                :disabled="!powerup.available || isAnswering"
                                class="flex flex-col items-center p-4 rounded-xl transition-all hover:scale-105 min-w-[100px]"
                                :class="powerup.available
                                    ? 'bg-gradient-to-br from-purple-50 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/20 hover:from-purple-100 hover:to-indigo-200 dark:hover:from-purple-900/40 dark:hover:to-indigo-900/30 text-gray-900 dark:text-white cursor-pointer shadow-lg hover:shadow-xl border border-purple-200 dark:border-purple-800/50'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-50 border border-gray-200 dark:border-gray-600'">
                            <span class="text-3xl mb-2">{{ powerup.icon }}</span>
                            <span class="text-xs font-medium text-center">{{ powerup.name_uz || powerup.name }}</span>
                        </button>
                    </div>

                    <!-- Double Points Indicator -->
                    <div v-if="gameState.doublePointsActive"
                         class="mt-6 text-center bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/50 rounded-xl py-3 px-4">
                        <span class="text-yellow-600 dark:text-yellow-400 font-semibold">
                            ⚡ 2x ball faol! {{ gameState.doublePointsRemaining }} savol qoldi
                        </span>
                    </div>
                </div>
            </div>

            <!-- Feedback Toast -->
            <Transition name="slide-up">
                <div v-if="showFeedback"
                     class="fixed bottom-8 left-1/2 -translate-x-1/2 px-8 py-4 rounded-xl text-white font-bold text-lg shadow-2xl z-50"
                     :class="lastAnswerCorrect ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-rose-600'">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">{{ lastAnswerCorrect ? '✓' : '✗' }}</span>
                        <span>{{ lastAnswerCorrect ? `+${lastPointsEarned} ball` : 'Noto\'g\'ri!' }}</span>
                    </div>
                </div>
            </Transition>

            <!-- Exit Confirmation Modal -->
            <Teleport to="body">
                <div v-if="showExitModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 max-w-sm w-full p-6">
                        <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4">O'yindan chiqish?</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">O'yinni tugatmasdan chiqsangiz, progress saqlanmaydi.</p>
                        <div class="flex space-x-3">
                            <button @click="showExitModal = false"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition-all font-semibold">
                                Davom etish
                            </button>
                            <button @click="goBack"
                                    class="flex-1 py-3 bg-gradient-to-r from-red-500 to-rose-600 hover:opacity-90 text-white rounded-xl transition-all font-semibold shadow-lg">
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
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.animate-shake {
    animation: shake 0.3s ease-in-out;
}

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
