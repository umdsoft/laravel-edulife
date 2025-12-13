<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object,
    hints: Object,
})

// Game State
const gameStarted = ref(false)
const gameCompleted = ref(false)
const isLoading = ref(false)
const error = ref(null)

// Session Data
const sessionId = ref(null)
const currentWord = ref(null)
const currentIndex = ref(0)
const totalWords = ref(0)
const score = ref(0)
const streak = ref(0)
const streakMultiplier = ref(1.0)
const hintsRemaining = ref(3)

// Timer
const timeLimit = ref(60)
const timeRemaining = ref(60)
const timerInterval = ref(null)
const timerStartTime = ref(null)

// Answer State
const answerSlots = ref([])
const scrambledLetters = ref([])
const usedLetterIds = ref([])
const revealedLetters = ref({})
const usedHints = ref([])
const activeHint = ref(null)
const feedbackMessage = ref('')
const feedbackType = ref('')
const isChecking = ref(false)

// Celebration
const showCelebration = ref(false)
const celebrationEmoji = ref('')
const celebrationText = ref('')
const lastScoreEarned = ref(0)

// Results
const sessionResult = ref(null)
const newAchievements = ref([])

// Computed
const progressPercentage = computed(() => {
    return totalWords.value > 0 ? Math.round((currentIndex.value / totalWords.value) * 100) : 0
})

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0
})

const timerColor = computed(() => {
    if (timerPercentage.value > 50) return 'bg-green-500'
    if (timerPercentage.value > 25) return 'bg-yellow-500'
    return 'bg-red-500'
})

const availableLetters = computed(() => {
    return scrambledLetters.value.filter(l => !usedLetterIds.value.includes(l.id))
})

const isAnswerComplete = computed(() => {
    return answerSlots.value.every((slot, index) => slot || revealedLetters.value[index])
})

const availableHintsList = computed(() => {
    if (!props.hints) return []
    return Object.entries(props.hints)
        .filter(([id, hint]) => hint.available_from_level <= props.level.number)
        .map(([id, hint]) => ({ id, ...hint }))
})

// Methods
async function startGame() {
    isLoading.value = true
    error.value = null

    try {
        const response = await axios.post(`/student/english/games/word-scramble/start/${props.level.number}`)

        if (response.data.success) {
            sessionId.value = response.data.session_id
            totalWords.value = response.data.total_words
            hintsRemaining.value = response.data.hints_allowed
            setCurrentWord(response.data.current_word)
            gameStarted.value = true
        } else {
            error.value = response.data.error || "O'yinni boshlashda xatolik"
        }
    } catch (e) {
        console.error('Start game error:', e)
        error.value = "Server bilan bog'lanishda xatolik"
    } finally {
        isLoading.value = false
    }
}

function setCurrentWord(word) {
    currentWord.value = word
    scrambledLetters.value = word.scrambled
    answerSlots.value = new Array(word.word_length).fill(null)
    usedLetterIds.value = []
    revealedLetters.value = word.revealed_letters || {}
    timeLimit.value = word.time_limit
    usedHints.value = []
    activeHint.value = null
    feedbackMessage.value = ''
    startTimer()
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
    if (!isChecking.value) {
        feedbackMessage.value = "Vaqt tugadi!"
        feedbackType.value = 'error'
        setTimeout(() => skipWord(), 1500)
    }
}

function addToAnswer(letter) {
    const emptyIndex = answerSlots.value.findIndex((slot, index) => !slot && !revealedLetters.value[index])
    if (emptyIndex !== -1) {
        answerSlots.value[emptyIndex] = letter.letter
        usedLetterIds.value.push(letter.id)
    }
}

function removeFromSlot(index) {
    if (answerSlots.value[index] && !revealedLetters.value[index]) {
        const letter = answerSlots.value[index]
        const letterObj = scrambledLetters.value.find(l => l.letter === letter && usedLetterIds.value.includes(l.id))
        if (letterObj) {
            usedLetterIds.value = usedLetterIds.value.filter(id => id !== letterObj.id)
        }
        answerSlots.value[index] = null
    }
}

function handleDragStart(event, letter) {
    event.dataTransfer.setData('text/plain', JSON.stringify(letter))
}

function handleDrop(event, slotIndex) {
    event.preventDefault()
    if (!answerSlots.value[slotIndex] && !revealedLetters.value[slotIndex]) {
        const letter = JSON.parse(event.dataTransfer.getData('text/plain'))
        answerSlots.value[slotIndex] = letter.letter
        usedLetterIds.value.push(letter.id)
    }
}

function clearAnswer() {
    answerSlots.value = answerSlots.value.map((slot, index) =>
        revealedLetters.value[index] ? revealedLetters.value[index] : null
    )
    usedLetterIds.value = []
}

