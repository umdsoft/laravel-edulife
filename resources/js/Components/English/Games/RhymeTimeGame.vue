<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const rhymes = ref(props.content?.rhymes || [
    { word: 'cat', rhymes: ['hat', 'bat', 'rat', 'mat', 'sat', 'fat'], notRhymes: ['dog', 'cup', 'bed'] },
    { word: 'light', rhymes: ['night', 'right', 'fight', 'sight', 'bright'], notRhymes: ['day', 'dark', 'lamp'] },
    { word: 'cake', rhymes: ['make', 'take', 'lake', 'wake', 'bake'], notRhymes: ['bread', 'pie', 'cookie'] },
    { word: 'day', rhymes: ['play', 'say', 'way', 'may', 'stay'], notRhymes: ['night', 'week', 'time'] },
    { word: 'blue', rhymes: ['true', 'new', 'you', 'shoe', 'flew'], notRhymes: ['red', 'color', 'sky'] },
])

const currentIndex = ref(0)
const options = ref([])
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentRhyme = computed(() => rhymes.value[currentIndex.value])

const setupOptions = () => {
    const correct = currentRhyme.value.rhymes[Math.floor(Math.random() * currentRhyme.value.rhymes.length)]
    const wrong = currentRhyme.value.notRhymes.sort(() => Math.random() - 0.5).slice(0, 3)
    options.value = [correct, ...wrong].sort(() => Math.random() - 0.5)
}

setupOptions()

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (currentRhyme.value.rhymes.includes(option)) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextWord(), 1500)
}

const nextWord = () => {
    if (currentIndex.value < rhymes.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
        setupOptions()
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / rhymes.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ rhymes.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-pink-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-8 mb-6 text-center">
            <p class="text-gray-500 mb-2">Find a word that rhymes with:</p>
            <p class="text-5xl font-bold text-pink-600">{{ currentRhyme?.word }}</p>
            <p class="text-gray-400 mt-2 text-sm">🎵 Same ending sound</p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium text-lg transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-pink-50': !feedback,
                    'bg-green-500 text-white': feedback && currentRhyme?.rhymes.includes(option),
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option && !currentRhyme?.rhymes.includes(option),
                    'opacity-50': feedback && !currentRhyme?.rhymes.includes(option) && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center">
            <p class="text-white/60 text-sm">
                Words that rhyme with "{{ currentRhyme?.word }}":
            </p>
            <p class="text-white font-medium">
                {{ currentRhyme?.rhymes.slice(0, 4).join(', ') }}
            </p>
        </div>
    </div>
</template>
