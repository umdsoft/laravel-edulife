<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import {
    ArrowLeftIcon,
    StarIcon,
    HeartIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/solid'

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
const gamePhase = ref('idle') // idle, countdown, showing, input, result, gameover
const selectedMode = ref('classic')
const sessionId = ref(null)
const countdown = ref(3)
const displayedWords = ref([])
const currentRoundWords = ref([])
const userInputs = ref([])
const inputStatus = ref([])
const inputRefs = ref([])
const showingProgress = ref(100)
const roundResult = ref({})
const finalResult = ref({})
const powerupUses = ref({})

const gameState = ref({
    score: 0,
    currentRound: 0,
    lives: props.level.max_lives || 3,
    streak: 0,
    timeLeft: 0,
    correctAnswers: 0,
    totalAnswers: 0
})

let timerInterval = null
let showingInterval = null

// Computed
const timerColor = computed(() => {
    if (gameState.value.timeLeft <= 5) return 'text-red-600 dark:text-red-400'
    if (gameState.value.timeLeft <= 10) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-gray-900 dark:text-white'
})

const canSubmit = computed(() => {
    return userInputs.value.every(input => input && input.trim().length > 0)
})

const availablePowerups = computed(() => {
    return props.powerups.filter(p => p.id !== 'double_points')
})

function getModeIcon(mode) {
    const icons = { 'classic': '🧠', 'reverse': '🔄', 'random': '🎲' }
    return icons[mode] || '🎮'
}

function getModeBgGradient(mode) {
    const gradients = {
        'classic': 'from-purple-500 to-indigo-600',
        'reverse': 'from-pink-500 to-rose-600',
        'random': 'from-amber-500 to-orange-600'
    }
    return gradients[mode] || 'from-purple-500 to-indigo-600'
}

// Methods
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const startGame = async () => {
    try {
        const response = await fetch(route('student.english.games.word-recall.start', { level: props.level.level_number }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ mode: selectedMode.value })
        })

        const data = await response.json()

        if (data.success) {
            sessionId.value = data.data.session_id
            gameState.value = {
                score: 0,
                currentRound: 0,
                lives: props.level.max_lives || 3,
                streak: 0,
                timeLeft: props.level.time_limit || 60,
                correctAnswers: 0,
                totalAnswers: 0
            }

            // Initialize powerup uses
            props.powerups.forEach(p => {
                powerupUses.value[p.id] = 0
            })

            startCountdown()
        }
    } catch (error) {
        console.error('Failed to start game:', error)
    }
}

const startCountdown = () => {
    gamePhase.value = 'countdown'
    countdown.value = 3

    const countInterval = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) {
            clearInterval(countInterval)
            getNextRound()
        }
    }, 1000)
}

const getNextRound = async () => {
    try {
        const response = await fetch(route('student.english.games.word-recall.next-round'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ session_id: sessionId.value })
        })

        const data = await response.json()

        if (data.success) {
            currentRoundWords.value = data.data.words
            gameState.value.currentRound = data.data.current_round
            showWords()
        }
    } catch (error) {
        console.error('Failed to get next round:', error)
    }
}

const showWords = () => {
    gamePhase.value = 'showing'
    displayedWords.value = [...currentRoundWords.value]
    showingProgress.value = 100

    const displayTime = props.level.display_time * 1000
    const interval = 100
    let elapsed = 0

    showingInterval = setInterval(() => {
        elapsed += interval
        showingProgress.value = Math.max(0, 100 - (elapsed / displayTime) * 100)

        if (elapsed >= displayTime) {
            clearInterval(showingInterval)
            startInputPhase()
        }
    }, interval)
}

const startInputPhase = () => {
    gamePhase.value = 'input'
    displayedWords.value = []
    userInputs.value = new Array(currentRoundWords.value.length).fill('')
    inputStatus.value = new Array(currentRoundWords.value.length).fill(null)
    gameState.value.timeLeft = props.level.time_per_word * currentRoundWords.value.length

    // Start timer
    timerInterval = setInterval(() => {
        gameState.value.timeLeft--
        if (gameState.value.timeLeft <= 0) {
            clearInterval(timerInterval)
            submitAnswers()
        }
    }, 1000)

    // Focus first input
    nextTick(() => {
        if (inputRefs.value[0]) {
            inputRefs.value[0].focus()
        }
    })
}

const handleEnterKey = (index) => {
    if (index < userInputs.value.length - 1) {
        inputRefs.value[index + 1]?.focus()
    } else if (canSubmit.value) {
        submitAnswers()
    }
}

