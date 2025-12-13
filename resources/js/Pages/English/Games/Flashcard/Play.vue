<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-100 to-gray-200">
        <!-- Header -->
        <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-md shadow-sm">
            <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
                <!-- Close Button -->
                <button @click="handleClose"
                    class="w-12 h-12 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Level Info -->
                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ session?.level?.icon }}</span>
                            <span class="font-bold text-gray-800">{{ session?.level?.name_uz }}</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                            :style="{ background: `${session?.level?.accent_color}20`, color: session?.level?.accent_color }">
                            {{ session?.level?.cefr_level }}
                        </span>
                    </div>
                </div>

                <!-- Progress & Score -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 bg-gray-100 rounded-full px-4 py-2">
                        <span class="text-sm font-medium text-gray-600">{{ currentIndex + 1 }}/{{ totalCards }}</span>
                        <div class="w-24 h-2 bg-gray-300 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-300"
                                :style="{ width: `${progressPercent}%` }" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-green-100 text-green-700 px-4 py-2 rounded-full">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="font-bold">{{ correctCount }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-2xl mx-auto px-6 py-8">
            <div v-if="!sessionComplete">
                <!-- Flashcard Container -->
                <div class="perspective-1000 mb-8">
                    <div @click="flipCard"
                        class="relative w-full cursor-pointer transition-transform duration-500 transform-style-preserve-3d"
                        :class="isFlipped ? 'rotate-y-180' : ''" style="min-height: 400px;">

                        <!-- Front Side -->
                        <div class="absolute inset-0 backface-hidden">
                            <div class="h-full bg-white rounded-3xl shadow-xl p-8 flex flex-col">
                                <!-- TTS Button -->
                                <button @click.stop="speakWord"
                                    class="absolute top-6 right-6 w-14 h-14 bg-blue-50 hover:bg-blue-100 rounded-2xl flex items-center justify-center transition group">
                                    <svg class="w-8 h-8 text-blue-500 group-hover:text-blue-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    </svg>
                                </button>

                                <!-- Card Content -->
                                <div class="flex-1 flex flex-col items-center justify-center text-center">
                                    <h2 class="text-5xl md:text-6xl font-black text-gray-800 mb-4">
                                        {{ currentCard?.word }}
                                    </h2>

                                    <p v-if="currentCard?.phonetic" class="text-xl text-gray-400 mb-4">
                                        {{ currentCard.phonetic }}
                                    </p>

                                    <span v-if="currentCard?.part_of_speech"
                                        class="inline-block bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-sm font-medium">
                                        {{ currentCard.part_of_speech }}
                                    </span>
                                </div>

                                <!-- Flip Hint -->
                                <div class="text-center">
                                    <p class="text-gray-400 text-sm flex items-center justify-center gap-2">
                                        <span class="inline-block animate-bounce">👆</span>
                                        Bosib aylantiring
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Back Side -->
                        <div class="absolute inset-0 backface-hidden rotate-y-180">
                            <div class="h-full rounded-3xl shadow-xl p-8 flex flex-col text-white"
                                :style="{ background: `linear-gradient(135deg, ${session?.level?.accent_color || '#6366F1'}, ${adjustColor(session?.level?.accent_color || '#6366F1', -30)})` }">

                                <!-- Translation -->
                                <div v-if="session?.features?.show_translation && currentCard?.translation_uz"
                                    class="flex-1 flex flex-col items-center justify-center text-center">
                                    <p class="text-white/60 text-sm mb-2">🇺🇿 O'zbekcha</p>
                                    <h3 class="text-4xl md:text-5xl font-black mb-6">
                                        {{ currentCard.translation_uz }}
                                    </h3>

                                    <!-- Definition -->
                                    <div v-if="session?.features?.show_definition && currentCard?.definition"
                                        class="bg-white/10 rounded-2xl p-4 mb-4 w-full max-w-md">
                                        <p class="text-white/60 text-sm mb-1">📖 Ta'rif</p>
                                        <p class="text-lg">{{ currentCard.definition }}</p>
                                    </div>

                                    <!-- Example -->
                                    <div v-if="session?.features?.show_example && currentCard?.example"
                                        class="bg-white/10 rounded-2xl p-4 w-full max-w-md">
                                        <p class="text-white/60 text-sm mb-1">💬 Misol</p>
                                        <p class="text-lg italic">"{{ currentCard.example }}"</p>
                                        <p v-if="currentCard.example_uz" class="text-white/60 text-sm mt-2">
                                            {{ currentCard.example_uz }}
                                        </p>
                                    </div>

                                    <!-- Synonyms -->
                                    <div v-if="session?.features?.show_synonyms && currentCard?.synonyms?.length"
                                        class="mt-4 flex flex-wrap justify-center gap-2">
                                        <span v-for="syn in currentCard.synonyms" :key="syn"
                                            class="bg-white/20 px-3 py-1 rounded-full text-sm">
                                            {{ syn }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Response Buttons -->
                <div v-if="isFlipped" class="space-y-4">
                    <p class="text-center text-gray-500 text-sm">Bu so'zni qanchalik bilasiz?</p>
                    <div class="grid grid-cols-4 gap-3">
                        <button @click="recordResponse('again')" :disabled="isProcessing"
                            class="py-4 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white rounded-2xl font-bold transition flex flex-col items-center gap-1 shadow-lg hover:shadow-xl">
                            <span class="text-2xl">😣</span>
                            <span class="text-sm">Qayta</span>
                        </button>
                        <button @click="recordResponse('hard')" :disabled="isProcessing"
                            class="py-4 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white rounded-2xl font-bold transition flex flex-col items-center gap-1 shadow-lg hover:shadow-xl">
                            <span class="text-2xl">😕</span>
                            <span class="text-sm">Qiyin</span>
                        </button>
                        <button @click="recordResponse('good')" :disabled="isProcessing"
                            class="py-4 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded-2xl font-bold transition flex flex-col items-center gap-1 shadow-lg hover:shadow-xl">
                            <span class="text-2xl">🙂</span>
                            <span class="text-sm">Yaxshi</span>
                        </button>
                        <button @click="recordResponse('easy')" :disabled="isProcessing"
                            class="py-4 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white rounded-2xl font-bold transition flex flex-col items-center gap-1 shadow-lg hover:shadow-xl">
                            <span class="text-2xl">😊</span>
                            <span class="text-sm">Oson</span>
                        </button>
                    </div>
                </div>

                <!-- Show Answer Button -->
                <div v-else class="text-center">
                    <button @click="flipCard"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-white hover:bg-gray-50 text-gray-800 font-bold rounded-2xl shadow-lg hover:shadow-xl transition">
                        <span class="text-xl">👀</span>
                        Javobni ko'rish
                    </button>
                </div>
            </div>

            <!-- Session Complete -->
            <div v-else class="py-8">
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                    <!-- Header with Stars -->
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-8 text-center text-white">
                        <div class="flex justify-center gap-3 mb-4">
                            <span v-for="i in 3" :key="i" class="text-5xl transition-all duration-500"
                                :class="i <= sessionResult?.stars ? 'drop-shadow-lg scale-110' : 'opacity-30'"
                                :style="{ transitionDelay: `${i * 200}ms` }">
                                ⭐
                            </span>
                        </div>
                        <h2 class="text-3xl font-black mb-2">Sessiya yakunlandi!</h2>
                        <p class="text-white/70">Ajoyib natija! 🎉</p>
                    </div>

                    <!-- Stats -->
                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="bg-green-50 rounded-2xl p-4">
                                <p class="text-3xl font-black text-green-600">{{ sessionResult?.correct_count || 0 }}
                                </p>
                                <p class="text-sm text-gray-500">To'g'ri</p>
                            </div>
                            <div class="bg-gray-50 rounded-2xl p-4">
                                <p class="text-3xl font-black text-gray-800">{{ sessionResult?.total_cards || 0 }}</p>
                                <p class="text-sm text-gray-500">Jami</p>
                            </div>
                            <div class="bg-blue-50 rounded-2xl p-4">
                                <p class="text-3xl font-black text-blue-600">{{ sessionResult?.accuracy || 0 }}%</p>
                                <p class="text-sm text-gray-500">Aniqlik</p>
                            </div>
                        </div>

                        <!-- Level Progress -->
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6">
                            <div class="flex justify-between items-center mb-3">
                                <p class="font-semibold text-gray-800">Level Progress</p>
                                <span class="text-sm font-bold text-indigo-600">
                                    {{ sessionResult?.level_progress?.cards_learned || 0 }}/{{
                                        sessionResult?.level_progress?.cards_required || 20 }}
                                </span>
                            </div>
                            <div class="h-4 bg-white rounded-full overflow-hidden shadow-inner">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-700"
                                    :style="{ width: `${Math.min(100, (sessionResult?.level_progress?.cards_learned / sessionResult?.level_progress?.cards_required) * 100)}%` }" />
                            </div>
                            <p class="text-xs text-gray-500 mt-2">O'zlashtirilgan kartalar</p>
                        </div>

                        <!-- Rewards -->
                        <div v-if="sessionResult?.is_new_record"
                            class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-2xl p-6 text-center">
                            <p class="text-sm text-gray-600 mb-3">🎁 Level mukofoti!</p>
                            <div class="flex justify-center gap-8">
                                <div class="flex items-center gap-2">
                                    <span class="text-3xl">⚡</span>
                                    <span class="text-2xl font-black text-purple-600">+{{ sessionResult?.xp_earned || 0
                                        }} XP</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-3xl">🪙</span>
                                    <span class="text-2xl font-black text-yellow-600">+{{ sessionResult?.coins_earned ||
                                        0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4">
                            <button @click="playAgain"
                                class="flex-1 py-4 bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-bold rounded-2xl hover:opacity-90 transition shadow-lg">
                                Davom etish
                            </button>
                            <button @click="goBack"
                                class="flex-1 py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl hover:bg-gray-200 transition">
                                Levellar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Close Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showCloseConfirm"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">⚠️</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Sessiyani tugatmoqchimisiz?</h3>
                    <p class="text-gray-500 mb-6">Progress saqlanadi.</p>
                    <div class="flex gap-4">
                        <button @click="confirmClose"
                            class="flex-1 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 font-bold transition">
                            Ha, chiqish
                        </button>
                        <button @click="showCloseConfirm = false"
                            class="flex-1 py-3 bg-gray-100 rounded-xl hover:bg-gray-200 font-bold transition">
                            Davom etish
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    session: Object
})

// State
const cards = ref(props.session?.cards || [])
const currentIndex = ref(0)
const isFlipped = ref(false)
const isProcessing = ref(false)
const correctCount = ref(0)
const showCloseConfirm = ref(false)
const sessionComplete = ref(false)
const sessionResult = ref(null)
const cardStartTime = ref(Date.now())

// Computed
const currentCard = computed(() => cards.value[currentIndex.value])
const totalCards = computed(() => cards.value.length)
const progressPercent = computed(() => ((currentIndex.value) / totalCards.value) * 100)

const adjustColor = (hex, amount) => {
    if (!hex) return '#6366F1'
    const num = parseInt(hex.replace('#', ''), 16)
    const r = Math.max(0, Math.min(255, (num >> 16) + amount))
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount))
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount))
    return `#${(1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1)}`
}

