<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Object,
    categories: Object,
    categoryInfos: Object,
    powerups: Object,
})

// Game state
const gameState = ref('mode_select') // mode_select, loading, playing, paused, completed
const selectedMode = ref('listen_choose')
const sessionId = ref(null)
const currentPair = ref(null)
const totalPairs = ref(0)
const completedPairs = ref(0)
const score = ref(0)
const xpEarned = ref(0)
const coinsEarned = ref(0)
const streak = ref(0)
const bestStreak = ref(0)
const correctAnswers = ref(0)
const wrongAnswers = ref(0)
const isPerfect = ref(true)

// Timer
const timeLimit = ref(15)
const timeRemaining = ref(15)
const timerInterval = ref(null)
const startTime = ref(null)

// Audio
const isPlaying = ref(false)
const audioElement = ref(null)
const replaysRemaining = ref(3)
const maxReplays = ref(3)

// UI state
const showResults = ref(false)
const results = ref(null)
const feedback = ref(null)
const feedbackTimeout = ref(null)
const showPowerups = ref(false)
const usedPowerups = ref([])

// Same/Different mode
const firstAudioPlayed = ref(false)
const secondAudioPlayed = ref(false)

// Odd One Out mode
const oddOneOutAudios = ref([])
const currentOddIndex = ref(0)

// Speak & Compare mode
const isRecording = ref(false)
const userRecording = ref(null)

// Computed
const progressPercent = computed(() => {
    return totalPairs.value > 0 ? Math.round((completedPairs.value / totalPairs.value) * 100) : 0
})

const streakMultiplier = computed(() => {
    const increment = props.config?.scoring?.streak_multiplier?.increment_per_streak || 0.2
    const maxMult = props.config?.scoring?.streak_multiplier?.max_multiplier || 3.0
    return Math.min(maxMult, 1.0 + ((streak.value - 1) * increment)).toFixed(1)
})

const currentModeConfig = computed(() => {
    return props.gameModes?.[selectedMode.value] || {}
})

const availableModes = computed(() => {
    return props.level?.available_modes || ['listen_choose']
})

const powerupsList = computed(() => {
    return Object.values(props.powerups || {})
})

// Methods
async function startGame() {
    if (!availableModes.value.includes(selectedMode.value)) {
        selectedMode.value = availableModes.value[0] || 'listen_choose'
    }

    gameState.value = 'loading'

    try {
        const response = await axios.post(`/student/english/games/minimal-pairs/start/${props.level.number}`, {
            game_mode: selectedMode.value
        })

        if (response.data.success) {
            sessionId.value = response.data.session_id
            currentPair.value = response.data.current_pair
            totalPairs.value = response.data.total_pairs
            timeLimit.value = response.data.time_limit_per_pair
            maxReplays.value = response.data.max_replays || 3
            replaysRemaining.value = maxReplays.value

            gameState.value = 'playing'
            startTimer()

            // Auto-play first audio after a short delay
            await nextTick()
            setTimeout(() => playCurrentAudio(), 500)
        } else {
            alert(response.data.error || 'Xatolik yuz berdi')
            gameState.value = 'mode_select'
        }
    } catch (error) {
        console.error('Start session error:', error)
        alert('Sessiyani boshlashda xatolik')
        gameState.value = 'mode_select'
    }
}

function startTimer() {
    timeRemaining.value = timeLimit.value
    startTime.value = Date.now()

    if (timerInterval.value) clearInterval(timerInterval.value)

    timerInterval.value = setInterval(() => {
        const elapsed = Math.floor((Date.now() - startTime.value) / 1000)
        timeRemaining.value = Math.max(0, timeLimit.value - elapsed)

        if (timeRemaining.value <= 0) {
            handleTimeout()
        }
    }, 100)
}

function stopTimer() {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
        timerInterval.value = null
    }
}

function handleTimeout() {
    stopTimer()
    // Wrong answer on timeout
    handleWrongAnswer()
}

function getTimeSpent() {
    if (!startTime.value) return 0
    return Math.floor((Date.now() - startTime.value) / 1000)
}

