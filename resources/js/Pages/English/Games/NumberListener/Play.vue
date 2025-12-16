<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted, watch } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'
import { SpeakerWaveIcon } from '@heroicons/vue/24/solid'

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
    }
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const gameStarted = ref(false)
const gameComplete = ref(false)
const showResult = ref(false)
const isSubmitting = ref(false)
const isPlaying = ref(false)

const sessionId = ref(null)
const selectedMode = ref('classic')
const currentNumber = ref(null)
const currentIndex = ref(0)
const totalNumbers = ref(0)
const userAnswer = ref('')
const lastUserAnswer = ref('')
const hint = ref(null)
const replaysRemaining = ref(0)
const totalReplays = ref(2)

const timeRemaining = ref(10)
let timerInterval = null

const gameState = ref({
    score: 0,
    streak: 0,
    correct: 0,
    wrong: 0,
    xpEarned: 0,
    coinsEarned: 0
})

const lastResult = ref(null)
const finalResult = ref(null)
const powerupUsed = ref({})

const showModeSelect = ref(true)

// Mode selection
function getModeIcon(modeId) {
    const icons = {
        'classic': '🎯',
        'time_attack': '⚡',
        'progressive': '📈'
    }
    return icons[modeId] || '🎮'
}

function getModeBgGradient(modeId) {
    const gradients = {
        'classic': 'from-blue-500 to-indigo-600',
        'time_attack': 'from-amber-500 to-orange-600',
        'progressive': 'from-emerald-500 to-teal-600'
    }
    return gradients[modeId] || 'from-blue-500 to-indigo-600'
}

async function selectMode(modeId) {
    selectedMode.value = modeId
    showModeSelect.value = false
    await startGame()
}

// Start game
const startGame = async () => {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(
            `/student/english/games/number-listener/start/${props.level.level_number}`,
            { mode: selectedMode.value }
        )

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            currentNumber.value = data.current_number
            currentIndex.value = data.current_index
            totalNumbers.value = data.total_numbers
            timeRemaining.value = data.time_per_number
            replaysRemaining.value = data.replays_remaining
            totalReplays.value = data.replays_remaining

            gameStarted.value = true
            startTimer()

            // Auto-play audio after a short delay
            setTimeout(() => {
                playAudio()
            }, 500)
        } else {
            hasError.value = true
            errorMessage.value = response.data.error || 'Failed to start session'
        }
    } catch (error) {
        hasError.value = true
        errorMessage.value = error.response?.data?.message || 'O\'yinni boshlashda xatolik yuz berdi'
    } finally {
        isLoading.value = false
    }
}

// Play audio using Web Speech API
const playAudio = () => {
    if (isPlaying.value || !currentNumber.value) return

    isPlaying.value = true

    const utterance = new SpeechSynthesisUtterance(currentNumber.value.word)
    utterance.lang = 'en-US'
    utterance.rate = 0.9

    utterance.onend = () => {
        isPlaying.value = false
    }

    utterance.onerror = () => {
        isPlaying.value = false
    }

    speechSynthesis.speak(utterance)
}

// Timer
const startTimer = () => {
    stopTimer()
    timeRemaining.value = props.level.time_per_number || 10

    timerInterval = setInterval(() => {
        timeRemaining.value--

        if (timeRemaining.value <= 0) {
            stopTimer()
            submitAnswer()
        }
    }, 1000)
}

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval)
        timerInterval = null
    }
}

// Submit answer
const submitAnswer = async () => {
    if (isSubmitting.value) return

    stopTimer()
    isSubmitting.value = true
    lastUserAnswer.value = userAnswer.value

    const timeSpent = (props.level.time_per_number || 10) - timeRemaining.value

    try {
        const response = await axios.post('/student/english/games/number-listener/check', {
            session_id: sessionId.value,
            answer: userAnswer.value || '',
            time: timeSpent
        })

        if (response.data.success) {
            lastResult.value = response.data.data
            gameState.value.score = lastResult.value.current_score
            gameState.value.streak = lastResult.value.streak
            gameState.value.correct = lastResult.value.correct_count
            gameState.value.wrong = lastResult.value.wrong_count
            gameState.value.xpEarned = lastResult.value.xp_earned || 0
            gameState.value.coinsEarned = lastResult.value.coins_earned || 0

            showResult.value = true
        }
    } catch (error) {
        console.error('Failed to check answer:', error)
    } finally {
        isSubmitting.value = false
    }
}

// Continue to next number
const continueGame = async () => {
    if (!lastResult.value.has_more) {
        await completeGame()
        return
    }

    showResult.value = false
    userAnswer.value = ''
    hint.value = null
    currentIndex.value = lastResult.value.progress.current
    currentNumber.value = lastResult.value.next_number
    replaysRemaining.value = lastResult.value.replays_remaining

    startTimer()

    // Auto-play audio
    setTimeout(() => {
        playAudio()
    }, 300)
}

