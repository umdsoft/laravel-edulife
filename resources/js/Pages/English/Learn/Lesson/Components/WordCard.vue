<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useTTS } from '@/Composables/useTTS'
import SpeakButton from '@/Components/SpeakButton.vue'
import uz from '@/Lang/uz'

const props = defineProps({
    word: {
        type: Object,
        required: true
    },
    currentIndex: {
        type: Number,
        default: 0
    },
    totalWords: {
        type: Number,
        default: 1
    },
    autoSpeak: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['next', 'previous'])

// Translations
const t = uz

// TTS
const { speak, speakSlowly, isSpeaking } = useTTS()

// State
const isFlipped = ref(false)
const hasSeenTranslation = ref(false)

// So'z o'zgarganda reset
watch(() => props.word, () => {
    isFlipped.value = false
    hasSeenTranslation.value = false
    if (props.autoSpeak && props.word?.english) {
        setTimeout(() => speak(props.word.english), 300)
    }
}, { immediate: false })

// Avtomatik o'qish
onMounted(() => {
    if (props.autoSpeak && props.word?.english) {
        setTimeout(() => speak(props.word.english), 500)
    }
})

// Flip card
const flipCard = () => {
    isFlipped.value = !isFlipped.value
    if (isFlipped.value) {
        hasSeenTranslation.value = true
    }
}

// TTS functions
const handleSpeak = () => {
    if (props.word?.english) {
        speak(props.word.english)
    }
}

const handleSpeakSlow = () => {
    if (props.word?.english) {
        speakSlowly(props.word.english)
    }
}

const handleSpeakExample = () => {
    if (props.word?.example) {
        speak(props.word.example)
    }
}

// Next word
const handleNext = () => {
    console.log('WordCard: handleNext called')
    emit('next')
}

// Progress percentage
const progressPercent = computed(() => {
    return Math.round(((props.currentIndex + 1) / props.totalWords) * 100)
})
</script>

<template>
    <div class="w-full max-w-lg mx-auto">
        
        <!-- Progress Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-full">
                    <span class="text-xl">📚</span>
                    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                        {{ t.lesson.learnNewWords }}
                    </span>
                </span>
                <span class="text-sm font-bold text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 px-3 py-1 rounded-full shadow-sm">
                    {{ currentIndex + 1 }} / {{ totalWords }}
                </span>
            </div>
            
            <!-- Progress bar -->
            <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                <div 
                    class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full transition-all duration-500 ease-out"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>
        </div>
        
        <!-- Word Card -->
        <div 
            class="relative perspective-1000"
            @click="flipCard"
        >
            <div 
                :class="[
                    'relative w-full transition-transform duration-600 transform-style-3d cursor-pointer',
                    isFlipped ? 'rotate-y-180' : ''
                ]"
                style="min-height: 380px;"
            >
                <!-- Front Side -->
                <div 
                    :class="[
                        'absolute inset-0 w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 backface-hidden border border-gray-100 dark:border-gray-700',
                        isFlipped ? 'invisible' : 'visible'
                    ]"
                >
                    <!-- Word Emoji/Image -->
                    <div class="flex justify-center mb-6">
                        <div class="w-28 h-28 bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 dark:from-indigo-900/40 dark:via-purple-900/40 dark:to-pink-900/40 rounded-3xl flex items-center justify-center shadow-lg">
                            <span class="text-6xl">{{ word?.emoji || '📖' }}</span>
                        </div>
                    </div>
                    
                    <!-- English Word -->
                    <h2 class="text-4xl md:text-5xl font-bold text-center bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-200 bg-clip-text text-transparent mb-3">
                        {{ word?.english || 'Word' }}
                    </h2>
                    
                    <!-- Pronunciation -->
                    <div class="flex items-center justify-center gap-2 mb-6">
                        <span class="text-lg text-gray-500 dark:text-gray-400 font-mono bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-lg">
                            {{ word?.pronunciation || '' }}
                        </span>
                    </div>
                    
                    <!-- TTS Buttons -->
                    <div class="flex items-center justify-center gap-3 mb-6" @click.stop>
                        <button
                            @click="handleSpeak"
                            :disabled="isSpeaking"
                            class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95"
                        >
                            <span :class="['text-xl', isSpeaking && 'animate-pulse']">🔊</span>
                            <span class="font-medium">{{ t.lesson.listen }}</span>
                        </button>
                        
                        <button
                            @click="handleSpeakSlow"
                            :disabled="isSpeaking"
                            class="flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-amber-400 to-orange-400 hover:from-amber-500 hover:to-orange-500 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95"
                        >
                            <span class="text-xl">🐢</span>
                            <span class="font-medium">{{ t.lesson.listenSlow || 'Sekin' }}</span>
                        </button>
                    </div>
                    
                    <!-- Flip hint -->
                    <div class="text-center mt-4">
                        <span class="inline-flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/50 px-4 py-2 rounded-full">
                            <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                            </svg>
                            {{ t.lesson.tapToFlip || "Tarjimasini ko'rish uchun bosing" }}
                        </span>
                    </div>
                </div>
                
                <!-- Back Side -->
                <div 
                    :class="[
                        'absolute inset-0 w-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-3xl shadow-2xl p-8 backface-hidden rotate-y-180',
                        isFlipped ? 'visible' : 'invisible'
                    ]"
                >
                    <!-- Back content -->
                    <div class="flex flex-col items-center justify-center h-full text-white">
                        <div class="text-6xl mb-4">{{ word?.emoji || '📖' }}</div>
                        
                        <h3 class="text-3xl md:text-4xl font-bold mb-6 text-center text-white drop-shadow-lg">
                            {{ word?.uzbek || 'Tarjima' }}
                        </h3>
                        
                        <!-- Example -->
                        <div class="w-full bg-white/20 backdrop-blur-sm rounded-2xl p-5 mb-6" @click.stop>
                            <div class="flex items-start gap-3 mb-2">
                                <button
                                    @click="handleSpeakExample"
                                    :disabled="isSpeaking"
                                    class="shrink-0 w-10 h-10 bg-white/30 hover:bg-white/40 rounded-full flex items-center justify-center transition-colors"
                                >
                                    🔊
                                </button>
                                <p class="text-lg font-medium text-white">
                                    "{{ word?.example || 'Example sentence' }}"
                                </p>
                            </div>
                            <p class="text-white/80 text-sm ml-13">
                                {{ word?.exampleTranslation || '' }}
                            </p>
                        </div>
                        
                        <!-- Flip back hint -->
                        <span class="text-white/70 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            Orqaga qaytish uchun bosing
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-center gap-4 mt-8">
            <button
                @click.stop="handleSpeak"
                :disabled="isSpeaking"
                class="px-6 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl font-semibold transition-all duration-200 flex items-center gap-2 text-gray-700 dark:text-gray-200 shadow-lg border border-gray-200 dark:border-gray-600 hover:shadow-xl transform hover:scale-105 active:scale-95"
            >
                <span class="text-xl">🔊</span>
                <span>{{ t.lesson.listen }}</span>
            </button>
            
            <button
                @click.stop="handleNext"
                :class="[
                    'px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 flex items-center gap-2 shadow-lg transform',
                    hasSeenTranslation 
                        ? 'bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white hover:shadow-xl hover:scale-105 active:scale-95'
                        : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white hover:shadow-xl hover:scale-105 active:scale-95'
                ]"
            >
                <span>{{ t.lesson.iKnowThis }}</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>
        
        <!-- Word dots -->
        <div class="flex justify-center gap-2 mt-6">
            <span 
                v-for="i in totalWords" 
                :key="i"
                :class="[
                    'w-3 h-3 rounded-full transition-all duration-300',
                    i - 1 < currentIndex 
                        ? 'bg-emerald-500 shadow-sm' 
                        : i - 1 === currentIndex 
                            ? 'bg-indigo-500 scale-125 shadow-md' 
                            : 'bg-gray-300 dark:bg-gray-600'
                ]"
            ></span>
        </div>
    </div>
</template>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}

.transform-style-3d {
    transform-style: preserve-3d;
}

.backface-hidden {
    backface-visibility: hidden;
}

.rotate-y-180 {
    transform: rotateY(180deg);
}

.duration-600 {
    transition-duration: 600ms;
}

.ml-13 {
    margin-left: 3.25rem;
}
</style>