// Audio playback using Web Speech API (TTS) as fallback
function playCurrentAudio() {
    if (isPlaying.value) return

    if (selectedMode.value === 'listen_choose') {
        speakWord(currentPair.value?.audio_word)
    } else if (selectedMode.value === 'same_different') {
        if (!firstAudioPlayed.value) {
            speakWord(currentPair.value?.first_word)
            firstAudioPlayed.value = true
        } else if (!secondAudioPlayed.value) {
            speakWord(currentPair.value?.second_word)
            secondAudioPlayed.value = true
        }
    } else if (selectedMode.value === 'odd_one_out') {
        playOddOneOutSequence()
    } else if (selectedMode.value === 'speak_compare') {
        // Play both words for comparison
        speakWord(currentPair.value?.word_1)
    }
}

function speakWord(word, rate = 0.9) {
    if (!word || isPlaying.value) return

    isPlaying.value = true

    const utterance = new SpeechSynthesisUtterance(word)
    utterance.lang = 'en-US'
    utterance.rate = rate
    utterance.pitch = 1

    utterance.onend = () => {
        isPlaying.value = false
    }

    utterance.onerror = () => {
        isPlaying.value = false
    }

    speechSynthesis.speak(utterance)
}

async function playOddOneOutSequence() {
    if (isPlaying.value || !currentPair.value?.options) return

    isPlaying.value = true

    for (let i = 0; i < currentPair.value.options.length; i++) {
        currentOddIndex.value = i
        await new Promise(resolve => {
            const utterance = new SpeechSynthesisUtterance(currentPair.value.options[i].word)
            utterance.lang = 'en-US'
            utterance.rate = 0.85
            utterance.onend = () => setTimeout(resolve, 400)
            utterance.onerror = resolve
            speechSynthesis.speak(utterance)
        })
    }

    currentOddIndex.value = -1
    isPlaying.value = false
}

async function useReplay() {
    if (replaysRemaining.value <= 0) return

    try {
        const response = await axios.post('/student/english/games/minimal-pairs/replay', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            replaysRemaining.value = response.data.replays_remaining
            isPerfect.value = false

            // Reset for same/different mode
            firstAudioPlayed.value = false
            secondAudioPlayed.value = false

            // Play audio again
            playCurrentAudio()
        }
    } catch (error) {
        console.error('Replay error:', error)
    }
}

async function selectAnswer(answer) {
    if (gameState.value !== 'playing' || isPlaying.value) return

    stopTimer()
    const timeSpent = getTimeSpent()

    try {
        const response = await axios.post('/student/english/games/minimal-pairs/check', {
            session_id: sessionId.value,
            pair_id: currentPair.value.id,
            answer: answer,
            time_spent: timeSpent
        })

        if (response.data.success) {
            if (response.data.correct) {
                handleCorrectAnswer(response.data)
            } else {
                handleWrongAnswer(response.data)
            }
        }
    } catch (error) {
        console.error('Check answer error:', error)
    }
}

function handleCorrectAnswer(data) {
    score.value = data.total_score || score.value + (data.score_earned || 0)
    xpEarned.value = data.total_xp || xpEarned.value + (data.xp_earned || 0)
    coinsEarned.value = data.total_coins || coinsEarned.value + (data.coins_earned || 0)
    streak.value = data.streak || streak.value + 1
    bestStreak.value = Math.max(bestStreak.value, streak.value)
    correctAnswers.value++
    completedPairs.value = data.progress?.completed || completedPairs.value + 1

    showFeedback('correct', data)

    if (data.is_complete) {
        setTimeout(() => completeGame(), 1500)
    } else if (data.next_pair) {
        setTimeout(() => {
            currentPair.value = data.next_pair
            replaysRemaining.value = maxReplays.value
            firstAudioPlayed.value = false
            secondAudioPlayed.value = false
            startTimer()
            setTimeout(() => playCurrentAudio(), 300)
        }, 1500)
    }
}

function handleWrongAnswer(data = {}) {
    streak.value = 0
    wrongAnswers.value++
    isPerfect.value = false

    showFeedback('wrong', data)

    // Don't advance, let player try again or use replay
    startTimer()
}

function showFeedback(type, data) {
    feedback.value = { type, data }

    if (feedbackTimeout.value) clearTimeout(feedbackTimeout.value)

    feedbackTimeout.value = setTimeout(() => {
        feedback.value = null
    }, type === 'correct' ? 1200 : 2000)
}

async function completeGame() {
    stopTimer()
    gameState.value = 'completed'

    try {
        const response = await axios.post('/student/english/games/minimal-pairs/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            results.value = response.data.results
            showResults.value = true
        }
    } catch (error) {
        console.error('Complete error:', error)
    }
}

