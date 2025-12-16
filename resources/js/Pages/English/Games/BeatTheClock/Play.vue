<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
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

const selectedMode = ref('classic')
const gameStarted = ref(false)
const gameCompleted = ref(false)
const sessionId = ref(null)
const questions = ref([])
const currentQuestionIndex = ref(0)
const answered = ref(false)
const selectedAnswer = ref(null)
const lastAnswerCorrect = ref(false)
const lastPointsEarned = ref(0)
const correctAnswer = ref('')
const questionStartTime = ref(null)
const questionTimeLeft = ref(0)
const timerInterval = ref(null)
const gameResults = ref({})
const powerupsUsed = ref({})
const showHint = ref(false)
const hintMessage = ref('')
const eliminatedOptions = ref([])
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')

const gameState = ref({
    score: 0,
    streak: 0,
    lives: null,
    correctCount: 0,
    incorrectCount: 0
})

const currentQuestion = computed(() => {
    if (!questions.value.length) return null
    return questions.value[currentQuestionIndex.value]
})

const timerProgress = computed(() => {
    if (!props.level.time_per_question) return 100
    return (questionTimeLeft.value / props.level.time_per_question) * 100
})

const availablePowerups = computed(() => {
    return props.powerups.filter(p => {
        const used = powerupsUsed.value[p.id] || 0
        return used < (p.uses_per_game || 1)
    })
})

const progress = computed(() => {
    return questions.value.length > 0 ? Math.round((currentQuestionIndex.value / questions.value.length) * 100) : 0
})

function getModeIcon(mode) {
    const icons = {
        'classic': '⏱️',
        'survival': '❤️',
        'speed': '⚡',
        'blitz': '🔥'
    }
    return icons[mode] || '🎮'
}

function getModeBgGradient(mode) {
    const gradients = {
        'classic': 'from-orange-500 to-red-600',
        'survival': 'from-red-500 to-rose-600',
        'speed': 'from-amber-500 to-orange-600',
        'blitz': 'from-yellow-500 to-red-600'
    }
    return gradients[mode] || 'from-orange-500 to-red-600'
}

const getQuestionTypeName = (type) => {
    const names = {
        multiple_choice: "Ko'p tanlovli",
        true_false: "To'g'ri/Noto'g'ri",
        fill_blank: "Bo'sh joyni to'ldiring",
        match: 'Moslash'
    }
    return names[type] || type
}

const formatTime = (seconds) => {
    return seconds.toString()
}

async function selectMode(mode) {
    selectedMode.value = mode
    await startGame()
}

