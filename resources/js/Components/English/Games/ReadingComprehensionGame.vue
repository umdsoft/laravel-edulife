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

const passages = ref(props.content?.reading || [
    {
        title: 'My Daily Routine',
        text: `Every morning, I wake up at 7 o'clock. First, I brush my teeth and take a shower. Then, I eat breakfast with my family. I usually have eggs and toast. After breakfast, I go to school by bus. School starts at 8:30 and finishes at 3:00. After school, I do my homework and play with my friends.`,
        questions: [
            { question: 'What time does he wake up?', options: ['6 o\'clock', '7 o\'clock', '8 o\'clock', '9 o\'clock'], answer: '7 o\'clock' },
            { question: 'How does he go to school?', options: ['By car', 'By bus', 'On foot', 'By bike'], answer: 'By bus' },
            { question: 'What does he eat for breakfast?', options: ['Cereal', 'Pancakes', 'Eggs and toast', 'Nothing'], answer: 'Eggs and toast' },
        ]
    },
])

const currentPassageIndex = ref(0)
const currentQuestionIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const showPassage = ref(true)

const { time, formatTime, start, stop, reset } = useTimer({
    initialTime: 60,
    countdown: true,
    onComplete: () => {},
})

const currentPassage = computed(() => passages.value[currentPassageIndex.value])
const currentQuestion = computed(() => currentPassage.value?.questions[currentQuestionIndex.value])

start()

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentQuestion.value.answer) {
        feedback.value = 'correct'
        const timeBonus = Math.floor(time.value / 5)
        emit('score', 20 + timeBonus)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextQuestion(), 2000)
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
        reset(60)
        start()
    } else {
        emit('complete')
    }
}

const totalQuestions = computed(() => 
    passages.value.reduce((sum, p) => sum + p.questions.length, 0)
)
const currentQuestionNum = computed(() => {
    let num = 0
    for (let i = 0; i < currentPassageIndex.value; i++) {
        num += passages.value[i].questions.length
    }
    return num + currentQuestionIndex.value + 1
})
const progress = computed(() => Math.round((currentQuestionNum.value / totalQuestions.value) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-2xl mb-4 px-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-white/80 text-sm">Question {{ currentQuestionNum }} / {{ totalQuestions }}</span>
                <div class="flex items-center space-x-3">
                    <button @click="showPassage = !showPassage"
                        class="px-3 py-1 bg-white/20 rounded-lg text-white text-sm hover:bg-white/30">
                        {{ showPassage ? 'Hide' : 'Show' }} Text
                    </button>
                    <span class="px-3 py-1 bg-white/20 rounded-full text-white font-mono">
                        {{ formatTime(time) }}
                    </span>
                </div>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <Transition name="slide">
            <div v-if="showPassage" class="w-full max-w-2xl bg-white rounded-2xl p-6 mb-4 mx-4">
                <h3 class="text-lg font-bold text-gray-900 mb-3">{{ currentPassage?.title }}</h3>
                <p class="text-gray-700 leading-relaxed">{{ currentPassage?.text }}</p>
            </div>
        </Transition>
        
        <div class="w-full max-w-2xl bg-white/20 rounded-xl p-4 mb-4 mx-4">
            <h4 class="text-white text-lg font-medium text-center">
                {{ currentQuestion?.question }}
            </h4>
        </div>
        
        <div class="w-full max-w-2xl grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentQuestion?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-emerald-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentQuestion?.answer,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentQuestion?.answer && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.3s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; }
</style>
