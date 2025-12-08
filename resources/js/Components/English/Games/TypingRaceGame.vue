<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref([])
const currentIndex = ref(0)
const userInput = ref('')
const timeLeft = ref(60)
const isGameActive = ref(false)
const wordsCompleted = ref(0)
const totalChars = ref(0)
const correctChars = ref(0)

let timer = null
const inputRef = ref(null)

onMounted(() => {
    words.value = (props.content?.words || [])
        .sort(() => Math.random() - 0.5)
        .map(w => w.word.toLowerCase())
    startGame()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const currentWord = computed(() => words.value[currentIndex.value] || '')

const typedChars = computed(() => {
    return currentWord.value.split('').map((char, i) => {
        if (i >= userInput.value.length) return { char, status: 'pending' }
        return {
            char,
            status: userInput.value[i] === char ? 'correct' : 'incorrect',
        }
    })
})

const wpm = computed(() => {
    const minutes = (60 - timeLeft.value) / 60
    if (minutes === 0) return 0
    return Math.round((correctChars.value / 5) / minutes)
})

const accuracy = computed(() => {
    if (totalChars.value === 0) return 100
    return Math.round((correctChars.value / totalChars.value) * 100)
})

const startGame = () => {
    isGameActive.value = true
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            endGame()
        }
    }, 1000)
    
    setTimeout(() => inputRef.value?.focus(), 100)
}

const endGame = () => {
    isGameActive.value = false
    if (timer) clearInterval(timer)
    
    const score = wordsCompleted.value * 10 + wpm.value
    emit('score', score)
    emit('complete')
}

watch(userInput, (newVal) => {
    if (!isGameActive.value) return
    
    totalChars.value++
    
    const currentChar = newVal[newVal.length - 1]
    const expectedChar = currentWord.value[newVal.length - 1]
    
    if (currentChar === expectedChar) {
        correctChars.value++
    }
    
    if (newVal === currentWord.value) {
        wordsCompleted.value++
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        userInput.value = ''
        if (currentIndex.value < words.value.length - 1) {
            currentIndex.value++
        } else {
            words.value = words.value.sort(() => Math.random() - 0.5)
            currentIndex.value = 0
        }
    }
})

const handleKeydown = (e) => {
    if (e.key === ' ' && userInput.value === '') {
        e.preventDefault()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg flex justify-between items-center mb-6 px-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-white">{{ wordsCompleted }}</p>
                <p class="text-white/60 text-sm">Words</p>
            </div>
            
            <div class="text-center">
                <p class="text-2xl font-bold text-white">{{ wpm }}</p>
                <p class="text-white/60 text-sm">WPM</p>
            </div>
            
            <div class="px-4 py-2 rounded-full font-mono font-bold text-xl"
                :class="timeLeft <= 10 ? 'bg-red-500 text-white animate-pulse' : 'bg-white text-gray-900'">
                {{ timeLeft }}s
            </div>
            
            <div class="text-center">
                <p class="text-2xl font-bold text-white">{{ accuracy }}%</p>
                <p class="text-white/60 text-sm">Accuracy</p>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-8 mb-6 min-w-[320px]">
            <div class="flex justify-center text-3xl font-mono tracking-wider">
                <span v-for="(item, index) in typedChars" :key="index"
                    :class="{
                        'text-gray-300': item.status === 'pending',
                        'text-green-500': item.status === 'correct',
                        'text-red-500 bg-red-100': item.status === 'incorrect',
                    }">
                    {{ item.char }}
                </span>
            </div>
        </div>
        
        <input
            ref="inputRef"
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="!isGameActive"
            type="text"
            autocomplete="off"
            autocapitalize="off"
            class="w-full max-w-sm px-6 py-4 text-xl text-center rounded-xl border-2 border-white/30 bg-white/10 text-white placeholder-white/50 focus:outline-none focus:border-white"
            placeholder="Start typing..."
        />
        
        <div class="flex space-x-4 mt-6 text-white/40">
            <span v-for="i in 3" :key="i" class="text-sm">
                {{ words[(currentIndex + i) % words.length] }}
            </span>
        </div>
    </div>
</template>
