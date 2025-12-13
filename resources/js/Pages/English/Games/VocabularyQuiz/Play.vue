<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object,
    powerups: Array,
})

// Game State
const gameStarted = ref(false)
const gameCompleted = ref(false)
const isLoading = ref(false)
const error = ref(null)

// Session Data
const sessionId = ref(null)
const currentQuestion = ref(null)
const currentIndex = ref(0)
const totalQuestions = ref(0)
const score = ref(0)
const streak = ref(0)
const streakMultiplier = ref(1.0)
const powerupsState = ref({})

// Timer
const timeLimit = ref(20)
const timeRemaining = ref(20)
const timerInterval = ref(null)
const timerStartTime = ref(null)

// Answer State
const selectedAnswer = ref(null)
const answerResult = ref(null)
const isAnswering = ref(false)
const removedOptions = ref([])
const showHint = ref(false)
const hintText = ref('')

// Results
const sessionResult = ref(null)

// Computed
const progressPercentage = computed(() => {
    return totalQuestions.value > 0 ? Math.round((currentIndex.value / totalQuestions.value) * 100) : 0
})

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0
})

const timerColor = computed(() => {
    if (timerPercentage.value > 50) return 'bg-green-500'
    if (timerPercentage.value > 25) return 'bg-yellow-500'
    return 'bg-red-500'
})

const filteredOptions = computed(() => {
    if (!currentQuestion.value?.options) return []
    return currentQuestion.value.options.filter(opt => !removedOptions.value.includes(opt))
})

// Methods
async function startGame() {
    isLoading.value = true
    error.value = null

    try {
        const response = await axios.post(`/student/english/games/vocabulary-quiz/start/${props.level.number}`)

        if (response.data.success) {
            sessionId.value = response.data.session_id
            currentQuestion.value = response.data.current_question
            currentIndex.value = response.data.current_index
            totalQuestions.value = response.data.total_questions
            timeLimit.value = response.data.time_limit
            powerupsState.value = response.data.powerups
            gameStarted.value = true
            startTimer()
        } else {
            error.value = response.data.error || 'O\'yinni boshlashda xatolik'
        }
    } catch (e) {
        console.error('Start game error:', e)
        error.value = 'Server bilan bog\'lanishda xatolik'
    } finally {
        isLoading.value = false
    }
}

function startTimer() {
    timeRemaining.value = timeLimit.value
    timerStartTime.value = Date.now()

    timerInterval.value = setInterval(() => {
        const elapsed = Math.floor((Date.now() - timerStartTime.value) / 1000)
        timeRemaining.value = Math.max(0, timeLimit.value - elapsed)

        if (timeRemaining.value <= 0) {
            clearInterval(timerInterval.value)
            handleTimeUp()
        }
    }, 100)
}

function stopTimer() {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
        timerInterval.value = null
    }
}

function handleTimeUp() {
    if (!isAnswering.value && !answerResult.value) {
        submitAnswer('')
    }
}

async function selectAnswer(answer) {
    if (isAnswering.value || answerResult.value) return

    selectedAnswer.value = answer
    isAnswering.value = true
    stopTimer()

    const timeSpent = timeLimit.value - timeRemaining.value

    try {
        const response = await axios.post('/student/english/games/vocabulary-quiz/check', {
            session_id: sessionId.value,
            answer: answer,
            time_spent: Math.round(timeSpent),
        })

        if (response.data.success) {
            answerResult.value = response.data
            score.value = response.data.current_score
            streak.value = response.data.streak
            streakMultiplier.value = response.data.streak_multiplier
            powerupsState.value = response.data.powerups

            // Wait before showing next question
            setTimeout(() => {
                if (response.data.is_last_question) {
                    completeSession()
                } else {
                    moveToNextQuestion(response.data)
                }
            }, 1500)
        }
    } catch (e) {
        console.error('Check answer error:', e)
        error.value = 'Javobni tekshirishda xatolik'
        isAnswering.value = false
    }
}

async function submitAnswer(answer) {
    await selectAnswer(answer)
}

function moveToNextQuestion(response) {
    currentQuestion.value = response.next_question
    currentIndex.value = response.current_index
    selectedAnswer.value = null
    answerResult.value = null
    isAnswering.value = false
    removedOptions.value = []
    showHint.value = false
    hintText.value = ''
    startTimer()
}

