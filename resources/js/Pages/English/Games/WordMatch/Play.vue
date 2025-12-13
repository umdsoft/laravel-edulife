<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Object,
    powerups: Object,
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const gameCompleted = ref(false)

// Session state
const sessionId = ref(null)
const gameMode = ref('classic_match')
const score = ref(0)
const xpEarned = ref(0)
const coinsEarned = ref(0)
const streak = ref(0)
const bestStreak = ref(0)
const matchedPairs = ref(0)
const totalPairs = ref(0)
const timeLimit = ref(120)
const timeRemaining = ref(120)
const timerInterval = ref(null)
const streakMultiplier = ref(1.0)
const streakMilestone = ref(null)

// Mode selection
const showModeSelect = ref(true)

// Classic Match state
const leftColumn = ref([])
const rightColumn = ref([])
const selectedLeft = ref(null)
const selectedRight = ref(null)
const matchedIds = ref([])

// Memory Flip state
const cards = ref([])
const flippedCards = ref([])
const canFlip = ref(true)

// Speed Match state
const currentPair = ref(null)
const speedOptions = ref([])

// Animation states
const showCelebration = ref(false)
const isCheckingAnswer = ref(false)
const wrongPair = ref(null)

// Results
const results = ref(null)

const progress = computed(() => {
    return totalPairs.value > 0 ? Math.round((matchedPairs.value / totalPairs.value) * 100) : 0
})

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0
})

const timerClass = computed(() => {
    if (timerPercentage.value > 50) return 'text-green-500'
    if (timerPercentage.value > 25) return 'text-yellow-500'
    return 'text-red-500'
})

function getModeIcon(mode) {
    const icons = { 'classic_match': '🎯', 'memory_flip': '🧠', 'speed_match': '⚡' }
    return icons[mode] || '🎮'
}

function getModeBgGradient(mode) {
    const gradients = {
        'classic_match': 'from-purple-500 to-indigo-600',
        'memory_flip': 'from-pink-500 to-rose-600',
        'speed_match': 'from-amber-500 to-orange-600'
    }
    return gradients[mode] || 'from-purple-500 to-indigo-600'
}

async function selectMode(mode) {
    gameMode.value = mode
    showModeSelect.value = false
    await startSession()
}

async function startSession() {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(`/student/english/games/word-match/start/${props.level.number}`, {
            game_mode: gameMode.value
        })

        if (response.data.success) {
            sessionId.value = response.data.session_id
            totalPairs.value = response.data.total_pairs
            timeLimit.value = response.data.time_limit
            timeRemaining.value = response.data.time_limit

            if (gameMode.value === 'classic_match') {
                leftColumn.value = response.data.left_column || []
                rightColumn.value = response.data.right_column || []
            } else if (gameMode.value === 'memory_flip') {
                cards.value = response.data.cards || []
            } else if (gameMode.value === 'speed_match') {
                if (response.data.current_pair) {
                    currentPair.value = response.data.current_pair
                    speedOptions.value = response.data.current_pair.options || []
                }
            }

            startTimer()
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
        if (timeRemaining.value > 0) {
            timeRemaining.value--
        } else {
            clearInterval(timerInterval.value)
            completeSession()
        }
    }, 1000)
}

// Classic Match functions
function selectLeftCard(card) {
    if (matchedIds.value.includes(card.id)) return
    selectedLeft.value = card
    if (selectedRight.value) {
        checkClassicMatch()
    }
}

function selectRightCard(card) {
    if (matchedIds.value.includes(card.id)) return
    selectedRight.value = card
    if (selectedLeft.value) {
        checkClassicMatch()
    }
}

async function checkClassicMatch() {
    if (!selectedLeft.value || !selectedRight.value || isCheckingAnswer.value) return

    isCheckingAnswer.value = true
    const startTime = Date.now()

    try {
        const response = await axios.post('/student/english/games/word-match/check', {
            session_id: sessionId.value,
            card1_id: selectedLeft.value.match_id,
            card2_id: selectedRight.value.match_id,
            time_spent: Math.round((Date.now() - startTime) / 1000) + 1
        })

        if (response.data.success) {
            if (response.data.correct) {
                handleCorrectMatch(response.data)
                matchedIds.value.push(selectedLeft.value.id, selectedRight.value.id)
            } else {
                handleWrongMatch()
                wrongPair.value = { left: selectedLeft.value.id, right: selectedRight.value.id }
                setTimeout(() => { wrongPair.value = null }, 500)
            }
        }
    } catch (error) {
        console.error('Check match error:', error)
    } finally {
        selectedLeft.value = null
        selectedRight.value = null
        isCheckingAnswer.value = false
    }
}