async function usePowerup(powerupType) {
    try {
        const response = await axios.post('/student/english/games/minimal-pairs/powerup', {
            session_id: sessionId.value,
            powerup_type: powerupType
        })

        if (response.data.success) {
            usedPowerups.value.push(powerupType)
            isPerfect.value = false

            const powerupData = response.data.powerup

            if (powerupData.type === 'slow_audio') {
                speakWord(currentPair.value?.audio_word || currentPair.value?.word_1, 0.6)
            } else if (powerupData.type === 'show_phonetic') {
                // Show phonetics in UI
                currentPair.value.show_phonetic = true
                currentPair.value.phonetic_1 = powerupData.phonetic_1
                currentPair.value.phonetic_2 = powerupData.phonetic_2
            } else if (powerupData.type === 'extra_replay') {
                replaysRemaining.value += 2
                maxReplays.value = powerupData.new_max_replays
            } else if (powerupData.type === 'skip') {
                if (powerupData.is_complete) {
                    completeGame()
                } else {
                    currentPair.value = powerupData.next_pair
                    replaysRemaining.value = maxReplays.value
                    completedPairs.value++
                    startTimer()
                    setTimeout(() => playCurrentAudio(), 300)
                }
            } else if (powerupData.type === 'show_meaning') {
                currentPair.value.show_meaning = true
                currentPair.value.meaning_1 = powerupData.meaning_1
                currentPair.value.meaning_2 = powerupData.meaning_2
            }

            showPowerups.value = false
        }
    } catch (error) {
        console.error('Powerup error:', error)
    }
}

function canUsePowerup(powerupId) {
    const powerup = props.powerups?.[powerupId]
    if (!powerup) return false
    const usedCount = usedPowerups.value.filter(p => p === powerupId).length
    return usedCount < (powerup.max_uses || 1)
}

function goBack() {
    router.visit('/student/english/games/minimal-pairs')
}

function playAgain() {
    showResults.value = false
    results.value = null
    score.value = 0
    xpEarned.value = 0
    coinsEarned.value = 0
    streak.value = 0
    bestStreak.value = 0
    correctAnswers.value = 0
    wrongAnswers.value = 0
    completedPairs.value = 0
    isPerfect.value = true
    usedPowerups.value = []
    gameState.value = 'mode_select'
}

// For Same/Different mode
function playSecondAudio() {
    if (selectedMode.value === 'same_different' && firstAudioPlayed.value && !secondAudioPlayed.value) {
        speakWord(currentPair.value?.second_word)
        secondAudioPlayed.value = true
    }
}

function answerSameDifferent(isSame) {
    selectAnswer(isSame)
}

// For Odd One Out mode
function selectOddOne(position) {
    selectAnswer(position)
}

// For Speak & Compare mode
async function startRecording() {
    // Placeholder - in production would use MediaRecorder API
    isRecording.value = true
    setTimeout(() => {
        isRecording.value = false
        userRecording.value = 'recorded'
    }, 2000)
}

function confirmSpeakCompare() {
    selectAnswer(true) // Self-assessment - user confirms they practiced
}

// Lifecycle
onMounted(() => {
    // Initialize audio element for potential use
    audioElement.value = new Audio()
})

onUnmounted(() => {
    stopTimer()
    speechSynthesis.cancel()
    if (feedbackTimeout.value) clearTimeout(feedbackTimeout.value)
})

// Watch for mode changes to reset audio state
watch(selectedMode, () => {
    firstAudioPlayed.value = false
    secondAudioPlayed.value = false
    currentOddIndex.value = -1
})
</script>

