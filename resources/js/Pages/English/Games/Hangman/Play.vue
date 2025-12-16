<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'
import { HeartIcon, StarIcon } from '@heroicons/vue/24/solid'

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
    categories: {
        type: Array,
        default: () => []
    }
})

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('')

// Game state
const gameState = ref('idle')
const sessionId = ref(null)
const score = ref(0)
const xpEarned = ref(0)
const coinsEarned = ref(0)
const streak = ref(0)
const bestStreak = ref(0)

// Word state
const wordDisplay = ref([])
const guessedLetters = ref([])
const wrongGuesses = ref(0)
const maxWrong = ref(6)
const livesRemaining = ref(6)
const wordIndex = ref(0)
const totalWords = ref(0)

// Mode selection
const selectedMode = ref('classic')
const showModeSelect = ref(true)

// Powerups
const usedPowerups = ref([])
const currentHint = ref(null)

// Result state
const lastWordSolved = ref(false)
const lastWord = ref('')
const lastWordHint = ref('')
const lastPointsEarned = ref(0)
const hasNextWord = ref(false)
const finalResult = ref(null)

// Loading and error states
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')

// Animation states
const showCelebration = ref(false)

const availablePowerups = computed(() => {
    return props.powerups.filter(p => {
        if (p.id === 'extra_time' && !props.gameModes.find(m => m.id === selectedMode.value)?.time_limit) {
            return false
        }
        return true
    })
})

const progress = computed(() => {
    return totalWords.value > 0 ? Math.round((wordIndex.value / totalWords.value) * 100) : 0
})

function getModeIcon(modeId) {
    const mode = props.gameModes.find(m => m.id === modeId)
    return mode?.icon || '🎮'
}

function getModeBgGradient(modeId) {
    const gradients = {
        'classic': 'from-purple-500 to-indigo-600',
        'time_attack': 'from-red-500 to-rose-600',
        'endless': 'from-emerald-500 to-teal-600',
        'themed': 'from-amber-500 to-orange-600',
        'hard_mode': 'from-slate-500 to-gray-600'
    }
    return gradients[modeId] || 'from-purple-500 to-indigo-600'
}

function isPowerupUsed(powerupId) {
    const maxUses = props.powerups.find(p => p.id === powerupId)?.uses_per_game || 1
    const usedCount = usedPowerups.value.filter(id => id === powerupId).length
    return usedCount >= maxUses
}

function getLetterClass(letter) {
    if (!guessedLetters.value.includes(letter)) {
        return 'bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white hover:bg-purple-50 dark:hover:bg-purple-900/20 border-gray-200 dark:border-gray-600 hover:border-purple-400 hover:shadow-xl hover:scale-105'
    }
    if (wordDisplay.value.includes(letter)) {
        return 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400 cursor-not-allowed opacity-70'
    }
    return 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 border-red-400 cursor-not-allowed opacity-70'
}

async function selectMode(mode) {
    selectedMode.value = mode
    showModeSelect.value = false
    await startGame()
}

