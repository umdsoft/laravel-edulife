<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: { type: Object, required: true },
    config: { type: Object, default: () => ({}) },
    powerups: { type: Array, default: () => [] }
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const gameState = ref('idle')
const sessionId = ref(null)
const grid = ref([])
const words = ref([])
const rows = ref(0)
const cols = ref(0)
const score = ref(0)
const xpEarned = ref(0)
const coinsEarned = ref(0)
const timeLimit = ref(null)
const timeRemaining = ref(null)
const timerInterval = ref(null)
const summary = ref(null)
const powerupsUsed = ref({})

// Selection state
const isSelecting = ref(false)
const selectedCells = ref([])
const foundWordCells = ref([])
const hintCell = ref(null)

// Animation states
const showCelebration = ref(false)

const foundCount = computed(() => words.value.filter(w => w.found).length)
const totalWords = computed(() => words.value.length)

const progress = computed(() => {
    return totalWords.value > 0 ? Math.round((foundCount.value / totalWords.value) * 100) : 0
})

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0
})

const startGame = async () => {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(route('student.english.games.word-search.start-session', { level: props.level.level_number }))
        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            grid.value = data.grid
            words.value = data.words
            rows.value = data.rows
            cols.value = data.cols
            timeLimit.value = data.time_limit
            timeRemaining.value = data.time_limit
            gameState.value = 'playing'
            if (timeLimit.value) startTimer()
        } else {
            hasError.value = true
            errorMessage.value = response.data.error || 'Failed to start game'
        }
    } catch (error) {
        hasError.value = true
        errorMessage.value = error.response?.data?.message || 'An error occurred'
    } finally {
        isLoading.value = false
    }
}

const startTimer = () => {
    timerInterval.value = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--
        } else {
            clearInterval(timerInterval.value)
            endGame()
        }
    }, 1000)
}

const startSelection = (row, col) => {
    isSelecting.value = true
    selectedCells.value = [{ row, col }]
}

const extendSelection = (row, col) => {
    if (!isSelecting.value) return
    const last = selectedCells.value[selectedCells.value.length - 1]
    if (last.row === row && last.col === col) return

    const first = selectedCells.value[0]
    const dx = row === first.row ? 0 : (row > first.row ? 1 : -1)
    const dy = col === first.col ? 0 : (col > first.col ? 1 : -1)

    selectedCells.value = []
    let r = first.row, c = first.col
    while (r >= 0 && r < rows.value && c >= 0 && c < cols.value) {
        selectedCells.value.push({ row: r, col: c })
        if (r === row && c === col) break
        r += dx
        c += dy
    }
}

const handleTouchMove = (e) => {
    const touch = e.touches[0]
    const element = document.elementFromPoint(touch.clientX, touch.clientY)
    if (element && element.dataset.row !== undefined) {
        extendSelection(parseInt(element.dataset.row), parseInt(element.dataset.col))
    }
}

const endSelection = async () => {
    if (!isSelecting.value || selectedCells.value.length < 2) {
        isSelecting.value = false
        selectedCells.value = []
        return
    }

    const word = selectedCells.value.map(c => grid.value[c.row][c.col]).join('')

    try {
        const response = await axios.post(route('student.english.games.word-search.check-word'), {
            session_id: sessionId.value,
            word: word,
            positions: selectedCells.value
        })

        if (response.data.success && response.data.data.valid) {
            const data = response.data.data
            foundWordCells.value.push(...selectedCells.value)
            score.value = data.score
            xpEarned.value = data.xp_earned || 0
            coinsEarned.value = data.coins_earned || 0

            const wordIndex = words.value.findIndex(w => w.word.toUpperCase() === word)
            if (wordIndex !== -1) words.value[wordIndex].found = true

            // Show celebration
            showCelebration.value = true
            setTimeout(() => { showCelebration.value = false }, 600)

            if (data.is_complete) {
                summary.value = data.summary
                endGame()
            }
        }
    } catch (error) {
        console.error('Failed to check word:', error)
    }

    isSelecting.value = false
    selectedCells.value = []
}