<template>
    <Head :title="`${level?.name_uz || 'Minimal Pairs'} - O'yin`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 font-sans">
            <!-- Mode Selection Screen -->
            <div v-if="gameState === 'mode_select'" class="min-h-screen flex flex-col items-center justify-center p-6">
                <div class="max-w-2xl w-full">
                    <!-- Level Header -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-4xl mb-4 shadow-xl">
                            {{ level?.icon || '👂' }}
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                            {{ level?.name_uz || level?.name }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ level?.description_uz || level?.description }}
                        </p>
                        <div class="flex items-center justify-center gap-4 mt-4">
                            <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 rounded-full text-sm font-medium">
                                {{ level?.cefr_level }}
                            </span>
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-sm font-medium">
                                {{ level?.pairs_count }} juftlik
                            </span>
                        </div>
                    </div>

                    <!-- Mode Selection -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl mb-6">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                            🎮 O'yin rejimini tanlang
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template v-for="(mode, modeId) in gameModes" :key="modeId">
                                <div v-if="availableModes.includes(modeId)"
                                     @click="selectedMode = modeId"
                                     :class="[
                                         'p-4 rounded-xl border-2 cursor-pointer transition-all',
                                         selectedMode === modeId
                                             ? 'bg-gradient-to-br from-emerald-500 to-teal-600 border-emerald-400 text-white shadow-lg'
                                             : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-emerald-400'
                                     ]">
                                    <div class="flex items-center gap-3">
                                        <span class="text-3xl">{{ mode.icon }}</span>
                                        <div>
                                            <h3 class="font-bold">{{ mode.name_uz }}</h3>
                                            <p :class="selectedMode === modeId ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'" class="text-sm">
                                                {{ mode.description_uz }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="selectedMode === modeId" class="mt-2 text-right">
                                        <span class="text-sm bg-white/20 px-2 py-1 rounded-full">✓ Tanlangan</span>
                                    </div>
                                </div>
                                <div v-else
                                     class="p-4 rounded-xl border-2 bg-gray-100 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 opacity-50 cursor-not-allowed">
                                    <div class="flex items-center gap-3">
                                        <span class="text-3xl grayscale">{{ mode.icon }}</span>
                                        <div>
                                            <h3 class="font-bold text-gray-600 dark:text-gray-400">{{ mode.name_uz }}</h3>
                                            <p class="text-sm text-gray-400 dark:text-gray-500">Yuqori darajada ochiladi</p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded-full">🔒 Qulflangan</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Start Button -->
                    <div class="flex gap-4">
                        <button @click="goBack"
                                class="flex-1 py-4 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            ← Orqaga
                        </button>
                        <button @click="startGame"
                                class="flex-1 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-bold text-lg hover:opacity-90 transition-opacity shadow-lg">
                            ▶ Boshlash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Screen -->
            <div v-else-if="gameState === 'loading'" class="min-h-screen flex items-center justify-center">
                <div class="text-center">
                    <div class="w-20 h-20 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">Yuklanmoqda...</p>
                </div>
            </div>

            <!-- Game Screen -->
            <div v-else-if="gameState === 'playing'" class="min-h-screen flex flex-col">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 shadow-lg px-4 py-3 sticky top-0 z-10">
                    <div class="max-w-4xl mx-auto">
                        <div class="flex items-center justify-between mb-3">
                            <button @click="goBack" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1 text-yellow-500 font-bold">
                                    <span class="text-lg">⭐</span>
                                    <span>{{ score }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-orange-500 font-bold">
                                    <span class="text-lg">🔥</span>
                                    <span>{{ streak }}</span>
                                </div>
                                <div v-if="streak > 1" class="px-2 py-1 bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 rounded-full text-sm font-bold">
                                    x{{ streakMultiplier }}
                                </div>
                            </div>

                            <button @click="showPowerups = !showPowerups" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors relative">
                                <span class="text-2xl">🔮</span>
                            </button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full transition-all duration-500"
                                     :style="{ width: progressPercent + '%' }"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ completedPairs }}/{{ totalPairs }}
                            </span>
                        </div>

                        <!-- Timer -->
                        <div class="mt-2">
                            <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div :class="[
                                    'h-full rounded-full transition-all duration-100',
                                    timeRemaining <= 5 ? 'bg-red-500' : timeRemaining <= 10 ? 'bg-yellow-500' : 'bg-emerald-500'
                                ]" :style="{ width: (timeRemaining / timeLimit * 100) + '%' }"></div>
                            </div>
                            <div class="text-center mt-1">
                                <span :class="[
                                    'text-sm font-bold',
                                    timeRemaining <= 5 ? 'text-red-500' : 'text-gray-600 dark:text-gray-400'
                                ]">{{ timeRemaining }}s</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Game Content -->
                <div class="flex-1 flex items-center justify-center p-6">
                    <div class="max-w-xl w-full">
                        <!-- Mode: Listen & Choose -->
                        <div v-if="selectedMode === 'listen_choose'" class="text-center">
                            <!-- Audio Player UI -->
                            <div class="mb-8">
                                <div @click="playCurrentAudio"
                                     :class="[
                                         'w-32 h-32 mx-auto rounded-full flex items-center justify-center cursor-pointer transition-all shadow-xl',
                                         isPlaying
                                             ? 'bg-gradient-to-br from-emerald-400 to-teal-500 animate-pulse scale-110'
                                             : 'bg-gradient-to-br from-emerald-500 to-teal-600 hover:scale-105'
                                     ]">
                                    <svg v-if="!isPlaying" class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    <div v-else class="flex items-center gap-1">
                                        <div class="w-2 h-8 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                        <div class="w-2 h-12 bg-white rounded-full animate-bounce" style="animation-delay: 100ms"></div>
                                        <div class="w-2 h-6 bg-white rounded-full animate-bounce" style="animation-delay: 200ms"></div>
                                        <div class="w-2 h-10 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                    </div>
                                </div>

                                <p class="text-gray-500 dark:text-gray-400 mt-4">
                                    {{ isPlaying ? 'Tinglang...' : 'Bosib tinglang' }}
                                </p>

                                <!-- Replay Button -->
                                <button v-if="replaysRemaining > 0"
                                        @click="useReplay"
                                        class="mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    🔁 Qayta tinglash ({{ replaysRemaining }})
                                </button>
                            </div>

                            <!-- Phonetics (if shown via powerup) -->
                            <div v-if="currentPair?.show_phonetic" class="mb-4 flex justify-center gap-4">
                                <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg text-emerald-700 dark:text-emerald-300 font-mono">
                                    {{ currentPair.phonetic_1 }}
                                </span>
                                <span class="px-3 py-1 bg-cyan-100 dark:bg-cyan-900/50 rounded-lg text-cyan-700 dark:text-cyan-300 font-mono">
                                    {{ currentPair.phonetic_2 }}
                                </span>
                            </div>

                            <!-- Options -->
                            <div class="grid grid-cols-2 gap-4">
                                <button v-for="(option, idx) in currentPair?.options" :key="idx"
                                        @click="selectAnswer(option.word)"
                                        :disabled="isPlaying"
                                        class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-2 border-gray-200 dark:border-gray-700 hover:border-emerald-500 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="text-2xl font-bold text-gray-800 dark:text-white">{{ option.word }}</span>
                                    <span v-if="currentPair?.show_meaning" class="block text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ option.meaning }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Mode: Same or Different -->
                        <div v-else-if="selectedMode === 'same_different'" class="text-center">
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Ikki tovush bir xilmi yoki boshqachami?</p>

                            <!-- Two Audio Players -->
                            <div class="flex items-center justify-center gap-8 mb-8">
                                <div>
                                    <div @click="playCurrentAudio"
                                         :class="[
                                             'w-24 h-24 rounded-full flex items-center justify-center cursor-pointer transition-all shadow-lg',
                                             isPlaying && !secondAudioPlayed
                                                 ? 'bg-emerald-400 animate-pulse scale-110'
                                                 : firstAudioPlayed
                                                     ? 'bg-emerald-600'
                                                     : 'bg-gradient-to-br from-emerald-500 to-teal-600 hover:scale-105'
                                         ]">
                                        <span class="text-4xl text-white">1</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2">{{ firstAudioPlayed ? '✓ Tinglandi' : 'Bosing' }}</p>
                                </div>

                                <span class="text-3xl text-gray-400">→</span>

                                <div>
                                    <div @click="playSecondAudio"
                                         :class="[
                                             'w-24 h-24 rounded-full flex items-center justify-center transition-all shadow-lg',
                                             !firstAudioPlayed
                                                 ? 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'
                                                 : isPlaying && firstAudioPlayed && !secondAudioPlayed
                                                     ? 'bg-cyan-400 animate-pulse scale-110 cursor-pointer'
                                                     : secondAudioPlayed
                                                         ? 'bg-cyan-600 cursor-pointer'
                                                         : 'bg-gradient-to-br from-cyan-500 to-blue-600 hover:scale-105 cursor-pointer'
                                         ]">
                                        <span class="text-4xl text-white">2</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2">{{ secondAudioPlayed ? '✓ Tinglandi' : firstAudioPlayed ? 'Bosing' : 'Avval 1-ni' }}</p>
                                </div>
                            </div>

                            <!-- Replay -->
                            <button v-if="replaysRemaining > 0"
                                    @click="useReplay"
                                    class="mb-6 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                🔁 Qayta tinglash ({{ replaysRemaining }})
                            </button>

                            <!-- Answer Buttons -->
                            <div class="grid grid-cols-2 gap-4">
                                <button @click="answerSameDifferent(true)"
                                        :disabled="!secondAudioPlayed"
                                        class="p-6 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl font-bold text-xl shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    ✓ Bir xil
                                </button>
                                <button @click="answerSameDifferent(false)"
                                        :disabled="!secondAudioPlayed"
                                        class="p-6 bg-cyan-500 hover:bg-cyan-600 text-white rounded-2xl font-bold text-xl shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    ✗ Boshqacha
                                </button>
                            </div>
                        </div>

                        <!-- Mode: Odd One Out -->
                        <div v-else-if="selectedMode === 'odd_one_out'" class="text-center">
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Boshqacha tovushli so'zni tanlang</p>

                            <!-- Play All Button -->
                            <button @click="playCurrentAudio"
                                    :disabled="isPlaying"
                                    :class="[
                                        'w-24 h-24 mx-auto rounded-full flex items-center justify-center transition-all shadow-xl mb-8',
                                        isPlaying
                                            ? 'bg-gradient-to-br from-violet-400 to-purple-500 animate-pulse'
                                            : 'bg-gradient-to-br from-violet-500 to-purple-600 hover:scale-105 cursor-pointer'
                                    ]">
                                <svg v-if="!isPlaying" class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                                <span v-else class="text-2xl text-white font-bold">{{ currentOddIndex + 1 }}</span>
                            </button>

                            <!-- Options Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <button v-for="(option, idx) in currentPair?.options" :key="idx"
                                        @click="selectOddOne(option.position)"
                                        :disabled="isPlaying"
                                        :class="[
                                            'p-6 rounded-2xl shadow-lg border-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed',
                                            currentOddIndex === idx
                                                ? 'bg-violet-500 border-violet-400 text-white scale-105'
                                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-violet-500'
                                        ]">
                                    <span class="text-3xl font-bold mb-2 block">{{ idx + 1 }}</span>
                                    <span :class="currentOddIndex === idx ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'" class="text-sm">
                                        {{ isPlaying && currentOddIndex === idx ? 'Tinglayapsiz...' : 'Tanlash uchun bosing' }}
                                    </span>
                                </button>
                            </div>

                            <!-- Replay -->
                            <button v-if="replaysRemaining > 0"
                                    @click="useReplay"
                                    class="mt-6 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                🔁 Qayta tinglash ({{ replaysRemaining }})
                            </button>
                        </div>

                        <!-- Mode: Speak & Compare -->
                        <div v-else-if="selectedMode === 'speak_compare'" class="text-center">
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Ikkala so'zni ham o'zingiz ayting va native speaker bilan solishtiring</p>

                            <!-- Words to practice -->
                            <div class="grid grid-cols-2 gap-6 mb-8">
                                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                                    <div @click="speakWord(currentPair?.word_1)"
                                         class="w-16 h-16 mx-auto bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center cursor-pointer hover:scale-105 transition-all shadow-lg mb-4">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">{{ currentPair?.word_1 }}</h3>
                                    <p class="text-emerald-600 dark:text-emerald-400 font-mono text-sm mt-1">{{ currentPair?.phonetic_1 }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ currentPair?.meaning_1 }}</p>
                                </div>

                                <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                                    <div @click="speakWord(currentPair?.word_2)"
                                         class="w-16 h-16 mx-auto bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center cursor-pointer hover:scale-105 transition-all shadow-lg mb-4">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">{{ currentPair?.word_2 }}</h3>
                                    <p class="text-cyan-600 dark:text-cyan-400 font-mono text-sm mt-1">{{ currentPair?.phonetic_2 }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ currentPair?.meaning_2 }}</p>
                                </div>
                            </div>

                            <!-- Record Button -->
                            <div class="mb-6">
                                <button @click="startRecording"
                                        :disabled="isRecording"
                                        :class="[
                                            'w-20 h-20 mx-auto rounded-full flex items-center justify-center transition-all shadow-xl',
                                            isRecording
                                                ? 'bg-red-500 animate-pulse scale-110'
                                                : 'bg-gradient-to-br from-rose-500 to-red-600 hover:scale-105 cursor-pointer'
                                        ]">
                                    <span class="text-3xl">🎤</span>
                                </button>
                                <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                                    {{ isRecording ? 'Yozilmoqda...' : 'O\'zingiz aytib ko\'ring' }}
                                </p>
                            </div>

                            <!-- Confirm Button -->
                            <button @click="confirmSpeakCompare"
                                    class="w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-bold text-lg hover:opacity-90 transition-opacity shadow-lg">
                                ✓ Mashq qildim, keyingisi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Powerups Panel -->
                <Transition name="slide">
                    <div v-if="showPowerups"
                         class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-2xl shadow-2xl p-6 z-20">
                        <div class="max-w-xl mx-auto">
                            <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-4">🔮 Maxsus Kuchlar</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <template v-for="powerup in powerupsList" :key="powerup.id">
                                    <button v-if="canUsePowerup(powerup.id)"
                                            @click="usePowerup(powerup.id)"
                                            class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl text-left hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">{{ powerup.icon }}</span>
                                            <div>
                                                <div class="font-semibold text-gray-800 dark:text-white">{{ powerup.name_uz }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ powerup.cost_coins }} 🪙</div>
                                            </div>
                                        </div>
                                    </button>
                                    <button v-else
                                            disabled
                                            class="p-4 bg-gray-100 dark:bg-gray-700/50 rounded-xl text-left opacity-50 cursor-not-allowed">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl grayscale">{{ powerup.icon }}</span>
                                            <div>
                                                <div class="font-semibold text-gray-600 dark:text-gray-400">{{ powerup.name_uz }}</div>
                                                <div class="text-xs text-gray-400">Ishlatildi</div>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <button @click="showPowerups = false" class="w-full mt-4 py-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                Yopish
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Feedback Overlay -->
                <Transition name="fade">
                    <div v-if="feedback" class="fixed inset-0 flex items-center justify-center z-30 pointer-events-none">
                        <div v-if="feedback.type === 'correct'"
                             class="bg-green-500 text-white px-8 py-4 rounded-2xl shadow-2xl text-2xl font-bold animate-bounce">
                            ✓ To'g'ri! +{{ feedback.data?.score_earned || 0 }}
                        </div>
                        <div v-else
                             class="bg-red-500 text-white px-8 py-4 rounded-2xl shadow-2xl text-xl font-bold">
                            ✗ Noto'g'ri
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Results Screen -->
            <Transition name="modal">
                <div v-if="showResults" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-6 text-center text-white">
                            <div class="text-6xl mb-4">
                                {{ results?.stars >= 3 ? '🏆' : results?.stars >= 2 ? '🌟' : results?.stars >= 1 ? '⭐' : '💪' }}
                            </div>
                            <h2 class="text-2xl font-bold">
                                {{ results?.is_perfect ? 'Mukammal!' : results?.stars >= 3 ? 'Ajoyib!' : results?.stars >= 2 ? 'Yaxshi!' : 'Harakat qilindingiz!' }}
                            </h2>
                            <p class="text-white/80 mt-1">{{ level?.name_uz }}</p>
                        </div>

                        <!-- Stats -->
                        <div class="p-6">
                            <!-- Stars -->
                            <div class="flex justify-center gap-2 mb-6">
                                <template v-for="i in 3" :key="i">
                                    <svg :class="[
                                        'w-12 h-12 transition-all',
                                        i <= (results?.stars || 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'
                                    ]" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </template>
                            </div>

                            <!-- Stats Grid -->
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ results?.score || 0 }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Ball</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ results?.accuracy || 0 }}%</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Aniqlik</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">+{{ results?.xp_earned || 0 }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">XP</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">+{{ results?.coins_earned || 0 }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Tangalar</div>
                                </div>
                            </div>

                            <!-- More Stats -->
                            <div class="space-y-2 text-sm mb-6">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">To'g'ri javoblar</span>
                                    <span class="font-semibold text-gray-800 dark:text-white">{{ results?.correct_answers || 0 }}/{{ results?.total_pairs || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Eng yaxshi streak</span>
                                    <span class="font-semibold text-orange-500">🔥 {{ results?.best_streak || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">O'rtacha javob vaqti</span>
                                    <span class="font-semibold text-gray-800 dark:text-white">{{ results?.average_response_time || 0 }}s</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <button @click="goBack"
                                        class="flex-1 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    ← Orqaga
                                </button>
                                <button @click="playAgain"
                                        class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
                                    🔄 Qayta o'ynash
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </StudentLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.95);
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateY(100%);
}
</style>
