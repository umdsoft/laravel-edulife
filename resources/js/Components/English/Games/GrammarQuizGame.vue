<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'
import { useTimer } from '@/Composables/useTimer'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const questions = ref(props.content?.grammar_questions || [
    { question: 'She ___ to school every day.', options: ['go', 'goes', 'going', 'went'], correct: 'goes', explanation: 'Third person singular uses "goes".' },
    { question: 'I ___ watching TV right now.', options: ['am', 'is', 'are', 'be'], correct: 'am', explanation: 'First person singular uses "am".' },
    { question: 'They ___ already finished.', options: ['has', 'have', 'had', 'having'], correct: 'have', explanation: 'Plural subjects use "have".' },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const streak = ref(0)

const { time, formatTime, start, stop, reset } = useTimer({
    initialTime: 20,
    countdown: true,
    onComplete: () => handleTimeout(),
})

const currentQuestion = computed(() => questions.value[currentIndex.value])

const handleTimeout = () => {
    if (!feedback.value) {
        feedback.value = 'timeout'
        streak.value = 0
        playAudio('/sounds/timeout.mp3')
        setTimeout(() => nextQuestion(), 2000)
    }
}

const selectAnswer = (answer) => {
    if (feedback.value) return
    
    stop()
    selectedAnswer.value = answer
    
    if (answer === currentQuestion.value.correct) {
        feedback.value = 'correct'
        streak.value++
        const timeBonus = Math.floor(time.value / 2)
        const streakBonus = streak.value > 1 ? streak.value * 2 : 0
        emit('score', 15 + timeBonus + streakBonus)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        streak.value = 0
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextQuestion(), 2500)
}

const nextQuestion = () => {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
        reset(20)
        start()
    } else {
        emit('complete')
    }
}

start()

const progress = computed(() => Math.round(((currentIndex.value + 1) / questions.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm">Question {{ currentIndex + 1 }} / {{ questions.length }}</span>
                <div class="flex items-center space-x-2">
                    <span v-if="streak > 1" class="text-orange-400 text-sm">🔥 {{ streak }}</span>
                    <span class="px-3 py-1 rounded-full font-mono font-bold"
                        :class="time <= 5 ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
                        {{ formatTime(time) }}
                    </span>
                </div>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6">
            <h3 class="text-xl font-medium text-gray-900 text-center">
                {{ currentQuestion?.question }}
            </h3>
        </div>
        
        <div class="w-full max-w-lg space-y-3 px-4">
            <button
                v-for="option in currentQuestion?.options"
                :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="w-full p-4 rounded-xl font-medium transition-all text-left"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-50': !feedback && selectedAnswer !== option,
                    'bg-blue-500 text-white': selectedAnswer === option && !feedback,
                    'bg-green-500 text-white': feedback && option === currentQuestion?.correct,
                    'bg-red-500 text-white': feedback && selectedAnswer === option && option !== currentQuestion?.correct,
                    'opacity-50': feedback && option !== currentQuestion?.correct && selectedAnswer !== option,
                }"
            >
                {{ option }}
            </button>
        </div>
        
        <Transition name="fade">
            <div v-if="feedback" class="w-full max-w-lg mt-6 px-4">
                <div class="p-4 rounded-xl"
                    :class="{
                        'bg-green-500/30': feedback === 'correct',
                        'bg-red-500/30': feedback === 'incorrect',
                        'bg-orange-500/30': feedback === 'timeout',
                    }">
                    <p class="font-medium text-white mb-2">
                        <template v-if="feedback === 'correct'">✓ Correct!</template>
                        <template v-else-if="feedback === 'incorrect'">✗ Incorrect</template>
                        <template v-else>⏰ Time's up!</template>
                    </p>
                    <p class="text-white/80 text-sm">{{ currentQuestion?.explanation }}</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
