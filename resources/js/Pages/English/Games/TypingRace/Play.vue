<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
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
    }
})

// Game state
const gamePhase = ref('idle')
const selectedMode = ref('classic')
const sessionId = ref(null)
const countdown = ref(3)
const content = ref([])
const currentText = ref('')
const userInput = ref('')
const inputRef = ref(null)
const lastResult = ref(null)
const finalResult = ref({})
const isSubmitting = ref(false)
const powerupUses = ref({})
const itemStartTime = ref(null)

const gameState = ref({
    score: 0,
    wpm: 0,
    accuracy: 100,
    currentIndex: 0,
    streak: 0,
    combo: 1,
    timeLeft: 0
})

const totalItems = ref(0)
let timerInterval = null

// Computed
const timerColor = computed(() => {
    if (gameState.value.timeLeft <= 10) return 'text-red-600 dark:text-red-400'
    if (gameState.value.timeLeft <= 30) return 'text-orange-600 dark:text-orange-400'
    return 'text-gray-900 dark:text-white'
})

const currentTextChars = computed(() => {
    return currentText.value.split('')
})

const currentErrors = computed(() => {
    let errors = 0
    for (let i = 0; i < userInput.value.length; i++) {
        if (userInput.value[i] !== currentText.value[i]) {
            errors++
        }
    }
    return errors
})

const availablePowerups = computed(() => {
    return props.powerups.filter(p => ['extra_time', 'skip_word', 'slow_motion'].includes(p.id))
})

// Methods
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const getCharClass = (index) => {
    if (index >= userInput.value.length) {
        return 'text-gray-500 dark:text-gray-400'
    }
    if (userInput.value[index] === currentText.value[index]) {
        return 'text-green-600 dark:text-green-400'
    }
    return 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30'
}

function getModeIcon(mode) {
    const icons = { 'classic': '⌨️', 'speed_race': '⚡', 'accuracy_mode': '🎯' }
    return icons[mode] || '⌨️'
}

function getModeBgGradient(mode) {
    const gradients = {
        'classic': 'from-orange-500 to-red-600',
        'speed_race': 'from-amber-500 to-orange-600',
        'accuracy_mode': 'from-blue-500 to-indigo-600'
    }
    return gradients[mode] || 'from-orange-500 to-red-600'
}

const startGame = async () => {
    try {
        const response = await axios.post(`/student/english/games/typing-race/start/${props.level.level_number}`, {
            mode: selectedMode.value
        })

        if (response.data.success) {
            sessionId.value = response.data.data.session_id
            content.value = response.data.data.content
            currentText.value = response.data.data.current_text
            totalItems.value = response.data.data.total_items

            gameState.value = {
                score: 0,
                wpm: 0,
                accuracy: 100,
                currentIndex: 0,
                streak: 0,
                combo: 1,
                timeLeft: response.data.data.time_limit
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
            startPlaying()
        }
    }, 1000)
}

const startPlaying = () => {
    gamePhase.value = 'playing'
    userInput.value = ''
    itemStartTime.value = Date.now()

    // Start timer
    timerInterval = setInterval(() => {
        gameState.value.timeLeft--
        if (gameState.value.timeLeft <= 0) {
            clearInterval(timerInterval)
            completeGame()
        }
    }, 1000)

    // Focus input
    nextTick(() => {
        inputRef.value?.focus()
    })
}

const submitText = async () => {
    if (!userInput.value.length || isSubmitting.value) return

    isSubmitting.value = true

    const timeSpent = (Date.now() - itemStartTime.value) / 1000

    try {
        const response = await axios.post('/student/english/games/typing-race/submit', {
            session_id: sessionId.value,
            typed_text: userInput.value,
            time: timeSpent
        })

        if (response.data.success) {
            lastResult.value = response.data.data

            // Update game state
            gameState.value.score = response.data.data.total_score
            gameState.value.wpm = response.data.data.wpm
            gameState.value.accuracy = response.data.data.overall_accuracy
            gameState.value.streak = response.data.data.streak
            gameState.value.combo = response.data.data.combo_multiplier
            gameState.value.currentIndex = response.data.data.current_index

            if (response.data.data.is_complete) {
                if (timerInterval) clearInterval(timerInterval)
                completeGame()
            } else {
                gamePhase.value = 'result'
            }
        }
    } catch (error) {
        console.error('Failed to submit text:', error)
    } finally {
        isSubmitting.value = false
    }
}

