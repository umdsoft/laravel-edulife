<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: { type: Object, required: true },
    config: { type: Object, default: () => ({}) },
    powerups: { type: Array, default: () => [] },
    challengeTypes: { type: Array, default: () => [] }
})

const gameState = ref('idle')
const sessionId = ref(null)
const questions = ref([])
const currentIndex = ref(0)
const score = ref(0)
const streak = ref(0)
const timeLimit = ref(null)
const timeRemaining = ref(null)
const timerInterval = ref(null)

const selectedAnswer = ref(null)
const answered = ref(false)
const isSubmitting = ref(false)
const lastResult = ref(null)
const summary = ref(null)

const powerupsUsed = ref({})
const removedOptions = ref([])
const doublePointsActive = ref(false)
const questionStartTime = ref(null)

const currentQuestion = computed(() => {
    if (currentIndex.value < questions.value.length) {
        return questions.value[currentIndex.value]
    }
    return null
})

const displayOptions = computed(() => {
    return currentQuestion.value?.options || []
})

const progress = computed(() => {
    return questions.value.length > 0 ? Math.round((currentIndex.value / questions.value.length) * 100) : 0
})

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0
})

const startGame = async () => {
    try {
        const response = await axios.post(`/student/english/games/daily-challenge/start/${props.level.level_number}`)

        if (response.data.success) {
            const data = response.data.data
            sessionId.value = data.session_id
            questions.value = data.questions
            timeLimit.value = data.level.time_limit
            timeRemaining.value = data.level.time_limit

            gameState.value = 'playing'
            questionStartTime.value = Date.now()

            if (timeLimit.value) {
                startTimer()
            }
        }
    } catch (error) {
        console.error('Failed to start game:', error)
        alert('O\'yinni boshlashda xatolik yuz berdi')
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

const selectAnswer = (answer) => {
    if (!answered.value && !removedOptions.value.includes(answer)) {
        selectedAnswer.value = answer
    }
}

const submitAnswer = async () => {
    if (!selectedAnswer.value || isSubmitting.value) return

    isSubmitting.value = true
    const timeSpent = (Date.now() - questionStartTime.value) / 1000

    try {
        const response = await axios.post('/student/english/games/daily-challenge/answer', {
            session_id: sessionId.value,
            question_index: currentIndex.value,
            answer: selectedAnswer.value,
            time_spent: timeSpent
        })

        if (response.data.success) {
            const data = response.data.data
            lastResult.value = data
            answered.value = true
            score.value = data.score
            streak.value = data.streak
            doublePointsActive.value = false

            if (data.is_complete && data.summary) {
                summary.value = data.summary
            }
        }
    } catch (error) {
        console.error('Failed to submit answer:', error)
    } finally {
        isSubmitting.value = false
    }
}

const nextQuestion = () => {
    if (lastResult.value?.is_complete) {
        endGame()
        return
    }

    currentIndex.value++
    selectedAnswer.value = null
    answered.value = false
    lastResult.value = null
    removedOptions.value = []
    questionStartTime.value = Date.now()
}

const endGame = async () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }

    if (!summary.value) {
        try {
            const response = await axios.post('/student/english/games/daily-challenge/complete', {
                session_id: sessionId.value
            })

            if (response.data.success) {
                summary.value = response.data.data
            }
        } catch (error) {
            console.error('Failed to complete session:', error)
        }
    }

    gameState.value = 'complete'
}

const restartGame = () => {
    gameState.value = 'idle'
    sessionId.value = null
    questions.value = []
    currentIndex.value = 0
    score.value = 0
    streak.value = 0
    timeRemaining.value = null
    selectedAnswer.value = null
    answered.value = false
    lastResult.value = null
    summary.value = null
    powerupsUsed.value = {}
    removedOptions.value = []
    doublePointsActive.value = false
}