const getCellClass = (row, col) => {
    const isSelected = selectedCells.value.some(c => c.row === row && c.col === col)
    const isFound = foundWordCells.value.some(c => c.row === row && c.col === col)
    const isHint = hintCell.value && hintCell.value.row === row && hintCell.value.col === col

    if (isHint) return 'bg-yellow-400 text-gray-900 border-2 border-yellow-500 shadow-lg scale-110 z-10'
    if (isFound) return 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-2 border-green-400 shadow-md'
    if (isSelected) return 'bg-gradient-to-br from-teal-500 to-cyan-600 text-white border-2 border-teal-400 shadow-xl scale-105 z-10'
    return 'bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white hover:bg-teal-50 dark:hover:bg-teal-900/20 border border-gray-200 dark:border-gray-600 hover:border-teal-400 hover:scale-105 hover:shadow-md'
}

const usePowerup = async (powerupId) => {
    try {
        const response = await axios.post(route('student.english.games.word-search.use-powerup'), {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1
            const data = response.data.data

            if (powerupId === 'hint' && data.hint) {
                hintCell.value = data.hint.first_letter_position
                setTimeout(() => hintCell.value = null, 3000)
            } else if (powerupId === 'reveal_word' && data.revealed_word) {
                foundWordCells.value.push(...data.revealed_word.positions)
                const wordIndex = words.value.findIndex(w => w.word.toUpperCase() === data.revealed_word.word)
                if (wordIndex !== -1) words.value[wordIndex].found = true
            } else if (powerupId === 'extra_time' && data.time_bonus) {
                timeRemaining.value += data.time_bonus
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

const getPowerupUsesRemaining = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId)
    if (!powerup) return 0
    return (powerup.uses_per_game || 1) - (powerupsUsed.value[powerupId] || 0)
}

const endGame = async () => {
    if (timerInterval.value) clearInterval(timerInterval.value)
    if (!summary.value) {
        try {
            const response = await axios.post(route('student.english.games.word-search.complete-session'), { session_id: sessionId.value })
            if (response.data.success) summary.value = response.data.data
        } catch (error) {
            console.error('Failed to complete session:', error)
        }
    }
    gameState.value = 'complete'
}

const restartGame = () => {
    gameState.value = 'idle'
    sessionId.value = null
    grid.value = []
    words.value = []
    score.value = 0
    xpEarned.value = 0
    coinsEarned.value = 0
    timeRemaining.value = null
    summary.value = null
    powerupsUsed.value = {}
    selectedCells.value = []
    foundWordCells.value = []
    hintCell.value = null
}

const goBack = () => {
    clearInterval(timerInterval.value)
    router.visit('/student/english/games/word-search')
}

const nextLevel = () => {
    clearInterval(timerInterval.value)
    const nextLevelNumber = props.level.level_number + 1
    if (nextLevelNumber <= 10) {
        router.visit(`/student/english/games/word-search/play/${nextLevelNumber}`)
    } else {
        router.visit('/student/english/games/word-search')
    }
}

const formatTime = (seconds) => {
    if (seconds === null) return '--:--'
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<template>
    <Head :title="`So'z Qidirish - ${level?.name || 'O\'yin'}`" />

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

            <!-- Start Screen -->
            <div v-else-if="gameState === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-2xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-teal-500 via-cyan-500 to-blue-600 rounded-3xl mb-6 shadow-2xl shadow-teal-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🔍</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.name_uz }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-teal-600 dark:text-teal-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.word_count }} so'z</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400 capitalize">{{ level?.grid_size }}</span>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700 mb-8">
                        <p class="text-gray-600 dark:text-gray-400 text-center mb-6 leading-relaxed">{{ level?.description }}</p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-2xl p-5 border border-teal-100 dark:border-teal-800/50">
                                <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ level?.word_count }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">So'z</div>
                            </div>
                            <div class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-2xl p-5 border border-cyan-100 dark:border-cyan-800/50">
                                <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400 capitalize">{{ level?.grid_size }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">To'r</div>
                            </div>
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-5 border border-blue-100 dark:border-blue-800/50">
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ level?.time_limit ? formatTime(level.time_limit) : '∞' }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Vaqt</div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button @click="startGame" class="w-full py-4 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105 text-lg">
                            O'yinni boshlash
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameState === 'complete' && summary" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ summary.stars === 3 ? '🏆' : summary.stars === 2 ? '🎉' : summary.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= summary.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ summary.stars === 3 ? 'Mukammal!' : summary.stars === 2 ? 'Ajoyib!' : summary.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ summary.words_found }}/{{ summary.total_words }} so'z topdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-teal-50 dark:bg-teal-900/20 rounded-2xl p-5 border border-teal-100 dark:border-teal-800/50">
                            <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ summary.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl p-5 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ summary.words_found }}/{{ summary.total_words }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">So'zlar</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ summary.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ summary.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
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
                        <button v-if="summary.stars > 0 && level.level_number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else class="max-w-7xl mx-auto">
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
                            <span class="text-2xl">🔍</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-teal-50 dark:bg-teal-900/30 rounded-xl border border-teal-100 dark:border-teal-800/50">
                                <span class="text-teal-500">✨</span>
                                <span class="text-teal-600 dark:text-teal-400 font-bold">{{ xpEarned }} XP</span>
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
                        <div class="bg-gradient-to-br from-teal-50 to-cyan-100 dark:from-teal-900/30 dark:to-cyan-900/20 rounded-xl p-4 border border-teal-100 dark:border-teal-800/50">
                            <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ foundCount }}/{{ totalWords }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">So'zlar</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ progress }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Progress</div>
                        </div>
                        <div v-if="timeLimit" :class="[
                            'rounded-xl p-4 border',
                            timeRemaining < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="timeRemaining < 30 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ formatTime(timeRemaining) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ foundCount }}/{{ totalWords }} so'z</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Game Area -->
                <div class="grid lg:grid-cols-3 gap-5">
                    <!-- Word Grid -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">🔤</span>
                                    <h3 class="text-gray-700 dark:text-gray-300 font-bold text-lg">So'zlar to'ri</h3>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                                    {{ foundCount }}/{{ totalWords }}
                                </div>
                            </div>

                            <div class="grid gap-1 select-none" :style="{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` }">
                                <div v-for="(row, rowIndex) in grid" :key="rowIndex" class="contents">
                                    <div v-for="(cell, colIndex) in row" :key="`${rowIndex}-${colIndex}`"
                                         :data-row="rowIndex"
                                         :data-col="colIndex"
                                         @mousedown="startSelection(rowIndex, colIndex)"
                                         @mouseenter="extendSelection(rowIndex, colIndex)"
                                         @mouseup="endSelection"
                                         @touchstart.prevent="startSelection(rowIndex, colIndex)"
                                         @touchmove.prevent="handleTouchMove($event)"
                                         @touchend.prevent="endSelection"
                                         class="aspect-square flex items-center justify-center text-base md:text-lg font-bold rounded-lg cursor-pointer transition-all duration-300"
                                         :class="getCellClass(rowIndex, colIndex)">
                                        {{ cell }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Powerups -->
                        <div v-if="powerups.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl p-5 mt-5 shadow-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-xl">⚡</span>
                                <h3 class="text-gray-700 dark:text-gray-300 font-bold">Kuchlar</h3>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button v-for="powerup in powerups" :key="powerup.id"
                                        @click="usePowerup(powerup.id)"
                                        :disabled="getPowerupUsesRemaining(powerup.id) <= 0"
                                        class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600 transition-all hover:scale-105 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                    <span class="text-xl">{{ powerup.icon }}</span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ powerup.name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-0.5 rounded-full">{{ getPowerupUsesRemaining(powerup.id) }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Words List -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-2xl">📝</span>
                            <h3 class="text-gray-700 dark:text-gray-300 font-bold text-lg">So'zlar ro'yxati</h3>
                        </div>
                        <div class="space-y-2.5 max-h-[600px] overflow-y-auto pr-2">
                            <div v-for="word in words" :key="word.word"
                                 :class="[
                                     'p-3.5 rounded-xl transition-all duration-300 border-2',
                                     word.found
                                         ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border-green-400 scale-95'
                                         : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 hover:border-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20'
                                 ]">
                                <div :class="word.found ? 'text-green-700 dark:text-green-400 line-through' : 'text-gray-900 dark:text-white'" class="font-bold mb-1">
                                    {{ word.word }}
                                </div>
                                <div :class="word.found ? 'text-green-600 dark:text-green-500' : 'text-gray-600 dark:text-gray-400'" class="text-sm">
                                    {{ word.translation }}
                                </div>
                            </div>
                        </div>
                    </div>
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

/* Custom scrollbar for word list */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #475569;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}
</style>
