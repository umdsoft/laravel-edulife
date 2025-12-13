<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    game: Object
})

// Game state
const sessionId = ref(props.game?.session_id || '')
const words = ref(props.game?.words || [])
const currentIndex = ref(0)
const score = ref(0)
const wordsCorrect = ref(0)
const wordsWrong = ref(0)
const wordsSkipped = ref(0)
const userInput = ref('')
const isChecking = ref(false)
const showFeedback = ref(false)
const showWordInfo = ref(false)
const lastResult = ref(null)
const gameOver = ref(false)
const gameResult = ref(null)
const showCloseConfirm = ref(false)
const showIntro = ref(true)
const wordStartTime = ref(Date.now())
const streak = ref(0)
const maxStreak = ref(0)

// Timer
const timeLeft = ref(props.game?.settings?.time_limit || 60)
let timerInterval = null
let gameStarted = ref(false)

// Input ref
const inputRef = ref(null)

// Computed
const currentWord = computed(() => words.value[currentIndex.value])
const level = computed(() => props.game?.level || {})
const settings = computed(() => props.game?.settings || {})
const progressPercent = computed(() => {
    const required = settings.value.words_required || 10
    return Math.min(100, (wordsCorrect.value / required) * 100)
})
const wordsProgress = computed(() => {
    return `${wordsCorrect.value}/${settings.value.words_required || 8}`
})

// Get accent color based on level
const accentColor = computed(() => level.value.color || '#6366F1')

// Format time
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

// Mode helpers
const getModeIcon = (mode) => {
    const icons = { type_word: '🔤', listen_type: '🎧', unscramble: '🔀', translation: '🌐' }
    return icons[mode] || '📝'
}

const getModeTitle = (mode) => {
    const names = { 
        type_word: "So'zni yozing", 
        listen_type: "Eshitib yozing", 
        unscramble: "Harflarni joylashtiring", 
        translation: "Tarjima qiling" 
    }
    return names[mode] || ''
}

const getModeInstruction = (mode) => {
    const instructions = { 
        type_word: "Quyidagi inglizcha so'zni yozing", 
        listen_type: "Audio tugmasini bosing va eshitgan so'zingizni yozing", 
        unscramble: "Aralashgan harflardan to'g'ri so'zni tuzing", 
        translation: "O'zbekcha so'zning inglizcha tarjimasini yozing" 
    }
    return instructions[mode] || ''
}

const getPlaceholder = () => {
    const mode = currentWord.value?.mode
    const placeholders = {
        type_word: "So'zni yozing...",
        listen_type: "Eshitgan so'zni yozing...",
        unscramble: "To'g'ri so'zni yozing...",
        translation: "Inglizcha tarjimasini yozing..."
    }
    return placeholders[mode] || 'Javobingizni yozing...'
}

// TTS
const speakWord = () => {
    if (!currentWord.value) return
    const text = currentWord.value.tts_text || currentWord.value.display || currentWord.value.answer
    if (!text) return
    
    speechSynthesis.cancel()
    const utterance = new SpeechSynthesisUtterance(text)
    utterance.lang = 'en-US'
    utterance.rate = 0.85
    speechSynthesis.speak(utterance)
}

// Start game
const startGame = () => {
    showIntro.value = false
    gameStarted.value = true
    startTimer()
    wordStartTime.value = Date.now()
    nextTick(() => {
        inputRef.value?.focus()
        if (currentWord.value?.mode === 'listen_type') {
            setTimeout(speakWord, 500)
        }
    })
}

// Submit answer
const submitAnswer = async () => {
    if (!userInput.value.trim() || isChecking.value || !currentWord.value) return
    
    isChecking.value = true
    const responseTime = Date.now() - wordStartTime.value
    
    try {
        const response = await axios.post('/api/v1/english/word-blitz/check-answer', {
            session_id: sessionId.value,
            word_id: currentWord.value.id,
            answer: userInput.value.trim(),
            response_time_ms: responseTime
        })
        
        lastResult.value = response.data
        score.value = response.data.current_score
        
        if (response.data.is_correct) {
            wordsCorrect.value = response.data.words_correct
            streak.value++
            if (streak.value > maxStreak.value) maxStreak.value = streak.value
        } else {
            wordsWrong.value++
            streak.value = 0
        }
        
        showFeedback.value = true
        showWordInfo.value = !response.data.is_correct
        
        setTimeout(() => {
            showFeedback.value = false
            showWordInfo.value = false
            nextWord()
        }, response.data.is_correct ? 600 : 1800)
        
    } catch (error) {
        console.error('Check error:', error)
    } finally {
        isChecking.value = false
    }
}

