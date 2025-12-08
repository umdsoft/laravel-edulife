<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTimer } from '@/Composables/useTimer'
import { useAudio } from '@/Composables/useAudio'
import { ClockIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    lesson: Object,
    vocabulary: Array,
})

const emit = defineEmits(['complete', 'back'])
const audio = useAudio()

const { time, formattedTime, start: startTimer, stop: stopTimer } = useTimer({ initialTime: 0 })

const questions = ref([])
const currentIndex = ref(0)
const selectedAnswer = ref(null)
const isAnswered = ref(false)
const answers = ref([])

const currentQuestion = computed(() => questions.value[currentIndex.value])
const isLast = computed(() => currentIndex.value === questions.value.length - 1)
const progress = computed(() => ((currentIndex.value + 1) / questions.value.length) * 100)

const correctCount = computed(() => answers.value.filter(a => a.correct).length)
const totalQuestions = computed(() => questions.value.length)
const percentage = computed(() => Math.round((correctCount.value / totalQuestions.value) * 100))

const generateQuestions = () => {
    if (!props.vocabulary?.length) return []
    
    return props.vocabulary.slice(0, 5).map((word, index) => {
        const otherWords = props.vocabulary.filter(w => w.id !== word.id)
        const wrongAnswers = otherWords.slice(0, 3).map(w => w.translation_uz)
        const options = [...wrongAnswers, word.translation_uz].sort(() => Math.random() - 0.5)
        
        return {
            id: word.id,
            question: `What is the meaning of "${word.word}"?`,
            options,
            correct_answer: word.translation_uz,
            word: word.word,
        }
    })
}

const selectAnswer = (answer) => {
    if (isAnswered.value) return
    
    selectedAnswer.value = answer
    isAnswered.value = true
    
    const isCorrect = answer === currentQuestion.value?.correct_answer
    answers.value.push({
        question_id: currentQuestion.value.id,
        answer,
        correct: isCorrect,
    })
    
    if (isCorrect) {
        audio.playCorrect()
    } else {
        audio.playWrong()
    }
}

const next = () => {
    if (isLast.value) {
        stopTimer()
        emit('complete', {
            correct_count: correctCount.value,
            total_count: totalQuestions.value,
            percentage: percentage.value,
            time_seconds: time.value,
            answers: answers.value,
        })
    } else {
        currentIndex.value++
        selectedAnswer.value = null
        isAnswered.value = false
    }
}

onMounted(() => {
    questions.value = generateQuestions()
    startTimer()
})
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Quiz</h2>
            <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                <ClockIcon class="w-5 h-5" />
                <span class="font-mono">{{ formattedTime }}</span>
            </div>
        </div>
        
        <!-- Progress -->
        <div class="mb-8">
            <div class="flex justify-between text-sm text-gray-500 mb-2">
                <span>Question {{ currentIndex + 1 }} of {{ questions.length }}</span>
                <span>{{ correctCount }} correct</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div 
                    class="h-full bg-gradient-to-r from-green-500 to-green-600 rounded-full transition-all duration-300"
                    :style="{ width: `${progress}%` }"
                ></div>
            </div>
        </div>
        
        <!-- Question -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-6">
            <p class="text-xl font-medium text-gray-900 dark:text-white text-center">
                {{ currentQuestion?.question }}
            </p>
        </div>
        
        <!-- Options -->
        <div class="grid grid-cols-2 gap-3 mb-8">
            <button
                v-for="option in currentQuestion?.options || []"
                :key="option"
                @click="selectAnswer(option)"
                :disabled="isAnswered"
                class="p-4 rounded-xl border-2 text-center transition-all font-medium"
                :class="{
                    'border-gray-200 dark:border-gray-700 hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20': !isAnswered,
                    'border-green-500 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200': isAnswered && option === currentQuestion?.correct_answer,
                    'border-red-500 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200': isAnswered && selectedAnswer === option && option !== currentQuestion?.correct_answer,
                    'border-gray-200 dark:border-gray-700 opacity-50': isAnswered && option !== selectedAnswer && option !== currentQuestion?.correct_answer,
                }"
            >
                <span>{{ option }}</span>
            </button>
        </div>
        
        <!-- Feedback & Next -->
        <div v-if="isAnswered" class="text-center">
            <div class="mb-4">
                <CheckCircleIcon v-if="selectedAnswer === currentQuestion?.correct_answer" class="w-16 h-16 text-green-500 mx-auto" />
                <XCircleIcon v-else class="w-16 h-16 text-red-500 mx-auto" />
            </div>
            <button
                @click="next"
                class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl font-medium hover:shadow-lg transition-all"
            >
                {{ isLast ? 'Finish Quiz' : 'Next Question' }}
            </button>
        </div>
    </div>
</template>
