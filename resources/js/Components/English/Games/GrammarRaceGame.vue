<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const questions = ref(props.content?.race_questions || [
    { sentence: 'She ___ to school every day.', answer: 'goes', options: ['go', 'goes', 'going', 'gone'] },
    { sentence: 'I ___ my homework yesterday.', answer: 'did', options: ['do', 'did', 'done', 'doing'] },
    { sentence: 'They ___ playing football now.', answer: 'are', options: ['is', 'are', 'was', 'were'] },
    { sentence: 'He ___ already eaten lunch.', answer: 'has', options: ['have', 'has', 'had', 'having'] },
])

const currentIndex = ref(0)
const playerPosition = ref(0)
const opponentPosition = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const raceFinished = ref(false)
const trackLength = 100

let opponentInterval = null

const currentQuestion = computed(() => questions.value[currentIndex.value])

onMounted(() => {
    opponentInterval = setInterval(() => {
        if (!raceFinished.value && opponentPosition.value < trackLength) {
            opponentPosition.value += 2
            if (opponentPosition.value >= trackLength) {
                raceFinished.value = true
                emit('complete')
            }
        }
    }, 500)
})

onUnmounted(() => {
    if (opponentInterval) clearInterval(opponentInterval)
})

const selectAnswer = (option) => {
    if (feedback.value || raceFinished.value) return
    
    selectedAnswer.value = option
    
    if (option === currentQuestion.value.answer) {
        feedback.value = 'correct'
        playerPosition.value = Math.min(trackLength, playerPosition.value + 20)
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        if (playerPosition.value >= trackLength) {
            raceFinished.value = true
            emit('score', 50)
            setTimeout(() => emit('complete'), 1500)
        }
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        if (!raceFinished.value && currentIndex.value < questions.value.length - 1) {
            currentIndex.value++
            selectedAnswer.value = null
            feedback.value = null
        } else if (!raceFinished.value) {
            currentIndex.value = 0
            selectedAnswer.value = null
            feedback.value = null
        }
    }, 1000)
}

const playerWinning = computed(() => playerPosition.value >= opponentPosition.value)
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg px-4 mb-6">
            <div class="relative h-12 bg-white/20 rounded-full mb-2 overflow-hidden">
                <div class="absolute top-0 right-0 h-full w-4 bg-green-500"></div>
                <div class="absolute top-1/2 -translate-y-1/2 text-2xl transition-all duration-300"
                    :style="{ left: `${playerPosition}%` }">
                    🏃
                </div>
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-white/60 text-sm">You</span>
            </div>
            
            <div class="relative h-12 bg-white/20 rounded-full overflow-hidden">
                <div class="absolute top-0 right-0 h-full w-4 bg-green-500"></div>
                <div class="absolute top-1/2 -translate-y-1/2 text-2xl transition-all duration-300"
                    :style="{ left: `${opponentPosition}%` }">
                    🤖
                </div>
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-white/60 text-sm">Bot</span>
            </div>
        </div>
        
        <p class="text-lg font-bold mb-4"
            :class="playerWinning ? 'text-green-400' : 'text-red-400'">
            {{ playerWinning ? '🏆 You\'re winning!' : '😰 Catch up!' }}
        </p>
        
        <div v-if="!raceFinished" class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-xl text-gray-900 text-center">{{ currentQuestion?.sentence }}</p>
        </div>
        
        <div v-if="!raceFinished" class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentQuestion?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentQuestion?.answer,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="raceFinished" class="text-center">
            <p class="text-4xl mb-2">{{ playerPosition >= trackLength ? '🏆' : '😢' }}</p>
            <p class="text-2xl font-bold"
                :class="playerPosition >= trackLength ? 'text-green-400' : 'text-red-400'">
                {{ playerPosition >= trackLength ? 'You Won!' : 'Bot Won!' }}
            </p>
        </div>
    </div>
</template>