// Flip card
const flipCard = () => {
    if (!isFlipped.value) {
        isFlipped.value = true
    }
}

// Speak word using TTS
const speakWord = () => {
    if (!currentCard.value) return
    speechSynthesis.cancel()
    const utterance = new SpeechSynthesisUtterance(currentCard.value.word)
    utterance.lang = 'en-US'
    utterance.rate = 0.9
    speechSynthesis.speak(utterance)
}

// Record response
const recordResponse = async (response) => {
    if (isProcessing.value) return
    isProcessing.value = true
    const responseTime = Date.now() - cardStartTime.value

    try {
        await axios.post('/student/english/games/flashcard/response', {
            session_id: props.session.session_id,
            card_id: currentCard.value.id,
            response: response,
            response_time_ms: responseTime,
        })

        if (response === 'good' || response === 'easy') {
            correctCount.value++
        }

        if (currentIndex.value < totalCards.value - 1) {
            nextCard()
        } else {
            await completeSession()
        }
    } catch (error) {
        console.error('Response error:', error)
    } finally {
        isProcessing.value = false
    }
}

// Move to next card
const nextCard = () => {
    currentIndex.value++
    isFlipped.value = false
    cardStartTime.value = Date.now()
    if (props.session?.features?.tts_auto_play) {
        setTimeout(speakWord, 300)
    }
}

