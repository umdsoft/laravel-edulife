<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.punctuation || [
    { wrong: 'I love pizza pasta and salad', correct: 'I love pizza, pasta, and salad.', rule: 'Use commas in lists' },
    { wrong: 'whats your name', correct: "What's your name?", rule: 'Use apostrophe for contractions and ? for questions' },
    { wrong: 'he said i am happy', correct: 'He said, "I am happy."', rule: 'Use quotation marks for direct speech' },
    { wrong: 'wow thats amazing', correct: "Wow! That's amazing!", rule: 'Use exclamation marks for strong emotions' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    if (userInput.value.trim() === currentSentence.value.correct) {
        feedback.value = 'correct'
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextSentence(), 2500)
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1) {
        currentIndex.value++
        userInput.value = ''
        feedback.value = null
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / sentences.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ sentences.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-pink-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">✏️</div>
        <p class="text-white/80 mb-4">Add correct punctuation!</p>
        
        <div class="w-full max-w-lg bg-red-500/20 rounded-2xl p-6 mb-4 mx-4">
            <p class="text-red-300 text-sm text-center mb-2">❌ Needs punctuation:</p>
            <p class="text-xl text-white text-center font-mono">{{ currentSentence?.wrong }}</p>
        </div>
        
        <div class="bg-white/20 rounded-xl px-4 py-2 mb-4">
            <span class="text-white/80 text-sm">💡 {{ currentSentence?.rule }}</span>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Type the corrected sentence..."
            class="w-full max-w-lg px-4 py-3 rounded-xl border-2 focus:outline-none mx-4"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <div v-if="feedback" class="mt-4 text-center max-w-lg">
            <p class="text-lg font-medium mb-2" :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Perfect!' : '✗ Not quite' }}
            </p>
            <p class="text-white/80 text-sm">Correct answer:</p>
            <p class="text-white font-medium font-mono">{{ currentSentence?.correct }}</p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="mt-4 px-8 py-3 bg-pink-500 rounded-xl font-bold text-white hover:bg-pink-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
