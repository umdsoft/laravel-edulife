<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const clues = ref(props.content?.word_clues || [
    {
        clues: ['A place where you sleep', 'Has a mattress and pillow', 'Usually in a bedroom'],
        word: 'bed',
        options: ['bed', 'sofa', 'chair', 'table']
    },
    {
        clues: ['An animal with four legs', 'Says "woof"', 'Often called man\'s best friend'],
        word: 'dog',
        options: ['cat', 'dog', 'bird', 'fish']
    },
    {
        clues: ['Round and red (usually)', 'A fruit', 'Keeps the doctor away'],
        word: 'apple',
        options: ['orange', 'banana', 'apple', 'grape']
    },
    {
        clues: ['Has 365 days', 'Has 12 months', 'Starts in January'],
        word: 'year',
        options: ['month', 'week', 'year', 'day']
    },
    {
        clues: ['You read it', 'Has pages', 'Written by an author'],
        word: 'book',
        options: ['book', 'magazine', 'newspaper', 'letter']
    },
])

const currentIndex = ref(0)
const revealedClues = ref(1)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentClue = computed(() => clues.value[currentIndex.value])
const visibleClues = computed(() => currentClue.value?.clues.slice(0, revealedClues.value) || [])

const revealNextClue = () => {
    if (revealedClues.value < currentClue.value.clues.length) {
        revealedClues.value++
    }
}

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentClue.value.word) {
        feedback.value = 'correct'
        const points = (currentClue.value.clues.length - revealedClues.value + 1) * 10
        emit('score', points)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextWord(), 2000)
}

const nextWord = () => {
    if (currentIndex.value < clues.value.length - 1) {
        currentIndex.value++
        revealedClues.value = 1
        selectedAnswer.value = null
        feedback.value = null
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / clues.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ clues.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-red-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-4xl mb-2">🕵️</div>
        <p class="text-white/80 mb-4">Guess the word from the clues!</p>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <div class="space-y-3">
                <div v-for="(clue, index) in visibleClues" :key="index"
                    class="flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-sm font-bold">
                        {{ index + 1 }}
                    </span>
                    <p class="text-gray-900">{{ clue }}</p>
                </div>
                
                <div v-for="i in (currentClue?.clues.length - revealedClues)" :key="'hidden-' + i"
                    class="flex items-start space-x-3 opacity-30">
                    <span class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-sm">?</span>
                    <p class="text-gray-400">Hidden clue...</p>
                </div>
            </div>
        </div>
        
        <button v-if="revealedClues < currentClue?.clues.length && !feedback"
            @click="revealNextClue"
            class="mb-4 px-4 py-2 bg-white/20 rounded-xl text-white text-sm hover:bg-white/30">
            Reveal next clue (-10 points)
        </button>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentClue?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium text-lg transition-all capitalize"
                :class="{
                    'bg-white text-gray-900 hover:bg-red-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentClue?.word,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentClue?.word && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
