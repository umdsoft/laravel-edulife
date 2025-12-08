<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const phrases = ref(props.content?.translations || [
    { english: 'Good morning', uzbek: 'Xayrli tong' },
    { english: 'How are you?', uzbek: 'Qalaysiz?' },
    { english: 'Thank you very much', uzbek: 'Katta rahmat' },
    { english: 'See you tomorrow', uzbek: 'Ertaga ko\'rishguncha' },
    { english: 'Nice to meet you', uzbek: 'Tanishganimdan xursandman' },
    { english: 'What is your name?', uzbek: 'Ismingiz nima?' },
    { english: 'I love learning English', uzbek: 'Men ingliz tilini o\'rganishni yaxshi ko\'raman' },
    { english: 'Have a nice day', uzbek: 'Kuningiz xayrli o\'tsin' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const timePerPhrase = ref(15)
const phraseTimer = ref(15)
const totalScore = ref(0)
const direction = ref('en_to_uz')

let timer = null

const currentPhrase = computed(() => phrases.value[currentIndex.value])
const sourceText = computed(() => 
    direction.value === 'en_to_uz' ? currentPhrase.value?.english : currentPhrase.value?.uzbek
)
const targetText = computed(() => 
    direction.value === 'en_to_uz' ? currentPhrase.value?.uzbek : currentPhrase.value?.english
)

onMounted(() => {
    direction.value = Math.random() > 0.5 ? 'en_to_uz' : 'uz_to_en'
    startTimer()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const startTimer = () => {
    phraseTimer.value = timePerPhrase.value
    timer = setInterval(() => {
        phraseTimer.value--
        if (phraseTimer.value <= 0) {
            handleTimeout()
        }
    }, 1000)
}

const handleTimeout = () => {
    if (!feedback.value) {
        feedback.value = 'timeout'
        playAudio('/sounds/timeout.mp3')
        setTimeout(() => nextPhrase(), 2000)
    }
}

const checkTranslation = () => {
    if (feedback.value) return
    
    clearInterval(timer)
    
    const userAnswer = userInput.value.trim().toLowerCase()
    const correctAnswer = targetText.value.toLowerCase()
    
    const similarity = calculateSimilarity(userAnswer, correctAnswer)
    
    if (similarity >= 0.8) {
        feedback.value = 'correct'
        const timeBonus = Math.floor(phraseTimer.value * 2)
        const points = 20 + timeBonus
        totalScore.value += points
        emit('score', points)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else if (similarity >= 0.5) {
        feedback.value = 'partial'
        const points = 10
        totalScore.value += points
        emit('score', points)
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextPhrase(), 2500)
}

const calculateSimilarity = (str1, str2) => {
    const len1 = str1.length
    const len2 = str2.length
    const matrix = []
    
    for (let i = 0; i <= len1; i++) {
        matrix[i] = [i]
    }
    for (let j = 0; j <= len2; j++) {
        matrix[0][j] = j
    }
    
    for (let i = 1; i <= len1; i++) {
        for (let j = 1; j <= len2; j++) {
            const cost = str1[i - 1] === str2[j - 1] ? 0 : 1
            matrix[i][j] = Math.min(
                matrix[i - 1][j] + 1,
                matrix[i][j - 1] + 1,
                matrix[i - 1][j - 1] + cost
            )
        }
    }
    
    const maxLen = Math.max(len1, len2)
    return maxLen === 0 ? 1 : 1 - matrix[len1][len2] / maxLen
}

const nextPhrase = () => {
    if (currentIndex.value < phrases.value.length - 1) {
        currentIndex.value++
        userInput.value = ''
        feedback.value = null
        direction.value = direction.value === 'en_to_uz' ? 'uz_to_en' : 'en_to_uz'
        startTimer()
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkTranslation()
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / phrases.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm">{{ currentIndex + 1 }} / {{ phrases.length }}</span>
                <div class="flex items-center space-x-4">
                    <span class="text-white font-bold">{{ totalScore }} pts</span>
                    <span class="w-12 h-12 rounded-full flex items-center justify-center font-mono font-bold text-xl"
                        :class="phraseTimer <= 5 ? 'bg-red-500 text-white animate-pulse' : 'bg-white text-gray-900'">
                        {{ phraseTimer }}
                    </span>
                </div>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-sky-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4 text-center">
            <div class="flex items-center justify-center space-x-2 mb-3">
                <span class="text-2xl">{{ direction === 'en_to_uz' ? '🇬🇧' : '🇺🇿' }}</span>
                <span class="text-gray-500">→</span>
                <span class="text-2xl">{{ direction === 'en_to_uz' ? '🇺🇿' : '🇬🇧' }}</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ sourceText }}</p>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            :placeholder="direction === 'en_to_uz' ? 'O\'zbekcha tarjima...' : 'English translation...'"
            class="w-full max-w-lg px-4 py-4 rounded-xl border-2 text-center text-lg focus:outline-none mx-4"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-yellow-500 bg-yellow-500/20 text-white': feedback === 'partial',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect' || feedback === 'timeout',
            }"
        />
        
        <div v-if="feedback" class="mt-4 text-center">
            <p class="text-lg font-medium mb-1"
                :class="{
                    'text-green-400': feedback === 'correct',
                    'text-yellow-400': feedback === 'partial',
                    'text-red-400': feedback === 'incorrect',
                    'text-orange-400': feedback === 'timeout',
                }">
                {{ feedback === 'correct' ? '✓ Perfect!' : 
                   feedback === 'partial' ? '~ Almost!' : 
                   feedback === 'timeout' ? '⏰ Time\'s up!' : '✗ Not quite' }}
            </p>
            <p class="text-white/80">
                Correct: <span class="font-medium">{{ targetText }}</span>
            </p>
        </div>
        
        <button v-if="!feedback" @click="checkTranslation" :disabled="!userInput.trim()"
            class="mt-6 px-8 py-3 bg-sky-500 rounded-xl font-bold text-white hover:bg-sky-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