const startGame = async () => {
    isLoading.value = true
    hasError.value = false

    try {
        const response = await axios.post(`/student/english/games/beat-the-clock/start/${props.level.level_number}`, {
            mode: selectedMode.value
        })

        if (response.data.success) {
            sessionId.value = response.data.data.session_id
            questions.value = response.data.data.questions
            gameState.value.lives = response.data.data.lives
            gameStarted.value = true
            currentQuestionIndex.value = 0
            startQuestionTimer()
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

const startQuestionTimer = () => {
    questionStartTime.value = Date.now()
    questionTimeLeft.value = props.level.time_per_question || 30

    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }

    timerInterval.value = setInterval(() => {
        questionTimeLeft.value--

        if (questionTimeLeft.value <= 0) {
            if (!answered.value) {
                submitAnswer(null)
            }
        }
    }, 1000)
}

const getOptionClass = (option) => {
    if (eliminatedOptions.value.includes(option)) {
        return 'bg-gray-200 dark:bg-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed line-through border border-gray-300 dark:border-gray-600'
    }

    if (!answered.value) {
        return 'bg-white dark:bg-gray-700 hover:bg-orange-50 dark:hover:bg-orange-900/20 text-gray-900 dark:text-white cursor-pointer border-2 border-gray-200 dark:border-gray-600 hover:border-orange-400 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all'
    }

    if (option === correctAnswer.value) {
        return 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-2 border-green-400'
    }

    if (option === selectedAnswer.value && option !== correctAnswer.value) {
        return 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 border-2 border-red-400 animate-shake'
    }

    return 'bg-gray-50 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500 border-2 border-gray-200 dark:border-gray-600'
}

const submitAnswer = async (answer) => {
    if (answered.value) return

    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }

    answered.value = true
    selectedAnswer.value = answer

    const timeSpent = (Date.now() - questionStartTime.value) / 1000

    try {
        const response = await axios.post('/student/english/games/beat-the-clock/answer', {
            session_id: sessionId.value,
            question_index: currentQuestionIndex.value,
            answer: answer || '',
            time_spent: timeSpent
        })

        if (response.data.success) {
            const result = response.data.data
            lastAnswerCorrect.value = result.correct
            lastPointsEarned.value = result.points_earned
            correctAnswer.value = result.correct_answer
            gameState.value.score = result.total_score
            gameState.value.streak = result.streak
            gameState.value.lives = result.lives
            gameState.value.correctCount = result.progress.correct
            gameState.value.incorrectCount = result.progress.incorrect

            if (result.hint) {
                hintMessage.value = result.hint
            }

            setTimeout(() => {
                if (result.game_over) {
                    completeGame()
                } else {
                    nextQuestion()
                }
            }, 1500)
        }
    } catch (error) {
        console.error('Error submitting answer:', error)
    }
}

const nextQuestion = () => {
    if (currentQuestionIndex.value < questions.value.length - 1) {
        currentQuestionIndex.value++
        answered.value = false
        selectedAnswer.value = null
        showHint.value = false
        hintMessage.value = ''
        eliminatedOptions.value = []
        startQuestionTimer()
    } else {
        completeGame()
    }
}

const completeGame = async () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }

    try {
        const response = await axios.post('/student/english/games/beat-the-clock/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            gameResults.value = response.data.data
            gameCompleted.value = true
            gameStarted.value = false
        }
    } catch (error) {
        console.error('Error completing game:', error)
    }
}

const canUsePowerup = (powerupId) => {
    const used = powerupsUsed.value[powerupId] || 0
    const powerup = props.powerups.find(p => p.id === powerupId)
    return used < (powerup?.uses_per_game || 1) && !answered.value
}

const getRemainingUses = (powerupId) => {
    const used = powerupsUsed.value[powerupId] || 0
    const powerup = props.powerups.find(p => p.id === powerupId)
    return (powerup?.uses_per_game || 1) - used
}

const usePowerup = async (powerupId) => {
    if (!canUsePowerup(powerupId)) return

    try {
        const response = await axios.post('/student/english/games/beat-the-clock/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1

            const effect = response.data.data.effect

            if (effect.type === 'hint') {
                showHint.value = true
                hintMessage.value = effect.message
            } else if (effect.type === 'fifty_fifty') {
                eliminatedOptions.value = effect.eliminated_options
            } else if (effect.type === 'skip') {
                nextQuestion()
            } else if (effect.type === 'time_boost') {
                questionTimeLeft.value += effect.time_added
            } else if (effect.type === 'freeze') {
                if (timerInterval.value) {
                    clearInterval(timerInterval.value)
                }
                setTimeout(() => {
                    if (gameStarted.value && !answered.value) {
                        timerInterval.value = setInterval(() => {
                            questionTimeLeft.value--
                            if (questionTimeLeft.value <= 0 && !answered.value) {
                                submitAnswer(null)
                            }
                        }, 1000)
                    }
                }, effect.duration * 1000)
            }
        }
    } catch (error) {
        console.error('Error using powerup:', error)
    }
}

const restartGame = () => {
    gameCompleted.value = false
    gameStarted.value = false
    sessionId.value = null
    questions.value = []
    currentQuestionIndex.value = 0
    gameState.value = {
        score: 0,
        streak: 0,
        lives: null,
        correctCount: 0,
        incorrectCount: 0
    }
    powerupsUsed.value = {}
    eliminatedOptions.value = []
    showHint.value = false
}

