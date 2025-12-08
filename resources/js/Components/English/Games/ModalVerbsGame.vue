<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.modals || [
    { sentence: 'You ___ see a doctor. You look very sick.', answer: 'should', options: ['should', 'can', 'may', 'would'], meaning: 'advice' },
    { sentence: '___ I use your phone, please?', answer: 'May', options: ['May', 'Must', 'Should', 'Would'], meaning: 'permission (formal)' },
    { sentence: 'Students ___ wear uniforms at school.', answer: 'must', options: ['must', 'may', 'can', 'would'], meaning: 'obligation' },
    { sentence: 'She ___ speak three languages fluently.', answer: 'can', options: ['can', 'must', 'should', 'may'], meaning: 'ability' },
    { sentence: 'You ___ smoke in the hospital.', answer: "mustn't", options: ["mustn't", "don't have to", "shouldn't", "can't"], meaning: 'prohibition' },
    { sentence: 'It ___ rain tomorrow according to the forecast.', answer: 'might', options: ['might', 'must', 'should', 'can'], meaning: 'possibility' },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option.toLowerCase() === currentSentence.value.answer.toLowerCase()) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextSentence(), 2000)
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
                <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-4xl mb-2">🔧</div>
        <h3 class="text-white font-medium mb-4">Modal Verbs</h3>
        
        <div class="bg-white/20 rounded-xl px-3 py-1 mb-4">
            <span class="text-white/80 text-sm">Expressing: {{ currentSentence?.meaning }}</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <p class="text-xl text-gray-900 text-center">{{ currentSentence?.sentence }}</p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentSentence?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-50': !feedback,
                    'bg-green-500 text-white': feedback && option.toLowerCase() === currentSentence?.answer.toLowerCase(),
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option.toLowerCase() !== currentSentence?.answer.toLowerCase() && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