// Skip word
const skipWord = async () => {
    if (isChecking.value || !currentWord.value) return
    
    isChecking.value = true
    streak.value = 0
    
    try {
        const response = await axios.post('/api/v1/english/word-blitz/skip', {
            session_id: sessionId.value,
            word_id: currentWord.value.id
        })
        
        wordsSkipped.value++
        lastResult.value = response.data
        showWordInfo.value = true
        
        setTimeout(() => {
            showWordInfo.value = false
            nextWord()
        }, 1200)
        
    } catch (error) {
        console.error('Skip error:', error)
    } finally {
        isChecking.value = false
    }
}

// Next word
const nextWord = () => {
    userInput.value = ''
    wordStartTime.value = Date.now()
    
    if (currentIndex.value < words.value.length - 1) {
        currentIndex.value++
        nextTick(() => {
            inputRef.value?.focus()
            if (currentWord.value?.mode === 'listen_type') {
                setTimeout(speakWord, 400)
            }
        })
    } else {
        endGame()
    }
}

// End game
const endGame = async () => {
    if (gameOver.value) return
    clearInterval(timerInterval)
    
    const timeSpent = (props.game?.settings?.time_limit || 60) - timeLeft.value
    
    try {
        const response = await axios.post('/api/v1/english/word-blitz/complete', {
            session_id: sessionId.value,
            time_spent: timeSpent
        })
        
        gameResult.value = response.data
        gameOver.value = true
        
    } catch (error) {
        console.error('Complete error:', error)
        gameOver.value = true
        gameResult.value = { passed: false, score: score.value, stars: 0 }
    }
}

// Handle close button
const handleClose = () => {
    if (gameOver.value) {
        goBack()
    } else {
        showCloseConfirm.value = true
    }
}

// Confirm close
const confirmClose = async () => {
    try {
        await axios.post('/api/v1/english/word-blitz/abandon', {
            session_id: sessionId.value
        })
    } catch (e) {}
    goBack()
}

// Play again
const playAgain = () => {
    router.visit(`/student/english/games/word-blitz/play/${level.value.number}`)
}

// Next level
const nextLevel = () => {
    const nextNum = level.value.number + 1
    if (nextNum <= 10) {
        router.visit(`/student/english/games/word-blitz/play/${nextNum}`)
    } else {
        goBack()
    }
}

// Go back
const goBack = () => {
    router.visit('/student/english/games/word-blitz')
}

// Timer
const startTimer = () => {
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--
        } else {
            endGame()
        }
    }, 1000)
}

// Auto-speak for listen mode
watch(currentWord, (word) => {
    if (word?.mode === 'listen_type' && gameStarted.value) {
        setTimeout(speakWord, 400)
    }
})

onUnmounted(() => {
    clearInterval(timerInterval)
    speechSynthesis.cancel()
})
</script>

