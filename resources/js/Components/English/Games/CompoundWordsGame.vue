<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const compounds = ref(props.content?.compounds || [
    { first: 'sun', second: 'flower', compound: 'sunflower', image: '🌻' },
    { first: 'rain', second: 'bow', compound: 'rainbow', image: '🌈' },
    { first: 'butter', second: 'fly', compound: 'butterfly', image: '🦋' },
    { first: 'foot', second: 'ball', compound: 'football', image: '⚽' },
    { first: 'tooth', second: 'brush', compound: 'toothbrush', image: '🪥' },
    { first: 'water', second: 'fall', compound: 'waterfall', image: '💧' },
    { first: 'book', second: 'shelf', compound: 'bookshelf', image: '📚' },
    { first: 'cup', second: 'cake', compound: 'cupcake', image: '🧁' },
])

const currentIndex = ref(0)
const options = ref([])
const selectedSecond = ref(null)
const feedback = ref(null)

const currentCompound = computed(() => compounds.value[currentIndex.value])

const setupOptions = () => {
    const otherCompounds = compounds.value.filter((_, i) => i !== currentIndex.value)
    const wrongSeconds = otherCompounds.map(c => c.second).slice(0, 3)
    options.value = [currentCompound.value.second, ...wrongSeconds].sort(() => Math.random() - 0.5)
}

setupOptions()

const selectSecond = (option) => {
    if (feedback.value) return
    
    selectedSecond.value = option
    
    if (option === currentCompound.value.second) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextWord(), 2000)
}

const nextWord = () => {
    if (currentIndex.value < compounds.value.length - 1) {
        currentIndex.value++
        selectedSecond.value = null
        feedback.value = null
        setupOptions()
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / compounds.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ compounds.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-yellow-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-6">
            <div class="text-6xl text-center mb-4">{{ currentCompound?.image }}</div>
            <div class="flex items-center justify-center space-x-4">
                <div class="px-6 py-3 bg-yellow-100 rounded-xl">
                    <span class="text-2xl font-bold text-yellow-700">{{ currentCompound?.first }}</span>
                </div>
                <span class="text-3xl text-gray-400">+</span>
                <div class="px-6 py-3 bg-gray-100 rounded-xl">
                    <span class="text-2xl font-bold text-gray-400">?</span>
                </div>
            </div>
        </div>
        
        <p class="text-white/80 mb-4">Complete the compound word</p>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in options" :key="option"
                @click="selectSecond(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium text-lg transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-yellow-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentCompound?.second,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedSecond === option,
                    'opacity-50': feedback && option !== currentCompound?.second && selectedSecond !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center">
            <p class="text-2xl font-bold text-white">
                {{ currentCompound?.first }}<span class="text-yellow-400">{{ currentCompound?.second }}</span> = {{ currentCompound?.compound }}
            </p>
        </div>
    </div>
</template>
