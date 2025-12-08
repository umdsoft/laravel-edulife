<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const questions = ref(props.content?.synonym_antonym || [
    { word: 'happy', type: 'synonym', answer: 'joyful', options: ['sad', 'joyful', 'angry', 'tired'] },
    { word: 'big', type: 'antonym', answer: 'small', options: ['large', 'huge', 'small', 'giant'] },
    { word: 'fast', type: 'synonym', answer: 'quick', options: ['slow', 'quick', 'lazy', 'heavy'] },
    { word: 'cold', type: 'antonym', answer: 'hot', options: ['cool', 'warm', 'hot', 'freezing'] },
    { word: 'beautiful', type: 'synonym', answer: 'gorgeous', options: ['ugly', 'gorgeous', 'plain', 'simple'] },
    { word: 'start', type: 'antonym', answer: 'finish', options: ['begin', 'finish', 'continue', 'pause'] },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const streak = ref(0)

const currentQuestion = computed(() => questions.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentQuestion.value.answer) {
        feedback.value = 'correct'
        streak.value++
        const points = 15 + (streak.value > 1 ? streak.value * 2 : 0)
        emit('score', points)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        streak.value = 0
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextQuestion(), 1500)
}

const nextQuestion = () => {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / questions.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ questions.length }}</span>
                <span v-if="streak > 1" class="text-orange-400">🔥 {{ streak }} streak!</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-6 text-center min-w-[300px]">
            <div class="inline-block px-4 py-1 rounded-full text-sm font-medium mb-4"
                :class="currentQuestion?.type === 'synonym' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'">
                {{ currentQuestion?.type === 'synonym' ? '🔄 Synonym' : '↔️ Antonym' }}
            </div>
            
            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ currentQuestion?.word }}</h2>
            <p class="text-gray-500">
                Find the {{ currentQuestion?.type === 'synonym' ? 'similar' : 'opposite' }} word
            </p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentQuestion?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all text-lg"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentQuestion?.answer,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentQuestion?.answer && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-lg font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedback === 'correct' ? '✓ Correct!' : `✗ Answer: ${currentQuestion?.answer}` }}
        </div>
    </div>
</template>
