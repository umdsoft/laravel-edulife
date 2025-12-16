<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: { type: Object, required: true },
    config: { type: Object, default: () => ({}) },
    gameModes: { type: Array, default: () => [] },
    powerups: { type: Array, default: () => [] }
})

const isLoading = ref(false)
const gameStarted = ref(false)
const gameComplete = ref(false)
const gameOver = ref(false)
const showResult = ref(false)
const isSubmitting = ref(false)
const isPlaying = ref(false)

const sessionId = ref(null)
const selectedMode = ref('classic')
const currentWord = ref(null)
const currentIndex = ref(0)
const totalWords = ref(0)
const userAnswer = ref('')
const lastUserAnswer = ref('')
const hintDisplay = ref(null)
const hintsRemaining = ref(0)
const showDefinition = ref(false)

const timeRemaining = ref(30)
let timerInterval = null

const gameState = ref({ score: 0, streak: 0, correct: 0, wrong: 0, lives: 3 })
const lastResult = ref(null)
const finalResult = ref(null)
const powerupUsed = ref({})

function getModeIcon(modeId) {
    const icons = {
        'classic': '📝',
        'timed': '⏱️',
        'lives': '❤️'
    }
    return icons[modeId] || '🎮'
}

function getModeBgGradient(modeId) {
    const gradients = {
        'classic': 'from-blue-500 to-cyan-600',
        'timed': 'from-orange-500 to-amber-600',
        'lives': 'from-red-500 to-rose-600'
    }
    return gradients[modeId] || 'from-blue-500 to-cyan-600'
}

const startGame = async () => {
    isLoading.value = true
    try {
        const response = await axios.post(
            route('student.english.games.spelling-bee.start', { level: props.level.level_number }),
            { mode: selectedMode.value }
        )

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            currentWord.value = data.current_word
            currentIndex.value = data.current_index
            totalWords.value = data.total_words
            timeRemaining.value = data.time_per_word
            gameState.value.lives = data.lives
            hintsRemaining.value = data.hints_remaining

            gameStarted.value = true
            startTimer()
            setTimeout(() => playAudio(), 500)
        }
    } catch (error) {
        console.error('Failed to start game:', error)
    } finally {
        isLoading.value = false
    }
}

const playAudio = () => {
    if (isPlaying.value || !currentWord.value) return
    isPlaying.value = true

    const utterance = new SpeechSynthesisUtterance(currentWord.value.audio_text)
    utterance.lang = 'en-US'
    utterance.rate = 0.85
    utterance.onend = () => { isPlaying.value = false }
    utterance.onerror = () => { isPlaying.value = false }
    speechSynthesis.speak(utterance)
}

