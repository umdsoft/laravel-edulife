<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Array,
    powerups: Array,
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')

// Session state
const gamePhase = ref('idle')
const selectedMode = ref('classic')
const sessionId = ref(null)
const countdown = ref(3)
const currentSentence = ref(null)
const userAnswers = ref([])
const answerStatus = ref([])
const lastResult = ref(null)
const finalResult = ref({})
const isSubmitting = ref(false)
const powerupUses = ref({})
const eliminatedOption = ref(null)
const showHint = ref(false)
const currentHint = ref(null)
const sentenceStartTime = ref(null)

const gameState = ref({
    score: 0,
    currentIndex: 0,
    streak: 0,
    combo: 1,
    timeLeft: 0,
    correctAnswers: 0,
    totalAnswers: 0
})

const totalSentences = ref(0)
const hasOptions = ref(true)
let timerInterval = null

// Computed
const timerColor = computed(() => {
    if (gameState.value.timeLeft <= 10) return 'text-red-600 dark:text-red-400'
    if (gameState.value.timeLeft <= 30) return 'text-amber-600 dark:text-amber-400'
    return 'text-gray-900 dark:text-white'
})

const currentAccuracy = computed(() => {
    if (gameState.value.totalAnswers === 0) return 100
    return Math.round((gameState.value.correctAnswers / gameState.value.totalAnswers) * 100)
})

const sentenceParts = computed(() => {
    if (!currentSentence.value) return []

    const text = currentSentence.value.sentence
    const parts = []
    let gapIndex = 0

    // Split by ___ pattern
    const segments = text.split(/(_+)/)

    segments.forEach(segment => {
        if (segment.match(/^_+$/)) {
            parts.push({ type: 'gap', gapIndex: gapIndex++ })
        } else if (segment) {
            parts.push({ type: 'text', text: segment })
        }
    })

    return parts
})

const filteredOptions = computed(() => {
    if (!currentSentence.value?.options) return []
    if (eliminatedOption.value) {
        return currentSentence.value.options.filter(opt => opt !== eliminatedOption.value)
    }
    return currentSentence.value.options
})

const canSubmit = computed(() => {
    const gapsCount = currentSentence.value?.gaps_count || 0
    return userAnswers.value.filter(a => a && a.trim()).length === gapsCount
})

const availablePowerups = computed(() => {
    return props.powerups.filter(p => ['reveal_letter', 'eliminate_option', 'extra_time', 'hint'].includes(p.id))
})

// Methods
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

function getModeIcon(mode) {
    const icons = { 'classic': '📝', 'timed': '⏱️', 'challenge': '🏆' }
    return icons[mode] || '🎮'
}

function getModeBgGradient(mode) {
    const gradients = {
        'classic': 'from-teal-500 to-cyan-600',
        'timed': 'from-amber-500 to-orange-600',
        'challenge': 'from-purple-500 to-indigo-600'
    }
    return gradients[mode] || 'from-teal-500 to-cyan-600'
}

const startGame = async () => {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(`/student/english/games/fill-the-gap/start/${props.level.level_number}`, {
            mode: selectedMode.value
        })

        if (response.data.success) {
            sessionId.value = response.data.data.session_id
            currentSentence.value = response.data.data.current_sentence
            totalSentences.value = response.data.data.total_sentences
            hasOptions.value = response.data.data.has_options

            gameState.value = {
                score: 0,
                currentIndex: 0,
                streak: 0,
                combo: 1,
                timeLeft: response.data.data.time_limit,
                correctAnswers: 0,
                totalAnswers: 0
            }

            // Initialize
            userAnswers.value = new Array(currentSentence.value.gaps_count).fill('')
            answerStatus.value = new Array(currentSentence.value.gaps_count).fill(null)

            props.powerups.forEach(p => {
                powerupUses.value[p.id] = 0
            })

            startCountdown()
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

const startCountdown = () => {
    gamePhase.value = 'countdown'
    countdown.value = 3

    const countInterval = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) {
            clearInterval(countInterval)
            startPlaying()
        }
    }, 1000)
}