const nextItem = () => {
    if (lastResult.value?.is_complete) {
        completeGame()
        return
    }

    currentText.value = lastResult.value.next_text
    userInput.value = ''
    itemStartTime.value = Date.now()
    gamePhase.value = 'playing'

    nextTick(() => {
        inputRef.value?.focus()
    })
}

const completeGame = async () => {
    try {
        const response = await axios.post('/student/english/games/typing-race/complete', {
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
    userInput.value = ''
    lastResult.value = null
    gameState.value = {
        score: 0,
        wpm: 0,
        accuracy: 100,
        currentIndex: 0,
        streak: 0,
        combo: 1,
        timeLeft: 0
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
        const response = await axios.post('/student/english/games/typing-race/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            powerupUses.value[powerupId] = (powerupUses.value[powerupId] || 0) + 1

            // Apply powerup effect
            switch (powerupId) {
                case 'extra_time':
                    gameState.value.timeLeft += response.data.data.seconds_added || 15
                    break
                case 'skip_word':
                    if (response.data.data.next_text) {
                        currentText.value = response.data.data.next_text
                        userInput.value = ''
                        gameState.value.currentIndex++
                        itemStartTime.value = Date.now()
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
    router.visit('/student/english/games/typing-race')
}

function nextLevel() {
    if (timerInterval) clearInterval(timerInterval)
    const nextLevelNumber = props.level.level_number + 1
    if (nextLevelNumber <= 10) {
        router.visit(`/student/english/games/typing-race/play/${nextLevelNumber}`)
    } else {
        router.visit('/student/english/games/typing-race')
    }
}

// Cleanup
onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
    <Head :title="`Typing Race - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Pre-game State / Mode Selection -->
            <div v-if="gamePhase === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-500 via-red-500 to-red-600 rounded-3xl mb-6 shadow-2xl shadow-orange-500/30">
                            <span class="text-5xl filter drop-shadow-lg">⌨️</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.description }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-orange-600 dark:text-orange-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.target_wpm }} WPM maqsad</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.target_accuracy }}% aniqlik</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectedMode = mode.id"
                                :class="[
                                    'group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 overflow-hidden',
                                    selectedMode === mode.id
                                        ? 'border-orange-400 dark:border-orange-500 ring-2 ring-orange-300 dark:ring-orange-700'
                                        : 'border-gray-100 dark:border-gray-700 hover:border-orange-400 dark:hover:border-orange-500'
                                ]">

                            <div class="relative z-10 p-8">
                                <!-- Icon -->
                                <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center text-5xl mb-5 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300', getModeBgGradient(mode.id)]">
                                    {{ mode.icon }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 transition-colors">{{ mode.name }}</h3>

                                <!-- Description -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ mode.description }}</p>

                                <!-- Selected indicator -->
                                <div v-if="selectedMode === mode.id" class="mt-5 flex items-center justify-center gap-2 text-orange-600 dark:text-orange-400 font-semibold">
                                    <span>Tanlandi</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Start Button -->
                    <div class="text-center mb-8">
                        <button @click="startGame" class="px-12 py-4 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105 text-lg">
                            Boshlash
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
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
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-8xl font-bold text-orange-600 dark:text-orange-400 animate-pulse mb-4">
                        {{ countdown }}
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">Tayyor bo'ling...</p>
                </div>
            </div>

            <!-- Game Over -->
            <div v-else-if="gamePhase === 'gameover'" class="flex items-center justify-center min-h-[80vh]">
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
                        {{ finalResult.stars >= 2 ? 'Ajoyib!' : finalResult.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ finalResult.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ finalResult.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ finalResult.wpm }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">WPM</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">+{{ finalResult.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ finalResult.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="finalResult.stars > 0 && level.level_number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Playing & Result Screen -->
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
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-lg">
                            <span class="text-2xl">{{ getModeIcon(selectedMode) }}</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-orange-50 dark:bg-orange-900/30 rounded-xl border border-orange-100 dark:border-orange-800/50">
                                <span class="text-orange-500">⚡</span>
                                <span class="text-orange-600 dark:text-orange-400 font-bold">{{ gameState.score }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-5 gap-4 text-center">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ gameState.wpm }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">WPM</div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ gameState.accuracy }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Aniqlik</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.currentIndex }}/{{ totalItems }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Bajarildi</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ gameState.streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Seriya</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            gameState.timeLeft < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 border-gray-100 dark:border-gray-600/50'
                        ]">
                            <div :class="timerColor" class="text-3xl font-bold">{{ formatTime(gameState.timeLeft) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>
                </div>

                <!-- Playing -->
                <div v-if="gamePhase === 'playing'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Text to type -->
                    <div class="mb-6 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
                        <p class="text-2xl font-mono leading-relaxed">
                            <span
                                v-for="(char, index) in currentTextChars"
                                :key="index"
                                :class="getCharClass(index)"
                            >{{ char === ' ' ? '\u00A0' : char }}</span>
                        </p>
                    </div>

                    <!-- Input field -->
                    <div class="mb-6">
                        <input
                            ref="inputRef"
                            v-model="userInput"
                            type="text"
                            class="w-full px-6 py-4 bg-white dark:bg-gray-900 border-2 border-orange-300 dark:border-orange-700 rounded-xl text-gray-900 dark:text-white text-xl font-mono focus:border-orange-500 dark:focus:border-orange-500 focus:outline-none transition-colors"
                            :class="{
                                'border-green-500 dark:border-green-500': lastResult?.is_correct,
                                'border-red-500 dark:border-red-500': lastResult && !lastResult.is_correct
                            }"
                            placeholder="Shu yerda yozing..."
                            @keydown.enter="submitText"
                            :disabled="isSubmitting"
                            autocomplete="off"
                            autocapitalize="off"
                            autocorrect="off"
                            spellcheck="false"
                        />
                    </div>

                    <!-- Real-time stats -->
                    <div class="flex justify-between items-center text-sm mb-6">
                        <div class="flex gap-4">
                            <span class="text-gray-600 dark:text-gray-400">
                                Belgilar: <span class="text-gray-900 dark:text-white font-semibold">{{ userInput.length }}/{{ currentText.length }}</span>
                            </span>
                            <span class="text-gray-600 dark:text-gray-400">
                                Xatolar: <span class="text-red-600 dark:text-red-400 font-semibold">{{ currentErrors }}</span>
                            </span>
                        </div>
                        <div v-if="gameState.combo > 1" class="text-orange-600 dark:text-orange-400 font-bold animate-pulse">
                            x{{ gameState.combo.toFixed(1) }} Combo!
                        </div>
                    </div>

                    <!-- Submit button -->
                    <div class="text-center">
                        <button
                            @click="submitText"
                            :disabled="!userInput.length || isSubmitting"
                            class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-105 disabled:cursor-not-allowed disabled:hover:scale-100"
                        >
                            {{ isSubmitting ? 'Tekshirilmoqda...' : 'Yuborish (Enter)' }}
                        </button>
                    </div>
                </div>

                <!-- Item Result -->
                <div v-else-if="gamePhase === 'result'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-6xl mb-4">
                        {{ lastResult?.is_correct ? '✅' : '❌' }}
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ lastResult?.is_correct ? 'To\'g\'ri!' : 'Xatolar bor' }}
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 border border-green-100 dark:border-green-800/50">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ lastResult?.accuracy }}%</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">Aniqlik</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-3 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-xl font-bold text-orange-600 dark:text-orange-400">+{{ lastResult?.points_earned }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">Ball</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ lastResult?.wpm }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">WPM</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-3 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ lastResult?.streak }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs">Seriya</div>
                        </div>
                    </div>

                    <!-- Show errors if any -->
                    <div v-if="lastResult?.errors?.length" class="mb-6 text-left">
                        <p class="text-gray-700 dark:text-gray-300 font-semibold mb-2">Xatolar:</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(error, index) in lastResult.errors.slice(0, 10)"
                                :key="index"
                                class="px-2 py-1 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded text-sm border border-red-200 dark:border-red-800/50"
                            >
                                "{{ error.expected }}" → "{{ error.typed || '∅' }}"
                            </span>
                        </div>
                    </div>

                    <button
                        @click="nextItem"
                        class="px-8 py-4 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold rounded-xl text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105"
                    >
                        {{ lastResult?.is_complete ? 'Natijalarni ko\'rish' : 'Keyingisi' }}
                    </button>
                </div>

                <!-- Powerups Bar -->
                <div v-if="gamePhase === 'playing' && availablePowerups.length > 0" class="mt-6">
                    <div class="flex justify-center gap-4 flex-wrap">
                        <button
                            v-for="powerup in availablePowerups"
                            :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="!canUsePowerup(powerup.id)"
                            class="px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all flex items-center gap-2 shadow-md border border-gray-200 dark:border-gray-700"
                        >
                            <span class="text-xl">{{ powerup.icon }}</span>
                            <span class="text-gray-900 dark:text-white text-sm font-medium">{{ powerup.name }}</span>
                            <span class="text-gray-600 dark:text-gray-400 text-xs">({{ getPowerupUsesLeft(powerup.id) }})</span>
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
