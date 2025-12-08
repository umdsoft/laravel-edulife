<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const wheels = ref(props.content?.word_wheels || [
    { center: 'A', outer: ['P', 'L', 'E', 'M', 'N', 'T'], minWords: 5, validWords: ['APE', 'ALE', 'PALE', 'MALE', 'LATE', 'PLATE', 'MENTAL', 'MANTLE'] },
    { center: 'E', outer: ['A', 'T', 'R', 'S', 'N', 'W'], minWords: 5, validWords: ['EAT', 'EAR', 'EARN', 'NEAR', 'TEAR', 'WEAR', 'WATER', 'WESTERN'] },
])

const currentWheelIndex = ref(0)
const foundWords = ref([])
const userInput = ref('')
const feedback = ref(null)
const timeLeft = ref(90)

let timer = null

const currentWheel = computed(() => wheels.value[currentWheelIndex.value])
const allLetters = computed(() => [currentWheel.value?.center, ...currentWheel.value?.outer || []])

onMounted(() => {
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            nextWheel()
        }
    }, 1000)
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const checkWord = () => {
    if (feedback.value) return
    
    const word = userInput.value.toUpperCase().trim()
    
    if (!word.includes(currentWheel.value.center)) {
        feedback.value = 'no_center'
        playAudio('/sounds/incorrect.mp3')
    } else if (word.length < 3) {
        feedback.value = 'too_short'
        playAudio('/sounds/incorrect.mp3')
    } else if (foundWords.value.includes(word)) {
        feedback.value = 'duplicate'
        playAudio('/sounds/incorrect.mp3')
    } else if (currentWheel.value.validWords.includes(word)) {
        feedback.value = 'correct'
        foundWords.value.push(word)
        emit('score', word.length * 5)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        if (foundWords.value.length >= currentWheel.value.minWords) {
            setTimeout(() => nextWheel(), 1000)
        }
    } else {
        feedback.value = 'invalid'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        feedback.value = null
        userInput.value = ''
    }, 800)
}

const nextWheel = () => {
    if (timer) clearInterval(timer)
    
    if (currentWheelIndex.value < wheels.value.length - 1) {
        currentWheelIndex.value++
        foundWords.value = []
        userInput.value = ''
        timeLeft.value = 90
        timer = setInterval(() => {
            timeLeft.value--
            if (timeLeft.value <= 0) nextWheel()
        }, 1000)
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkWord()
    }
}

const feedbackMessage = computed(() => {
    switch (feedback.value) {
        case 'no_center': return `Must contain "${currentWheel.value?.center}"!`
        case 'too_short': return 'Minimum 3 letters!'
        case 'duplicate': return 'Already found!'
        case 'invalid': return 'Not a valid word!'
        case 'correct': return '✓ Great!'
        default: return ''
    }
})
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-md flex justify-between items-center mb-4 px-4">
            <span class="text-white/80">Wheel {{ currentWheelIndex + 1 }}/{{ wheels.length }}</span>
            <span class="px-3 py-1 rounded-full font-mono"
                :class="timeLeft <= 15 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                {{ timeLeft }}s
            </span>
        </div>
        
        <div class="relative w-64 h-64 mb-6">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg">
                <span class="text-3xl font-bold text-gray-900">{{ currentWheel?.center }}</span>
            </div>
            
            <div v-for="(letter, index) in currentWheel?.outer" :key="index"
                class="absolute w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg"
                :style="{
                    top: `${50 + 40 * Math.sin(index * Math.PI / 3 - Math.PI / 2)}%`,
                    left: `${50 + 40 * Math.cos(index * Math.PI / 3 - Math.PI / 2)}%`,
                    transform: 'translate(-50%, -50%)'
                }">
                <span class="text-xl font-bold text-gray-900">{{ letter }}</span>
            </div>
        </div>
        
        <p class="text-white/80 text-sm mb-2">Make words using the center letter!</p>
        <p class="text-white/60 text-sm mb-4">Found: {{ foundWords.length }} / {{ currentWheel?.minWords }} needed</p>
        
        <div class="flex flex-wrap justify-center gap-2 mb-4 max-w-md px-4">
            <span v-for="word in foundWords" :key="word"
                class="px-2 py-1 bg-green-500/30 text-green-300 rounded text-sm">
                {{ word }}
            </span>
        </div>
        
        <div class="flex space-x-2">
            <input
                v-model="userInput"
                @keydown="handleKeydown"
                type="text"
                maxlength="10"
                placeholder="Type a word..."
                class="px-4 py-3 text-lg text-center font-mono tracking-widest rounded-xl border-2 focus:outline-none uppercase w-40"
                :class="{
                    'border-white/30 bg-white/10 text-white': !feedback,
                    'border-green-500 bg-green-500/20': feedback === 'correct',
                    'border-red-500 bg-red-500/20': feedback && feedback !== 'correct',
                }"
            />
            <button @click="checkWord" :disabled="!userInput"
                class="px-6 py-3 bg-yellow-500 rounded-xl font-bold text-gray-900 hover:bg-yellow-400 disabled:opacity-50">
                Check
            </button>
        </div>
        
        <p v-if="feedback" class="mt-2 text-sm font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedbackMessage }}
        </p>
    </div>
</template>
