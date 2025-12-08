<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const items = ref(props.content?.picture_words || [
    { image: '/images/vocab/apple.jpg', word: 'apple', options: ['apple', 'orange', 'banana', 'grape'] },
    { image: '/images/vocab/house.jpg', word: 'house', options: ['house', 'tree', 'car', 'dog'] },
    { image: '/images/vocab/sun.jpg', word: 'sun', options: ['moon', 'star', 'sun', 'cloud'] },
    { image: '/images/vocab/book.jpg', word: 'book', options: ['pen', 'book', 'paper', 'desk'] },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const imageLoaded = ref(false)

const currentItem = computed(() => items.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentItem.value.word) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextItem(), 1500)
}

const nextItem = () => {
    if (currentIndex.value < items.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
        imageLoaded.value = false
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / items.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ items.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-pink-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-4 mb-6 shadow-xl">
            <div class="relative w-64 h-64 bg-gray-100 rounded-xl overflow-hidden">
                <img 
                    :src="currentItem?.image" 
                    :alt="currentItem?.word"
                    @load="imageLoaded = true"
                    class="w-full h-full object-cover transition-opacity"
                    :class="imageLoaded ? 'opacity-100' : 'opacity-0'"
                />
                <div v-if="!imageLoaded" class="absolute inset-0 flex items-center justify-center">
                    <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>
        </div>
        
        <p class="text-white/80 mb-4">What's in the picture?</p>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentItem?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium text-lg transition-all capitalize"
                :class="{
                    'bg-white text-gray-900 hover:bg-pink-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentItem?.word,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentItem?.word && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
    </div>
</template>
