<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: Object,
    config: Object
})

// Game State
const sessionId = ref(null)
const currentItem = ref(null)
const currentIndex = ref(0)
const totalItems = ref(0)
const score = ref(0)
const streak = ref(0)
const correctCount = ref(0)

// UI State
const isLoading = ref(true)
const isPlaying = ref(false)
const isSlowMode = ref(false)
const userAnswer = ref('')
const showResult = ref(false)
const lastResult = ref(null)
const showSessionComplete = ref(false)
const sessionResult = ref(null)
const replaysRemaining = ref(5)
const hintsRemaining = ref(2)
const currentHint = ref('')

// TTS
const speechSynthesis = ref(null)
const voices = ref([])
const selectedVoice = ref(null)

// Initialize game
onMounted(async () => {
    speechSynthesis.value = window.speechSynthesis

    // Load voices
    const loadVoices = () => {
        voices.value = speechSynthesis.value.getVoices()
        selectedVoice.value = voices.value.find(v => v.lang.startsWith('en')) || voices.value[0]
    }

    if (speechSynthesis.value.onvoiceschanged !== undefined) {
        speechSynthesis.value.onvoiceschanged = loadVoices
    }
    loadVoices()

    await startGame()
})

onUnmounted(() => {
    if (speechSynthesis.value) {
        speechSynthesis.value.cancel()
    }
})

// Start game session
const startGame = async () => {
    isLoading.value = true
    try {
        const response = await axios.post(`/student/english/games/dictation/start/${props.level.number}`)
        if (response.data.success) {
            sessionId.value = response.data.session_id
            currentItem.value = response.data.current_item
            currentIndex.value = 0
            totalItems.value = response.data.total_items
            score.value = 0
            streak.value = 0
            correctCount.value = 0
            replaysRemaining.value = props.config?.settings?.max_replays || 5
            hintsRemaining.value = props.config?.settings?.max_hints_per_item || 2
            currentHint.value = ''
        }
    } catch (error) {
        console.error('Failed to start game:', error)
    } finally {
        isLoading.value = false
    }
}

// Play audio
const playAudio = () => {
    if (!currentItem.value || !speechSynthesis.value) return

    speechSynthesis.value.cancel()

    const utterance = new SpeechSynthesisUtterance(currentItem.value.text)
    utterance.voice = selectedVoice.value
    utterance.rate = isSlowMode.value ? 0.6 : (props.config?.settings?.tts_rate || 0.9)
    utterance.pitch = 1
    utterance.lang = props.config?.settings?.tts_lang || 'en-US'

    utterance.onstart = () => {
        isPlaying.value = true
    }

    utterance.onend = () => {
        isPlaying.value = false
    }

    speechSynthesis.value.speak(utterance)

    if (replaysRemaining.value > 0) {
        recordReplay()
    }
}

// Record replay usage
const recordReplay = async () => {
    try {
        const response = await axios.post('/student/english/games/dictation/replay', {
            session_id: sessionId.value
        })
        if (response.data.success) {
            replaysRemaining.value = response.data.replays_remaining
        }
    } catch (error) {
        console.error('Failed to record replay:', error)
    }
}

// Toggle slow mode
const toggleSlowMode = () => {
    isSlowMode.value = !isSlowMode.value
}

// Use hint
const useHint = async () => {
    if (hintsRemaining.value <= 0) return

    try {
        const response = await axios.post('/student/english/games/dictation/hint', {
            session_id: sessionId.value
        })
        if (response.data.success) {
            currentHint.value = response.data.hint
            hintsRemaining.value = response.data.hints_remaining
        }
    } catch (error) {
        console.error('Failed to get hint:', error)
    }
}

// Check answer
const checkAnswer = async () => {
    if (!userAnswer.value.trim()) return

    try {
        const response = await axios.post('/student/english/games/dictation/check', {
            session_id: sessionId.value,
            answer: userAnswer.value.trim()
        })

        if (response.data.success) {
            lastResult.value = response.data
            showResult.value = true
            score.value = response.data.current_score
            streak.value = response.data.streak

            if (response.data.is_correct) {
                correctCount.value++
            }

            setTimeout(() => {
                if (response.data.is_last_item) {
                    completeSession()
                } else {
                    nextItem(response.data)
                }
            }, 2500)
        }
    } catch (error) {
        console.error('Failed to check answer:', error)
    }
}

