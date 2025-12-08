<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.conditionals || [
    { 
        type: 'First Conditional',
        ifClause: 'If it rains tomorrow,',
        mainClause: 'I will stay at home.',
        blank: 'will stay',
        options: ['stay', 'will stay', 'would stay', 'stayed']
    },
    { 
        type: 'Second Conditional',
        ifClause: 'If I won the lottery,',
        mainClause: 'I would buy a house.',
        blank: 'would buy',
        options: ['buy', 'will buy', 'would buy', 'bought']
    },
    { 
        type: 'Zero Conditional',
        ifClause: 'If you heat water to 100°C,',
        mainClause: 'it boils.',
        blank: 'boils',
        options: ['boils', 'will boil', 'would boil', 'boiled']
    },
    { 
        type: 'Third Conditional',
        ifClause: 'If I had studied harder,',
        mainClause: 'I would have passed the exam.',
        blank: 'would have passed',
        options: ['passed', 'would pass', 'would have passed', 'had passed']
    },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentSentence.value.blank) {
        feedback.value = 'correct'
        emit('score', 20)
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
                <div class="h-full bg-emerald-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-emerald-500 rounded-xl px-4 py-2 mb-4">
            <span class="text-white font-medium">{{ currentSentence?.type }}</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <p class="text-xl text-gray-900 text-center">
                <span class="text-emerald-600 font-medium">{{ currentSentence?.ifClause }}</span>
                <br>
                I <span class="px-2 py-1 bg-yellow-200 rounded">______</span> ...
            </p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentSentence?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-emerald-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentSentence?.blank,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentSentence?.blank && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center max-w-lg">
            <p class="text-white">
                {{ currentSentence?.ifClause }} <span class="text-emerald-400 font-bold">{{ currentSentence?.mainClause }}</span>
            </p>
        </div>
    </div>
</template>