async function completeSession() {
    stopTimer()
    isLoading.value = true

    try {
        const response = await axios.post('/student/english/games/vocabulary-quiz/complete', {
            session_id: sessionId.value,
        })

        if (response.data.success) {
            sessionResult.value = response.data.result
            gameCompleted.value = true
        }
    } catch (e) {
        console.error('Complete session error:', e)
        error.value = 'Sessiyani yakunlashda xatolik'
    } finally {
        isLoading.value = false
    }
}

async function usePowerup(powerupId) {
    if (!powerupsState.value[powerupId]) return
    if (powerupsState.value[powerupId].remaining <= 0) return
    if (powerupsState.value[powerupId].cooldown > 0) return
    if (isAnswering.value || answerResult.value) return

    try {
        const response = await axios.post('/student/english/games/vocabulary-quiz/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId,
        })

        if (response.data.success) {
            powerupsState.value = response.data.powerups

            switch (powerupId) {
                case 'fifty_fifty':
                    removedOptions.value = response.data.removed_options || []
                    break

                case 'skip':
                    if (response.data.is_last_question) {
                        completeSession()
                    } else {
                        moveToNextQuestion(response.data)
                    }
                    break

                case 'extra_time':
                    timeRemaining.value += response.data.bonus_seconds || 10
                    timeLimit.value += response.data.bonus_seconds || 10
                    break

                case 'hint':
                    showHint.value = true
                    hintText.value = response.data.hint || ''
                    break

                case 'double_points':
                    // Visual feedback handled in UI
                    break
            }
        } else {
            console.warn('Powerup error:', response.data.error)
        }
    } catch (e) {
        console.error('Use powerup error:', e)
    }
}

function getPowerupIcon(id) {
    const icons = {
        'fifty_fifty': '✂️',
        'skip': '⏩',
        'extra_time': '⏰',
        'hint': '💡',
        'double_points': '⚡',
    }
    return icons[id] || '🔮'
}

function getQuestionTypeLabel(type) {
    const labels = {
        'word_to_definition': "So'zning ta'rifini toping",
        'definition_to_word': "Ta'rifga mos so'zni toping",
        'word_to_translation': "So'zning tarjimasini toping",
        'translation_to_word': "Tarjimaga mos inglizcha so'zni toping",
        'synonym_match': "Sinonimini toping",
        'antonym_match': "Antonimini toping",
        'context_fill': "Bo'sh joyni to'ldiring",
    }
    return labels[type] || currentQuestion.value?.prompt_label || 'Savolga javob bering'
}

function restartGame() {
    gameStarted.value = false
    gameCompleted.value = false
    sessionId.value = null
    currentQuestion.value = null
    currentIndex.value = 0
    score.value = 0
    streak.value = 0
    streakMultiplier.value = 1.0
    selectedAnswer.value = null
    answerResult.value = null
    sessionResult.value = null
    removedOptions.value = []
    showHint.value = false
    startGame()
}

function goBack() {
    router.visit('/student/english/games/vocabulary-quiz')
}

// Lifecycle
onMounted(() => {
    startGame()
})

onUnmounted(() => {
    stopTimer()
})
</script>