// Skip item
const skipItem = async () => {
    try {
        const response = await axios.post('/student/english/games/dictation/skip', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            lastResult.value = {
                is_correct: false,
                is_perfect: false,
                accuracy: 0,
                points: 0,
                correct_answer: response.data.correct_answer,
                skipped: true
            }
            showResult.value = true
            streak.value = 0

            setTimeout(() => {
                if (response.data.is_last_item) {
                    completeSession()
                } else {
                    nextItem(response.data)
                }
            }, 2000)
        }
    } catch (error) {
        console.error('Failed to skip:', error)
    }
}

// Move to next item
const nextItem = (data) => {
    showResult.value = false
    lastResult.value = null
    userAnswer.value = ''
    currentHint.value = ''
    currentItem.value = data.next_item
    currentIndex.value = data.current_index
    hintsRemaining.value = props.config?.settings?.max_hints_per_item || 2
    replaysRemaining.value = props.config?.settings?.max_replays || 5
}

// Complete session
const completeSession = async () => {
    try {
        const response = await axios.post('/student/english/games/dictation/complete', {
            session_id: sessionId.value
        })

        if (response.data.success) {
            sessionResult.value = response.data.result
            showSessionComplete.value = true
            showResult.value = false
        }
    } catch (error) {
        console.error('Failed to complete session:', error)
    }
}

// Restart game
const restartGame = () => {
    showSessionComplete.value = false
    sessionResult.value = null
    startGame()
}

// Go back to levels
const goBack = () => {
    router.visit('/student/english/games/dictation')
}

// Progress percentage
const progressPercent = computed(() => {
    if (totalItems.value === 0) return 0
    return Math.round((currentIndex.value / totalItems.value) * 100)
})

// Get content type label
const getContentTypeLabel = computed(() => {
    const types = {
        'words': "So'z",
        'phrases': 'Ibora',
        'sentences': 'Gap',
        'paragraphs': 'Paragraf'
    }
    return types[currentItem.value?.content_type] || 'Matn'
})

// Handle Enter key
const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey && !showResult.value) {
        e.preventDefault()
        checkAnswer()
    }
}

const getLevelColor = () => {
    const colors = {
        'A1': '#10b981',
        'A2': '#3b82f6',
        'B1': '#8b5cf6',
        'B2': '#f97316',
        'C1': '#ec4899',
        'C2': '#ef4444'
    }
    return colors[props.level?.cefr_level] || '#8b5cf6'
}
</script>