async function checkAnswer() {
    if (!isAnswerComplete.value || isChecking.value) return

    isChecking.value = true
    stopTimer()

    const answer = answerSlots.value.map((slot, index) =>
        slot || revealedLetters.value[index] || ''
    ).join('')

    const timeSpent = timeLimit.value - timeRemaining.value

    try {
        const response = await axios.post('/student/english/games/word-scramble/check', {
            session_id: sessionId.value,
            answer: answer,
            time_spent: Math.round(timeSpent),
        })

        if (response.data.success) {
            if (response.data.correct) {
                handleCorrectAnswer(response.data)
            } else {
                handleWrongAnswer(response.data)
            }
        }
    } catch (e) {
        console.error('Check answer error:', e)
        error.value = 'Javobni tekshirishda xatolik'
        isChecking.value = false
        startTimer()
    }
}

function handleCorrectAnswer(data) {
    score.value = data.total_score
    streak.value = data.streak
    streakMultiplier.value = data.streak_multiplier
    lastScoreEarned.value = data.score_earned
    currentIndex.value = data.progress.current

    // Show celebration
    showCelebration.value = true
    celebrationEmoji.value = data.streak_milestone ? '🔥' : '✨'
    celebrationText.value = data.streak_milestone ? data.streak_milestone.message : "To'g'ri!"

    setTimeout(() => {
        showCelebration.value = false
        isChecking.value = false

        if (data.is_complete) {
            completeSession()
        } else {
            setCurrentWord(data.next_word)
        }
    }, 1500)
}

function handleWrongAnswer(data) {
    streak.value = 0
    feedbackMessage.value = data.hint || "Noto'g'ri! Qaytadan urinib ko'ring."
    feedbackType.value = 'error'
    isChecking.value = false

    setTimeout(() => {
        feedbackMessage.value = ''
        startTimer()
    }, 2000)
}

async function useHint(hintId) {
    if (usedHints.value.includes(hintId) || hintsRemaining.value <= 0 || isChecking.value) return

    try {
        const response = await axios.post('/student/english/games/word-scramble/hint', {
            session_id: sessionId.value,
            hint_type: hintId,
        })

        if (response.data.success) {
            usedHints.value.push(hintId)
            hintsRemaining.value = response.data.hints_remaining
            activeHint.value = response.data.hint

            if (response.data.hint.type === 'first_letter' || response.data.hint.type === 'reveal_letter') {
                Object.entries(response.data.revealed_letters).forEach(([pos, letter]) => {
                    revealedLetters.value[parseInt(pos)] = letter
                    answerSlots.value[parseInt(pos)] = letter
                })
            }
        }
    } catch (e) {
        console.error('Use hint error:', e)
    }
}

async function skipWord() {
    stopTimer()
    isChecking.value = true

    try {
        const response = await axios.post('/student/english/games/word-scramble/skip', {
            session_id: sessionId.value,
        })

        if (response.data.success) {
            streak.value = 0
            currentIndex.value = response.data.progress.current

            feedbackMessage.value = `So'z: ${response.data.skipped_word}`
            feedbackType.value = 'info'

            setTimeout(() => {
                feedbackMessage.value = ''
                isChecking.value = false

                if (response.data.is_complete) {
                    completeSession()
                } else {
                    setCurrentWord(response.data.next_word)
                }
            }, 1500)
        }
    } catch (e) {
        console.error('Skip word error:', e)
        isChecking.value = false
    }
}

async function completeSession() {
    stopTimer()
    isLoading.value = true

    try {
        const response = await axios.post('/student/english/games/word-scramble/complete', {
            session_id: sessionId.value,
        })

        if (response.data.success) {
            sessionResult.value = response.data.results
            newAchievements.value = response.data.new_achievements || []
            gameCompleted.value = true
        }
    } catch (e) {
        console.error('Complete session error:', e)
        error.value = 'Sessiyani yakunlashda xatolik'
    } finally {
        isLoading.value = false
    }
}

function getHintIcon(id) {
    const icons = {
        'definition': '📖',
        'translation': '🌐',
        'first_letter': '🔤',
        'reveal_letter': '✨',
        'image': '🖼️',
    }
    return icons[id] || '💡'
}

function restartGame() {
    gameStarted.value = false
    gameCompleted.value = false
    sessionId.value = null
    currentWord.value = null
    currentIndex.value = 0
    score.value = 0
    streak.value = 0
    streakMultiplier.value = 1.0
    sessionResult.value = null
    newAchievements.value = []
    answerSlots.value = []
    usedLetterIds.value = []
    usedHints.value = []
    startGame()
}