const usePowerup = async (powerupId) => {
    if (getPowerupUsesRemaining(powerupId) <= 0 || answered.value) return

    try {
        const response = await axios.post('/student/english/games/daily-challenge/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        })

        if (response.data.success) {
            const data = response.data.data
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1

            switch (powerupId) {
                case 'extra_time':
                    if (data.time_remaining !== undefined) {
                        timeRemaining.value = data.time_remaining
                    }
                    break
                case 'fifty_fifty':
                    if (data.removed_options) {
                        removedOptions.value = data.removed_options
                    }
                    break
                case 'skip':
                    if (data.is_complete) {
                        summary.value = data.summary
                        endGame()
                    } else {
                        currentIndex.value = data.current_index
                        selectedAnswer.value = null
                        answered.value = false
                        removedOptions.value = []
                        questionStartTime.value = Date.now()
                    }
                    break
                case 'hint':
                    alert(data.hint)
                    break
                case 'double_points':
                    doublePointsActive.value = true
                    break
                case 'freeze_timer':
                    // Timer freeze handled server-side
                    break
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

const getPowerupUsesRemaining = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId)
    if (!powerup) return 0
    const used = powerupsUsed.value[powerupId] || 0
    return (powerup.uses_per_game || 1) - used
}

const formatTime = (seconds) => {
    if (seconds === null) return '--:--'
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

function goBack() {
    clearInterval(timerInterval.value)
    router.visit('/student/english/games/daily-challenge')
}

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})
</script>

<template>
    <Head :title="`Kunlik Sinov - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Pre-game Screen -->
            <div v-if="gameState === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-3xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 rounded-3xl mb-6 shadow-2xl shadow-blue-500/30">
                            <span class="text-5xl filter drop-shadow-lg">📅</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level?.name_uz }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-blue-600 dark:text-blue-400 font-bold capitalize">{{ level?.difficulty }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.questions_count }} savol</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_limit ? formatTime(level.time_limit) : '∞' }}</span>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 mb-8 shadow-xl border border-gray-100 dark:border-gray-700">
                        <p class="text-gray-600 dark:text-gray-400 text-center leading-relaxed">{{ level?.description }}</p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4 mt-8">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 border border-blue-100 dark:border-blue-800/50">
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ level?.questions_count }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Savol</div>
                            </div>
                            <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                                <div class="text-3xl font-bold text-violet-600 dark:text-violet-400 capitalize">{{ level?.difficulty }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Qiyinlik</div>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ level?.time_limit ? formatTime(level.time_limit) : '∞' }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Vaqt</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-4">
                        <button @click="startGame" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold text-lg hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            O'yinni boshlash
                        </button>
                        <button @click="goBack" class="w-full py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
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
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ summary.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 border border-blue-100 dark:border-blue-800/50">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ summary.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ summary.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ summary.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ summary.streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Accuracy -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50 mb-8">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ summary.correct_answers }}/{{ summary.total_answers }}</div>
                        <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">To'g'ri javoblar</div>
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
            <div v-else-if="gameState === 'playing'" class="max-w-4xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-lg">
                            <span class="text-2xl">📅</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold capitalize">{{ level?.difficulty }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-100 dark:border-blue-800/50">
                                <span>🎯</span>
                                <span class="text-blue-600 dark:text-blue-400 font-bold">{{ score }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ currentIndex + 1 }}/{{ questions.length }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savol</div>
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
                    <div v-if="doublePointsActive" class="mt-5">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800/50 rounded-xl px-4 py-2 text-center">
                            <span class="text-yellow-700 dark:text-yellow-400 font-bold text-sm">⚡ 2x Ball faol!</span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentIndex }}/{{ questions.length }} savol</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Question Card -->
                <div v-if="currentQuestion" class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Category & Difficulty Badge -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-3 py-1 rounded-full text-sm capitalize border border-blue-200 dark:border-blue-800/50 font-semibold">
                            {{ currentQuestion.category }}
                        </span>
                        <span class="text-gray-600 dark:text-gray-400 text-sm capitalize font-semibold">{{ currentQuestion.difficulty }}</span>
                    </div>

                    <!-- Reading Passage -->
                    <div v-if="currentQuestion.passage" class="bg-slate-50 dark:bg-gray-700/50 rounded-xl p-4 mb-4 max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600">
                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ currentQuestion.passage }}</p>
                    </div>

                    <!-- Audio Text (for listening questions) -->
                    <div v-if="currentQuestion.audio_text" class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-4 border border-blue-200 dark:border-blue-800/50">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">🎧</span>
                            <span class="text-blue-700 dark:text-blue-400 font-medium">Tinglang:</span>
                        </div>
                        <p class="text-gray-900 dark:text-white">{{ currentQuestion.audio_text }}</p>
                    </div>

                    <!-- Question -->
                    <h3 class="text-xl text-gray-900 dark:text-white font-medium mb-6">{{ currentQuestion.question }}</h3>

                    <!-- Answer Options -->
                    <div v-if="!answered" class="space-y-3">
                        <button
                            v-for="(option, optionIndex) in displayOptions"
                            :key="optionIndex"
                            @click="selectAnswer(option)"
                            class="w-full p-4 rounded-xl text-gray-900 dark:text-white text-left transition-all hover:scale-[1.02] border-2 font-medium shadow-lg"
                            :class="{
                                'bg-blue-100 dark:bg-blue-900/30 border-blue-400 dark:border-blue-600 ring-4 ring-blue-200 dark:ring-blue-800/50': selectedAnswer === option,
                                'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-400': selectedAnswer !== option && !removedOptions.includes(option),
                                'opacity-50 cursor-not-allowed bg-gray-50 dark:bg-gray-700/30 border-gray-200 dark:border-gray-600': removedOptions.includes(option)
                            }"
                            :disabled="removedOptions.includes(option)"
                        >
                            <span class="font-bold mr-2 text-gray-600 dark:text-gray-400">{{ String.fromCharCode(65 + optionIndex) }}.</span>
                            {{ option }}
                        </button>

                        <button
                            v-if="selectedAnswer"
                            @click="submitAnswer"
                            :disabled="isSubmitting"
                            class="w-full mt-4 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:opacity-90 transition-all disabled:opacity-50 shadow-lg hover:shadow-xl hover:scale-105"
                        >
                            {{ isSubmitting ? 'Tekshirilmoqda...' : 'Javobni tekshirish' }}
                        </button>
                    </div>

                    <!-- Result -->
                    <div v-else class="text-center space-y-4">
                        <div
                            v-if="lastResult?.correct"
                            class="bg-green-50 dark:bg-green-900/20 border-2 border-green-300 dark:border-green-800/50 rounded-xl p-6 shadow-lg"
                        >
                            <div class="text-5xl mb-3">🎉</div>
                            <div class="text-green-700 dark:text-green-400 font-bold text-2xl mb-2">To'g'ri!</div>
                            <div class="text-green-600 dark:text-green-500 text-lg font-semibold">+{{ lastResult.points_earned }} ball</div>
                        </div>
                        <div v-else class="bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-800/50 rounded-xl p-6 shadow-lg">
                            <div class="text-5xl mb-3">😔</div>
                            <div class="text-red-700 dark:text-red-400 font-bold text-2xl mb-2">Noto'g'ri</div>
                            <div class="text-gray-900 dark:text-white mt-2">To'g'ri javob: <span class="text-green-700 dark:text-green-400 font-bold">{{ lastResult.correct_answer }}</span></div>
                        </div>

                        <!-- Explanation -->
                        <div v-if="lastResult?.explanation" class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-left border border-blue-200 dark:border-blue-800/50">
                            <div class="text-blue-700 dark:text-blue-400 font-bold mb-2 flex items-center gap-2">
                                <span>💡</span>
                                <span>Izoh:</span>
                            </div>
                            <p class="text-gray-900 dark:text-white text-sm leading-relaxed">{{ lastResult.explanation }}</p>
                        </div>

                        <button
                            @click="nextQuestion"
                            class="px-8 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105"
                        >
                            {{ currentIndex < questions.length - 1 ? 'Keyingi savol' : 'Yakunlash' }}
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="powerups.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="text-center mb-3">
                        <span class="text-gray-700 dark:text-gray-300 font-bold text-sm">⚡ Qo'shimcha imkoniyatlar</span>
                    </div>
                    <div class="flex flex-wrap justify-center gap-3">
                        <button
                            v-for="powerup in powerups"
                            :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="getPowerupUsesRemaining(powerup.id) <= 0 || answered"
                            class="px-4 py-2 bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 hover:from-violet-100 hover:to-purple-100 dark:hover:from-violet-900/30 dark:hover:to-purple-900/30 rounded-xl text-gray-900 dark:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 border border-violet-200 dark:border-violet-800/50 shadow-md hover:shadow-lg hover:scale-105"
                        >
                            <span>{{ powerup.icon }}</span>
                            <span class="text-sm font-semibold">{{ powerup.name }}</span>
                            <span class="text-xs bg-white dark:bg-gray-700 px-2 py-0.5 rounded-full text-gray-600 dark:text-gray-400 font-bold">{{ getPowerupUsesRemaining(powerup.id) }}</span>
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