const submitAnswers = async () => {
    if (timerInterval) {
        clearInterval(timerInterval)
    }

    try {
        const response = await fetch(route('student.english.games.word-recall.check-round'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                session_id: sessionId.value,
                answers: userInputs.value.map(a => a.trim().toLowerCase()),
                time: props.level.time_per_word * currentRoundWords.value.length - gameState.value.timeLeft
            })
        })

        const data = await response.json()

        if (data.success) {
            const result = data.data

            // Update input statuses
            result.results.forEach((isCorrect, index) => {
                inputStatus.value[index] = isCorrect ? 'correct' : 'incorrect'
            })

            // Update game state
            gameState.value.score = result.total_score
            gameState.value.streak = result.streak
            gameState.value.correctAnswers += result.correct_count
            gameState.value.totalAnswers += currentRoundWords.value.length

            if (!result.all_correct) {
                gameState.value.lives--
            }

            // Show result
            roundResult.value = {
                allCorrect: result.all_correct,
                correctWords: result.correct_answers,
                pointsEarned: result.points_earned
            }

            setTimeout(() => {
                if (gameState.value.lives <= 0 || result.game_complete) {
                    completeGame()
                } else {
                    gamePhase.value = 'result'
                }
            }, 1000)
        }
    } catch (error) {
        console.error('Failed to check round:', error)
    }
}

const nextRound = () => {
    if (gameState.value.currentRound >= props.level.rounds) {
        completeGame()
    } else {
        getNextRound()
    }
}

const completeGame = async () => {
    try {
        const response = await fetch(route('student.english.games.word-recall.complete'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ session_id: sessionId.value })
        })

        const data = await response.json()

        if (data.success) {
            finalResult.value = data.data
            gamePhase.value = 'gameover'
        }
    } catch (error) {
        console.error('Failed to complete game:', error)
    }
}

const restartGame = () => {
    gamePhase.value = 'idle'
    sessionId.value = null
    gameState.value = {
        score: 0,
        currentRound: 0,
        lives: props.level.max_lives || 3,
        streak: 0,
        timeLeft: 0,
        correctAnswers: 0,
        totalAnswers: 0
    }
}

const goBack = () => {
    if (timerInterval) clearInterval(timerInterval)
    if (showingInterval) clearInterval(showingInterval)
    window.location.href = route('student.english.games.word-recall.index')
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
        const response = await fetch(route('student.english.games.word-recall.powerup'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                session_id: sessionId.value,
                powerup_id: powerupId
            })
        })

        const data = await response.json()

        if (data.success) {
            powerupUses.value[powerupId] = (powerupUses.value[powerupId] || 0) + 1

            // Apply powerup effect
            switch (powerupId) {
                case 'extra_time':
                    gameState.value.timeLeft += 10
                    break
                case 'slow_display':
                    // This is handled server-side for next round
                    break
                case 'hints':
                    // Show first letter hints
                    currentRoundWords.value.forEach((word, index) => {
                        if (!userInputs.value[index]) {
                            userInputs.value[index] = word.charAt(0) + '...'
                        }
                    })
                    break
                case 'skip':
                    // Skip to next round
                    if (showingInterval) clearInterval(showingInterval)
                    if (timerInterval) clearInterval(timerInterval)
                    nextRound()
                    break
                case 'extra_life':
                    gameState.value.lives++
                    break
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

// Cleanup
onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
    if (showingInterval) clearInterval(showingInterval)
})
</script>