<template>
    <Head :title="`Diktant - ${level?.name_uz || level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6 font-sans">

            <!-- Loading State -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center min-h-[60vh]">
                <div class="w-16 h-16 border-4 border-violet-200 dark:border-violet-800 border-t-violet-600 rounded-full animate-spin mb-4"></div>
                <p class="text-gray-600 dark:text-gray-400 font-medium">O'yin yuklanmoqda...</p>
            </div>

            <!-- Game Content -->
            <div v-else class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <Link href="/student/english/games/dictation"
                            class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </div>
                        </Link>

                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold text-white" :style="{ background: getLevelColor() }">
                                {{ level?.cefr_level }}
                            </span>
                            <span class="text-gray-900 dark:text-white font-bold">{{ level?.name_uz || level?.name }}</span>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5 text-amber-500">
                                <span class="text-lg">⭐</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ score }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-orange-500">
                                <span class="text-lg">🔥</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ streak }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-2">
                            <span>{{ currentIndex + 1 }} / {{ totalItems }}</span>
                            <span>{{ progressPercent }}%</span>
                        </div>
                        <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :style="{ width: progressPercent + '%', background: `linear-gradient(90deg, ${getLevelColor()}, ${getLevelColor()}dd)` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Game Area -->
                <div v-if="!showSessionComplete" class="space-y-6">
                    <!-- Content Type Badge -->
                    <div class="text-center">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400 rounded-full text-sm font-medium">
                            🎧 {{ getContentTypeLabel }}
                        </span>
                    </div>

                    <!-- Audio Player Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-6">
                        <div class="text-center">
                            <!-- Audio Visual -->
                            <div class="w-32 h-32 mx-auto mb-6 rounded-full flex flex-col items-center justify-center relative"
                                :class="isPlaying ? 'bg-gradient-to-br from-violet-500 to-purple-600' : 'bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30'">
                                <!-- Animated Rings -->
                                <div v-if="isPlaying" class="absolute inset-0 rounded-full border-4 border-violet-400/50 animate-ping"></div>
                                <div v-if="isPlaying" class="absolute inset-2 rounded-full border-2 border-purple-400/50 animate-ping" style="animation-delay: 0.2s;"></div>

                                <!-- Audio Bars -->
                                <div class="flex items-end gap-1 mb-2" v-if="isPlaying">
                                    <div class="w-1.5 bg-white rounded-full animate-bounce" style="height: 16px; animation-delay: 0s;"></div>
                                    <div class="w-1.5 bg-white rounded-full animate-bounce" style="height: 24px; animation-delay: 0.1s;"></div>
                                    <div class="w-1.5 bg-white rounded-full animate-bounce" style="height: 32px; animation-delay: 0.2s;"></div>
                                    <div class="w-1.5 bg-white rounded-full animate-bounce" style="height: 24px; animation-delay: 0.3s;"></div>
                                    <div class="w-1.5 bg-white rounded-full animate-bounce" style="height: 16px; animation-delay: 0.4s;"></div>
                                </div>
                                <span class="text-4xl" :class="isPlaying ? '' : 'grayscale-0'">🎧</span>
                            </div>

                            <p class="text-gray-500 dark:text-gray-400 mb-4">
                                {{ isPlaying ? 'Tinglayapsiz...' : 'Tinglash uchun tugmani bosing' }}
                            </p>

                            <!-- Audio Controls -->
                            <div class="flex items-center justify-center gap-3 mb-4">
                                <button @click="playAudio" :disabled="isPlaying"
                                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span>{{ isPlaying ? '⏸️' : '▶️' }}</span>
                                    {{ isPlaying ? 'Ijro etilmoqda' : 'Tinglash' }}
                                </button>

                                <button @click="toggleSlowMode"
                                    class="flex items-center gap-2 px-4 py-3 rounded-xl font-bold transition-all"
                                    :class="isSlowMode
                                        ? 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 border-2 border-cyan-500'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-2 border-transparent hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    🐢 {{ isSlowMode ? 'Sekin' : 'Oddiy' }}
                                </button>
                            </div>

                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Qayta tinglash: {{ replaysRemaining }} ta qoldi
                            </p>
                        </div>
                    </div>

                    <!-- Translation Card -->
                    <div v-if="currentItem?.translation_uz && config?.settings?.show_translation"
                        class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">🇺🇿</span>
                            <div>
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Tarjima</p>
                                <p class="text-blue-900 dark:text-blue-200">{{ currentItem.translation_uz }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hint Card -->
                    <div v-if="currentHint"
                        class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">💡</span>
                            <div>
                                <p class="text-xs text-amber-600 dark:text-amber-400 font-medium mb-1">Maslahat</p>
                                <p class="text-amber-900 dark:text-amber-200 font-mono">{{ currentHint }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Answer Input -->
                    <div v-if="!showResult" class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Eshitganingizni yozing:
                        </label>
                        <textarea
                            v-model="userAnswer"
                            @keydown="handleKeydown"
                            class="w-full p-4 bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-0 transition resize-none"
                            :placeholder="currentItem?.content_type === 'words' ? 'So\'zni yozing...' : 'Matnni yozing...'"
                            rows="3"
                        ></textarea>

                        <div class="flex flex-wrap gap-3 mt-4">
                            <button @click="useHint" :disabled="hintsRemaining <= 0"
                                class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-4 py-3 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-xl font-bold hover:bg-amber-200 dark:hover:bg-amber-900/50 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                💡 Maslahat ({{ hintsRemaining }})
                            </button>

                            <button @click="skipItem"
                                class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                ⏭️ O'tkazish
                            </button>

                            <button @click="checkAnswer" :disabled="!userAnswer.trim()"
                                class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-lg">
                                ✓ Tekshirish
                            </button>
                        </div>
                    </div>

                    <!-- Result Display -->
                    <div v-if="showResult && lastResult"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border-2 overflow-hidden animate-scale-in"
                        :class="lastResult.is_correct ? 'border-green-500' : 'border-red-500'">
                        <div class="p-6 text-center"
                            :class="lastResult.is_correct ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                            <div class="text-6xl mb-3">
                                {{ lastResult.is_perfect ? '🏆' : (lastResult.is_correct ? '✅' : '❌') }}
                            </div>
                            <h3 class="text-2xl font-black mb-2"
                                :class="lastResult.is_correct ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                                {{ lastResult.is_perfect ? 'Mukammal!' : (lastResult.is_correct ? "To'g'ri!" : "Noto'g'ri") }}
                            </h3>
                            <div v-if="lastResult.accuracy !== undefined" class="text-gray-600 dark:text-gray-400 mb-2">
                                Aniqlik: {{ lastResult.accuracy }}%
                            </div>
                            <div class="text-2xl font-bold text-amber-500">
                                +{{ lastResult.points }} ball
                            </div>
                        </div>

                        <div class="p-4 space-y-3">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">To'g'ri javob:</p>
                                <p class="text-green-900 dark:text-green-200 font-medium">{{ lastResult.correct_answer }}</p>
                            </div>
                            <div v-if="userAnswer && !lastResult.skipped" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">Sizning javobingiz:</p>
                                <p class="text-gray-900 dark:text-gray-200">{{ userAnswer }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Session Complete Modal -->
                <div v-if="showSessionComplete && sessionResult"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full shadow-2xl overflow-hidden animate-scale-in">
                        <!-- Header -->
                        <div class="relative p-6 text-center"
                            :class="sessionResult.accuracy >= 80
                                ? 'bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-600'
                                : 'bg-gradient-to-br from-gray-600 to-gray-700'">
                            <!-- Celebration particles -->
                            <div v-if="sessionResult.accuracy >= 80" class="absolute inset-0 overflow-hidden">
                                <div class="absolute -top-4 -left-4 w-24 h-24 bg-white/20 rounded-full blur-xl animate-pulse"></div>
                                <div class="absolute -bottom-4 -right-4 w-32 h-32 bg-pink-400/20 rounded-full blur-xl animate-pulse" style="animation-delay: 0.5s;"></div>
                            </div>

                            <div class="relative">
                                <div class="text-7xl mb-3 animate-bounce">
                                    {{ sessionResult.stars === 3 ? '🏆' : (sessionResult.stars >= 1 ? '⭐' : '📝') }}
                                </div>
                                <h2 class="text-3xl font-black text-white mb-1">
                                    {{ sessionResult.accuracy === 100 ? 'Mukammal!' : (sessionResult.accuracy >= 80 ? 'Ajoyib!' : (sessionResult.accuracy >= 50 ? 'Yaxshi!' : 'Harakat qiling!')) }}
                                </h2>
                                <p class="text-white/70">
                                    {{ sessionResult.correct_count }} / {{ sessionResult.total_items }} to'g'ri
                                </p>
                            </div>
                        </div>

                        <!-- Stars -->
                        <div class="flex justify-center gap-2 py-4 bg-gray-50 dark:bg-gray-900">
                            <span v-for="i in 3" :key="i" class="text-4xl transition-all duration-500"
                                :class="i <= sessionResult.stars ? 'text-yellow-400 drop-shadow-lg scale-110' : 'text-gray-300 dark:text-gray-600'">
                                ★
                            </span>
                        </div>

                        <!-- Stats -->
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Aniqlik</p>
                                    <p class="text-2xl font-black text-blue-700 dark:text-blue-300">{{ sessionResult.accuracy }}%</p>
                                </div>
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-3 text-center">
                                    <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Ball</p>
                                    <p class="text-2xl font-black text-purple-700 dark:text-purple-300">{{ sessionResult.total_score }}</p>
                                </div>
                                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-3 text-center">
                                    <p class="text-xs text-orange-600 dark:text-orange-400 font-medium">Streak</p>
                                    <p class="text-2xl font-black text-orange-700 dark:text-orange-300">{{ sessionResult.max_streak }}</p>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 text-center">
                                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">Vaqt</p>
                                    <p class="text-2xl font-black text-green-700 dark:text-green-300">{{ Math.floor(sessionResult.duration / 60) }}:{{ String(sessionResult.duration % 60).padStart(2, '0') }}</p>
                                </div>
                            </div>

                            <!-- Rewards -->
                            <div class="flex justify-center gap-6 py-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">✨</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400">+{{ sessionResult.rewards?.xp_earned || 0 }} XP</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">🪙</span>
                                    <span class="font-bold text-yellow-600 dark:text-yellow-400">+{{ sessionResult.rewards?.coins_earned || 0 }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <button @click="goBack"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    Darajalar
                                </button>
                                <button @click="restartGame"
                                    class="flex-1 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-bold hover:from-violet-700 hover:to-purple-700 transition shadow-lg">
                                    Qayta o'ynash
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes scale-in {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}
</style>