// Complete game
const completeGame = async () => {
    try {
        const response = await axios.post('/student/english/games/number-listener/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            finalResult.value = response.data.data
            showResult.value = false
            gameComplete.value = true
        }
    } catch (error) {
        console.error('Failed to complete game:', error)
    }
}

// Use powerup
const usePowerup = async (powerupId) => {
    try {
        const response = await axios.post('/student/english/games/number-listener/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            const data = response.data.data
            powerupUsed.value[powerupId] = (powerupUsed.value[powerupId] || 0) + 1

            if (data.hint) {
                hint.value = data.hint
            }

            if (data.replays_remaining !== undefined) {
                replaysRemaining.value = data.replays_remaining
            }

            if (data.skipped && data.has_more) {
                currentNumber.value = data.next_number
                currentIndex.value++
                userAnswer.value = ''
                hint.value = null
                replaysRemaining.value = data.replays_remaining
                startTimer()
                setTimeout(() => playAudio(), 300)
            } else if (data.skipped && !data.has_more) {
                await completeGame()
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

// Restart game
const restartGame = () => {
    gameStarted.value = false
    gameComplete.value = false
    showResult.value = false
    sessionId.value = null
    currentNumber.value = null
    currentIndex.value = 0
    userAnswer.value = ''
    hint.value = null
    gameState.value = { score: 0, streak: 0, correct: 0, wrong: 0, xpEarned: 0, coinsEarned: 0 }
    finalResult.value = null
    powerupUsed.value = {}
    showModeSelect.value = true
}

function goBack() {
    stopTimer()
    router.visit('/student/english/games/number-listener')
}

function nextLevel() {
    stopTimer()
    const nextLevelNumber = props.level.level_number + 1
    // Assuming you have multiple levels - adjust as needed
    router.visit(`/student/english/games/number-listener/play/${nextLevelNumber}`)
}

// Cleanup
onUnmounted(() => {
    stopTimer()
    speechSynthesis.cancel()
})
</script>

<template>
    <Head :title="`Raqamlarni Tinglash - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-blue-200 dark:border-blue-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
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
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="showModeSelect" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-600 rounded-3xl mb-6 shadow-2xl shadow-blue-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🔢</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-blue-600 dark:text-blue-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.numbers_count }} raqam</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_per_number }}s</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectMode(mode.id)"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 overflow-hidden">

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
                                <div class="mt-5 flex items-center justify-center gap-2 text-blue-600 dark:text-blue-400 font-semibold opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
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
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameComplete && finalResult" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ finalResult.stars === 3 ? '🏆' : finalResult.stars === 2 ? '🎉' : finalResult.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= finalResult.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ finalResult.stars === 3 ? 'Mukammal!' : finalResult.stars === 2 ? 'Ajoyib!' : finalResult.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ finalResult.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ finalResult.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">+{{ finalResult.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ finalResult.correct_count }}/{{ totalNumbers }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">To'g'ri</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ finalResult.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="finalResult.new_achievements?.length" class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Yangi Yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div v-for="achievement in finalResult.new_achievements" :key="achievement.id"
                                 class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl px-4 py-2 flex items-center gap-2 border border-yellow-200 dark:border-yellow-800/50">
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-yellow-700 dark:text-yellow-400 font-medium">{{ achievement.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameStarted && !showResult" class="max-w-6xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                            <span class="text-2xl">🔢</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-100 dark:border-purple-800/50">
                                <span class="text-purple-500">✨</span>
                                <span class="text-purple-600 dark:text-purple-400 font-bold">{{ gameState.xpEarned }} XP</span>
                            </div>
                            <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-100 dark:border-amber-800/50">
                                <span>🪙</span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold">{{ gameState.coinsEarned }}</span>
                            </div>
                        </div>
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
                        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ gameState.correct }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">To'g'ri</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            timeRemaining < 5
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/20 border-blue-100 dark:border-blue-800/50'
                        ]">
                            <div :class="timeRemaining < 5 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'" class="text-3xl font-bold">{{ timeRemaining }}s</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentIndex }}/{{ totalNumbers }} raqam</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: ((currentIndex / totalNumbers) * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Game Content -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Audio Player Section -->
                    <div class="text-center mb-8">
                        <button @click="playAudio" :disabled="isPlaying"
                                class="w-32 h-32 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center mx-auto hover:from-blue-600 hover:to-indigo-600 transition-all transform hover:scale-105 disabled:opacity-50 shadow-2xl shadow-blue-500/30">
                            <SpeakerWaveIcon v-if="!isPlaying" class="w-16 h-16 text-white" />
                            <div v-else class="flex items-center gap-2">
                                <div class="w-3 h-12 bg-white rounded animate-pulse"></div>
                                <div class="w-3 h-16 bg-white rounded animate-pulse delay-75"></div>
                                <div class="w-3 h-10 bg-white rounded animate-pulse delay-150"></div>
                            </div>
                        </button>
                        <p class="text-gray-600 dark:text-gray-400 mt-6 text-lg">Tinglash uchun bosing</p>
                    </div>

                    <!-- Replays Indicator -->
                    <div class="flex items-center justify-center gap-3 mb-8 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/50 max-w-xs mx-auto">
                        <span class="text-gray-700 dark:text-gray-300 text-sm font-medium">Takrorlash:</span>
                        <div class="flex gap-2">
                            <div v-for="i in totalReplays" :key="i"
                                 class="w-4 h-4 rounded-full transition-all duration-300"
                                 :class="i <= replaysRemaining ? 'bg-blue-500 scale-110 shadow-lg' : 'bg-gray-300 dark:bg-gray-600 scale-90'"></div>
                        </div>
                    </div>

                    <!-- Hint Display -->
                    <div v-if="hint" class="mb-6 text-center">
                        <div class="inline-block px-6 py-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800/50">
                            <span class="text-3xl font-mono font-bold text-amber-600 dark:text-amber-400">{{ hint }}</span>
                        </div>
                    </div>

                    <!-- Answer Input -->
                    <div class="max-w-md mx-auto mb-6">
                        <input v-model="userAnswer" type="text" inputmode="numeric" @keyup.enter="submitAnswer"
                               class="w-full px-8 py-5 bg-white dark:bg-gray-700 border-2 border-blue-300 dark:border-blue-700 rounded-2xl text-gray-800 dark:text-white text-center text-3xl font-mono focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900/50 placeholder-gray-400 dark:placeholder-gray-500 shadow-lg transition-all"
                               placeholder="Raqamni yozing..."
                               :disabled="isSubmitting"
                               autofocus />
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center mb-8">
                        <button @click="submitAnswer" :disabled="!userAnswer || isSubmitting"
                                class="px-10 py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold text-lg rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl hover:scale-105">
                            <span v-if="isSubmitting">Tekshirilmoqda...</span>
                            <span v-else>Yuborish</span>
                        </button>
                    </div>

                    <!-- Powerups -->
                    <div v-if="powerups?.length" class="flex justify-center gap-4 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button v-for="powerup in powerups" :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="powerupUsed[powerup.id] >= powerup.uses_per_game"
                                class="flex flex-col items-center gap-2 px-5 py-3 bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700 dark:to-gray-600 rounded-xl shadow-md hover:shadow-lg border border-gray-200 dark:border-gray-600 transition-all hover:scale-105 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                                :title="powerup.description">
                            <span class="text-2xl">{{ powerup.icon }}</span>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">{{ powerup.name_uz }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ (powerupUsed[powerup.id] || 0) }}/{{ powerup.uses_per_game }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Answer Result Screen -->
            <div v-else-if="showResult && lastResult" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-10 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Result Icon -->
                    <div class="mb-6" :class="lastResult.is_correct ? 'animate-bounce' : 'animate-shake'">
                        <span class="text-8xl">{{ lastResult.is_correct ? '✅' : '❌' }}</span>
                    </div>

                    <!-- Result Title -->
                    <h2 class="text-3xl font-bold mb-4"
                        :class="lastResult.is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        {{ lastResult.is_correct ? "To'g'ri!" : "Noto'g'ri!" }}
                    </h2>

                    <!-- Answer Info -->
                    <div class="mb-8 bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-6 border border-gray-100 dark:border-gray-600">
                        <p class="text-gray-700 dark:text-gray-300 text-lg mb-2">
                            To'g'ri javob: <span class="font-bold text-blue-600 dark:text-blue-400 text-2xl">{{ lastResult.correct_answer }}</span>
                        </p>
                        <p v-if="!lastResult.is_correct" class="text-gray-500 dark:text-gray-400">
                            Sizning javobingiz: <span class="font-semibold">{{ lastUserAnswer || "(bo'sh)" }}</span>
                        </p>
                    </div>

                    <!-- Earned Stats -->
                    <div v-if="lastResult.is_correct" class="grid grid-cols-3 gap-4 mb-8">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-100 dark:border-yellow-800/50">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">+{{ lastResult.points_earned }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Ball</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">+{{ lastResult.xp_earned }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">XP</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ lastResult.streak }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">🔥 Streak</div>
                        </div>
                    </div>

                    <!-- Continue Button -->
                    <button @click="continueGame"
                            class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-lg rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        {{ lastResult.has_more ? 'Davom etish' : 'Natijalarni ko\'rish' }}
                    </button>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}

.delay-75 {
    animation-delay: 75ms;
}

.delay-150 {
    animation-delay: 150ms;
}
</style>
