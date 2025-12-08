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

const anagrams = ref(props.content?.anagrams || [
    { letters: 'LISTEN', answers: ['LISTEN', 'SILENT', 'ENLIST', 'TINSEL'] },
    { letters: 'EARTH', answers: ['EARTH', 'HEART', 'HATER'] },
    { letters: 'BELOW', answers: ['BELOW', 'ELBOW', 'BOWEL'] },
    { letters: 'NOTES', answers: ['NOTES', 'STONE', 'TONES', 'ONSET'] },
])

const currentIndex = ref(0)
const userInput = ref('')
const foundWords = ref([])
const feedback = ref(null)

const { time, formatTime, start, stop } = useTimer({
    initialTime: 90,
    countdown: true,
    onComplete: () => nextAnagram(),
})

const currentAnagram = computed(() => anagrams.value[currentIndex.value])
const remainingWords = computed(() => 
    currentAnagram.value?.answers.filter(w => !foundWords.value.includes(w.toUpperCase())) || []
)

onMounted(() => {
    start()
})

const checkWord = () => {
    if (feedback.value) return
    
    const word = userInput.value.toUpperCase().trim()
    
    if (foundWords.value.includes(word)) {
        feedback.value = 'duplicate'
        playAudio('/sounds/incorrect.mp3')
    } else if (currentAnagram.value.answers.map(a => a.toUpperCase()).includes(word)) {
        feedback.value = 'correct'
        foundWords.value.push(word)
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        if (remainingWords.value.length === 0) {
            setTimeout(() => nextAnagram(), 1000)
        }
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        feedback.value = null
        userInput.value = ''
    }, 800)
}

const nextAnagram = () => {
    stop()
    
    if (currentIndex.value < anagrams.value.length - 1) {
        currentIndex.value++
        foundWords.value = []
        userInput.value = ''
        feedback.value = null
        start()
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkWord()
    }
}

const shuffledLetters = computed(() => {
    return currentAnagram.value?.letters.split('').sort(() => Math.random() - 0.5) || []
})

const progress = computed(() => Math.round(((currentIndex.value + 1) / anagrams.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-md mb-4 px-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm">Set {{ currentIndex + 1 }} / {{ anagrams.length }}</span>
                <span class="px-3 py-1 rounded-full font-mono font-bold"
                    :class="time <= 15 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                    {{ formatTime(time) }}
                </span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-orange-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="flex space-x-2 mb-6">
            <div v-for="(letter, index) in shuffledLetters" :key="index"
                class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-2xl font-bold text-gray-900 shadow-lg">
                {{ letter }}
            </div>
        </div>
        
        <p class="text-white/80 mb-2">Find all words using these letters!</p>
        <p class="text-white/60 text-sm mb-4">{{ remainingWords.length }} words remaining</p>
        
        <div class="flex flex-wrap justify-center gap-2 mb-6 max-w-md">
            <span v-for="word in foundWords" :key="word"
                class="px-3 py-1 bg-green-500/30 text-green-300 rounded-full text-sm font-medium">
                ✓ {{ word }}
            </span>
        </div>
        
        <div class="flex space-x-2">
            <input
                v-model="userInput"
                @keydown="handleKeydown"
                :maxlength="currentAnagram?.letters.length"
                type="text"
                placeholder="Type a word..."
                class="px-4 py-3 text-lg text-center font-mono tracking-widest rounded-xl border-2 focus:outline-none uppercase w-48"
                :class="{
                    'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                    'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                    'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect' || feedback === 'duplicate',
                }"
            />
            <button @click="checkWord" :disabled="!userInput"
                class="px-6 py-3 bg-orange-500 rounded-xl font-bold text-white hover:bg-orange-600 disabled:opacity-50">
                Check
            </button>
        </div>
        
        <p v-if="feedback === 'duplicate'" class="mt-2 text-yellow-400 text-sm">Already found!</p>
        
        <button @click="nextAnagram" class="mt-6 text-white/60 hover:text-white text-sm">
            Skip to next →
        </button>
    </div>
</template>
