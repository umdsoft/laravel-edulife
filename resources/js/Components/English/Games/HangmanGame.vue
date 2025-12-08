<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref(props.content?.words || [])
const currentIndex = ref(0)
const guessedLetters = ref([])
const wrongGuesses = ref(0)
const maxWrong = 6
const gameStatus = ref('playing')

const currentWord = computed(() => words.value[currentIndex.value])
const wordLetters = computed(() => currentWord.value?.word.toLowerCase().split('') || [])
const uniqueLetters = computed(() => [...new Set(wordLetters.value)])

const displayWord = computed(() => {
    return wordLetters.value.map(letter => {
        if (letter === ' ') return ' '
        return guessedLetters.value.includes(letter) ? letter : '_'
    })
})

const isWordComplete = computed(() => {
    return uniqueLetters.value.every(letter => guessedLetters.value.includes(letter))
})

const alphabet = 'abcdefghijklmnopqrstuvwxyz'.split('')

const hangmanParts = computed(() => {
    const parts = ['head', 'body', 'leftArm', 'rightArm', 'leftLeg', 'rightLeg']
    return parts.slice(0, wrongGuesses.value)
})

const guessLetter = (letter) => {
    if (guessedLetters.value.includes(letter) || gameStatus.value !== 'playing') return
    
    guessedLetters.value.push(letter)
    
    if (wordLetters.value.includes(letter)) {
        playAudio('/sounds/correct.mp3')
        
        if (isWordComplete.value) {
            gameStatus.value = 'won'
            const points = Math.max(10, 30 - (wrongGuesses.value * 5))
            emit('score', points)
            emit('correct')
            
            setTimeout(() => nextWord(), 2000)
        }
    } else {
        wrongGuesses.value++
        playAudio('/sounds/incorrect.mp3')
        
        if (wrongGuesses.value >= maxWrong) {
            gameStatus.value = 'lost'
            setTimeout(() => nextWord(), 2000)
        }
    }
}

const nextWord = () => {
    if (currentIndex.value < words.value.length - 1) {
        currentIndex.value++
        guessedLetters.value = []
        wrongGuesses.value = 0
        gameStatus.value = 'playing'
    } else {
        emit('complete')
    }
}

const isLetterGuessed = (letter) => guessedLetters.value.includes(letter)
const isLetterCorrect = (letter) => guessedLetters.value.includes(letter) && wordLetters.value.includes(letter)
const isLetterWrong = (letter) => guessedLetters.value.includes(letter) && !wordLetters.value.includes(letter)
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-md mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Word {{ currentIndex + 1 }} / {{ words.length }}</span>
                <span>Wrong: {{ wrongGuesses }}/{{ maxWrong }}</span>
            </div>
        </div>
        
        <div class="relative w-48 h-48 mb-6">
            <svg viewBox="0 0 200 200" class="w-full h-full">
                <line x1="40" y1="180" x2="160" y2="180" stroke="white" stroke-width="4" />
                <line x1="60" y1="180" x2="60" y2="20" stroke="white" stroke-width="4" />
                <line x1="60" y1="20" x2="120" y2="20" stroke="white" stroke-width="4" />
                <line x1="120" y1="20" x2="120" y2="40" stroke="white" stroke-width="4" />
                
                <circle v-if="hangmanParts.includes('head')" cx="120" cy="55" r="15" 
                    stroke="white" stroke-width="4" fill="none" class="animate-draw" />
                <line v-if="hangmanParts.includes('body')" x1="120" y1="70" x2="120" y2="120"
                    stroke="white" stroke-width="4" class="animate-draw" />
                <line v-if="hangmanParts.includes('leftArm')" x1="120" y1="80" x2="90" y2="100"
                    stroke="white" stroke-width="4" class="animate-draw" />
                <line v-if="hangmanParts.includes('rightArm')" x1="120" y1="80" x2="150" y2="100"
                    stroke="white" stroke-width="4" class="animate-draw" />
                <line v-if="hangmanParts.includes('leftLeg')" x1="120" y1="120" x2="90" y2="160"
                    stroke="white" stroke-width="4" class="animate-draw" />
                <line v-if="hangmanParts.includes('rightLeg')" x1="120" y1="120" x2="150" y2="160"
                    stroke="white" stroke-width="4" class="animate-draw" />
            </svg>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 mb-4">
            <span v-for="(letter, index) in displayWord" :key="index"
                class="w-10 h-12 flex items-center justify-center text-2xl font-bold border-b-4 border-white"
                :class="letter === ' ' ? 'border-transparent w-4' : ''">
                {{ letter === '_' ? '' : letter.toUpperCase() }}
            </span>
        </div>
        
        <p class="text-white/80 mb-6">
            Hint: <span class="font-medium">{{ currentWord?.translation_uz }}</span>
        </p>
        
        <div v-if="gameStatus !== 'playing'" class="mb-4 px-6 py-3 rounded-xl"
            :class="gameStatus === 'won' ? 'bg-green-500/30' : 'bg-red-500/30'">
            <p class="text-white text-lg font-medium">
                {{ gameStatus === 'won' ? '🎉 You won!' : '💀 Game Over!' }}
            </p>
            <p v-if="gameStatus === 'lost'" class="text-white/80">
                The word was: <span class="font-bold">{{ currentWord?.word }}</span>
            </p>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 max-w-md">
            <button
                v-for="letter in alphabet"
                :key="letter"
                @click="guessLetter(letter)"
                :disabled="isLetterGuessed(letter) || gameStatus !== 'playing'"
                class="w-9 h-9 rounded-lg font-bold text-sm transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-100': !isLetterGuessed(letter),
                    'bg-green-500 text-white': isLetterCorrect(letter),
                    'bg-red-500 text-white': isLetterWrong(letter),
                    'opacity-50 cursor-not-allowed': isLetterGuessed(letter),
                }"
            >
                {{ letter.toUpperCase() }}
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes draw {
    from { stroke-dashoffset: 100; }
    to { stroke-dashoffset: 0; }
}
.animate-draw {
    stroke-dasharray: 100;
    animation: draw 0.5s ease-out forwards;
}
</style>