// Memory Flip functions
function flipCard(card) {
    if (!canFlip.value || card.flipped || card.matched || flippedCards.value.length >= 2) return

    card.flipped = true
    flippedCards.value.push(card)

    if (flippedCards.value.length === 2) {
        canFlip.value = false
        checkMemoryMatch()
    }
}

async function checkMemoryMatch() {
    const [card1, card2] = flippedCards.value
    const startTime = Date.now()

    try {
        const response = await axios.post('/student/english/games/word-match/check', {
            session_id: sessionId.value,
            card1_id: card1.id,
            card2_id: card2.id,
            time_spent: Math.round((Date.now() - startTime) / 1000) + 1
        })

        if (response.data.success) {
            if (response.data.correct) {
                handleCorrectMatch(response.data)
                card1.matched = true
                card2.matched = true
            } else {
                handleWrongMatch()
                setTimeout(() => {
                    card1.flipped = false
                    card2.flipped = false
                }, 1000)
            }
        }
    } catch (error) {
        console.error('Check match error:', error)
        card1.flipped = false
        card2.flipped = false
    } finally {
        flippedCards.value = []
        setTimeout(() => { canFlip.value = true }, 300)
    }
}

// Speed Match functions
async function selectSpeedAnswer(answer) {
    if (isCheckingAnswer.value) return

    isCheckingAnswer.value = true
    const startTime = Date.now()

    try {
        const response = await axios.post('/student/english/games/word-match/check-speed', {
            session_id: sessionId.value,
            answer: answer,
            time_spent: Math.round((Date.now() - startTime) / 1000) + 1
        })

        if (response.data.success) {
            if (response.data.correct) {
                handleCorrectMatch(response.data)
                if (response.data.next_pair) {
                    currentPair.value = { word: response.data.next_pair.word }
                    speedOptions.value = response.data.next_pair.options
                }
            } else {
                handleWrongMatch()
            }
        }
    } catch (error) {
        console.error('Check speed answer error:', error)
    } finally {
        isCheckingAnswer.value = false
    }
}

function handleCorrectMatch(data) {
    score.value = data.total_score || score.value + (data.score_earned || 0)
    xpEarned.value = data.total_xp || xpEarned.value + (data.xp_earned || 0)
    coinsEarned.value = data.total_coins || coinsEarned.value + (data.coins_earned || 0)
    streak.value = data.streak || 0
    bestStreak.value = Math.max(bestStreak.value, streak.value)
    streakMultiplier.value = data.streak_multiplier || 1.0
    matchedPairs.value = data.progress?.matched || matchedPairs.value + 1

    if (data.streak_milestone) {
        streakMilestone.value = data.streak_milestone
        setTimeout(() => { streakMilestone.value = null }, 2000)
    }

    // Show celebration
    showCelebration.value = true
    setTimeout(() => { showCelebration.value = false }, 600)

    if (data.is_complete) {
        completeSession()
    }
}

function handleWrongMatch() {
    streak.value = 0
    streakMultiplier.value = 1.0
}

async function completeSession() {
    clearInterval(timerInterval.value)

    try {
        const response = await axios.post('/student/english/games/word-match/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            results.value = response.data.results
            gameCompleted.value = true
        }
    } catch (error) {
        console.error('Complete session error:', error)
    }
}

function playAgain() {
    // Reset all state
    gameCompleted.value = false
    results.value = null
    score.value = 0
    xpEarned.value = 0
    coinsEarned.value = 0
    streak.value = 0
    bestStreak.value = 0
    matchedPairs.value = 0
    matchedIds.value = []
    cards.value = []
    flippedCards.value = []
    leftColumn.value = []
    rightColumn.value = []
    selectedLeft.value = null
    selectedRight.value = null
    currentPair.value = null
    speedOptions.value = []
    showModeSelect.value = true
}