// Start game
async function startGame() {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(`/student/english/games/hangman/start/${props.level.level_number}`, {
            mode: selectedMode.value
        })

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            totalWords.value = data.total_words
            maxWrong.value = data.max_wrong

            updateWordState(data.current_word)

            score.value = 0
            xpEarned.value = 0
            coinsEarned.value = 0
            streak.value = 0
            bestStreak.value = 0
            usedPowerups.value = []
            currentHint.value = null

            gameState.value = 'playing'
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

function updateWordState(wordData) {
    wordDisplay.value = wordData.display
    guessedLetters.value = wordData.guessed_letters || []
    wrongGuesses.value = wordData.wrong_guesses
    livesRemaining.value = wordData.lives_remaining
    wordIndex.value = wordData.word_index
}

// Guess a letter
async function guessLetter(letter) {
    if (guessedLetters.value.includes(letter)) return

    try {
        const response = await axios.post('/student/english/games/hangman/guess', {
            session_id: sessionId.value,
            letter: letter
        })

        if (response.data.success) {
            const data = response.data.data

            if (data.already_guessed) return

            updateWordState(data.current_word)
            score.value = data.score || score.value
            xpEarned.value = data.xp_earned || xpEarned.value
            coinsEarned.value = data.coins_earned || coinsEarned.value
            streak.value = data.streak || streak.value
            bestStreak.value = Math.max(bestStreak.value, streak.value)

            if (data.word_solved) {
                // Show celebration
                showCelebration.value = true
                setTimeout(() => { showCelebration.value = false }, 600)

                lastWordSolved.value = true
                lastWord.value = data.word
                lastPointsEarned.value = data.points_earned || 0
                lastWordHint.value = ''
                hasNextWord.value = data.has_next_word || false
                gameState.value = 'word_result'
            } else if (data.word_failed) {
                lastWordSolved.value = false
                lastWord.value = data.word
                lastPointsEarned.value = 0
                lastWordHint.value = data.hint || ''
                hasNextWord.value = data.has_next_word || false
                streak.value = 0
                gameState.value = 'word_result'
            }

            if (data.level_complete) {
                hasNextWord.value = false
            }
        }
    } catch (error) {
        console.error('Failed to guess letter:', error)
    }
}

// Handle next action after word result
async function handleNextAction() {
    if (hasNextWord.value) {
        await nextWord()
    } else {
        await completeGame()
    }
}

// Get next word
async function nextWord() {
    try {
        const response = await axios.post('/student/english/games/hangman/next', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            const data = response.data.data
            updateWordState(data.current_word)
            score.value = data.score || score.value
            xpEarned.value = data.xp_earned || xpEarned.value
            coinsEarned.value = data.coins_earned || coinsEarned.value
            streak.value = data.streak || streak.value
            currentHint.value = null
            gameState.value = 'playing'
        }
    } catch (error) {
        console.error('Failed to get next word:', error)
    }
}

// Complete game
async function completeGame() {
    try {
        const response = await axios.post('/student/english/games/hangman/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            finalResult.value = response.data.data
            gameState.value = 'complete'
        }
    } catch (error) {
        console.error('Failed to complete game:', error)
    }
}

// Use powerup
async function usePowerup(powerupId) {
    try {
        const response = await axios.post('/student/english/games/hangman/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            usedPowerups.value.push(powerupId)
            const data = response.data.data

            if (data.current_word) {
                updateWordState(data.current_word)
            }
            if (data.hint) {
                currentHint.value = data.hint
            }
            if (data.word_solved) {
                // Show celebration
                showCelebration.value = true
                setTimeout(() => { showCelebration.value = false }, 600)

                lastWordSolved.value = true
                lastWord.value = data.word
                lastPointsEarned.value = data.points_earned || 0
                hasNextWord.value = data.has_next_word || false
                streak.value = data.streak || streak.value
                gameState.value = 'word_result'
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

// Restart game
function playAgain() {
    gameState.value = 'idle'
    sessionId.value = null
    score.value = 0
    xpEarned.value = 0
    coinsEarned.value = 0
    streak.value = 0
    bestStreak.value = 0
    wordDisplay.value = []
    guessedLetters.value = []
    wrongGuesses.value = 0
    usedPowerups.value = []
    currentHint.value = null
    finalResult.value = null
    showModeSelect.value = true
}

function goBack() {
    router.visit('/student/english/games/hangman')
}

function nextLevel() {
    const nextLevelNumber = props.level.level_number + 1
    if (nextLevelNumber <= 10) {
        router.visit(`/student/english/games/hangman/play/${nextLevelNumber}`)
    } else {
        router.visit('/student/english/games/hangman')
    }
}

onUnmounted(() => {
    // Cleanup if needed
})
</script>

<template>
    <Head :title="`Hangman - ${level?.name || 'O\'yin'}`" />

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
                            <span class="text-5xl filter drop-shadow-lg">🎯</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name || 'Hangman' }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-purple-600 dark:text-purple-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.word_count || 0 }} ta so'z</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectMode(mode.id)"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500 overflow-hidden">

                            <div class="relative z-10 p-8">
                                <!-- Icon -->
                                <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center text-5xl mb-5 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300', getModeBgGradient(mode.id)]">
                                    {{ mode.icon }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2 transition-colors">{{ mode.name }}</h3>

                                <!-- Description -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ mode.description }}</p>

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
            <div v-else-if="gameState === 'complete' && finalResult" class="flex items-center justify-center min-h-[80vh]">
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
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ finalResult.words_solved || 0 }}/{{ finalResult.total_words || 0 }} so'z topildi</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ finalResult.total_score || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ finalResult.xp_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ finalResult.coins_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ bestStreak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="finalResult?.new_achievements?.length" class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Yangi yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div v-for="achievement in finalResult.new_achievements" :key="achievement.id"
                                 class="bg-gradient-to-br from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 rounded-xl px-4 py-2 flex items-center gap-2 border border-yellow-200 dark:border-yellow-800/50">
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
                        <button @click="playAgain" class="flex-1 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="finalResult.stars > 0 && level.level_number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Word Result Screen -->
            <div v-else-if="gameState === 'word_result'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <div :class="['w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center text-5xl', lastWordSolved ? 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30' : 'bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30']">
                        {{ lastWordSolved ? '🎉' : '😔' }}
                    </div>

                    <h2 :class="['text-3xl font-bold mb-3', lastWordSolved ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']">
                        {{ lastWordSolved ? 'Ajoyib!' : 'Topilmadi' }}
                    </h2>

                    <p class="text-gray-800 dark:text-white text-xl mb-2">
                        So'z: <span class="font-bold text-purple-600 dark:text-purple-400">{{ lastWord }}</span>
                    </p>

                    <p v-if="lastWordHint" class="text-gray-500 dark:text-gray-400 text-sm mb-6">
                        {{ lastWordHint }}
                    </p>

                    <div v-if="lastPointsEarned > 0" class="mb-8 p-4 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-2xl border border-amber-100 dark:border-amber-800/50">
                        <span class="text-amber-600 dark:text-amber-400 text-2xl font-bold">+{{ lastPointsEarned }} ball</span>
                    </div>

                    <button @click="handleNextAction" class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-lg font-semibold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        {{ hasNextWord ? 'Keyingi so\'z' : 'Natijalarni ko\'rish' }}
                    </button>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameState === 'playing'" class="max-w-6xl mx-auto">
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
                            <span class="text-2xl">{{ getModeIcon(selectedMode) }}</span>
                            <span class="font-bold text-white">{{ level?.name || 'Hangman' }}</span>
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
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/50">
                            <div class="flex items-center justify-center gap-1">
                                <HeartIcon v-for="i in maxWrong" :key="i" :class="i <= livesRemaining ? 'text-red-500' : 'text-gray-300 dark:text-gray-600'" class="w-6 h-6" />
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Jonlar</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ wordIndex }}/{{ totalWords }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">So'zlar</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ wordIndex }}/{{ totalWords }} so'z</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-purple-500 via-violet-500 to-indigo-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Game Area -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 md:p-8 shadow-xl border border-gray-100 dark:border-gray-700 mb-5">
                    <!-- Hangman Figure -->
                    <div class="flex justify-center mb-8">
                        <svg width="240" height="240" viewBox="0 0 200 200" class="text-gray-700 dark:text-gray-300">
                            <!-- Gallows -->
                            <line x1="20" y1="180" x2="80" y2="180" stroke="currentColor" stroke-width="4"/>
                            <line x1="50" y1="180" x2="50" y2="20" stroke="currentColor" stroke-width="4"/>
                            <line x1="50" y1="20" x2="130" y2="20" stroke="currentColor" stroke-width="4"/>
                            <line x1="130" y1="20" x2="130" y2="40" stroke="currentColor" stroke-width="4"/>

                            <!-- Head -->
                            <circle v-if="wrongGuesses >= 1" cx="130" cy="55" r="15" fill="none" stroke="currentColor" stroke-width="3"/>
                            <!-- Body -->
                            <line v-if="wrongGuesses >= 2" x1="130" y1="70" x2="130" y2="120" stroke="currentColor" stroke-width="3"/>
                            <!-- Left arm -->
                            <line v-if="wrongGuesses >= 3" x1="130" y1="85" x2="105" y2="105" stroke="currentColor" stroke-width="3"/>
                            <!-- Right arm -->
                            <line v-if="wrongGuesses >= 4" x1="130" y1="85" x2="155" y2="105" stroke="currentColor" stroke-width="3"/>
                            <!-- Left leg -->
                            <line v-if="wrongGuesses >= 5" x1="130" y1="120" x2="105" y2="155" stroke="currentColor" stroke-width="3"/>
                            <!-- Right leg -->
                            <line v-if="wrongGuesses >= 6" x1="130" y1="120" x2="155" y2="155" stroke="currentColor" stroke-width="3"/>
                        </svg>
                    </div>

                    <!-- Word Display -->
                    <div class="flex justify-center gap-2 md:gap-3 mb-8 flex-wrap">
                        <span v-for="(letter, index) in wordDisplay" :key="index"
                              class="w-10 h-12 md:w-14 md:h-16 flex items-center justify-center text-2xl md:text-4xl font-bold border-b-4 transition-all"
                              :class="letter !== '_' ? 'text-purple-600 dark:text-purple-400 border-purple-600 dark:border-purple-400' : 'text-transparent border-gray-300 dark:border-gray-600'">
                            {{ letter }}
                        </span>
                    </div>

                    <!-- Hint -->
                    <div v-if="currentHint" class="text-center mb-6 p-4 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 rounded-xl border border-amber-100 dark:border-amber-800/50">
                        <p class="text-amber-700 dark:text-amber-400">
                            <span class="font-bold">💡 Ipucu:</span> {{ currentHint }}
                        </p>
                    </div>
                </div>

                <!-- Keyboard -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 md:p-6 shadow-xl border border-gray-100 dark:border-gray-700 mb-5">
                    <div class="flex flex-wrap justify-center gap-2">
                        <button v-for="letter in alphabet" :key="letter"
                                @click="guessLetter(letter)"
                                :disabled="guessedLetters.includes(letter)"
                                :class="getLetterClass(letter)"
                                class="w-10 h-10 md:w-12 md:h-12 rounded-xl font-bold text-base md:text-lg transition-all border-2 shadow-md">
                            {{ letter }}
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="availablePowerups.length > 0" class="flex justify-center gap-3 flex-wrap">
                    <button v-for="powerup in availablePowerups" :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="isPowerupUsed(powerup.id)"
                            class="px-5 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg border-2 border-gray-100 dark:border-gray-700 flex items-center gap-2 hover:shadow-xl transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <span class="text-2xl">{{ powerup.icon }}</span>
                        <span class="text-gray-700 dark:text-gray-300 font-semibold">{{ powerup.name }}</span>
                    </button>
                </div>
            </div>

            <!-- Celebration Effect -->
            <div v-if="showCelebration" class="fixed inset-0 pointer-events-none z-40">
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div class="text-8xl animate-bounce">✨</div>
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
