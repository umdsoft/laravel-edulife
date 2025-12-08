<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const items = ref(props.content?.comparatives || [
    { adjective: 'big', comparative: 'bigger', superlative: 'biggest', type: 'comparative' },
    { adjective: 'beautiful', comparative: 'more beautiful', superlative: 'most beautiful', type: 'superlative' },
    { adjective: 'good', comparative: 'better', superlative: 'best', type: 'comparative' },
    { adjective: 'happy', comparative: 'happier', superlative: 'happiest', type: 'superlative' },
    { adjective: 'expensive', comparative: 'more expensive', superlative: 'most expensive', type: 'comparative' },
    { adjective: 'bad', comparative: 'worse', superlative: 'worst', type: 'superlative' },
    { adjective: 'far', comparative: 'farther', superlative: 'farthest', type: 'comparative' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)

const currentItem = computed(() => items.value[currentIndex.value])
const targetForm = computed(() => 
    currentItem.value?.type === 'comparative' 
        ? currentItem.value?.comparative 
        : currentItem.value?.superlative
)

const checkAnswer = () => {
    if (feedback.value) return
    
    if (userInput.value.toLowerCase().trim() === targetForm.value.toLowerCase()) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextItem(), 2000)
}

const nextItem = () => {
    if (currentIndex.value < items.value.length - 1) {
        currentIndex.value++
        userInput.value = ''
        feedback.value = null
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
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
                <div class="h-full bg-orange-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="px-4 py-2 rounded-xl mb-4"
            :class="currentItem?.type === 'comparative' ? 'bg-blue-500' : 'bg-purple-500'">
            <span class="text-white font-bold">
                {{ currentItem?.type === 'comparative' ? 'Comparative (-er / more)' : 'Superlative (-est / most)' }}
            </span>
        </div>
        
        <div class="bg-white rounded-2xl p-8 mb-6 text-center">
            <p class="text-gray-500 mb-2">Base form:</p>
            <p class="text-5xl font-bold text-gray-900">{{ currentItem?.adjective }}</p>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            :placeholder="currentItem?.type === 'comparative' ? 'Type comparative form...' : 'Type superlative form...'"
            class="w-full max-w-sm px-4 py-3 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <div v-if="feedback" class="mt-4 text-center">
            <p class="text-lg font-medium"
                :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : '✗ Incorrect' }}
            </p>
            <p class="text-white mt-2">
                {{ currentItem?.adjective }} → 
                <span class="text-blue-400">{{ currentItem?.comparative }}</span> → 
                <span class="text-purple-400">{{ currentItem?.superlative }}</span>
            </p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="mt-4 px-8 py-3 bg-orange-500 rounded-xl font-bold text-white hover:bg-orange-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
