<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const collocations = ref(props.content?.collocations || [
    { word: 'make', collocate: 'decision', options: ['decision', 'homework', 'shower', 'walk'] },
    { word: 'do', collocate: 'homework', options: ['homework', 'decision', 'mistake', 'progress'] },
    { word: 'take', collocate: 'shower', options: ['shower', 'homework', 'decision', 'mistake'] },
    { word: 'have', collocate: 'breakfast', options: ['breakfast', 'shower', 'homework', 'walk'] },
    { word: 'pay', collocate: 'attention', options: ['attention', 'homework', 'decision', 'breakfast'] },
    { word: 'catch', collocate: 'cold', options: ['cold', 'attention', 'breakfast', 'homework'] },
    { word: 'keep', collocate: 'promise', options: ['promise', 'cold', 'attention', 'decision'] },
    { word: 'break', collocate: 'record', options: ['record', 'promise', 'attention', 'homework'] },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentCollocation = computed(() => collocations.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentCollocation.value.collocate) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextCollocation(), 1500)
}

const nextCollocation = () => {
    if (currentIndex.value < collocations.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / collocations.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ collocations.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-cyan-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-8 mb-6 text-center">
            <p class="text-gray-500 text-sm mb-2">Complete the collocation:</p>
            <div class="flex items-center justify-center space-x-4">
                <span class="text-4xl font-bold text-cyan-600">{{ currentCollocation?.word }}</span>
                <span class="text-4xl text-gray-300">+</span>
                <span class="text-4xl font-bold text-gray-300">?</span>
            </div>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentCollocation?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium text-lg transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-cyan-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentCollocation?.collocate,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentCollocation?.collocate && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center">
            <p class="text-lg font-bold"
                :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : '✗ Incorrect' }}
            </p>
            <p class="text-white mt-2">
                <span class="font-bold">{{ currentCollocation?.word }}</span> + 
                <span class="font-bold text-cyan-400">{{ currentCollocation?.collocate }}</span>
            </p>
        </div>
    </div>
</template>