const startPlaying = () => {
    gamePhase.value = 'playing'
    sentenceStartTime.value = Date.now()
    eliminatedOption.value = null
    showHint.value = false
    currentHint.value = currentSentence.value?.hint || null

    // Start timer
    timerInterval = setInterval(() => {
        gameState.value.timeLeft--
        if (gameState.value.timeLeft <= 0) {
            clearInterval(timerInterval)
            completeGame()
        }
    }, 1000)
}

const submitAnswer = async () => {
    if (!canSubmit.value || isSubmitting.value) return

    isSubmitting.value = true

    const timeSpent = (Date.now() - sentenceStartTime.value) / 1000

    try {
        const response = await axios.post('/student/english/games/fill-the-gap/check', {
            session_id: sessionId.value,
            answers: userAnswers.value,
            time: timeSpent
        })

        if (response.data.success) {
            lastResult.value = response.data.data

            // Update answer statuses
            response.data.data.results.forEach((result, index) => {
                answerStatus.value[index] = result.correct ? 'correct' : 'incorrect'
            })

            // Update game state
            gameState.value.score = response.data.data.total_score
            gameState.value.streak = response.data.data.streak
            gameState.value.combo = response.data.data.combo_multiplier
            gameState.value.currentIndex = response.data.data.current_index
            gameState.value.correctAnswers += response.data.data.correct_count
            gameState.value.totalAnswers += response.data.data.total_gaps

            setTimeout(() => {
                if (response.data.data.is_complete) {
                    if (timerInterval) clearInterval(timerInterval)
                    completeGame()
                } else {
                    gamePhase.value = 'result'
                }
            }, 1000)
        }
    } catch (error) {
        console.error('Failed to submit answer:', error)
    } finally {
        isSubmitting.value = false
    }
}

const nextSentence = () => {
    if (lastResult.value?.is_complete) {
        completeGame()
        return
    }

    currentSentence.value = lastResult.value.next_sentence
    userAnswers.value = new Array(currentSentence.value.gaps_count).fill('')
    answerStatus.value = new Array(currentSentence.value.gaps_count).fill(null)
    sentenceStartTime.value = Date.now()
    eliminatedOption.value = null
    showHint.value = false
    currentHint.value = currentSentence.value?.hint || null
    gamePhase.value = 'playing'
}

const completeGame = async () => {
    try {
        const response = await axios.post('/student/english/games/fill-the-gap/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            finalResult.value = response.data.data
            gamePhase.value = 'gameover'
        }
    } catch (error) {
        console.error('Failed to complete game:', error)
    }
}

const restartGame = () => {
    gamePhase.value = 'idle'
    sessionId.value = null
    userAnswers.value = []
    answerStatus.value = []
    lastResult.value = null
    gameState.value = {
        score: 0,
        currentIndex: 0,
        streak: 0,
        combo: 1,
        timeLeft: 0,
        correctAnswers: 0,
        totalAnswers: 0
    }
}

const canUsePowerup = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId)
    if (!powerup) return false
    return (powerupUses.value[powerupId] || 0) < (powerup.uses_per_game || 1)
}

const getPowerupUsesLeft = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId)
    if (!powerup) return 0
    return (powerup.uses_per_game || 1) - (powerupUses.value[powerupId] || 0)
}