const startTimer = () => {
    stopTimer()
    timeRemaining.value = props.level.time_per_word || 30
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

const submitAnswer = async () => {
    if (isSubmitting.value) return
    stopTimer()
    isSubmitting.value = true
    lastUserAnswer.value = userAnswer.value

    const timeSpent = (props.level.time_per_word || 30) - timeRemaining.value

    try {
        const response = await axios.post(route('student.english.games.spelling-bee.check'), {
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
            gameState.value.lives = lastResult.value.lives

            if (lastResult.value.game_over) {
                gameOver.value = true
            } else {
                showResult.value = true
            }
        }
    } catch (error) {
        console.error('Failed to check answer:', error)
    } finally {
        isSubmitting.value = false
    }
}

const continueGame = async () => {
    if (!lastResult.value.has_more) {
        await completeGame()
        return
    }

    showResult.value = false
    userAnswer.value = ''
    hintDisplay.value = null
    showDefinition.value = false
    currentIndex.value = lastResult.value.progress.current
    currentWord.value = lastResult.value.next_word
    hintsRemaining.value = lastResult.value.hints_remaining

    startTimer()
    setTimeout(() => playAudio(), 300)
}

const completeGame = async () => {
    try {
        const response = await axios.post(route('student.english.games.spelling-bee.complete'), {
            session_id: sessionId.value
        })

        if (response.data.success) {
            finalResult.value = response.data.data
            showResult.value = false
            gameComplete.value = true
        }
    } catch (error) {
        console.error('Failed to complete:', error)
    }
}

const useHint = async () => {
    try {
        const response = await axios.post(route('student.english.games.spelling-bee.hint'), {
            session_id: sessionId.value
        })

        if (response.data.success) {
            hintDisplay.value = response.data.data.hint_display
            hintsRemaining.value = response.data.data.hints_remaining
        }
    } catch (error) {
        console.error('Failed to use hint:', error)
    }
}

const usePowerup = async (powerupId) => {
    try {
        const response = await axios.post(route('student.english.games.spelling-bee.powerup'), {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            const data = response.data.data
            powerupUsed.value[powerupId] = (powerupUsed.value[powerupId] || 0) + 1

            if (data.revealed_letter) {
                if (!hintDisplay.value) {
                    hintDisplay.value = '_'.repeat(currentWord.value.length)
                }
                const arr = hintDisplay.value.split('')
                arr[data.revealed_position] = data.revealed_letter
                hintDisplay.value = arr.join('')
            }

            if (data.extra_time) {
                timeRemaining.value += data.extra_time
            }

            if (data.definition) {
                showDefinition.value = true
            }

            if (data.skipped && data.has_more) {
                currentWord.value = data.next_word
                currentIndex.value++
                userAnswer.value = ''
                hintDisplay.value = null
                showDefinition.value = false
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

const restartGame = () => {
    gameStarted.value = false
    gameComplete.value = false
    gameOver.value = false
    showResult.value = false
    sessionId.value = null
    currentWord.value = null
    currentIndex.value = 0
    userAnswer.value = ''
    hintDisplay.value = null
    showDefinition.value = false
    gameState.value = { score: 0, streak: 0, correct: 0, wrong: 0, lives: 3 }
    finalResult.value = null
    powerupUsed.value = {}
}

function goBack() {
    stopTimer()
    router.visit('/student/english/games/spelling-bee')
}

onUnmounted(() => {
    stopTimer()
    speechSynthesis.cancel()
})
</script>

<template>
    <Head :title="`Spelling Bee - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-blue-200 dark:border-blue-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-blue-600 border-t-transparent animate-spin"></div>
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
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-blue-500 via-cyan-500 to-teal-600 rounded-3xl mb-6 shadow-2xl shadow-blue-500/30">
                            <span class="text-5xl filter drop-shadow-lg">📝</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-blue-600 dark:text-blue-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.words_count }} ta so'z</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_per_word }}s</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectedMode = mode.id"
                                :class="[
                                    'group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl overflow-hidden',
                                    selectedMode === mode.id
                                        ? 'border-2 border-blue-400 dark:border-blue-500 ring-4 ring-blue-100 dark:ring-blue-900/30'
                                        : 'border-2 border-gray-100 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500'
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

                                <!-- Selected indicator -->
                                <div v-if="selectedMode === mode.id" class="mt-5 flex items-center justify-center gap-2 text-blue-600 dark:text-blue-400 font-semibold">
                                    <span>Tanlangan</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-10 flex gap-4 justify-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                        <button @click="startGame" class="px-8 py-3.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Boshlash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Over Screen -->
            <div v-else-if="gameOver" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="mb-4">
                        <span class="text-7xl">😞</span>
                    </div>

                    <h2 class="text-3xl font-bold text-red-600 dark:text-red-400 mb-2">O'yin Tugadi!</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">Jonlar tugadi</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ gameState.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ gameState.correct }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">To'g'ri</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
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
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ finalResult.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ finalResult.accuracy }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Aniqlik</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ finalResult.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="finalResult?.new_achievements?.length" class="mb-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Yangi Yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div v-for="achievement in finalResult.new_achievements" :key="achievement.id"
                                 class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl px-4 py-2 flex items-center gap-2 border border-yellow-200 dark:border-yellow-800/50">
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-yellow-700 dark:text-yellow-400 font-medium">{{ achievement.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Darajalar
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Result Screen -->
            <div v-else-if="showResult && lastResult" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="text-7xl mb-6" :class="lastResult?.is_correct ? 'animate-bounce' : 'animate-shake'">
                        {{ lastResult?.is_correct ? '✅' : '❌' }}
                    </div>

                    <h2 class="text-3xl font-bold mb-4" :class="lastResult?.is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        {{ lastResult?.is_correct ? "To'g'ri!" : "Noto'g'ri!" }}
                    </h2>

                    <div class="mb-8">
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">To'g'ri javob:</p>
                        <p class="text-gray-900 dark:text-white text-3xl font-bold mb-3 tracking-widest">
                            {{ lastResult?.correct_spelling }}
                        </p>
                        <p v-if="!lastResult?.is_correct" class="text-red-500 dark:text-red-400 text-lg">
                            Siz yozdingiz: <span class="font-semibold">{{ lastUserAnswer || '(bo\'sh)' }}</span>
                        </p>
                    </div>

                    <div v-if="lastResult?.is_correct" class="grid grid-cols-3 gap-4 mb-8">
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-4 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">+{{ lastResult?.points_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-4 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-2xl font-bold text-violet-600 dark:text-violet-400">+{{ lastResult?.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ lastResult?.streak }}🔥</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Streak</div>
                        </div>
                    </div>

                    <button @click="continueGame" class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        {{ lastResult?.has_more ? 'Davom etish' : 'Natijalarni ko\'rish' }}
                    </button>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else class="max-w-5xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl shadow-lg">
                            <span class="text-2xl">📝</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/30 rounded-xl border border-red-100 dark:border-red-800/50">
                                <span class="text-red-500">❤️</span>
                                <span class="text-red-600 dark:text-red-400 font-bold">{{ gameState.lives }}</span>
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
                            timeRemaining < 10
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-blue-50 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/20 border-blue-100 dark:border-blue-800/50'
                        ]">
                            <div :class="timeRemaining < 10 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'" class="text-3xl font-bold">{{ timeRemaining }}s</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentIndex + 1 }}/{{ totalWords }} so'z</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: ((currentIndex + 1) / totalWords * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Word Audio Section -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 mb-6 shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                    <!-- Audio Button -->
                    <div class="mb-6">
                        <button @click="playAudio" :disabled="isPlaying"
                                class="w-32 h-32 rounded-full bg-gradient-to-r from-blue-500 to-cyan-600 flex items-center justify-center mx-auto hover:from-blue-600 hover:to-cyan-700 transition-all transform hover:scale-105 disabled:opacity-50 shadow-2xl shadow-blue-500/30">
                            <svg v-if="!isPlaying" class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                            </svg>
                            <div v-else class="flex items-center gap-1">
                                <div class="w-2 h-10 bg-white rounded animate-pulse"></div>
                                <div class="w-2 h-16 bg-white rounded animate-pulse delay-75"></div>
                                <div class="w-2 h-12 bg-white rounded animate-pulse delay-150"></div>
                                <div class="w-2 h-14 bg-white rounded animate-pulse"></div>
                            </div>
                        </button>
                        <p class="text-gray-600 dark:text-gray-400 mt-4 text-lg font-medium">Tinglash uchun bosing</p>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ currentWord?.length }} harfli so'z</p>
                    </div>

                    <!-- Hint Display -->
                    <div v-if="hintDisplay" class="mb-6 font-mono text-4xl tracking-widest text-blue-600 dark:text-blue-400 font-bold">
                        {{ hintDisplay }}
                    </div>

                    <!-- Definition Hint -->
                    <div v-if="showDefinition && currentWord?.definition" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <p class="text-blue-900 dark:text-blue-300 italic text-lg">"{{ currentWord.definition }}"</p>
                    </div>

                    <!-- Input -->
                    <div class="max-w-md mx-auto mb-6">
                        <input v-model="userAnswer" type="text" @keyup.enter="submitAnswer"
                               class="w-full px-6 py-5 bg-gray-50 dark:bg-gray-700/50 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-center text-3xl tracking-widest focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none placeholder-gray-400 dark:placeholder-gray-500 transition-all"
                               :class="{ 'blur-sm': currentWord?.hide_input && userAnswer }"
                               placeholder="So'zni yozing..."
                               :disabled="isSubmitting"
                               autofocus />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-center gap-3">
                        <button v-if="hintsRemaining > 0" @click="useHint"
                                class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all font-semibold shadow-md">
                            💡 Yordam ({{ hintsRemaining }})
                        </button>
                        <button @click="submitAnswer" :disabled="!userAnswer || isSubmitting"
                                class="px-10 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all disabled:opacity-50 shadow-lg hover:shadow-xl">
                            <span v-if="isSubmitting">Tekshirilmoqda...</span>
                            <span v-else>Yuborish</span>
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="powerups?.length" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
                    <h3 class="text-center text-gray-700 dark:text-gray-300 font-bold text-lg mb-4">Power-ups</h3>
                    <div class="flex justify-center gap-4 flex-wrap">
                        <button v-for="powerup in powerups" :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="powerupUsed[powerup.id] >= powerup.uses_per_game"
                                class="flex flex-col items-center gap-2 px-6 py-4 bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700 dark:to-gray-600 rounded-xl shadow-md hover:shadow-xl border border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:scale-105">
                            <span class="text-3xl">{{ powerup.icon }}</span>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">{{ powerup.name_uz }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ powerupUsed[powerup.id] || 0 }}/{{ powerup.uses_per_game }}</span>
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
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}
</style>
