<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'
import { useTimer } from '@/Composables/useTimer'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const chains = ref(props.content?.word_chains || [
    { category: 'Food', words: ['apple', 'banana', 'orange', 'grape'], intruder: 'chair' },
    { category: 'Animals', words: ['dog', 'cat', 'bird', 'fish'], intruder: 'tree' },
    { category: 'Colors', words: ['red', 'blue', 'green', 'yellow'], intruder: 'happy' },
    { category: 'Transport', words: ['car', 'bus', 'train', 'plane'], intruder: 'book' },
])

const currentIndex = ref(0)
const displayWords = ref([])
const selectedWord = ref(null)
const feedback = ref(null)

const { time, formatTime, start, stop, reset } = useTimer({
    initialTime: 15,
    countdown: true,
    onComplete: () => handleTimeout(),
})

const currentChain = computed(() => chains.value[currentIndex.value])

onMounted(() => {
    setupRound()
})

const setupRound = () => {
    const chain = currentChain.value
    displayWords.value = [...chain.words, chain.intruder].sort(() => Math.random() - 0.5)
    reset(15)
    start()
}

const handleTimeout = () => {
    if (!feedback.value) {
        feedback.value = 'timeout'
        playAudio('/sounds/timeout.mp3')
        setTimeout(() => nextRound(), 2000)
    }
}

const selectWord = (word) => {
    if (feedback.value) return
    
    stop()
    selectedWord.value = word
    
    if (word === currentChain.value.intruder) {
        feedback.value = 'correct'
        const timeBonus = Math.floor(time.value)
        emit('score', 20 + timeBonus)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextRound(), 2000)
}

const nextRound = () => {
    if (currentIndex.value < chains.value.length - 1) {
        currentIndex.value++
        selectedWord.value = null
        feedback.value = null
        setupRound()
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / chains.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm">Round {{ currentIndex + 1 }} / {{ chains.length }}</span>
                <span class="px-3 py-1 rounded-full font-mono font-bold"
                    :class="time <= 5 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                    {{ formatTime(time) }}
                </span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white/20 rounded-xl px-6 py-3 mb-6">
            <p class="text-white/80 text-sm">Category:</p>
            <p class="text-white text-2xl font-bold">{{ currentChain?.category }}</p>
        </div>
        
        <p class="text-white/80 mb-6">Find the word that doesn't belong!</p>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="word in displayWords" :key="word"
                @click="selectWord(word)"
                :disabled="feedback !== null"
                class="p-5 rounded-xl font-medium text-lg transition-all transform hover:scale-105"
                :class="{
                    'bg-white text-gray-900': !feedback,
                    'bg-green-500 text-white ring-4 ring-green-300': feedback && word === currentChain?.intruder,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedWord === word,
                    'bg-blue-500 text-white': feedback && currentChain?.words.includes(word),
                }">
                {{ word }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center">
            <p class="text-lg font-medium mb-2"
                :class="{
                    'text-green-400': feedback === 'correct',
                    'text-red-400': feedback === 'incorrect',
                    'text-orange-400': feedback === 'timeout',
                }">
                {{ feedback === 'correct' ? '✓ Correct!' : feedback === 'timeout' ? '⏰ Time\'s up!' : '✗ Wrong!' }}
            </p>
            <p class="text-white/60 text-sm">
                "{{ currentChain?.intruder }}" doesn't belong to {{ currentChain?.category }}
            </p>
        </div>
    </div>
</template>
