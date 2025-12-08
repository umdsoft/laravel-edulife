<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.articles || [
    { sentence: 'I saw ___ elephant at the zoo.', answer: 'an', options: ['a', 'an', 'the', '—'] },
    { sentence: '___ sun rises in the east.', answer: 'The', options: ['A', 'An', 'The', '—'] },
    { sentence: 'She is ___ teacher.', answer: 'a', options: ['a', 'an', 'the', '—'] },
    { sentence: 'I love ___ music.', answer: '—', options: ['a', 'an', 'the', '—'] },
    { sentence: 'This is ___ best restaurant in town.', answer: 'the', options: ['a', 'an', 'the', '—'] },
    { sentence: 'He plays ___ guitar very well.', answer: 'the', options: ['a', 'an', 'the', '—'] },
    { sentence: 'I need ___ umbrella.', answer: 'an', options: ['a', 'an', 'the', '—'] },
    { sentence: '___ honesty is the best policy.', answer: '—', options: ['A', 'An', 'The', '—'] },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    const correct = option.toLowerCase() === currentSentence.value.answer.toLowerCase() ||
                   (option === '—' && currentSentence.value.answer === '—')
    
    if (correct) {
        feedback.value = 'correct'
        emit('score', 10)
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
                <div class="h-full bg-amber-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white/20 rounded-xl px-4 py-2 mb-6">
            <span class="text-white font-medium">A / An / The / — (no article)</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <p class="text-xl text-gray-900 text-center leading-relaxed">
                {{ currentSentence?.sentence }}
            </p>
        </div>
        
        <div class="grid grid-cols-4 gap-3 px-4">
            <button v-for="option in currentSentence?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="px-6 py-4 rounded-xl font-bold text-lg transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-amber-50': !feedback,
                    'bg-green-500 text-white': feedback && (option.toLowerCase() === currentSentence?.answer.toLowerCase() || (option === '—' && currentSentence?.answer === '—')),
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && !(option.toLowerCase() === currentSentence?.answer.toLowerCase() || (option === '—' && currentSentence?.answer === '—')) && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center">
            <p class="text-white/80">
                Answer: <span class="font-bold text-amber-400">{{ currentSentence?.answer === '—' ? 'No article' : currentSentence?.answer }}</span>
            </p>
        </div>
    </div>
</template>
