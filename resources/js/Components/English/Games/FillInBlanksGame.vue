<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.fill_blanks || [
    { sentence: 'I ___ to school every day.', blank: 'go', options: ['go', 'goes', 'going', 'went'] },
    { sentence: 'She ___ reading a book now.', blank: 'is', options: ['is', 'are', 'am', 'be'] },
    { sentence: 'We ___ dinner at 7 PM yesterday.', blank: 'had', options: ['have', 'has', 'had', 'having'] },
    { sentence: 'They ___ playing football tomorrow.', blank: 'will be', options: ['will be', 'are', 'were', 'was'] },
])
const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])
const displaySentence = computed(() => {
    if (!currentSentence.value) return ''
    return currentSentence.value.sentence.replace('___', '______')
})

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentSentence.value.blank) {
        feedback.value = 'correct'
        emit('score', 15)
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
                <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 text-center">
            <p class="text-xl text-gray-900 leading-relaxed">
                {{ displaySentence }}
            </p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentSentence?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentSentence?.blank,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentSentence?.blank && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-lg font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedback === 'correct' ? '✓ Correct!' : `✗ Answer: ${currentSentence?.blank}` }}
        </div>
    </div>
</template>
