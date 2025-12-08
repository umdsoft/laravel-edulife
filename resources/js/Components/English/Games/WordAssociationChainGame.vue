<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const associations = ref(props.content?.word_associations || {
    'sun': ['hot', 'bright', 'day', 'summer', 'yellow', 'light', 'warm', 'sky'],
    'hot': ['cold', 'fire', 'summer', 'warm', 'heat', 'sun', 'temperature'],
    'cold': ['hot', 'winter', 'ice', 'snow', 'freeze', 'cool', 'temperature'],
    'winter': ['snow', 'cold', 'christmas', 'ice', 'summer', 'season'],
    'snow': ['white', 'cold', 'winter', 'ice', 'christmas', 'ski'],
})

const chain = ref(['sun'])
const userInput = ref('')
const feedback = ref(null)
const score = ref(0)
const timeLeft = ref(60)

let timer = null

const currentWord = computed(() => chain.value[chain.value.length - 1])
const validAssociations = computed(() => associations.value[currentWord.value] || [])

onMounted(() => {
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            emit('complete')
        }
    }, 1000)
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const checkWord = () => {
    if (feedback.value) return
    
    const word = userInput.value.toLowerCase().trim()
    
    if (chain.value.includes(word)) {
        feedback.value = 'used'
        playAudio('/sounds/incorrect.mp3')
    } else if (validAssociations.value.includes(word)) {
        feedback.value = 'correct'
        chain.value.push(word)
        score.value += 10
        emit('score', 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'invalid'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        feedback.value = null
        userInput.value = ''
    }, 500)
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkWord()
    }
}

const feedbackMessage = computed(() => {
    switch (feedback.value) {
        case 'used': return 'Already used!'
        case 'invalid': return 'Not associated!'
        case 'correct': return '✓ Great connection!'
        default: return ''
    }
})
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg flex justify-between items-center mb-4 px-4">
            <div class="text-white">
                <span class="text-2xl font-bold">{{ score }}</span>
                <span class="text-white/60 ml-1">pts</span>
            </div>
            <span class="px-3 py-1 rounded-full font-mono"
                :class="timeLeft <= 10 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                {{ timeLeft }}s
            </span>
        </div>
        
        <div class="w-full max-w-lg px-4 mb-4">
            <div class="flex flex-wrap items-center gap-1 p-3 bg-white/10 rounded-xl min-h-16">
                <span v-for="(word, index) in chain" :key="index" class="flex items-center">
                    <span class="px-2 py-1 bg-blue-500/30 text-blue-200 rounded text-sm">{{ word }}</span>
                    <span v-if="index < chain.length - 1" class="text-white/30 mx-1">→</span>
                </span>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-4 text-center">
            <p class="text-gray-500 text-sm mb-2">What is associated with:</p>
            <p class="text-4xl font-bold text-blue-600">{{ currentWord }}</p>
        </div>
        
        <div class="flex space-x-2">
            <input
                v-model="userInput"
                @keydown="handleKeydown"
                type="text"
                placeholder="Type associated word..."
                class="px-4 py-3 text-lg rounded-xl border-2 focus:outline-none w-48"
                :class="{
                    'border-white/30 bg-white/10 text-white': !feedback,
                    'border-green-500 bg-green-500/20': feedback === 'correct',
                    'border-red-500 bg-red-500/20': feedback && feedback !== 'correct',
                }"
            />
            <button @click="checkWord" :disabled="!userInput"
                class="px-6 py-3 bg-blue-500 rounded-xl font-bold text-white disabled:opacity-50">
                Link
            </button>
        </div>
        
        <p v-if="feedback" class="mt-2 text-sm font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedbackMessage }}
        </p>
        
        <p class="mt-4 text-white/60 text-sm">Chain length: {{ chain.length }} words</p>
    </div>
</template>
