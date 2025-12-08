<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const validWords = ref(props.content?.word_list || [
    'apple', 'elephant', 'tiger', 'rabbit', 'table', 'egg', 'green', 'night',
    'tree', 'eat', 'think', 'king', 'give', 'eleven', 'never', 'red', 'dog',
    'game', 'east', 'time', 'end', 'dance', 'every', 'yellow', 'water'
])

const chain = ref([])
const userInput = ref('')
const feedback = ref(null)
const timeLeft = ref(60)
const score = ref(0)
const usedWords = ref(new Set())

let timer = null

const lastLetter = computed(() => {
    if (chain.value.length === 0) return 's'
    const lastWord = chain.value[chain.value.length - 1]
    return lastWord[lastWord.length - 1].toLowerCase()
})

onMounted(() => {
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            endGame()
        }
    }, 1000)
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const checkWord = () => {
    if (feedback.value) return
    
    const word = userInput.value.toLowerCase().trim()
    
    if (word.length < 2) {
        feedback.value = 'too_short'
    } else if (word[0] !== lastLetter.value) {
        feedback.value = 'wrong_letter'
    } else if (usedWords.value.has(word)) {
        feedback.value = 'used'
    } else if (!validWords.value.includes(word)) {
        feedback.value = 'invalid'
    } else {
        feedback.value = 'correct'
        chain.value.push(word)
        usedWords.value.add(word)
        score.value += word.length * 5
        emit('score', word.length * 5)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    }
    
    if (feedback.value !== 'correct') {
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        feedback.value = null
        userInput.value = ''
    }, 800)
}

const endGame = () => {
    if (timer) clearInterval(timer)
    emit('complete')
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkWord()
    }
}

const feedbackMessage = computed(() => {
    switch (feedback.value) {
        case 'too_short': return 'Word too short!'
        case 'wrong_letter': return `Must start with "${lastLetter.value.toUpperCase()}"!`
        case 'used': return 'Word already used!'
        case 'invalid': return 'Not a valid word!'
        case 'correct': return '✓ Great!'
        default: return ''
    }
})
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg flex justify-between items-center mb-4 px-4">
            <div class="text-white">
                <span class="text-2xl font-bold">{{ score }}</span>
                <span class="text-white/60 ml-1">pts</span>
            </div>
            <div class="text-white">
                <span class="text-sm">Chain:</span>
                <span class="text-2xl font-bold ml-2">{{ chain.length }}</span>
            </div>
            <div class="px-4 py-2 rounded-full font-mono font-bold"
                :class="timeLeft <= 10 ? 'bg-red-500 text-white animate-pulse' : 'bg-white text-gray-900'">
                {{ timeLeft }}s
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white/10 rounded-xl p-4 mb-4 mx-4 max-h-32 overflow-y-auto">
            <div class="flex flex-wrap gap-2">
                <span v-for="(word, index) in chain" :key="index"
                    class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-sm">
                    {{ word }}
                </span>
                <span v-if="chain.length === 0" class="text-white/50">Start the chain!</span>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-6 text-center">
            <p class="text-gray-500 text-sm mb-2">Your word must start with:</p>
            <p class="text-6xl font-bold text-blue-600">{{ lastLetter.toUpperCase() }}</p>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            type="text"
            placeholder="Type a word..."
            class="w-full max-w-xs px-4 py-3 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback && feedback !== 'correct',
            }"
        />
        
        <p v-if="feedback" class="mt-2 font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedbackMessage }}
        </p>
        
        <button @click="checkWord" :disabled="!userInput"
            class="mt-4 px-8 py-3 bg-blue-500 rounded-xl font-bold text-white hover:bg-blue-600 disabled:opacity-50">
            Submit
        </button>
        
        <button @click="endGame" class="mt-6 text-white/60 hover:text-white text-sm">
            End Game
        </button>
    </div>
</template>