function goBack() {
    clearInterval(timerInterval.value)
    router.visit('/student/english/games/word-match')
}

function nextLevel() {
    clearInterval(timerInterval.value)
    const nextLevelNumber = props.level.number + 1
    if (nextLevelNumber <= 10) {
        router.visit(`/student/english/games/word-match/play/${nextLevelNumber}`)
    } else {
        router.visit('/student/english/games/word-match')
    }
}

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<template>
    <Head :title="`So'z Moslashtirish - ${level?.name_uz || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-purple-200 dark:border-purple-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-purple-600 border-t-transparent animate-spin"></div>
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
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="showModeSelect" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 via-violet-500 to-indigo-600 rounded-3xl mb-6 shadow-2xl shadow-purple-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🔗</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name_uz }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-purple-600 dark:text-purple-400 font-bold">{{ level?.cefr_level }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.pairs_count }} juftlik</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_limit?.classic_match || 120 }}s</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in Object.values(gameModes || {})" :key="mode.id"
                                @click="selectMode(mode.id)"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500 overflow-hidden">

                            <div class="relative z-10 p-8">
                                <!-- Icon -->
                                <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center text-5xl mb-5 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300', getModeBgGradient(mode.id)]">
                                    {{ getModeIcon(mode.id) }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 transition-colors">{{ mode.name_uz }}</h3>

                                <!-- Description -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ mode.description_uz }}</p>

                                <!-- Play indicator -->
                                <div class="mt-5 flex items-center justify-center gap-2 text-purple-600 dark:text-purple-400 font-semibold opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
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
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameCompleted && results" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ results.stars === 3 ? '🏆' : results.stars === 2 ? '🎉' : results.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= results.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ results.stars === 3 ? 'Mukammal!' : results.stars === 2 ? 'Ajoyib!' : results.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ results.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ results.score }}</div>
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

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="playAgain" class="flex-1 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="results.stars > 0 && level.number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
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
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg">
                            <span class="text-2xl">{{ getModeIcon(gameMode) }}</span>
                            <span class="font-bold text-white">{{ level?.name_uz }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">{{ level?.cefr_level }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-100 dark:border-purple-800/50">
                                <span class="text-purple-500">✨</span>
                                <span class="text-purple-600 dark:text-purple-400 font-bold">{{ xpEarned }} XP</span>
                            </div>
                            <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-100 dark:border-amber-800/50">
                                <span>🪙</span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold">{{ coinsEarned }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">x{{ streakMultiplier.toFixed(1) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Multiplikator</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            timeRemaining < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="timeRemaining < 30 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ timeRemaining }}s</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ matchedPairs }}/{{ totalPairs }} juftlik</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-purple-500 via-violet-500 to-indigo-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Classic Match Mode -->
                <div v-if="gameMode === 'classic_match'" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-6 md:gap-10">
                        <!-- Left Column -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-center gap-2 mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-2xl">📝</span>
                                <h3 class="text-gray-700 dark:text-gray-300 font-bold text-lg">So'zlar</h3>
                            </div>
                            <div v-for="card in leftColumn" :key="card.id"
                                 @click="selectLeftCard(card)"
                                 :class="[
                                     'p-4 md:p-5 rounded-xl cursor-pointer transition-all duration-300 text-center font-semibold shadow-lg border-2',
                                     matchedIds.includes(card.id)
                                         ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400 scale-95 opacity-70'
                                         : selectedLeft?.id === card.id
                                             ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white scale-[1.03] shadow-2xl shadow-purple-500/30 border-transparent ring-4 ring-purple-300 dark:ring-purple-700'
                                             : wrongPair?.left === card.id
                                                 ? 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 animate-shake border-red-400'
                                                 : 'bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white hover:bg-purple-50 dark:hover:bg-purple-900/20 border-gray-200 dark:border-gray-600 hover:border-purple-400 hover:shadow-xl hover:scale-[1.02]'
                                 ]">
                                {{ card.content }}
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-center gap-2 mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-2xl">🔤</span>
                                <h3 class="text-gray-700 dark:text-gray-300 font-bold text-lg">Tarjimalar</h3>
                            </div>
                            <div v-for="card in rightColumn" :key="card.id"
                                 @click="selectRightCard(card)"
                                 :class="[
                                     'p-4 md:p-5 rounded-xl cursor-pointer transition-all duration-300 text-center font-semibold shadow-lg border-2',
                                     matchedIds.includes(card.id)
                                         ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400 scale-95 opacity-70'
                                         : selectedRight?.id === card.id
                                             ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white scale-[1.03] shadow-2xl shadow-indigo-500/30 border-transparent ring-4 ring-indigo-300 dark:ring-indigo-700'
                                             : wrongPair?.right === card.id
                                                 ? 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 animate-shake border-red-400'
                                                 : 'bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white hover:bg-indigo-50 dark:hover:bg-indigo-900/20 border-gray-200 dark:border-gray-600 hover:border-indigo-400 hover:shadow-xl hover:scale-[1.02]'
                                 ]">
                                {{ card.content }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Memory Flip Mode -->
                <div v-else-if="gameMode === 'memory_flip'" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-center gap-2 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-2xl">🧠</span>
                        <h3 class="text-gray-700 dark:text-gray-300 font-bold text-lg">Kartalarni moslang</h3>
                    </div>
                    <div class="grid grid-cols-4 md:grid-cols-6 gap-3">
                        <div v-for="card in cards" :key="card.id"
                             @click="flipCard(card)"
                             :class="[
                                 'aspect-square rounded-xl cursor-pointer transition-all duration-300 flex items-center justify-center text-center p-2 shadow-lg border-2',
                                 card.matched
                                     ? 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border-green-400 scale-95'
                                     : card.flipped
                                         ? 'bg-gradient-to-br from-pink-500 to-rose-600 text-white border-pink-400 shadow-xl shadow-pink-500/30 scale-105'
                                         : 'bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700 dark:to-gray-600 hover:from-pink-50 hover:to-rose-50 dark:hover:from-pink-900/20 dark:hover:to-rose-900/20 border-gray-200 dark:border-gray-600 hover:border-pink-400 hover:scale-105 hover:shadow-xl'
                             ]">
                            <span v-if="card.flipped || card.matched" class="text-sm md:text-base font-bold" :class="card.matched ? 'text-green-700 dark:text-green-400' : 'text-white drop-shadow-md'">
                                {{ card.content }}
                            </span>
                            <span v-else class="text-4xl opacity-50">❓</span>
                        </div>
                    </div>
                </div>

                <!-- Speed Match Mode -->
                <div v-else-if="gameMode === 'speed_match'" class="max-w-2xl mx-auto">
                    <div v-if="currentPair" class="text-center">
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 mb-8 shadow-xl border border-gray-100 dark:border-gray-700">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-sm font-semibold mb-6">
                                <span>⚡</span> Tez javob bering!
                            </div>
                            <div class="text-5xl md:text-6xl font-bold text-gray-800 dark:text-white mb-4">{{ currentPair.word }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-lg">Bu so'zning tarjimasini tanlang</div>
                        </div>

                        <div class="grid grid-cols-2 gap-5">
                            <button v-for="(option, index) in speedOptions" :key="index"
                                    @click="selectSpeedAnswer(option)"
                                    :disabled="isCheckingAnswer"
                                    class="group p-6 bg-white dark:bg-gray-800 rounded-2xl font-bold text-lg transition-all duration-200 hover:scale-[1.03] disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                                <span class="text-gray-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">{{ option }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Streak Milestone Popup -->
                <Transition name="bounce">
                    <div v-if="streakMilestone" class="fixed inset-0 flex items-center justify-center z-50 pointer-events-none">
                        <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 text-white px-12 py-8 rounded-3xl shadow-2xl shadow-orange-500/50">
                            <div class="text-5xl font-bold mb-2">{{ streakMilestone.message }}</div>
                            <div class="text-center text-2xl font-semibold opacity-90">+{{ streakMilestone.bonus_xp }} XP</div>
                        </div>
                    </div>
                </Transition>

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

.bounce-enter-active {
    animation: bounce-in 0.5s;
}

.bounce-leave-active {
    animation: bounce-in 0.3s reverse;
}

@keyframes bounce-in {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
