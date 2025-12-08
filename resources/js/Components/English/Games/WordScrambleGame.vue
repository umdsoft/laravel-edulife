<script setup>
import { ref, computed, watch } from 'vue'
import { useAudio } from '@/Composables/useAudio'
import { LightBulbIcon, SpeakerWaveIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref(props.content?.words || [])
const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const streak = ref(0)
const hintsUsed = ref(0)
const revealedLetters = ref([])

const currentWord = computed(() => words.value[currentIndex.value])

const scrambledWord = computed(() => {
    if (!currentWord.value) return ''
    const word = currentWord.value.word
    let scrambled = word.split('').sort(() => Math.random() - 0.5).join('')
    while (scrambled === word && word.length > 1) {
        scrambled = word.split('').sort(() => Math.random() - 0.5).join('')
    }
    return scrambled.toUpperCase()
})

const displayLetters = computed(() => {
    return scrambledWord.value.split('').map((letter, index) => ({
        letter,
        revealed: revealedLetters.value.includes(index),
    }))
})

const checkAnswer = () => {
    if (feedback.value) return
    
    const isCorrect = userInput.value.toLowerCase().trim() === currentWord.value.word.toLowerCase()
    
    if (isCorrect) {
        feedback.value = 'correct'
        streak.value++
        const basePoints = 10
        const streakBonus = streak.value > 1 ? streak.value * 2 : 0
        const hintPenalty = hintsUsed.value * 2
        const points = Math.max(5, basePoints + streakBonus - hintPenalty)
        emit('score', points)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        streak.value = 0
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        if (currentIndex.value < words.value.length - 1) {
            currentIndex.value++
            userInput.value = ''
            feedback.value = null
            hintsUsed.value = 0
            revealedLetters.value = []
        } else {
            emit('complete')
        }
    }, 1500)
}

const useHint = () => {
    if (hintsUsed.value >= 3) return
    
    hintsUsed.value++
    const word = currentWord.value.word
    
    for (let i = 0; i < word.length; i++) {
        if (userInput.value.length <= i || userInput.value[i].toLowerCase() !== word[i].toLowerCase()) {
            userInput.value = word.substring(0, i + 1)
            break
        }
    }
}

const playWordAudio = () => {
    if (currentWord.value?.audio_url) {
        playAudio(currentWord.value.audio_url)
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value && !feedback.value) {
        checkAnswer()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-md mb-6">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Word {{ currentIndex + 1 }} / {{ words.length }}</span>
                <span v-if="streak > 1" class="text-orange-400">🔥 {{ streak }} streak!</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-white rounded-full transition-all"
                    :style="{ width: `${((currentIndex + 1) / words.length) * 100}%` }"></div>
            </div>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 mb-6">
            <div v-for="(item, index) in displayLetters" :key="index"
                class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl font-bold transition-all"
                :class="item.revealed ? 'bg-green-500 text-white' : 'bg-white text-gray-900'">
                {{ item.letter }}
            </div>
        </div>
        
        <div class="flex items-center space-x-3 mb-6">
            <p class="text-white/80 text-lg">{{ currentWord?.translation_uz }}</p>
            <button @click="playWordAudio" class="p-2 rounded-full bg-white/20 hover:bg-white/30">
                <SpeakerWaveIcon class="w-5 h-5 text-white" />
            </button>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Type the word..."
            autocomplete="off"
            class="w-full max-w-sm px-6 py-4 text-xl text-center rounded-2xl border-2 focus:outline-none transition-all"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <Transition name="fade">
            <div v-if="feedback" class="mt-4 text-lg font-medium"
                :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : `✗ Answer: ${currentWord?.word}` }}
            </div>
        </Transition>
        
        <div class="flex space-x-4 mt-8">
            <button @click="useHint" :disabled="hintsUsed >= 3 || feedback !== null"
                class="flex items-center space-x-2 px-4 py-2 bg-white/20 rounded-xl text-white hover:bg-white/30 disabled:opacity-50 disabled:cursor-not-allowed">
                <LightBulbIcon class="w-5 h-5" />
                <span>Hint ({{ 3 - hintsUsed }})</span>
            </button>
            
            <button @click="checkAnswer" :disabled="!userInput || feedback !== null"
                class="px-8 py-3 bg-white rounded-xl font-bold text-blue-600 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Check
            </button>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
