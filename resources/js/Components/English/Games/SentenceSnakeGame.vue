<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const snakes = ref(props.content?.sentence_snakes || [
    { words: ['I', 'like', 'to', 'play', 'football', 'with', 'my', 'friends'], shuffled: ['football', 'I', 'my', 'play', 'with', 'like', 'to', 'friends'] },
    { words: ['She', 'is', 'reading', 'a', 'very', 'interesting', 'book'], shuffled: ['book', 'reading', 'She', 'very', 'a', 'is', 'interesting'] },
])

const currentSnakeIndex = ref(0)
const selectedWords = ref([])
const feedback = ref(null)

const currentSnake = computed(() => snakes.value[currentSnakeIndex.value])
const availableWords = computed(() => 
    currentSnake.value?.shuffled.filter(w => !selectedWords.value.includes(w)) || []
)

const selectWord = (word) => {
    if (feedback.value) return
    selectedWords.value.push(word)
}

const removeLastWord = () => {
    if (feedback.value) return
    selectedWords.value.pop()
}

const checkSentence = () => {
    if (feedback.value) return
    
    const userSentence = selectedWords.value.join(' ')
    const correctSentence = currentSnake.value.words.join(' ')
    
    if (userSentence === correctSentence) {
        feedback.value = 'correct'
        emit('score', selectedWords.value.length * 5)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        setTimeout(() => nextSnake(), 1500)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        setTimeout(() => { feedback.value = null }, 1000)
    }
}

const nextSnake = () => {
    if (currentSnakeIndex.value < snakes.value.length - 1) {
        currentSnakeIndex.value++
        selectedWords.value = []
        feedback.value = null
    } else {
        emit('complete')
    }
}

const resetSentence = () => {
    selectedWords.value = []
}

const progress = computed(() => Math.round(((currentSnakeIndex.value + 1) / snakes.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Sentence {{ currentSnakeIndex + 1 }} / {{ snakes.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">🐍</div>
        <p class="text-white/80 mb-4">Build the sentence in correct order!</p>
        
        <div class="w-full max-w-lg min-h-20 bg-white rounded-2xl p-4 mb-4 mx-4">
            <div class="flex flex-wrap gap-2">
                <span v-for="(word, index) in selectedWords" :key="index"
                    class="px-3 py-2 bg-green-500 text-white rounded-lg font-medium relative">
                    {{ word }}
                    <span v-if="index < selectedWords.length - 1" 
                        class="absolute -right-2 top-1/2 -translate-y-1/2 text-green-600">→</span>
                </span>
                <span v-if="selectedWords.length === 0" class="text-gray-400">
                    Click words below to build the sentence...
                </span>
            </div>
        </div>
        
        <div class="w-full max-w-lg px-4 mb-4">
            <div class="flex flex-wrap justify-center gap-2">
                <button v-for="word in availableWords" :key="word"
                    @click="selectWord(word)"
                    :disabled="feedback !== null"
                    class="px-4 py-2 bg-white rounded-lg font-medium text-gray-900 hover:bg-blue-50 transition-all">
                    {{ word }}
                </button>
            </div>
        </div>
        
        <div class="flex space-x-4">
            <button @click="removeLastWord" :disabled="selectedWords.length === 0 || feedback !== null"
                class="px-6 py-3 bg-white/20 rounded-xl font-bold text-white hover:bg-white/30 disabled:opacity-50">
                ← Undo
            </button>
            <button @click="resetSentence" :disabled="selectedWords.length === 0 || feedback !== null"
                class="px-6 py-3 bg-red-500/80 rounded-xl font-bold text-white hover:bg-red-500 disabled:opacity-50">
                Reset
            </button>
            <button @click="checkSentence" 
                :disabled="selectedWords.length !== currentSnake?.words.length || feedback !== null"
                class="px-6 py-3 bg-green-500 rounded-xl font-bold text-white hover:bg-green-600 disabled:opacity-50">
                Check ✓
            </button>
        </div>
        
        <div v-if="feedback" class="mt-4 text-xl font-bold"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedback === 'correct' ? '✓ Perfect!' : '✗ Try again!' }}
        </div>
    </div>
</template>
