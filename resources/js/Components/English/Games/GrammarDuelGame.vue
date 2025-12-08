<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const questions = ref(props.content?.duel_questions || [
    { sentence: 'I ___ to school yesterday.', answer: 'went', options: ['go', 'went', 'gone', 'going'] },
    { sentence: 'She ___ cooking dinner now.', answer: 'is', options: ['is', 'are', 'was', 'were'] },
    { sentence: 'They have ___ finished.', answer: 'already', options: ['yet', 'already', 'still', 'just'] },
    { sentence: 'He ___ play tennis very well.', answer: 'can', options: ['can', 'must', 'should', 'will'] },
])

const currentIndex = ref(0)
const playerScore = ref(0)
const botScore = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const timeLeft = ref(10)
const roundWinner = ref(null)

let timer = null

const currentQuestion = computed(() => questions.value[currentIndex.value])

onMounted(() => {
    startTimer()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const startTimer = () => {
    timeLeft.value = 10
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            botWins()
        }
    }, 1000)
}

const selectAnswer = (option) => {
    if (feedback.value) return
    if (timer) clearInterval(timer)
    
    selectedAnswer.value = option
    
    if (option === currentQuestion.value.answer) {
        feedback.value = 'correct'
        playerScore.value++
        roundWinner.value = 'player'
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        botScore.value++
        roundWinner.value = 'bot'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextQuestion(), 2000)
}

const botWins = () => {
    if (timer) clearInterval(timer)
    feedback.value = 'timeout'
    botScore.value++
    roundWinner.value = 'bot'
    playAudio('/sounds/incorrect.mp3')
    
    setTimeout(() => nextQuestion(), 2000)
}

const nextQuestion = () => {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
        roundWinner.value = null
        startTimer()
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / questions.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg flex justify-between items-center mb-4 px-4">
            <div class="text-center">
                <div class="text-3xl mb-1">🧑</div>
                <p class="text-2xl font-bold text-white">{{ playerScore }}</p>
                <p class="text-white/60 text-sm">You</p>
            </div>
            
            <div class="text-center">
                <div class="px-4 py-2 rounded-full font-mono font-bold text-xl"
                    :class="timeLeft <= 3 ? 'bg-red-500 text-white animate-pulse' : 'bg-white text-gray-900'">
                    {{ timeLeft }}
                </div>
                <p class="text-white/60 text-sm mt-2">Round {{ currentIndex + 1 }}/{{ questions.length }}</p>
            </div>
            
            <div class="text-center">
                <div class="text-3xl mb-1">🤖</div>
                <p class="text-2xl font-bold text-white">{{ botScore }}</p>
                <p class="text-white/60 text-sm">Bot</p>
            </div>
        </div>
        
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div v-if="roundWinner" class="mb-4 text-xl font-bold"
            :class="roundWinner === 'player' ? 'text-green-400' : 'text-red-400'">
            {{ roundWinner === 'player' ? '🎉 You won this round!' : '😔 Bot won this round!' }}
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-xl text-gray-900 text-center">{{ currentQuestion?.sentence }}</p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentQuestion?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-purple-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentQuestion?.answer,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentQuestion?.answer && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
