<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const passages = ref(props.content?.speed_reading || [
    {
        text: 'The sun was setting over the mountains. Birds flew home to their nests. A cool breeze rustled through the trees. It was a perfect evening.',
        questions: [
            { question: 'What was setting?', options: ['The moon', 'The sun', 'A star'], answer: 'The sun' },
            { question: 'Where did birds fly?', options: ['To the lake', 'To their nests', 'To the city'], answer: 'To their nests' },
        ],
        wordCount: 28,
        readingTime: 10
    },
])

const currentPassageIndex = ref(0)
const currentQuestionIndex = ref(0)
const phase = ref('reading')
const timeLeft = ref(10)
const selectedAnswer = ref(null)
const feedback = ref(null)
const startTime = ref(0)
const readingWPM = ref(0)

let timer = null

const currentPassage = computed(() => passages.value[currentPassageIndex.value])
const currentQuestion = computed(() => currentPassage.value?.questions[currentQuestionIndex.value])

onMounted(() => {
    startReading()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const startReading = () => {
    phase.value = 'reading'
    timeLeft.value = currentPassage.value?.readingTime || 10
    startTime.value = Date.now()
    
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            endReading()
        }
    }, 1000)
}

const endReading = () => {
    if (timer) clearInterval(timer)
    
    const elapsed = (Date.now() - startTime.value) / 1000 / 60
    readingWPM.value = Math.round(currentPassage.value.wordCount / elapsed)
    
    phase.value = 'questions'
}

const finishEarly = () => {
    endReading()
}

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentQuestion.value.answer) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextQuestion(), 1500)
}

const nextQuestion = () => {
    if (currentQuestionIndex.value < currentPassage.value.questions.length - 1) {
        currentQuestionIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else if (currentPassageIndex.value < passages.value.length - 1) {
        currentPassageIndex.value++
        currentQuestionIndex.value = 0
        selectedAnswer.value = null
        feedback.value = null
        startReading()
    } else {
        emit('complete')
    }
}

const progress = computed(() => {
    const total = passages.value.reduce((sum, p) => sum + p.questions.length, 0)
    let done = 0
    for (let i = 0; i < currentPassageIndex.value; i++) {
        done += passages.value[i].questions.length
    }
    if (phase.value === 'questions') {
        done += currentQuestionIndex.value
    }
    return Math.round((done / total) * 100)
})
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-2xl mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Passage {{ currentPassageIndex + 1 }} / {{ passages.length }}</span>
                <span v-if="phase === 'reading'" class="px-3 py-1 rounded-full"
                    :class="timeLeft <= 3 ? 'bg-red-500 animate-pulse' : 'bg-white/20'">
                    {{ timeLeft }}s
                </span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-lime-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <template v-if="phase === 'reading'">
            <div class="text-3xl mb-2">📖</div>
            <p class="text-white/80 mb-4">Read quickly and remember!</p>
            
            <div class="w-full max-w-2xl bg-white rounded-2xl p-6 mb-6 mx-4">
                <p class="text-lg text-gray-900 leading-relaxed">{{ currentPassage?.text }}</p>
            </div>
            
            <button @click="finishEarly"
                class="px-6 py-3 bg-lime-500 rounded-xl font-bold text-white hover:bg-lime-600">
                I'm Done Reading ✓
            </button>
        </template>
        
        <template v-else>
            <div class="bg-lime-500/30 rounded-xl px-4 py-2 mb-4">
                <span class="text-white">Reading Speed: <span class="font-bold">{{ readingWPM }} WPM</span></span>
            </div>
            
            <p class="text-white/80 mb-2">Question {{ currentQuestionIndex + 1 }} / {{ currentPassage?.questions.length }}</p>
            
            <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
                <p class="text-xl text-gray-900 text-center">{{ currentQuestion?.question }}</p>
            </div>
            
            <div class="w-full max-w-lg space-y-3 px-4">
                <button v-for="option in currentQuestion?.options" :key="option"
                    @click="selectAnswer(option)"
                    :disabled="feedback !== null"
                    class="w-full p-4 rounded-xl font-medium transition-all text-left"
                    :class="{
                        'bg-white text-gray-900 hover:bg-lime-50': !feedback,
                        'bg-green-500 text-white': feedback && option === currentQuestion?.answer,
                        'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                        'opacity-50': feedback && option !== currentQuestion?.answer && selectedAnswer !== option,
                    }">
                    {{ option }}
                </button>
            </div>
        </template>
    </div>
</template>
