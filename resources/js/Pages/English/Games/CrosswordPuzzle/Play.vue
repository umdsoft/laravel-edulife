<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: { type: Object, required: true },
    config: { type: Object, default: () => ({}) },
    powerups: { type: Array, default: () => [] },
})

// Game state
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const gameCompleted = ref(false)

// Session state
const sessionId = ref(null)
const gameState = ref('idle')
const grid = ref([])
const userGrid = ref([])
const clues = ref({ across: [], down: [] })
const wordPositions = ref([])
const rows = ref(0)
const cols = ref(0)
const score = ref(0)
const xpEarned = ref(0)
const coinsEarned = ref(0)
const streak = ref(0)
const wordsFound = ref(0)
const totalWords = ref(0)
const foundWords = ref([])
const timeLimit = ref(null)
const timeRemaining = ref(null)
const timerInterval = ref(null)
const summary = ref(null)
const powerupsUsed = ref({})

// Selection state
const selectedWord = ref(null)
const currentAnswer = ref('')
const selectedCells = ref([])

// Animation states
const showCelebration = ref(false)

// Progress computed
const progress = computed(() => {
    return totalWords.value > 0 ? Math.round((wordsFound.value / totalWords.value) * 100) : 0
})

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0
})

const timerClass = computed(() => {
    if (timerPercentage.value > 50) return 'text-green-500'
    if (timerPercentage.value > 25) return 'text-yellow-500'
    return 'text-red-500'
})

async function startGame() {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(`/student/english/games/crossword-puzzle/start/${props.level.level_number}`)

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            grid.value = data.grid
            userGrid.value = JSON.parse(JSON.stringify(data.grid))
            clues.value = data.clues
            rows.value = data.rows
            cols.value = data.cols
            totalWords.value = data.words_count
            timeLimit.value = data.time_limit
            timeRemaining.value = data.time_limit
            gameState.value = 'playing'

            buildWordPositions()

            if (timeLimit.value) {
                startTimer()
            }
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

function buildWordPositions() {
    wordPositions.value = []

    clues.value.across.forEach(clue => {
        wordPositions.value.push({
            number: clue.number,
            direction: 'across',
            length: clue.length,
        })
    })

    clues.value.down.forEach(clue => {
        wordPositions.value.push({
            number: clue.number,
            direction: 'down',
            length: clue.length,
        })
    })
}

function startTimer() {
    timerInterval.value = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--
        } else {
            clearInterval(timerInterval.value)
            endGame()
        }
    }, 1000)
}

function selectCell(row, col) {
    if (grid.value[row][col] === null) return
    // In a full implementation, you'd track word positions
}

function selectWordByClue(number, direction) {
    const clue = direction === 'across'
        ? clues.value.across.find(c => c.number === number)
        : clues.value.down.find(c => c.number === number)

    if (clue && !isWordFound(number, direction)) {
        selectedWord.value = { number, direction, length: clue.length }
        currentAnswer.value = ''
    }
}

function getClueForWord(number, direction) {
    const clueList = direction === 'across' ? clues.value.across : clues.value.down
    const clue = clueList.find(c => c.number === number)
    return clue?.clue || ''
}

function isWordFound(number, direction) {
    return foundWords.value.some(w => w.number === number && w.direction === direction)
}

function getCellClass(row, col) {
    const isSelected = selectedCells.value.some(c => c.row === row && c.col === col)

    if (isSelected) return 'bg-purple-500 text-white'
    return 'bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600'
}

function getCellNumber(row, col) {
    // This would need word position data from server
    return null
}

async function submitAnswer() {
    if (!selectedWord.value || !currentAnswer.value.trim()) return

    try {
        const response = await axios.post('/student/english/games/crossword-puzzle/submit-word', {
            session_id: sessionId.value,
            word_number: selectedWord.value.number,
            answer: currentAnswer.value.trim(),
        })

        if (response.data.success) {
            const data = response.data.data

            if (data.correct) {
                score.value = data.score
                xpEarned.value = data.xp_earned || 0
                coinsEarned.value = data.coins_earned || 0
                streak.value = data.streak
                wordsFound.value = data.words_found
                foundWords.value.push({
                    number: selectedWord.value.number,
                    direction: selectedWord.value.direction,
                })

                // Show celebration
                showCelebration.value = true
                setTimeout(() => { showCelebration.value = false }, 600)
            }

            if (data.is_complete) {
                summary.value = data.summary
                endGame()
            }

            selectedWord.value = null
            currentAnswer.value = ''
        }
    } catch (error) {
        console.error('Failed to submit answer:', error)
    }
}

