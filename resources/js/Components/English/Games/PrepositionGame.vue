<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.prepositions || [
    { sentence: 'I live ___ London.', answer: 'in', options: ['in', 'on', 'at', 'to'] },
    { sentence: 'The book is ___ the table.', answer: 'on', options: ['in', 'on', 'at', 'under'] },
    { sentence: 'She arrived ___ 5 o\'clock.', answer: 'at', options: ['in', 'on', 'at', 'by'] },
    { sentence: 'We are going ___ holiday.', answer: 'on', options: ['in', 'on', 'at', 'for'] },
    { sentence: 'I\'m interested ___ art.', answer: 'in', options: ['in', 'on', 'at', 'about'] },
    { sentence: 'She\'s good ___ mathematics.', answer: 'at', options: ['in', 'on', 'at', 'with'] },
    { sentence: 'The meeting is ___ Monday.', answer: 'on', options: ['in', 'on', 'at', 'by'] },
    { sentence: 'I depend ___ my parents.', answer: 'on', options: ['in', 'on', 'at', 'for'] },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])
const displaySentence = computed(() => {
    if (!currentSentence.value) return ''
    return currentSentence.value.sentence.replace('___', '<span class="px-3 py-1 bg-yellow-300 text-yellow-900 rounded font-bold">___</span>')
})

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentSentence.value.answer) {
        feedback.value = 'correct'
        emit('score', 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextSentence(), 1500)
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / sentences.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ sentences.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-4xl mb-4">📍</div>
        <h3 class="text-white/80 mb-6">Choose the correct preposition</h3>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <p class="text-xl text-gray-900 text-center leading-relaxed" v-html="displaySentence"></p>
        </div>
        
        <div class="flex flex-wrap justify-center gap-3 px-4">
            <button v-for="option in currentSentence?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="px-8 py-4 rounded-xl font-bold text-lg transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-purple-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentSentence?.answer,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentSentence?.answer && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
