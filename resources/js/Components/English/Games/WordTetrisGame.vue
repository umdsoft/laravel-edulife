<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const wordPairs = ref(props.content?.tetris_words || [
    { english: 'apple', uzbek: 'olma' },
    { english: 'book', uzbek: 'kitob' },
    { english: 'cat', uzbek: 'mushuk' },
    { english: 'dog', uzbek: 'it' },
    { english: 'house', uzbek: 'uy' },
    { english: 'water', uzbek: 'suv' },
    { english: 'sun', uzbek: 'quyosh' },
    { english: 'moon', uzbek: 'oy' },
])

const fallingWord = ref(null)
const fallingPosition = ref(0)
const options = ref([])
const score = ref(0)
const lives = ref(3)
const speed = ref(2000)
const gameActive = ref(true)

let gameInterval = null

onMounted(() => {
    startNewWord()
    gameInterval = setInterval(() => {
        fallingPosition.value += 10
        if (fallingPosition.value >= 100) {
            loseLife()
        }
    }, speed.value / 10)
})

onUnmounted(() => {
    if (gameInterval) clearInterval(gameInterval)
})

const startNewWord = () => {
    if (!gameActive.value) return
    
    const randomPair = wordPairs.value[Math.floor(Math.random() * wordPairs.value.length)]
    fallingWord.value = randomPair
    fallingPosition.value = 0
    
    const wrongOptions = wordPairs.value
        .filter(p => p.uzbek !== randomPair.uzbek)
        .sort(() => Math.random() - 0.5)
        .slice(0, 3)
        .map(p => p.uzbek)
    
    options.value = [randomPair.uzbek, ...wrongOptions].sort(() => Math.random() - 0.5)
}

const selectAnswer = (answer) => {
    if (!gameActive.value) return
    
    if (answer === fallingWord.value.uzbek) {
        score.value += 10
        emit('score', 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        speed.value = Math.max(500, speed.value - 100)
        startNewWord()
    } else {
        loseLife()
    }
}

const loseLife = () => {
    lives.value--
    playAudio('/sounds/incorrect.mp3')
    
    if (lives.value <= 0) {
        gameActive.value = false
        if (gameInterval) clearInterval(gameInterval)
        emit('complete')
    } else {
        startNewWord()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-md flex justify-between items-center mb-4 px-4">
            <div class="text-white">
                <span class="text-2xl font-bold">{{ score }}</span>
                <span class="text-white/60 ml-1">pts</span>
            </div>
            <div class="flex space-x-1">
                <span v-for="i in 3" :key="i" class="text-2xl">
                    {{ i <= lives ? '❤️' : '🖤' }}
                </span>
            </div>
        </div>
        
        <div class="w-full max-w-md h-64 bg-white/10 rounded-xl relative overflow-hidden mb-4 mx-4">
            <div v-if="fallingWord"
                class="absolute left-1/2 -translate-x-1/2 px-6 py-3 bg-white rounded-xl shadow-lg transition-all duration-200"
                :style="{ top: `${fallingPosition}%` }">
                <span class="text-xl font-bold text-gray-900">{{ fallingWord.english }}</span>
            </div>
            
            <div class="absolute bottom-0 left-0 right-0 h-2 bg-red-500/50"></div>
        </div>
        
        <div class="w-full max-w-md grid grid-cols-2 gap-3 px-4">
            <button v-for="option in options" :key="option"
                @click="selectAnswer(option)"
                :disabled="!gameActive"
                class="p-4 bg-white rounded-xl font-medium text-gray-900 hover:bg-blue-50 transition-all active:scale-95">
                {{ option }}
            </button>
        </div>
        
        <div v-if="!gameActive" class="mt-6 text-center">
            <p class="text-2xl font-bold text-red-400">Game Over!</p>
            <p class="text-white mt-2">Final Score: {{ score }}</p>
        </div>
    </div>
</template>