async function usePowerup(powerupId) {
    if (!selectedWord.value) return

    try {
        const response = await axios.post('/student/english/games/crossword-puzzle/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId,
            word_number: selectedWord.value.number,
        })

        if (response.data.success) {
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1
            const data = response.data.data

            if (powerupId === 'reveal_letter' && data.revealed_letter) {
                userGrid.value[data.revealed_letter.row][data.revealed_letter.col] = data.revealed_letter.letter
            } else if (powerupId === 'reveal_word' && data.revealed_word) {
                foundWords.value.push({
                    number: data.revealed_word.number,
                    direction: selectedWord.value.direction,
                })
                wordsFound.value++
                selectedWord.value = null

                if (data.is_complete) {
                    endGame()
                }
            } else if (powerupId === 'hint' && data.hint) {
                alert(`Maslahat: Birinchi harf "${data.hint.first_letter}", tarjima: "${data.hint.translation}"`)
            } else if (powerupId === 'extra_time' && data.time_bonus) {
                timeRemaining.value += data.time_bonus
            } else if (powerupId === 'check_word' && data.check_result) {
                alert(data.check_result.is_correct ? "To'g'ri yo'ldasiz!" : "Xato bor, qayta tekshiring.")
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

function getPowerupUsesRemaining(powerupId) {
    const powerup = props.powerups.find(p => p.id === powerupId)
    if (!powerup) return 0
    return (powerup.uses_per_game || 1) - (powerupsUsed.value[powerupId] || 0)
}

async function endGame() {
    if (timerInterval.value) clearInterval(timerInterval.value)

    if (!summary.value) {
        try {
            const response = await axios.post('/student/english/games/crossword-puzzle/complete', {
                session_id: sessionId.value,
            })
            if (response.data.success) {
                summary.value = response.data.data
            }
        } catch (error) {
            console.error('Failed to complete session:', error)
        }
    }

    gameCompleted.value = true
}

function playAgain() {
    gameCompleted.value = false
    gameState.value = 'idle'
    sessionId.value = null
    grid.value = []
    userGrid.value = []
    clues.value = { across: [], down: [] }
    score.value = 0
    xpEarned.value = 0
    coinsEarned.value = 0
    streak.value = 0
    wordsFound.value = 0
    foundWords.value = []
    timeRemaining.value = null
    summary.value = null
    powerupsUsed.value = {}
    selectedWord.value = null
    currentAnswer.value = ''
}

function goBack() {
    if (timerInterval.value) clearInterval(timerInterval.value)
    router.visit('/student/english/games/crossword-puzzle')
}

function nextLevel() {
    if (timerInterval.value) clearInterval(timerInterval.value)
    const nextLevelNumber = props.level.level_number + 1
    router.visit(`/student/english/games/crossword-puzzle/play/${nextLevelNumber}`)
}

function formatTime(seconds) {
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
    <Head :title="`Krossvord - ${level?.name || 'O\'yin'}`" />

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

            <!-- Pre-game Screen (Idle) -->
            <div v-else-if="gameState === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-2xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 via-violet-500 to-indigo-600 rounded-3xl mb-6 shadow-2xl shadow-purple-500/30">
                            <span class="text-5xl filter drop-shadow-lg">{{ config.icon || '📝' }}</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.name_uz }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-purple-600 dark:text-purple-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.word_count }} so'z</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_limit ? formatTime(level.time_limit) : '∞' }}</span>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8 mb-6">
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-5 border border-gray-100 dark:border-gray-600/50 text-center">
                                <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ level?.word_count }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">So'z</div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/20 rounded-xl p-5 border border-purple-100 dark:border-purple-800/50 text-center">
                                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 capitalize">{{ level?.grid_size }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">To'r</div>
                            </div>
                            <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-5 border border-cyan-100 dark:border-cyan-800/50 text-center">
                                <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ level?.time_limit ? formatTime(level.time_limit) : '∞' }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Vaqt</div>
                            </div>
                        </div>

                        <button @click="startGame" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            O'yinni boshlash
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="text-center">
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
            <div v-else-if="gameCompleted && summary" class="flex items-center justify-center min-h-[80vh]">
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
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">O'yinni muvaffaqiyatli tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ summary.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ summary.words_found }}/{{ summary.total_words }}</div>
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
                        <button @click="playAgain" class="flex-1 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="summary.stars > 0" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameState === 'playing'" class="max-w-7xl mx-auto">
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
                            <span class="text-2xl">{{ config.icon || '📝' }}</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
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
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Seriya</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ wordsFound }}/{{ totalWords }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">So'zlar</div>
                        </div>
                        <div v-if="timeLimit" :class="[
                            'rounded-xl p-4 border',
                            timeRemaining < 60
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 border-cyan-100 dark:border-cyan-800/50'
                        ]">
                            <div :class="timeRemaining < 60 ? 'text-red-600 dark:text-red-400' : 'text-cyan-600 dark:text-cyan-400'" class="text-3xl font-bold">{{ formatTime(timeRemaining) }}</div>
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
                            <span>{{ wordsFound }}/{{ totalWords }} so'z</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-purple-500 via-violet-500 to-indigo-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Game Content -->
                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Crossword Grid -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-6">
                            <!-- Grid -->
                            <div class="overflow-x-auto mb-6">
                                <div class="grid gap-1 mx-auto" :style="{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`, maxWidth: `${cols * 36}px` }">
                                    <div v-for="(row, rowIndex) in grid" :key="rowIndex" class="contents">
                                        <div v-for="(cell, colIndex) in row" :key="`${rowIndex}-${colIndex}`"
                                             @click="selectCell(rowIndex, colIndex)"
                                             :class="[
                                                 'w-8 h-8 flex items-center justify-center text-sm font-bold rounded transition-all relative',
                                                 cell === null ? 'bg-transparent' : getCellClass(rowIndex, colIndex)
                                             ]">
                                            <span v-if="cell !== null">
                                                <span v-if="getCellNumber(rowIndex, colIndex)" class="absolute text-[8px] top-0 left-0.5 text-purple-600 dark:text-purple-400">{{ getCellNumber(rowIndex, colIndex) }}</span>
                                                {{ userGrid[rowIndex]?.[colIndex] || '' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Input for selected word -->
                            <div v-if="selectedWord" class="p-4 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border border-purple-100 dark:border-purple-800/50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-3 py-1 bg-purple-600 text-white text-sm font-bold rounded-lg">{{ selectedWord.number }}.</span>
                                    <span class="px-3 py-1 bg-white dark:bg-gray-700 text-purple-600 dark:text-purple-400 text-sm font-medium rounded-lg">{{ selectedWord.direction === 'across' ? 'Gorizontal' : 'Vertikal' }}</span>
                                    <span class="text-gray-800 dark:text-white font-medium">{{ getClueForWord(selectedWord.number, selectedWord.direction) }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <input v-model="currentAnswer" @keyup.enter="submitAnswer"
                                           :maxlength="selectedWord.length"
                                           class="flex-1 px-4 py-3 bg-white dark:bg-gray-700 border-2 border-purple-200 dark:border-purple-800 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-200 dark:focus:ring-purple-900/50 font-medium"
                                           placeholder="Javobingizni yozing...">
                                    <button @click="submitAnswer" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                        Tekshirish
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Powerups -->
                        <div v-if="powerups && powerups.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-4 mt-4">
                            <h3 class="text-gray-700 dark:text-gray-300 font-bold mb-3 text-center">Kuchlar</h3>
                            <div class="flex flex-wrap justify-center gap-3">
                                <button v-for="powerup in powerups" :key="powerup.id"
                                        @click="usePowerup(powerup.id)"
                                        :disabled="getPowerupUsesRemaining(powerup.id) <= 0 || !selectedWord"
                                        class="px-4 py-2.5 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 hover:from-blue-100 hover:to-purple-100 dark:hover:from-blue-900/30 dark:hover:to-purple-900/30 rounded-xl text-gray-900 dark:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 border border-blue-100 dark:border-blue-800/50 hover:scale-105 shadow-md">
                                    <span class="text-xl">{{ powerup.icon }}</span>
                                    <span class="text-sm font-medium">{{ powerup.name_uz }}</span>
                                    <span class="text-xs px-2 py-0.5 bg-purple-600 text-white rounded-full font-bold">{{ getPowerupUsesRemaining(powerup.id) }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Clues Panel -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-5 max-h-[700px] overflow-y-auto">
                        <h3 class="text-gray-800 dark:text-white font-bold mb-5 text-lg text-center">Savollar</h3>

                        <!-- Across Clues -->
                        <div class="mb-6">
                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-xl">→</span>
                                <h4 class="text-purple-600 dark:text-purple-400 font-bold">Gorizontal</h4>
                            </div>
                            <div class="space-y-2">
                                <div v-for="clue in clues.across" :key="`across-${clue.number}`"
                                     @click="selectWordByClue(clue.number, 'across')"
                                     :class="[
                                         'p-3 rounded-xl cursor-pointer transition-all border-2',
                                         isWordFound(clue.number, 'across')
                                             ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border-green-400 line-through opacity-70'
                                             : selectedWord?.number === clue.number && selectedWord?.direction === 'across'
                                                 ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white border-transparent shadow-lg'
                                                 : 'bg-gray-50 dark:bg-gray-700/50 hover:bg-purple-50 dark:hover:bg-purple-900/20 border-gray-200 dark:border-gray-600 hover:border-purple-400'
                                     ]">
                                    <span :class="[
                                        'font-bold mr-2',
                                        isWordFound(clue.number, 'across') ? 'text-green-600 dark:text-green-400' : selectedWord?.number === clue.number && selectedWord?.direction === 'across' ? 'text-white' : 'text-purple-600 dark:text-purple-400'
                                    ]">{{ clue.number }}.</span>
                                    <span :class="[
                                        'text-sm',
                                        isWordFound(clue.number, 'across') ? 'text-green-700 dark:text-green-400' : selectedWord?.number === clue.number && selectedWord?.direction === 'across' ? 'text-white' : 'text-gray-800 dark:text-gray-200'
                                    ]">{{ clue.clue }}</span>
                                    <span :class="[
                                        'text-xs ml-2',
                                        isWordFound(clue.number, 'across') ? 'text-green-600 dark:text-green-500' : selectedWord?.number === clue.number && selectedWord?.direction === 'across' ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'
                                    ]">({{ clue.length }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- Down Clues -->
                        <div>
                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-xl">↓</span>
                                <h4 class="text-purple-600 dark:text-purple-400 font-bold">Vertikal</h4>
                            </div>
                            <div class="space-y-2">
                                <div v-for="clue in clues.down" :key="`down-${clue.number}`"
                                     @click="selectWordByClue(clue.number, 'down')"
                                     :class="[
                                         'p-3 rounded-xl cursor-pointer transition-all border-2',
                                         isWordFound(clue.number, 'down')
                                             ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border-green-400 line-through opacity-70'
                                             : selectedWord?.number === clue.number && selectedWord?.direction === 'down'
                                                 ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white border-transparent shadow-lg'
                                                 : 'bg-gray-50 dark:bg-gray-700/50 hover:bg-purple-50 dark:hover:bg-purple-900/20 border-gray-200 dark:border-gray-600 hover:border-purple-400'
                                     ]">
                                    <span :class="[
                                        'font-bold mr-2',
                                        isWordFound(clue.number, 'down') ? 'text-green-600 dark:text-green-400' : selectedWord?.number === clue.number && selectedWord?.direction === 'down' ? 'text-white' : 'text-purple-600 dark:text-purple-400'
                                    ]">{{ clue.number }}.</span>
                                    <span :class="[
                                        'text-sm',
                                        isWordFound(clue.number, 'down') ? 'text-green-700 dark:text-green-400' : selectedWord?.number === clue.number && selectedWord?.direction === 'down' ? 'text-white' : 'text-gray-800 dark:text-gray-200'
                                    ]">{{ clue.clue }}</span>
                                    <span :class="[
                                        'text-xs ml-2',
                                        isWordFound(clue.number, 'down') ? 'text-green-600 dark:text-green-500' : selectedWord?.number === clue.number && selectedWord?.direction === 'down' ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'
                                    ]">({{ clue.length }})</span>
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
/* Custom scrollbar for clues panel */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