function goBack() {
    clearInterval(timerInterval.value)
    router.visit('/student/english/games/beat-the-clock')
}

function nextLevel() {
    clearInterval(timerInterval.value)
    if (gameResults.value.level_unlocked) {
        router.visit(`/student/english/games/beat-the-clock/play/${gameResults.value.level_unlocked}`)
    } else {
        router.visit('/student/english/games/beat-the-clock')
    }
}

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<template>
    <Head :title="`Beat The Clock - ${level?.name || 'O\'yin'}`" />

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

            <!-- Error Screen -->
            <div v-else-if="hasError" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-10 max-w-md shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-5xl">😞</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-3">Xatolik yuz berdi</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">{{ errorMessage }}</p>
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Mode Selection Screen -->
            <div v-else-if="!gameStarted && !gameCompleted" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-500 via-red-500 to-rose-600 rounded-3xl mb-6 shadow-2xl shadow-orange-500/30">
                            <span class="text-5xl filter drop-shadow-lg">⏱️</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.description }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-orange-600 dark:text-orange-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.questions_count }} savol</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_per_question || '∞' }}s/savol</span>
                        </div>
                    </div>

                    <!-- Stats Preview -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ level?.questions_count }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Savollar</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ level?.time_per_question || '∞' }}s</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Savol vaqti</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">{{ level?.xp_reward }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP mukofot</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ level?.coin_reward }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tangalar</div>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div v-if="gameModes && gameModes.length > 0">
                        <h3 class="text-gray-900 dark:text-white font-bold text-center mb-4">O'yin rejimini tanlang</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                            <button v-for="mode in gameModes" :key="mode.id"
                                    @click="selectMode(mode.id)"
                                    class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-orange-400 dark:hover:border-orange-500 overflow-hidden">

                                <div class="relative z-10 p-6">
                                    <!-- Icon -->
                                    <div :class="['w-16 h-16 rounded-2xl flex items-center justify-center text-4xl mb-4 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300', getModeBgGradient(mode.id)]">
                                        {{ mode.icon }}
                                    </div>

                                    <!-- Title -->
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2 transition-colors">{{ mode.name }}</h3>

                                    <!-- Play indicator -->
                                    <div class="mt-4 flex items-center justify-center gap-2 text-orange-600 dark:text-orange-400 font-semibold opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                                        <span>O'ynash</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center mb-8">
                        <button
                            @click="startGame"
                            class="px-8 py-4 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold rounded-xl text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105"
                        >
                            Boshlash
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-10 text-center">
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
            <div v-else-if="gameCompleted && gameResults" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ gameResults.stars === 3 ? '🏆' : gameResults.stars === 2 ? '🎉' : gameResults.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= gameResults.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ gameResults.stars === 3 ? 'Mukammal!' : gameResults.stars === 2 ? 'Ajoyib!' : gameResults.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ gameResults.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameResults.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ gameResults.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ gameResults.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-5 border border-red-100 dark:border-red-800/50">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ gameResults.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Detailed Stats -->
                    <div class="grid grid-cols-4 gap-3 mb-8">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 border border-green-100 dark:border-green-800/50">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ gameResults.correct_count }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">To'g'ri</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 border border-red-100 dark:border-red-800/50">
                            <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ gameResults.incorrect_count }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Noto'g'ri</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-3 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ gameResults.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Best streak</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ gameResults.fast_answers }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Tez javob</div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="gameResults.new_achievements?.length" class="mb-8">
                        <h3 class="text-gray-900 dark:text-white font-bold mb-3">Yangi yutuqlar!</h3>
                        <div class="flex justify-center gap-4 flex-wrap">
                            <div
                                v-for="achievement in gameResults.new_achievements"
                                :key="achievement.id"
                                class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 text-center border border-yellow-200 dark:border-yellow-800/50"
                            >
                                <div class="text-3xl mb-1">{{ achievement.icon }}</div>
                                <div class="text-gray-900 dark:text-white font-medium text-sm">{{ achievement.name }}</div>
                            </div>
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
                        <button v-if="gameResults.stars > 0 && gameResults.level_unlocked" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameStarted" class="max-w-4xl mx-auto">
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
                            <span class="text-2xl">⏱️</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div v-if="gameState.lives !== null" class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/30 rounded-xl border border-red-100 dark:border-red-800/50">
                            <span class="text-red-500 font-bold text-xl">{{ '❤️'.repeat(gameState.lives) }}</span>
                        </div>
                        <div v-else class="w-20"></div>
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
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ currentQuestionIndex + 1 }}/{{ questions.length }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savol</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            questionTimeLeft < 5
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="questionTimeLeft < 5 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ questionTimeLeft }}s</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentQuestionIndex }}/{{ questions.length }} savollar</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-orange-500 via-red-500 to-rose-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Question Card -->
                <div v-if="currentQuestion" class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-4 shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Question Type Badge -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full text-sm capitalize border border-orange-200 dark:border-orange-800/50 font-semibold">
                            {{ currentQuestion.category }}
                        </span>
                        <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-sm border border-yellow-200 dark:border-yellow-800/50 font-semibold">
                            {{ getQuestionTypeName(currentQuestion.type) }}
                        </span>
                    </div>

                    <!-- Question Content -->
                    <div class="text-center mb-6">
                        <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ currentQuestion.question || currentQuestion.statement || currentQuestion.sentence }}
                        </p>
                        <p v-if="currentQuestion.question_uz || currentQuestion.statement_uz || currentQuestion.sentence_uz"
                           class="text-gray-600 dark:text-gray-400">
                            {{ currentQuestion.question_uz || currentQuestion.statement_uz || currentQuestion.sentence_uz }}
                        </p>
                    </div>

                    <!-- Hint -->
                    <div v-if="showHint && hintMessage" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-3 mb-4 text-center border border-yellow-200 dark:border-yellow-800/50">
                        <span class="text-yellow-700 dark:text-yellow-400 text-sm font-medium">💡 {{ hintMessage }}</span>
                    </div>

                    <!-- Answer Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button
                            v-for="(option, index) in currentQuestion.options"
                            :key="index"
                            :class="getOptionClass(option)"
                            :disabled="answered || eliminatedOptions.includes(option)"
                            @click="submitAnswer(option)"
                            class="p-4 rounded-xl text-lg font-medium"
                        >
                            {{ option }}
                        </button>
                    </div>

                    <!-- Feedback -->
                    <div v-if="answered" class="mt-6 text-center">
                        <div v-if="lastAnswerCorrect" class="text-green-600 dark:text-green-400 text-xl font-bold mb-2">
                            To'g'ri! +{{ lastPointsEarned }} ball
                        </div>
                        <div v-else class="text-red-600 dark:text-red-400 text-xl font-bold mb-2">
                            Noto'g'ri!
                        </div>
                        <div v-if="!lastAnswerCorrect" class="text-gray-900 dark:text-white">
                            To'g'ri javob: <span class="text-orange-600 dark:text-orange-400 font-bold">{{ correctAnswer }}</span>
                        </div>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="availablePowerups.length > 0" class="flex justify-center gap-4 flex-wrap">
                    <button
                        v-for="powerup in availablePowerups"
                        :key="powerup.id"
                        class="px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                        :disabled="!canUsePowerup(powerup.id)"
                        @click="usePowerup(powerup.id)"
                    >
                        {{ powerup.icon }} {{ powerup.name }}
                        <span class="text-xs text-gray-600 dark:text-gray-400">({{ getRemainingUses(powerup.id) }})</span>
                    </button>
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