<template>
    <Head :title="`So'z Eslab Qolish - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Pre-game State / Mode Selection -->
            <div v-if="gamePhase === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 via-violet-500 to-indigo-600 rounded-3xl mb-6 shadow-2xl shadow-purple-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🧠</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.description }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-purple-600 dark:text-purple-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.words_per_round }} so'z</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.display_time }}s vaqt</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectedMode = mode.id"
                                :class="[
                                    'group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 overflow-hidden',
                                    selectedMode === mode.id
                                        ? 'border-purple-400 dark:border-purple-500 ring-4 ring-purple-300 dark:ring-purple-700'
                                        : 'border-gray-100 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500'
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
                            </div>
                        </button>
                    </div>

                    <!-- Start Button -->
                    <div class="mt-10 text-center">
                        <button @click="startGame" class="inline-flex items-center gap-2 px-10 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105 text-lg">
                            Boshlash
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-6 text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Countdown -->
            <div v-else-if="gamePhase === 'countdown'" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="text-8xl font-bold text-purple-600 dark:text-purple-400 animate-pulse mb-4">
                        {{ countdown }}
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 text-xl">Tayyor bo'ling...</p>
                </div>
            </div>

            <!-- Showing Words Phase -->
            <div v-else-if="gamePhase === 'showing'" class="max-w-4xl mx-auto">
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
                            <span class="text-2xl">🧠</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-100 dark:border-purple-800/50">
                            <span class="text-purple-500">🔥</span>
                            <span class="text-purple-600 dark:text-purple-400 font-bold">{{ gameState.streak }}</span>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ gameState.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ gameState.currentRound }}/{{ level.rounds }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Raund</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/50">
                            <div class="flex items-center justify-center gap-1">
                                <HeartIcon v-for="i in level.max_lives" :key="i"
                                    class="w-6 h-6"
                                    :class="i <= gameState.lives ? 'text-red-500 dark:text-red-400' : 'text-gray-300 dark:text-gray-600'" />
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Jon</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Seriya</div>
                        </div>
                    </div>
                </div>

                <!-- Words Display Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <p class="text-gray-700 dark:text-gray-300 font-semibold text-center mb-6 text-lg">So'zlarni eslang!</p>

                    <!-- Words Display -->
                    <div class="mb-8">
                        <TransitionGroup
                            name="word-fade"
                            tag="div"
                            class="flex flex-wrap justify-center gap-4"
                        >
                            <div
                                v-for="(word, index) in displayedWords"
                                :key="word + index"
                                class="px-8 py-6 bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 border-2 border-purple-200 dark:border-purple-800 rounded-2xl shadow-lg"
                            >
                                <span class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ word }}</span>
                                <div class="text-gray-600 dark:text-gray-400 text-sm mt-2 text-center font-semibold">{{ index + 1 }}</div>
                            </div>
                        </TransitionGroup>
                    </div>

                    <!-- Progress Bar -->
                    <div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-purple-500 via-violet-500 to-indigo-500 transition-all duration-100 ease-linear rounded-full shadow-lg"
                                 :style="{ width: `${showingProgress}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Powerups Bar -->
                <div v-if="availablePowerups.length" class="mt-6">
                    <div class="flex justify-center gap-4 flex-wrap">
                        <button
                            v-for="powerup in availablePowerups"
                            :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="!canUsePowerup(powerup.id)"
                            class="px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all flex items-center gap-2 shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg"
                        >
                            <span class="text-xl">{{ powerup.icon }}</span>
                            <span class="text-gray-900 dark:text-white text-sm font-medium">{{ powerup.name }}</span>
                            <span class="text-gray-600 dark:text-gray-400 text-xs">({{ getPowerupUsesLeft(powerup.id) }})</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Input Phase -->
            <div v-else-if="gamePhase === 'input'" class="max-w-4xl mx-auto">
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
                            <span class="text-2xl">🧠</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-xl transition-all',
                            gameState.timeLeft <= 5
                                ? 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800/50'
                                : 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800/50'
                        ]">
                            <ClockIcon class="w-5 h-5" :class="timerColor" />
                            <span :class="timerColor" class="font-bold text-xl">{{ formatTime(gameState.timeLeft) }}</span>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ gameState.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ gameState.currentRound }}/{{ level.rounds }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Raund</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/50">
                            <div class="flex items-center justify-center gap-1">
                                <HeartIcon v-for="i in level.max_lives" :key="i"
                                    class="w-6 h-6"
                                    :class="i <= gameState.lives ? 'text-red-500 dark:text-red-400' : 'text-gray-300 dark:text-gray-600'" />
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Jon</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Seriya</div>
                        </div>
                    </div>
                </div>

                <!-- Input Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="text-center mb-6">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold text-lg">
                            <span v-if="selectedMode === 'reverse'">Teskari tartibda yozing!</span>
                            <span v-else-if="selectedMode === 'random'">Istalgan tartibda yozing</span>
                            <span v-else>So'zlarni tartibda yozing</span>
                        </p>
                    </div>

                    <!-- Input Fields -->
                    <div class="grid gap-4 mb-8">
                        <div
                            v-for="(_, index) in currentRoundWords.length"
                            :key="index"
                            class="flex items-center gap-4"
                        >
                            <span class="text-gray-700 dark:text-gray-300 font-bold w-8 text-lg">{{ index + 1 }}.</span>
                            <input
                                :ref="el => inputRefs[index] = el"
                                v-model="userInputs[index]"
                                type="text"
                                class="flex-1 px-5 py-4 bg-white dark:bg-gray-700 border-2 rounded-xl text-gray-900 dark:text-white text-lg focus:outline-none transition-all shadow-sm"
                                :class="{
                                    'border-green-500 dark:border-green-400 bg-green-50 dark:bg-green-900/20': inputStatus[index] === 'correct',
                                    'border-red-500 dark:border-red-400 bg-red-50 dark:bg-red-900/20': inputStatus[index] === 'incorrect',
                                    'border-gray-300 dark:border-gray-600 focus:border-purple-500 dark:focus:border-purple-400': inputStatus[index] === null
                                }"
                                :placeholder="`${index + 1}-so'z`"
                                @keydown.enter="handleEnterKey(index)"
                                :disabled="inputStatus[index] !== null"
                            />
                            <div class="w-8">
                                <CheckCircleIcon v-if="inputStatus[index] === 'correct'" class="w-8 h-8 text-green-500 dark:text-green-400" />
                                <XCircleIcon v-else-if="inputStatus[index] === 'incorrect'" class="w-8 h-8 text-red-500 dark:text-red-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center gap-4">
                        <button
                            @click="submitAnswers"
                            :disabled="!canSubmit"
                            class="px-10 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105 disabled:cursor-not-allowed disabled:hover:scale-100"
                        >
                            Tekshirish
                        </button>
                    </div>
                </div>

                <!-- Powerups Bar -->
                <div v-if="availablePowerups.length" class="mt-6">
                    <div class="flex justify-center gap-4 flex-wrap">
                        <button
                            v-for="powerup in availablePowerups"
                            :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="!canUsePowerup(powerup.id)"
                            class="px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all flex items-center gap-2 shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg"
                        >
                            <span class="text-xl">{{ powerup.icon }}</span>
                            <span class="text-gray-900 dark:text-white text-sm font-medium">{{ powerup.name }}</span>
                            <span class="text-gray-600 dark:text-gray-400 text-xs">({{ getPowerupUsesLeft(powerup.id) }})</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Round Result -->
            <div v-else-if="gamePhase === 'result'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-10 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="text-7xl mb-6">
                        {{ roundResult.allCorrect ? '🎉' : '😔' }}
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                        {{ roundResult.allCorrect ? 'Ajoyib!' : 'Qayta urinib ko\'ring' }}
                    </h2>

                    <!-- Show correct answers if wrong -->
                    <div v-if="!roundResult.allCorrect" class="mb-8">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold mb-4 text-lg">To'g'ri javoblar:</p>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div
                                v-for="(word, index) in roundResult.correctWords"
                                :key="index"
                                class="px-5 py-3 bg-green-50 dark:bg-green-900/20 border-2 border-green-300 dark:border-green-800 rounded-xl shadow-sm"
                            >
                                <span class="text-gray-900 dark:text-white font-semibold">{{ index + 1 }}. {{ word }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-gray-700 dark:text-gray-300 mb-8 text-lg">
                        <span class="text-green-600 dark:text-green-400 font-bold text-2xl">+{{ roundResult.pointsEarned }}</span> ball
                    </div>

                    <button
                        @click="nextRound"
                        class="px-10 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold rounded-xl text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105"
                    >
                        {{ gameState.currentRound < level.rounds ? 'Keyingi raund' : 'Natijalarni ko\'rish' }}
                    </button>
                </div>
            </div>

            <!-- Game Over / Completion Screen -->
            <div v-else-if="gamePhase === 'gameover'" class="flex items-center justify-center min-h-[80vh]">
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
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ finalResult.score }}</div>
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
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ finalResult.accuracy }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Aniqlik</div>
                        </div>
                    </div>

                    <!-- Achievements -->
                    <div v-if="finalResult.achievements?.length" class="mb-8">
                        <h3 class="text-gray-900 dark:text-white font-semibold mb-4 text-lg">Yangi yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div
                                v-for="achievement in finalResult.achievements"
                                :key="achievement.id"
                                class="px-4 py-2 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-300 dark:border-yellow-800 rounded-xl flex items-center gap-2 shadow-sm"
                            >
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-gray-900 dark:text-white font-semibold">{{ achievement.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<style scoped>
.word-fade-enter-active,
.word-fade-leave-active {
    transition: all 0.5s ease;
}

.word-fade-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.word-fade-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
