<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const pyramidLevels = ref(props.content?.pyramid || [
    { level: 1, points: 10, words: [{ clue: 'Opposite of "hot"', answer: 'cold' }]},
    { level: 2, points: 20, words: [
        { clue: 'Day after Monday', answer: 'tuesday' },
        { clue: 'Color of grass', answer: 'green' },
    ]},
    { level: 3, points: 30, words: [
        { clue: 'Animal that barks', answer: 'dog' },
        { clue: 'We drink it every day', answer: 'water' },
        { clue: 'You read this', answer: 'book' },
    ]},
])

const currentLevel = ref(0)
const currentWordIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const totalScore = ref(0)

const currentPyramidLevel = computed(() => pyramidLevels.value[currentLevel.value])
const currentWord = computed(() => currentPyramidLevel.value?.words[currentWordIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    if (userInput.value.toLowerCase().trim() === currentWord.value.answer.toLowerCase()) {
        feedback.value = 'correct'
        totalScore.value += currentPyramidLevel.value.points
        emit('score', currentPyramidLevel.value.points)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        setTimeout(() => {
            if (currentWordIndex.value < currentPyramidLevel.value.words.length - 1) {
                currentWordIndex.value++
            } else if (currentLevel.value < pyramidLevels.value.length - 1) {
                currentLevel.value++
                currentWordIndex.value = 0
            } else {
                emit('complete')
                return
            }
            userInput.value = ''
            feedback.value = null
        }, 1000)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        setTimeout(() => { feedback.value = null }, 1000)
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="text-white mb-4">
            <span class="text-3xl font-bold">{{ totalScore }}</span>
            <span class="text-white/60 ml-2">points</span>
        </div>
        
        <div class="flex flex-col items-center mb-6">
            <div v-for="(level, index) in pyramidLevels" :key="index" class="flex justify-center mb-1">
                <div v-for="word in level.words.length" :key="word"
                    class="w-10 h-10 mx-0.5 rounded flex items-center justify-center text-xs font-bold"
                    :class="{
                        'bg-green-500 text-white': index < currentLevel || (index === currentLevel && word - 1 < currentWordIndex),
                        'bg-yellow-500 text-gray-900 animate-pulse': index === currentLevel && word - 1 === currentWordIndex,
                        'bg-white/20 text-white/50': index > currentLevel || (index === currentLevel && word - 1 > currentWordIndex),
                    }">
                    {{ level.points }}
                </div>
            </div>
        </div>
        
        <div class="bg-yellow-500 rounded-xl px-4 py-2 mb-4">
            <span class="font-bold text-gray-900">Level {{ currentLevel + 1 }} - {{ currentPyramidLevel?.points }} pts</span>
        </div>
        
        <div class="w-full max-w-md bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-gray-500 text-sm text-center mb-2">Clue:</p>
            <p class="text-xl text-gray-900 text-center">{{ currentWord?.clue }}</p>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Your answer..."
            class="w-full max-w-sm px-4 py-3 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <button @click="checkAnswer" :disabled="!userInput || feedback !== null"
            class="mt-4 px-8 py-3 bg-yellow-500 rounded-xl font-bold text-gray-900 hover:bg-yellow-400 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
