<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const passages = ref(props.content?.context_clues || [
    {
        text: 'The children were jubilant when they heard school was cancelled. They jumped up and down with excitement.',
        word: 'jubilant',
        meaning: 'very happy',
        options: ['very happy', 'very sad', 'very tired', 'very angry']
    },
    {
        text: 'The frugal man never wasted money. He always saved and never bought unnecessary things.',
        word: 'frugal',
        meaning: 'careful with money',
        options: ['careful with money', 'wasteful', 'generous', 'wealthy']
    },
    {
        text: 'The weather was so frigid that the lake froze completely. Everyone wore heavy coats.',
        word: 'frigid',
        meaning: 'extremely cold',
        options: ['extremely cold', 'very hot', 'rainy', 'windy']
    },
    {
        text: 'She was a novice at skiing, falling down many times on her first day on the slopes.',
        word: 'novice',
        meaning: 'beginner',
        options: ['beginner', 'expert', 'teacher', 'champion']
    },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentPassage = computed(() => passages.value[currentIndex.value])

const highlightedText = computed(() => {
    if (!currentPassage.value) return ''
    return currentPassage.value.text.replace(
        currentPassage.value.word,
        `<span class="bg-yellow-300 text-yellow-900 px-1 rounded font-bold">${currentPassage.value.word}</span>`
    )
})

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentPassage.value.meaning) {
        feedback.value = 'correct'
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextPassage(), 2000)
}

const nextPassage = () => {
    if (currentIndex.value < passages.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / passages.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-2xl mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ passages.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">🔍</div>
        <p class="text-white/80 mb-4">Use context clues to find the meaning</p>
        
        <div class="w-full max-w-2xl bg-white rounded-2xl p-6 mb-6 mx-4">
            <p class="text-lg text-gray-900 leading-relaxed" v-html="highlightedText"></p>
        </div>
        
        <p class="text-white mb-4">
            What does "<span class="font-bold text-amber-400">{{ currentPassage?.word }}</span>" mean?
        </p>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentPassage?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-amber-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentPassage?.meaning,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentPassage?.meaning && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