const usePowerup = async (powerupId) => {
    if (!canUsePowerup(powerupId)) return

    try {
        const response = await axios.post('/student/english/games/fill-the-gap/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            powerupUses.value[powerupId] = (powerupUses.value[powerupId] || 0) + 1

            switch (powerupId) {
                case 'reveal_letter':
                    if (response.data.data.letter && userAnswers.value.length > 0) {
                        userAnswers.value[0] = response.data.data.letter
                    }
                    break
                case 'eliminate_option':
                    if (response.data.data.eliminated) {
                        eliminatedOption.value = response.data.data.eliminated
                    }
                    break
                case 'extra_time':
                    gameState.value.timeLeft += response.data.data.seconds_added || 15
                    break
                case 'hint':
                    if (response.data.data.hint) {
                        showHint.value = true
                        currentHint.value = response.data.data.hint
                    }
                    break
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

function goBack() {
    if (timerInterval) clearInterval(timerInterval)
    router.visit('/student/english/games/fill-the-gap')
}

function nextLevel() {
    if (timerInterval) clearInterval(timerInterval)
    const nextLevelNumber = props.level.level_number + 1
    if (nextLevelNumber <= 10) {
        router.visit(`/student/english/games/fill-the-gap/play/${nextLevelNumber}`)
    } else {
        router.visit('/student/english/games/fill-the-gap')
    }
}

// Cleanup
onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
    <Head :title="`Fill the Gap - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/30 dark:to-cyan-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-teal-200 dark:border-teal-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-teal-600 border-t-transparent animate-spin"></div>
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
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="gamePhase === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-teal-500 via-cyan-500 to-blue-600 rounded-3xl mb-6 shadow-2xl shadow-teal-500/30">
                            <span class="text-5xl filter drop-shadow-lg">📝</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-teal-600 dark:text-teal-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.sentences_count }} gap</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.gaps_per_sentence }} bo'sh joy</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectedMode = mode.id"
                                :class="[
                                    'group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 overflow-hidden',
                                    selectedMode === mode.id
                                        ? 'border-teal-400 dark:border-teal-500 ring-4 ring-teal-200 dark:ring-teal-700'
                                        : 'border-gray-100 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-500'
                                ]">

                            <div class="relative z-10 p-8">
                                <!-- Icon -->
                                <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center text-5xl mb-5 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300', getModeBgGradient(mode.id)]">
                                    {{ getModeIcon(mode.id) }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 transition-colors">{{ mode.name }}</h3>

                                <!-- Description -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ mode.description }}</p>

                                <!-- Selection indicator -->
                                <div v-if="selectedMode === mode.id" class="mt-5 flex items-center justify-center gap-2 text-teal-600 dark:text-teal-400 font-semibold">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Tanlangan</span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-10 flex justify-center gap-4">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                        <button @click="startGame" class="px-8 py-3.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Boshlash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Countdown -->
            <div v-else-if="gamePhase === 'countdown'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-8xl font-bold text-gray-800 dark:text-white animate-pulse mb-4">
                        {{ countdown }}
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-xl">Tayyor bo'ling...</p>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gamePhase === 'gameover' && finalResult" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ finalResult.stars >= 2 ? '🏆' : finalResult.stars === 1 ? '🎯' : '💪' }}</span>
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
                        <div class="bg-teal-50 dark:bg-teal-900/20 rounded-2xl p-5 border border-teal-100 dark:border-teal-800/50">
                            <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ finalResult.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ finalResult.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ finalResult.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ finalResult.accuracy }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Aniqlik</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="finalResult.stars > 0 && level.level_number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gamePhase === 'playing' || gamePhase === 'result'" class="max-w-6xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-xl shadow-lg">
                            <span class="text-2xl">📝</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-xl border',
                                gameState.timeLeft < 30
                                    ? 'bg-red-50 dark:bg-red-900/30 border-red-100 dark:border-red-800/50'
                                    : 'bg-green-50 dark:bg-green-900/30 border-green-100 dark:border-green-800/50'
                            ]">
                                <span class="text-xl">⏱️</span>
                                <span :class="timerColor" class="font-bold">{{ formatTime(gameState.timeLeft) }}</span>
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
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ currentAccuracy }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Aniqlik</div>
                        </div>
                        <div class="bg-gradient-to-br from-teal-50 to-emerald-100 dark:from-teal-900/30 dark:to-emerald-900/20 rounded-xl p-4 border border-teal-100 dark:border-teal-800/50">
                            <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ gameState.currentIndex }}/{{ totalSentences }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savol</div>
                        </div>
                    </div>
                </div>

                <!-- Playing State -->
                <div v-if="gamePhase === 'playing'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Sentence with gaps -->
                    <div class="mb-8">
                        <div class="text-xl md:text-2xl text-gray-900 dark:text-white leading-relaxed">
                            <template v-for="(part, index) in sentenceParts" :key="index">
                                <span v-if="part.type === 'text'">{{ part.text }}</span>
                                <span v-else class="inline-block mx-1">
                                    <input
                                        v-if="!hasOptions"
                                        v-model="userAnswers[part.gapIndex]"
                                        type="text"
                                        class="w-32 px-3 py-1 bg-white dark:bg-gray-700 border-2 border-teal-300 dark:border-teal-700 rounded-lg text-gray-900 dark:text-white text-center focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200 dark:focus:ring-teal-800"
                                        :class="{
                                            'border-green-500 bg-green-50 dark:bg-green-900/30': answerStatus[part.gapIndex] === 'correct',
                                            'border-red-500 bg-red-50 dark:bg-red-900/30': answerStatus[part.gapIndex] === 'incorrect'
                                        }"
                                        :placeholder="`${part.gapIndex + 1}`"
                                        :disabled="answerStatus[part.gapIndex] !== null"
                                    />
                                    <select
                                        v-else
                                        v-model="userAnswers[part.gapIndex]"
                                        class="px-3 py-1 bg-white dark:bg-gray-700 border-2 border-teal-300 dark:border-teal-700 rounded-lg text-gray-900 dark:text-white focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200 dark:focus:ring-teal-800"
                                        :class="{
                                            'border-green-500 bg-green-50 dark:bg-green-900/30': answerStatus[part.gapIndex] === 'correct',
                                            'border-red-500 bg-red-50 dark:bg-red-900/30': answerStatus[part.gapIndex] === 'incorrect'
                                        }"
                                        :disabled="answerStatus[part.gapIndex] !== null"
                                    >
                                        <option value="" disabled>Tanlang</option>
                                        <option
                                            v-for="opt in filteredOptions"
                                            :key="opt"
                                            :value="opt"
                                            class="text-gray-900 dark:text-white"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Hint -->
                    <div v-if="showHint && currentHint" class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800/50 rounded-xl">
                        <p class="text-yellow-800 dark:text-yellow-300">
                            <span class="font-semibold">💡 Maslahat:</span> {{ currentHint }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button
                            @click="submitAnswer"
                            :disabled="!canSubmit || isSubmitting"
                            class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105"
                        >
                            {{ isSubmitting ? 'Tekshirilmoqda...' : 'Tekshirish' }}
                        </button>
                    </div>
                </div>

                <!-- Result State -->
                <div v-else-if="gamePhase === 'result'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-6xl mb-4">
                        {{ lastResult?.all_correct ? '✅' : '❌' }}
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
                        {{ lastResult?.all_correct ? 'To\'g\'ri!' : 'Xato' }}
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-3">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                {{ lastResult?.correct_count }}/{{ lastResult?.total_gaps }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">To'g'ri</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-3">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">+{{ lastResult?.points_earned }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">Ball</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-3">
                            <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ lastResult?.streak }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">Seriya</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-3">
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">x{{ lastResult?.combo_multiplier?.toFixed(1) }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">Combo</div>
                        </div>
                    </div>

                    <!-- Show correct answers if wrong -->
                    <div v-if="!lastResult?.all_correct" class="mb-6 text-left">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold mb-2">To'g'ri javoblar:</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(result, index) in lastResult?.results"
                                :key="index"
                                class="px-3 py-1 rounded-lg text-sm"
                                :class="result.correct ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'"
                            >
                                {{ result.correct_answer }}
                                <span v-if="!result.correct" class="text-gray-500 dark:text-gray-400">
                                    (siz: {{ result.user_answer || '—' }})
                                </span>
                            </span>
                        </div>
                    </div>

                    <button
                        @click="nextSentence"
                        class="px-8 py-4 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-bold rounded-xl text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105"
                    >
                        {{ lastResult?.is_complete ? 'Natijalarni ko\'rish' : 'Keyingisi' }}
                    </button>
                </div>

                <!-- Powerups Bar -->
                <div v-if="gamePhase === 'playing'" class="mt-6 bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-center flex-wrap gap-4">
                        <button
                            v-for="powerup in availablePowerups"
                            :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="!canUsePowerup(powerup.id)"
                            class="px-4 py-2 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 hover:from-purple-100 hover:to-indigo-100 dark:hover:from-purple-900/30 dark:hover:to-indigo-900/30 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all flex items-center gap-2 border border-purple-200 dark:border-purple-800/50 shadow-sm hover:shadow-md"
                        >
                            <span class="text-xl">{{ powerup.icon }}</span>
                            <span class="text-gray-900 dark:text-white text-sm font-medium">{{ powerup.name }}</span>
                            <span class="text-purple-600 dark:text-purple-400 text-xs font-bold">({{ getPowerupUsesLeft(powerup.id) }})</span>
                        </button>
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