function goBack() {
    router.visit('/student/english/games/word-scramble')
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
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
    <Head :title="`So'z Jumboq - ${level?.name_uz || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-900 -m-6 font-sans">
            <!-- Loading Screen -->
            <div v-if="isLoading && !gameStarted" class="flex items-center justify-center min-h-screen">
                <div class="text-center">
                    <div class="w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
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
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-96 h-96 bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-full blur-3xl"></div>
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
                            {{ sessionResult.stars >= 3 ? 'Ajoyib!' : sessionResult.stars >= 2 ? 'Yaxshi!' : sessionResult.stars >= 1 ? "Yomon emas!" : "Qayta urinib ko'ring!" }}
                        </h2>

                        <p class="text-white/60 mb-8">{{ level?.name_uz }} yakunlandi</p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.score }}</div>
                                <div class="text-gray-400 text-sm">Umumiy ball</div>
                            </div>
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.accuracy }}%</div>
                                <div class="text-gray-400 text-sm">Aniqlik</div>
                            </div>
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.correct_answers }}/{{ sessionResult.total_words }}</div>
                                <div class="text-gray-400 text-sm">To'g'ri javoblar</div>
                            </div>
                            <div class="bg-gray-700/50 rounded-xl p-4">
                                <div class="text-3xl font-bold text-white">{{ sessionResult.best_streak }}</div>
                                <div class="text-gray-400 text-sm">Eng uzun streak</div>
                            </div>
                        </div>

                        <!-- Rewards -->
                        <div class="flex justify-center gap-6 mb-8">
                            <div class="flex items-center gap-2 bg-purple-500/20 px-4 py-2 rounded-xl">
                                <span class="text-xl">✨</span>
                                <span class="text-white font-semibold">+{{ sessionResult.xp_earned }} XP</span>
                            </div>
                            <div class="flex items-center gap-2 bg-yellow-500/20 px-4 py-2 rounded-xl">
                                <span class="text-xl">🪙</span>
                                <span class="text-white font-semibold">+{{ sessionResult.coins_earned }} tanga</span>
                            </div>
                        </div>

                        <!-- New Achievements -->
                        <div v-if="newAchievements?.length" class="mb-8">
                            <h3 class="text-white font-semibold mb-3">Yangi yutuqlar!</h3>
                            <div class="flex flex-wrap justify-center gap-2">
                                <div v-for="achievement in newAchievements" :key="achievement.id"
                                     class="bg-yellow-500/20 px-3 py-2 rounded-lg flex items-center gap-2">
                                    <span>{{ achievement.icon }}</span>
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
                                    class="flex-1 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
                                Qayta o'ynash
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameStarted && currentWord" class="min-h-screen flex flex-col">
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
                                <div v-if="streak > 0" class="flex items-center gap-2 bg-orange-500/20 px-4 py-2 rounded-xl animate-pulse">
                                    <span class="text-orange-400">🔥</span>
                                    <span class="text-orange-400 font-semibold">{{ streak }}x</span>
                                </div>

                                <!-- Multiplier -->
                                <div v-if="streakMultiplier > 1" class="flex items-center gap-2 bg-purple-500/20 px-4 py-2 rounded-xl">
                                    <span class="text-purple-400 font-semibold">{{ streakMultiplier.toFixed(1) }}x</span>
                                </div>
                            </div>

                            <div class="text-gray-400">
                                {{ currentIndex + 1 }}/{{ totalWords }}
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-pink-600 transition-all duration-300"
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
                        <!-- Word Info -->
                        <div class="text-center mb-6">
                            <span class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500/20 text-purple-400 rounded-full text-sm">
                                <span>📚</span>
                                <span class="capitalize">{{ currentWord?.category }}</span>
                                <span class="text-purple-400/60">•</span>
                                <span>{{ currentWord?.word_length }} harf</span>
                            </span>
                        </div>

                        <!-- Hint Display -->
                        <div v-if="activeHint" class="bg-blue-500/20 border border-blue-500/30 rounded-xl p-4 mb-6 text-center">
                            <span class="text-blue-400">{{ getHintIcon(activeHint.type) }}</span>
                            <span class="text-blue-300 ml-2">{{ activeHint.content }}</span>
                        </div>

                        <!-- Answer Slots -->
                        <div class="bg-gray-800 rounded-2xl p-8 mb-6">
                            <div class="flex justify-center gap-2 mb-8 flex-wrap">
                                <div v-for="(slot, index) in answerSlots" :key="'slot-' + index"
                                     @click="removeFromSlot(index)"
                                     @dragover.prevent
                                     @drop="handleDrop($event, index)"
                                     :class="[
                                         'w-12 h-14 sm:w-14 sm:h-16 rounded-xl flex items-center justify-center text-xl sm:text-2xl font-bold cursor-pointer transition-all duration-200',
                                         slot
                                             ? 'bg-gradient-to-br from-purple-500 to-pink-500 text-white shadow-lg transform hover:scale-105'
                                             : revealedLetters[index]
                                                 ? 'bg-gradient-to-br from-green-500 to-emerald-500 text-white'
                                                 : 'border-2 border-dashed border-gray-600 bg-gray-700/50 text-gray-500'
                                     ]">
                                    {{ slot || revealedLetters[index] || '' }}
                                </div>
                            </div>

                            <!-- Scrambled Letters -->
                            <div class="flex justify-center gap-2 flex-wrap">
                                <div v-for="letter in availableLetters" :key="letter.id"
                                     @click="addToAnswer(letter)"
                                     draggable="true"
                                     @dragstart="handleDragStart($event, letter)"
                                     class="w-12 h-14 sm:w-14 sm:h-16 rounded-xl flex items-center justify-center text-xl sm:text-2xl font-bold cursor-grab active:cursor-grabbing transition-all duration-200 transform hover:scale-110 hover:-translate-y-1 bg-gray-700 text-white border-2 border-gray-600 shadow-lg hover:shadow-xl hover:border-purple-500">
                                    {{ letter.letter }}
                                </div>
                            </div>

                            <!-- Feedback Message -->
                            <Transition name="fade">
                                <div v-if="feedbackMessage"
                                     :class="[
                                         'mt-6 p-4 rounded-xl text-center font-medium',
                                         feedbackType === 'error'
                                             ? 'bg-red-500/20 text-red-400'
                                             : 'bg-blue-500/20 text-blue-400'
                                     ]">
                                    {{ feedbackMessage }}
                                </div>
                            </Transition>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <!-- Hints Row -->
                            <div class="flex justify-center gap-2 flex-wrap">
                                <button v-for="hint in availableHintsList" :key="hint.id"
                                        @click="useHint(hint.id)"
                                        :disabled="usedHints.includes(hint.id) || hintsRemaining <= 0 || isChecking"
                                        :class="[
                                            'flex items-center gap-2 px-4 py-2 rounded-xl transition-all',
                                            usedHints.includes(hint.id) || hintsRemaining <= 0 || isChecking
                                                ? 'bg-gray-800 text-gray-500 cursor-not-allowed'
                                                : 'bg-gray-700 text-white hover:bg-gray-600'
                                        ]">
                                    <span>{{ getHintIcon(hint.id) }}</span>
                                    <span class="hidden sm:inline text-sm">{{ hint.name_uz }}</span>
                                </button>
                                <div class="flex items-center gap-1 px-3 py-2 bg-gray-800 rounded-xl text-gray-400 text-sm">
                                    <span>💡</span>
                                    <span>{{ hintsRemaining }}</span>
                                </div>
                            </div>

                            <!-- Main Actions -->
                            <div class="flex gap-3 justify-center">
                                <button @click="clearAnswer"
                                        :disabled="answerSlots.every(s => !s) || isChecking"
                                        class="px-6 py-3 rounded-xl bg-gray-700 text-white font-semibold hover:bg-gray-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    🔄 Tozalash
                                </button>
                                <button @click="skipWord"
                                        :disabled="isChecking"
                                        class="px-6 py-3 rounded-xl bg-orange-500/20 text-orange-400 font-semibold hover:bg-orange-500/30 transition-all disabled:opacity-50">
                                    ⏭️ O'tkazish
                                </button>
                                <button @click="checkAnswer"
                                        :disabled="!isAnswerComplete || isChecking"
                                        class="px-8 py-3 rounded-xl bg-gradient-to-r from-purple-500 to-pink-600 text-white font-bold hover:opacity-90 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                    ✓ Tekshirish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Celebration Overlay -->
            <Transition name="celebrate">
                <div v-if="showCelebration"
                     class="fixed inset-0 pointer-events-none flex items-center justify-center z-50">
                    <div class="text-center">
                        <div class="text-8xl mb-4 animate-bounce">{{ celebrationEmoji }}</div>
                        <div class="text-3xl font-bold text-green-400">{{ celebrationText }}</div>
                        <div class="text-xl text-purple-400 mt-2">+{{ lastScoreEarned }} ball</div>
                    </div>
                </div>
            </Transition>
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

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.celebrate-enter-active {
    animation: celebrateIn 0.5s ease-out;
}

.celebrate-leave-active {
    transition: opacity 0.3s ease;
}

.celebrate-leave-to {
    opacity: 0;
}

@keyframes celebrateIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