// Complete session
const completeSession = async () => {
    try {
        const result = await axios.post('/student/english/games/flashcard/complete', {
            session_id: props.session.session_id,
        })
        sessionResult.value = result.data
        sessionComplete.value = true
    } catch (error) {
        console.error('Complete error:', error)
        sessionComplete.value = true
    }
}

// Handle close button
const handleClose = () => {
    if (sessionComplete.value) {
        goBack()
    } else {
        showCloseConfirm.value = true
    }
}

// Confirm close
const confirmClose = async () => {
    try {
        await axios.post('/student/english/games/flashcard/abandon', {
            session_id: props.session.session_id,
        })
    } catch (e) { }
    goBack()
}

// Play again
const playAgain = () => {
    router.visit(`/student/english/games/flashcard/play/${props.session.level.number}`)
}

// Go back
const goBack = () => {
    router.visit('/student/english/games/flashcard')
}

// Lifecycle
onMounted(() => {
    if (props.session?.features?.tts_auto_play) {
        setTimeout(speakWord, 500)
    }
})

onUnmounted(() => {
    speechSynthesis.cancel()
})
</script>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}

.transform-style-preserve-3d {
    transform-style: preserve-3d;
}

.backface-hidden {
    backface-visibility: hidden;
}

.rotate-y-180 {
    transform: rotateY(180deg);
}
</style>