<template>
    <Head :title="`So'z Viktorinasi - ${level?.name_uz || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-900 -m-6 font-sans">
            <!-- Loading Screen -->
            <div v-if="isLoading && !gameStarted" class="flex items-center justify-center min-h-screen">
                <div class="text-center">
                    <div class="w-16 h-16 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-white text-lg">O'yin yuklanmoqda...</p>
                </div>
            </div>

            <!-- Error Screen -->
            <div v-else-if="error && !gameStarted" class="flex items-center justify-center min-h-screen p-6">
                <div class="bg-red-500/20 backdrop-blur-sm rounded-2xl p-8 max-w-md text-center">
                    <span class="text-5xl mb-4 block">❌</span>
                    <h2 class="text-xl font-bold text-white mb-2">Xatolik yuz berdi</h2>
                    <p class="text-white/70 mb-6">{{ error }}</p>
                    <button @click="startGame" class="px-6 py-3 bg-white text-gray-900 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                        Qayta urinish
                    </button>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameCompleted && sessionResult" class="flex items-center justify-center min-h-screen p-6">
                <div class="bg-gray-800 rounded-3xl p-8 max-w-lg w-full text-center relative overflow-hidden">
                    <!-- Background decoration -->
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-gradient-to-br from-indigo-500/20 to-purple-500/20 rounded-full blur-3xl"></div>
                    </div>

                    <div class="relative z-10">
                        <!-- Stars -->
                        <div class="flex justify-center gap-4 mb-6">
                            <span v-for="i in 3" :key="i"
                                  :class="[
                                      'text-5xl transition-all duration-500',
                                      i <= sessionResult.stars ? 'animate-bounce' : 'opacity-30'
                                  ]"
                                  :style="{ animationDelay: `${i * 0.2}s` }">
                                ⭐
                            </span>
                        </div>

                        <h2 class="text-3xl font-bold text-white mb-2">
                            {{ sessionResult.stars >= 3 ? 'Ajoyib!' : sessionResult.stars >= 2 ? 'Yaxshi!' : sessionResult.stars >= 1 ? 'Yomon emas!' : 'Qayta urinib ko\'ring!' }}
                        </h2>

                        <p class="text-white/60 mb-8">{{ level?.name_uz }} yakunlandi</p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.total_score }}</div>
                                <div class="text-gray-400 text-sm">Umumiy ball</div>
                            </div>
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.accuracy }}%</div>
                                <div class="text-gray-400 text-sm">Aniqlik</div>
                            </div>
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.correct_count }}/{{ sessionResult.total_questions }}</div>
                                <div class="text-gray-400 text-sm">To'g'ri javoblar</div>
                            </div>
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.max_streak }}</div>
                                <div class="text-gray-400 text-sm">Eng uzun streak</div>
                            </div>
                        </div>

                        <!-- Rewards -->
                        <div class="flex justify-center gap-6 mb-8">
                            <div class="flex items-center gap-2 bg-indigo-500/20 px-4 py-2 rounded-xl">
                                <span class="text-xl">✨</span>
                                <span class="text-white font-semibold">+{{ sessionResult.rewards.xp_earned }} XP</span>
                            </div>
                            <div class="flex items-center gap-2 bg-yellow-500/20 px-4 py-2 rounded-xl">
                                <span class="text-xl">🪙</span>
                                <span class="text-white font-semibold">+{{ sessionResult.rewards.coins_earned }} tanga</span>
                            </div>
                        </div>

                        <!-- New Achievements -->
                        <div v-if="sessionResult.new_achievements?.length" class="mb-8">
                            <h3 class="text-white font-semibold mb-3">Yangi yutuqlar!</h3>
                            <div class="flex flex-wrap justify-center gap-2">
                                <div v-for="achievement in sessionResult.new_achievements" :key="achievement.id"
                                     class="bg-yellow-500/20 px-3 py-2 rounded-lg flex items-center gap-2">
                                    <span>🏆</span>
                                    <span class="text-yellow-400 text-sm">{{ achievement.name_uz }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-4">
                            <button @click="goBack"
                                    class="flex-1 py-3 bg-gray-700 text-white rounded-xl font-semibold hover:bg-gray-600 transition-colors">
                                Bosqichlarga
                            </button>
                            <button @click="restartGame"
                                    class="flex-1 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
                                Qayta o'ynash
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameStarted && currentQuestion" class="min-h-screen flex flex-col">
                <!-- Header -->
                <div class="bg-gray-800 border-b border-gray-700 p-4">
                    <div class="max-w-4xl mx-auto">
                        <div class="flex items-center justify-between mb-4">
                            <button @click="goBack" class="p-2 text-gray-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="flex items-center gap-4">
                                <!-- Score -->
                                <div class="flex items-center gap-2 bg-gray-700 px-4 py-2 rounded-xl">
                                    <span class="text-yellow-400">🏆</span>
                                    <span class="text-white font-semibold">{{ score }}</span>
                                </div>

                                <!-- Streak -->
                                <div v-if="streak > 0" class="flex items-center gap-2 bg-orange-500/20 px-4 py-2 rounded-xl">
                                    <span class="text-orange-400">🔥</span>
                                    <span class="text-orange-400 font-semibold">{{ streak }}x</span>
                                </div>

                                <!-- Multiplier -->
                                <div v-if="streakMultiplier > 1" class="flex items-center gap-2 bg-purple-500/20 px-4 py-2 rounded-xl">
                                    <span class="text-purple-400 font-semibold">{{ streakMultiplier.toFixed(1) }}x</span>
                                </div>
                            </div>

                            <div class="text-gray-400">
                                {{ currentIndex + 1 }}/{{ totalQuestions }}
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-300"
                                 :style="{ width: `${progressPercentage}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Timer -->
                <div class="bg-gray-800 px-4 py-2">
                    <div class="max-w-4xl mx-auto">
                        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                            <div :class="['h-full transition-all duration-100', timerColor]"
                                 :style="{ width: `${timerPercentage}%` }"></div>
                        </div>
                        <div class="text-center text-gray-400 text-sm mt-1">
                            {{ timeRemaining }}s
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="flex-1 p-6 flex flex-col">
                    <div class="max-w-2xl mx-auto w-full flex-1 flex flex-col">
                        <!-- Question Type Label -->
                        <div class="text-center mb-6">
                            <span class="inline-block px-4 py-2 bg-indigo-500/20 text-indigo-400 rounded-full text-sm">
                                {{ getQuestionTypeLabel(currentQuestion.type) }}
                            </span>
                        </div>

                        <!-- Question Prompt -->
                        <div class="bg-gray-800 rounded-2xl p-8 mb-6 text-center">
                            <p class="text-2xl md:text-3xl font-bold text-white mb-2">
                                {{ currentQuestion.prompt }}
                            </p>
                            <p v-if="currentQuestion.pronunciation" class="text-gray-400">
                                {{ currentQuestion.pronunciation }}
                            </p>
                            <p v-if="currentQuestion.context_translation && showHint" class="text-gray-400 mt-2 text-sm">
                                {{ currentQuestion.context_translation }}
                            </p>
                        </div>

                        <!-- Hint -->
                        <div v-if="showHint && hintText" class="bg-yellow-500/20 border border-yellow-500/30 rounded-xl p-4 mb-6 text-center">
                            <span class="text-yellow-400">💡</span>
                            <span class="text-yellow-300 ml-2">{{ hintText }}</span>
                        </div>

                        <!-- Options -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            <button v-for="option in filteredOptions" :key="option"
                                    @click="selectAnswer(option)"
                                    :disabled="isAnswering || answerResult"
                                    :class="[
                                        'p-5 rounded-xl text-lg font-medium transition-all',
                                        answerResult
                                            ? option === answerResult.correct_answer
                                                ? 'bg-green-500 text-white'
                                                : selectedAnswer === option
                                                    ? 'bg-red-500 text-white'
                                                    : 'bg-gray-700 text-gray-400'
                                            : selectedAnswer === option
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-700 text-white hover:bg-gray-600'
                                    ]">
                                {{ option }}
                            </button>
                        </div>

                        <!-- Powerups -->
                        <div class="flex justify-center gap-3">
                            <button v-for="(powerup, id) in powerupsState" :key="id"
                                    @click="usePowerup(id)"
                                    :disabled="powerup.remaining <= 0 || powerup.cooldown > 0 || isAnswering || answerResult"
                                    :class="[
                                        'flex flex-col items-center gap-1 p-3 rounded-xl transition-all',
                                        powerup.remaining > 0 && powerup.cooldown === 0 && !isAnswering && !answerResult
                                            ? 'bg-gray-700 hover:bg-gray-600 text-white'
                                            : 'bg-gray-800 text-gray-500 cursor-not-allowed'
                                    ]">
                                <span class="text-2xl">{{ getPowerupIcon(id) }}</span>
                                <span class="text-xs">{{ powerup.remaining }}</span>
                                <span v-if="powerup.cooldown > 0" class="text-xs text-orange-400">{{ powerup.cooldown }}🔄</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.animate-bounce {
    animation: bounce 0.6s ease-in-out infinite;
}
</style>
