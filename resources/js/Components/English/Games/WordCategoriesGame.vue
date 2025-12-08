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

const rounds = ref(props.content?.categories || [
    {
        categories: ['Animals', 'Food'],
        words: [
            { word: 'cat', category: 'Animals' },
            { word: 'apple', category: 'Food' },
            { word: 'dog', category: 'Animals' },
            { word: 'bread', category: 'Food' },
            { word: 'bird', category: 'Animals' },
            { word: 'cheese', category: 'Food' },
        ]
    },
    {
        categories: ['Verbs', 'Adjectives'],
        words: [
            { word: 'run', category: 'Verbs' },
            { word: 'beautiful', category: 'Adjectives' },
            { word: 'eat', category: 'Verbs' },
            { word: 'happy', category: 'Adjectives' },
            { word: 'sleep', category: 'Verbs' },
            { word: 'big', category: 'Adjectives' },
        ]
    },
])

const currentRoundIndex = ref(0)
const sortedWords = ref({})
const remainingWords = ref([])
const selectedWord = ref(null)
const feedback = ref(null)

const { time, formatTime, start, stop, reset } = useTimer({
    initialTime: 60,
    countdown: true,
    onComplete: () => endRound(),
})

const currentRound = computed(() => rounds.value[currentRoundIndex.value])

onMounted(() => {
    setupRound()
})

const setupRound = () => {
    const round = currentRound.value
    sortedWords.value = {}
    round.categories.forEach(cat => {
        sortedWords.value[cat] = []
    })
    remainingWords.value = [...round.words].sort(() => Math.random() - 0.5)
    reset(60)
    start()
}

const selectWord = (word) => {
    if (feedback.value) return
    selectedWord.value = word
}

const dropToCategory = (category) => {
    if (!selectedWord.value || feedback.value) return
    
    if (selectedWord.value.category === category) {
        sortedWords.value[category].push(selectedWord.value)
        remainingWords.value = remainingWords.value.filter(w => w.word !== selectedWord.value.word)
        emit('score', 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        if (remainingWords.value.length === 0) {
            endRound()
        }
    } else {
        feedback.value = 'wrong'
        playAudio('/sounds/incorrect.mp3')
        setTimeout(() => {
            feedback.value = null
        }, 500)
    }
    
    selectedWord.value = null
}

const endRound = () => {
    stop()
    
    if (currentRoundIndex.value < rounds.value.length - 1) {
        setTimeout(() => {
            currentRoundIndex.value++
            setupRound()
        }, 1500)
    } else {
        emit('complete')
    }
}

const progress = computed(() => {
    const totalWords = currentRound.value?.words.length || 0
    const sorted = Object.values(sortedWords.value).flat().length
    return Math.round((sorted / totalWords) * 100)
})
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-2xl mb-4 px-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm">Round {{ currentRoundIndex + 1 }} / {{ rounds.length }}</span>
                <span class="px-3 py-1 rounded-full font-mono font-bold"
                    :class="time <= 10 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                    {{ formatTime(time) }}
                </span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-lime-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-2xl grid gap-4 mb-6 px-4"
            :style="{ gridTemplateColumns: `repeat(${currentRound?.categories.length}, 1fr)` }">
            <div v-for="category in currentRound?.categories" :key="category"
                @click="dropToCategory(category)"
                class="bg-white rounded-xl p-4 min-h-[150px] cursor-pointer transition-all hover:ring-2 hover:ring-lime-500"
                :class="{ 'ring-2 ring-red-500 animate-shake': feedback === 'wrong' }">
                <h3 class="font-bold text-gray-900 mb-3 text-center">{{ category }}</h3>
                <div class="flex flex-wrap gap-2">
                    <span v-for="word in sortedWords[category]" :key="word.word"
                        class="px-3 py-1 bg-lime-100 text-lime-800 rounded-full text-sm font-medium">
                        {{ word.word }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="w-full max-w-2xl px-4">
            <p class="text-white/60 text-sm mb-2 text-center">Click a word, then click a category:</p>
            <div class="flex flex-wrap justify-center gap-2">
                <button v-for="word in remainingWords" :key="word.word"
                    @click="selectWord(word)"
                    class="px-4 py-2 rounded-xl font-medium transition-all transform hover:scale-105"
                    :class="selectedWord?.word === word.word 
                        ? 'bg-lime-500 text-white scale-110' 
                        : 'bg-white text-gray-900'">
                    {{ word.word }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.3s ease-in-out; }
</style>