<template>
    <Head :title="`Word Blitz - Level ${level.number}`" />
    
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 flex flex-col">
        <!-- Intro Modal -->
        <div 
            v-if="showIntro"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        >
            <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden animate-scale-in shadow-2xl border border-gray-100">
                <!-- Header with accent color -->
                <div 
                    class="p-6 text-center text-white relative overflow-hidden"
                    :style="{ background: `linear-gradient(135deg, ${accentColor}, ${accentColor}DD)` }"
                >
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
                    <div class="relative">
                        <div class="text-6xl mb-3">⚡</div>
                        <h2 class="text-2xl font-black">Level {{ level.number }}</h2>
                        <p class="opacity-90 text-lg">{{ level.name }}</p>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <!-- Level Stats -->
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-4 text-center border border-blue-200">
                            <div class="text-3xl mb-1">⏱</div>
                            <div class="text-2xl font-black text-blue-700">{{ settings.time_limit }}s</div>
                            <div class="text-xs text-blue-600 font-medium">Vaqt</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-4 text-center border border-purple-200">
                            <div class="text-3xl mb-1">📝</div>
                            <div class="text-2xl font-black text-purple-700">{{ settings.words_required }}</div>
                            <div class="text-xs text-purple-600 font-medium">So'z kerak</div>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-4 text-center border border-amber-200">
                            <div class="text-3xl mb-1">📚</div>
                            <div class="text-2xl font-black text-amber-700">{{ level.cefr }}</div>
                            <div class="text-xs text-amber-600 font-medium">Daraja</div>
                        </div>
                    </div>
                    
                    <!-- Instructions -->
                    <div class="bg-gradient-to-r from-indigo-50 to-violet-50 rounded-2xl p-5 mb-6 border border-indigo-100">
                        <h3 class="font-bold text-indigo-900 mb-3 flex items-center gap-2 text-lg">
                            <span>📋</span> Qanday o'ynash:
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-indigo-500 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">1</div>
                                <p class="text-indigo-800">So'zni ko'ring yoki <strong>eshiting</strong></p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-indigo-500 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">2</div>
                                <p class="text-indigo-800">Input maydoniga <strong>to'g'ri javobni</strong> yozing</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-indigo-500 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">3</div>
                                <p class="text-indigo-800"><kbd class="px-2 py-0.5 bg-white rounded text-indigo-600 font-mono text-sm">Enter</kbd> bosing yoki <strong>Tekshirish</strong> tugmasini</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Game Modes -->
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2 font-medium">Bu levelda:</p>
                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="mode in settings.modes" 
                                :key="mode"
                                class="inline-flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-full text-sm transition-colors"
                            >
                                <span>{{ getModeIcon(mode) }}</span>
                                <span class="text-gray-700 font-medium">{{ getModeTitle(mode) }}</span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Start Button -->
                    <button 
                        @click="startGame"
                        class="w-full py-4 text-white font-bold text-lg rounded-2xl transition-all hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]"
                        :style="{ background: `linear-gradient(135deg, ${accentColor}, ${accentColor}CC)` }"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <span>O'yinni Boshlash</span>
                            <span class="text-xl">🚀</span>
                        </span>
                    </button>
                    
                    <!-- Back Link -->
                    <button 
                        @click="goBack"
                        class="w-full mt-3 py-2 text-gray-500 hover:text-gray-700 text-sm transition font-medium"
                    >
                        ← Orqaga qaytish
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Game Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <!-- Close Button -->
                    <button 
                        @click="handleClose"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    
                    <!-- Score -->
                    <div class="flex items-center gap-2 bg-gradient-to-r from-amber-100 to-yellow-100 px-4 py-2 rounded-xl border border-amber-200">
                        <span class="text-amber-600">⚡</span>
                        <span class="text-amber-700 font-bold text-lg">{{ score }}</span>
                    </div>
                    
                    <!-- Timer -->
                    <div 
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-xl font-bold transition-all',
                            timeLeft <= 10 
                                ? 'bg-red-100 text-red-700 animate-pulse border border-red-200' 
                                : timeLeft <= 30 
                                    ? 'bg-orange-100 text-orange-700 border border-orange-200' 
                                    : 'bg-blue-100 text-blue-700 border border-blue-200'
                        ]"
                    >
                        <span>⏱</span>
                        <span class="text-lg">{{ formatTime(timeLeft) }}</span>
                    </div>
                    
                    <!-- Progress -->
                    <div class="flex items-center gap-2 bg-green-100 px-4 py-2 rounded-xl border border-green-200">
                        <span class="text-green-600">✓</span>
                        <span class="text-green-700 font-bold">{{ wordsProgress }}</span>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-3 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-green-400 to-emerald-500 transition-all duration-500 rounded-full"
                        :style="{ width: `${progressPercent}%` }"
                    />
                </div>
            </div>
        </header>
        
        <!-- Game Content -->
        <main v-if="!gameOver && !showIntro" class="flex-1 flex flex-col items-center justify-center px-4 py-8">
            <div class="w-full max-w-2xl">
                <!-- Streak Badge -->
                <div 
                    v-if="streak >= 2"
                    class="flex justify-center mb-4"
                >
                    <span class="inline-flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-500 text-white px-4 py-1.5 rounded-full text-sm font-bold shadow-lg animate-bounce">
                        🔥 {{ streak }} streak!
                    </span>
                </div>
                
                <!-- Mode Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-6 overflow-hidden">
                    <!-- Mode Header -->
                    <div 
                        class="px-5 py-3 border-b border-gray-100 flex items-center justify-center gap-3"
                        :style="{ background: `linear-gradient(135deg, ${accentColor}15, ${accentColor}08)` }"
                    >
                        <span class="text-2xl">{{ getModeIcon(currentWord?.mode) }}</span>
                        <div class="text-center">
                            <p class="font-bold text-gray-900">{{ getModeTitle(currentWord?.mode) }}</p>
                            <p class="text-sm text-gray-500">{{ getModeInstruction(currentWord?.mode) }}</p>
                        </div>
                    </div>
                    
                    <!-- Word Display -->
                    <div class="p-8 min-h-[200px] flex flex-col items-center justify-center">
                        <!-- Type Word Mode -->
                        <div v-if="currentWord?.mode === 'type_word'" class="text-center">
                            <p 
                                class="text-5xl md:text-7xl font-black tracking-wider mb-4"
                                :style="{ color: accentColor }"
                            >
                                {{ currentWord.display }}
                            </p>
                            <div class="bg-gray-100 rounded-xl px-4 py-2 inline-block">
                                <span class="text-gray-500 text-sm">O'zbekcha:</span>
                                <span class="text-gray-700 font-medium ml-2">{{ currentWord.hint }}</span>
                            </div>
                        </div>
                        
                        <!-- Listen Mode -->
                        <div v-else-if="currentWord?.mode === 'listen_type'" class="text-center">
                            <button
                                @click="speakWord"
                                class="w-28 h-28 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 group mb-5"
                            >
                                <span class="text-5xl group-hover:scale-110 transition-transform">🔊</span>
                            </button>
                            <p class="text-gray-700 text-lg mb-2">So'zni eshiting va yozing</p>
                            <p class="text-gray-400">{{ currentWord.length }} ta harf</p>
                            <button
                                @click="speakWord"
                                class="mt-4 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-xl hover:bg-indigo-200 transition flex items-center gap-2 mx-auto font-medium"
                            >
                                <span>🔊</span> Qayta eshitish
                            </button>
                        </div>
                        
                        <!-- Unscramble Mode -->
                        <div v-else-if="currentWord?.mode === 'unscramble'" class="text-center">
                            <p class="text-gray-500 text-sm mb-4">Quyidagi harflardan so'z tuzing:</p>
                            <div class="flex justify-center gap-2 flex-wrap mb-5">
                                <span
                                    v-for="(letter, idx) in currentWord.scrambled?.split('')"
                                    :key="idx"
                                    class="w-12 h-14 md:w-14 md:h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex items-center justify-center text-2xl md:text-3xl font-black text-gray-800 shadow-md border border-gray-200 transform hover:scale-105 hover:-rotate-3 transition-all"
                                >
                                    {{ letter }}
                                </span>
                            </div>
                            <div class="bg-gray-100 rounded-xl px-4 py-2 inline-block">
                                <span class="text-gray-500 text-sm">Ma'nosi:</span>
                                <span class="text-gray-700 font-medium ml-2">{{ currentWord.hint }}</span>
                            </div>
                        </div>
                        
                        <!-- Translation Mode -->
                        <div v-else-if="currentWord?.mode === 'translation'" class="text-center">
                            <div class="mb-4">
                                <span class="text-sm bg-blue-100 text-blue-600 px-3 py-1 rounded-full font-medium">🇺🇿 O'zbekcha</span>
                            </div>
                            <p 
                                class="text-4xl md:text-5xl font-bold mb-4"
                                :style="{ color: accentColor }"
                            >
                                {{ currentWord.display }}
                            </p>
                            <p class="text-gray-500 bg-gray-100 px-4 py-2 rounded-xl inline-block">
                                {{ currentWord.hint }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Input Area -->
                <div class="relative mb-4">
                    <input
                        ref="inputRef"
                        v-model="userInput"
                        @keyup.enter="submitAnswer"
                        type="text"
                        :placeholder="getPlaceholder()"
                        class="w-full px-6 py-5 text-2xl text-center rounded-2xl bg-white border-2 border-gray-200 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 focus:outline-none uppercase tracking-widest transition-all shadow-sm"
                        :disabled="isChecking"
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    />
                    
                    <!-- Feedback Overlay -->
                    <div 
                        v-if="showFeedback"
                        :class="[
                            'absolute inset-0 rounded-2xl flex items-center justify-center transition-all animate-fade-in',
                            lastResult?.is_correct 
                                ? 'bg-green-500' 
                                : 'bg-red-500'
                        ]"
                    >
                        <div class="flex items-center gap-3 text-white">
                            <span class="text-4xl">{{ lastResult?.is_correct ? '✓' : '✗' }}</span>
                            <span class="text-2xl font-bold">
                                {{ lastResult?.is_correct ? 'To\'g\'ri!' : 'Noto\'g\'ri' }}
                            </span>
                            <span v-if="lastResult?.is_correct && lastResult?.time_bonus > 0" class="text-yellow-300 font-bold animate-pulse">
                                +{{ lastResult.time_bonus }} bonus!
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Word Info (on wrong/skip) -->
                <div 
                    v-if="showWordInfo && lastResult" 
                    class="bg-white rounded-2xl p-5 mb-4 text-center border-2 border-red-100 shadow-sm animate-fade-in"
                >
                    <p class="text-gray-500 text-sm mb-1">To'g'ri javob:</p>
                    <p class="text-2xl font-black text-red-600 mb-1">{{ lastResult.correct_answer }}</p>
                    <p class="text-gray-600">{{ lastResult.translation }}</p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-center gap-4">
                    <button
                        @click="skipWord"
                        :disabled="isChecking"
                        class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-all border border-gray-200 disabled:opacity-50 flex items-center gap-2 font-medium"
                    >
                        <span>O'tkazib yuborish</span>
                        <span>→</span>
                    </button>
                    <button
                        @click="submitAnswer"
                        :disabled="!userInput.trim() || isChecking"
                        class="px-8 py-3 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        :style="{ background: `linear-gradient(135deg, ${accentColor}, ${accentColor}DD)` }"
                    >
                        <span>Tekshirish</span>
                        <span class="opacity-70">↵</span>
                    </button>
                </div>
                
                <!-- Keyboard Hint -->
                <p class="text-center text-gray-400 text-sm mt-5">
                    💡 Javobni yozib <kbd class="px-2 py-1 bg-gray-100 rounded text-gray-600 font-mono">Enter</kbd> bosing
                </p>
            </div>
        </main>
        
        <!-- Game Complete Modal -->
        <div v-if="gameOver && gameResult" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden animate-scale-in shadow-2xl">
                <!-- Result Header -->
                <div 
                    :class="[
                        'p-8 text-center',
                        gameResult.passed ? 'bg-gradient-to-br from-green-500 to-emerald-600' : 'bg-gradient-to-br from-gray-500 to-gray-600'
                    ]"
                >
                    <div class="text-7xl mb-2 animate-bounce-in">
                        {{ gameResult.passed ? '🎉' : '😔' }}
                    </div>
                    <h2 class="text-3xl font-black text-white mb-1">
                        {{ gameResult.passed ? 'Tabriklaymiz!' : 'Harakat qiling' }}
                    </h2>
                    <p class="text-white/80">
                        {{ gameResult.passed ? 'Siz bu levelni muvaffaqiyatli o\'tdingiz!' : `Kamida ${gameResult.words_required} ta so'z kerak edi` }}
                    </p>
                </div>
                
                <!-- Stars -->
                <div class="py-6 flex justify-center gap-3">
                    <span 
                        v-for="i in 3" 
                        :key="i"
                        :class="[
                            'text-5xl transition-all duration-500',
                            i <= gameResult.stars 
                                ? 'text-yellow-400 scale-100 animate-star-pop' 
                                : 'text-gray-300 scale-90'
                        ]"
                        :style="{ animationDelay: `${i * 200}ms` }"
                    >
                        ⭐
                    </span>
                </div>
                
                <!-- Stats -->
                <div class="px-6 pb-6">
                    <div class="bg-gray-100 rounded-2xl p-4 mb-4">
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div>
                                <p class="text-2xl font-bold text-green-600">{{ gameResult.words_correct }}</p>
                                <p class="text-xs text-gray-500">To'g'ri</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-red-500">{{ gameResult.words_wrong }}</p>
                                <p class="text-xs text-gray-500">Xato</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-700">{{ gameResult.score }}</p>
                                <p class="text-xs text-gray-500">Ball</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-blue-600">{{ gameResult.accuracy }}%</p>
                                <p class="text-xs text-gray-500">Aniqlik</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rewards -->
                    <div v-if="gameResult.is_first_completion && gameResult.passed" class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-2xl p-4 mb-4 border border-yellow-200">
                        <p class="text-sm text-gray-600 mb-2 text-center">🎁 Mukofotlar:</p>
                        <div class="flex justify-center gap-8">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">⚡</span>
                                <span class="text-xl font-black text-purple-600">+{{ gameResult.xp_earned }} XP</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">🪙</span>
                                <span class="text-xl font-black text-yellow-600">+{{ gameResult.coins_earned }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else-if="gameResult.passed && !gameResult.is_first_completion" class="bg-gray-100 rounded-xl p-3 mb-4 text-center">
                        <p class="text-sm text-gray-500">Bu level uchun mukofot oldindan olingan</p>
                    </div>
                    
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 justify-center mb-4">
                        <span v-if="gameResult.is_new_record" class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                            🏆 Yangi rekord!
                        </span>
                        <span v-if="gameResult.next_level_unlocked" class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                            🔓 Keyingi level ochildi!
                        </span>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-3">
                        <button 
                            @click="playAgain"
                            class="py-3 bg-gray-200 font-bold rounded-xl hover:bg-gray-300 transition-all"
                        >
                            🔄 Qayta
                        </button>
                        <button 
                            v-if="gameResult.passed && gameResult.next_level_unlocked"
                            @click="nextLevel"
                            class="py-3 font-bold rounded-xl transition-all text-white"
                            :style="{ background: `linear-gradient(135deg, ${accentColor}, ${accentColor}CC)` }"
                        >
                            Keyingi →
                        </button>
                        <button 
                            v-else
                            @click="goBack"
                            class="py-3 bg-gray-800 text-white font-bold rounded-xl hover:bg-gray-700 transition-all"
                        >
                            Levellar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Close Confirmation -->
        <div 
            v-if="showCloseConfirm"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        >
            <div class="bg-white rounded-2xl p-6 max-w-sm w-full text-center animate-scale-in shadow-2xl">
                <div class="text-5xl mb-4">🚪</div>
                <h3 class="text-xl font-bold mb-2">O'yindan chiqmoqchimisiz?</h3>
                <p class="text-gray-600 mb-6">Hozirgi natijangiz saqlanmaydi va mukofot berilmaydi.</p>
                <div class="flex gap-3">
                    <button 
                        @click="showCloseConfirm = false"
                        class="flex-1 py-3 bg-gray-200 font-bold rounded-xl hover:bg-gray-300 transition"
                    >
                        Yo'q, davom etish
                    </button>
                    <button 
                        @click="confirmClose"
                        class="flex-1 py-3 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition"
                    >
                        Ha, chiqish
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-bounce-in {
    animation: bounceIn 0.5s ease-out;
}

@keyframes bounceIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.animate-star-pop {
    animation: starPop 0.5s ease-out forwards;
}

@keyframes starPop {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
