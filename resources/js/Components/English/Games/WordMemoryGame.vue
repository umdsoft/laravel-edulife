<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const wordSets = ref(props.content?.memory_words || [
    { words: ['apple', 'book', 'cat'], showTime: 5 },
    { words: ['dog', 'elephant', 'fish', 'green'], showTime: 7 },
])

const currentSetIndex = ref(0)
const phase = ref('show')
const timeLeft = ref(5)
const userInput = ref('')
const recalledWords = ref([])
const feedback = ref(null)

let timer = null

const currentSet = computed(() => wordSets.value[currentSetIndex.value])

onMounted(() => {
    startShowPhase()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const startShowPhase = () => {
    phase.value = 'show'
    timeLeft.value = currentSet.value.showTime
    
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            startRecallPhase()
        }
    }, 1000)
}

const startRecallPhase = () => {
    if (timer) clearInterval(timer)
    phase.value = 'recall'
    timeLeft.value = currentSet.value.showTime * 2
    
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            finishSet()
        }
    }, 1000)
}

const addWord = () => {
    const word = userInput.value.toLowerCase().trim()
    if (word && !recalledWords.value.includes(word)) {
        recalledWords.value.push(word)
        
        if (currentSet.value.words.includes(word)) {
            emit('score', 10)
            emit('correct')
            playAudio('/sounds/correct.mp3')
        } else {
            playAudio('/sounds/incorrect.mp3')
        }
    }
    userInput.value = ''
}

const finishSet = () => {
    if (timer) clearInterval(timer)
    feedback.value = 'finished'
    
    const correct = recalledWords.value.filter(w => currentSet.value.words.includes(w)).length
    if (correct === currentSet.value.words.length) {
        emit('score', 20)
    }
    
    setTimeout(() => nextSet(), 3000)
}

const nextSet = () => {
    if (currentSetIndex.value < wordSets.value.length - 1) {
        currentSetIndex.value++
        recalledWords.value = []
        feedback.value = null
        startShowPhase()
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter') {
        addWord()
    }
}

const getWordStatus = (word) => {
    if (recalledWords.value.includes(word)) return 'correct'
    return 'missed'
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg flex justify-between items-center mb-4 px-4">
            <span class="text-white/80">Set {{ currentSetIndex + 1 }} / {{ wordSets.length }}</span>
            <span class="px-3 py-1 rounded-full font-mono"
                :class="timeLeft <= 3 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                {{ timeLeft }}s
            </span>
        </div>
        
        <div v-if="phase === 'show'" class="text-center">
            <div class="text-3xl mb-4">🧠</div>
            <p class="text-white/80 mb-6">Memorize these words!</p>
            
            <div class="flex flex-wrap justify-center gap-4 mb-4">
                <div v-for="word in currentSet?.words" :key="word"
                    class="px-6 py-4 bg-white rounded-xl text-2xl font-bold text-gray-900">
                    {{ word }}
                </div>
            </div>
        </div>
        
        <div v-else-if="phase === 'recall' && !feedback" class="w-full max-w-lg px-4">
            <div class="text-3xl text-center mb-4">✍️</div>
            <p class="text-white/80 text-center mb-4">Type the words you remember!</p>
            
            <div class="flex flex-wrap justify-center gap-2 mb-4 min-h-12">
                <span v-for="word in recalledWords" :key="word"
                    class="px-3 py-1 rounded-full"
                    :class="currentSet?.words.includes(word) ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                    {{ word }}
                </span>
            </div>
            
            <div class="flex space-x-2">
                <input v-model="userInput" @keydown="handleKeydown" type="text" placeholder="Type a word..."
                    class="flex-1 px-4 py-3 rounded-xl border-2 border-white/30 bg-white/10 text-white focus:outline-none"
                />
                <button @click="addWord" :disabled="!userInput"
                    class="px-6 py-3 bg-blue-500 rounded-xl font-bold text-white disabled:opacity-50">
                    Add
                </button>
            </div>
            
            <p class="text-white/60 text-center mt-4">
                {{ recalledWords.filter(w => currentSet?.words.includes(w)).length }} / {{ currentSet?.words.length }} correct
            </p>
        </div>
        
        <div v-if="feedback === 'finished'" class="text-center">
            <p class="text-xl font-bold text-white mb-4">Results:</p>
            <div class="flex flex-wrap justify-center gap-2">
                <span v-for="word in currentSet?.words" :key="word"
                    class="px-4 py-2 rounded-lg font-medium"
                    :class="getWordStatus(word) === 'correct' ? 'bg-green-500 text-white' : 'bg-red-500/50 text-white'">
                    {{ word }} {{ getWordStatus(word) === 'correct' ? '✓' : '✗' }}
                </span>
            </div>
        </div>
    </div>
</template>
