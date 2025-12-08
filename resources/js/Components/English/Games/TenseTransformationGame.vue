<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.tense_transform || [
    { original: 'I eat breakfast.', tense: 'Past Simple', answer: 'I ate breakfast.', verb: 'eat → ate' },
    { original: 'She goes to school.', tense: 'Present Continuous', answer: 'She is going to school.', verb: 'goes → is going' },
    { original: 'They play football.', tense: 'Future Simple', answer: 'They will play football.', verb: 'play → will play' },
    { original: 'He writes a letter.', tense: 'Present Perfect', answer: 'He has written a letter.', verb: 'writes → has written' },
    { original: 'We watch TV.', tense: 'Past Continuous', answer: 'We were watching TV.', verb: 'watch → were watching' },
    { original: 'I drink coffee.', tense: 'Past Simple', answer: 'I drank coffee.', verb: 'drink → drank' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const showHint = ref(false)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    const userAnswer = userInput.value.trim().toLowerCase().replace(/[.,!?]/g, '')
    const correctAnswer = currentSentence.value.answer.toLowerCase().replace(/[.,!?]/g, '')
    
    if (userAnswer === correctAnswer) {
        feedback.value = 'correct'
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextSentence(), 2500)
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1) {
        currentIndex.value++
        userInput.value = ''
        feedback.value = null
        showHint.value = false
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
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
                <div class="h-full bg-teal-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-teal-500 rounded-xl px-4 py-2 mb-4">
            <span class="text-white font-bold">Change to: {{ currentSentence?.tense }}</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-gray-500 text-sm text-center mb-2">Original (Present Simple):</p>
            <p class="text-xl text-gray-900 text-center font-medium">{{ currentSentence?.original }}</p>
        </div>
        
        <button @click="showHint = !showHint" class="text-white/60 hover:text-white text-sm mb-4">
            {{ showHint ? 'Hide hint' : 'Show hint' }} 💡
        </button>
        <p v-if="showHint" class="text-yellow-400 text-sm mb-4">{{ currentSentence?.verb }}</p>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Type the transformed sentence..."
            class="w-full max-w-lg px-4 py-3 text-center rounded-xl border-2 focus:outline-none mx-4"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <div v-if="feedback" class="mt-4 text-center">
            <p class="text-lg font-medium mb-2"
                :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : '✗ Incorrect' }}
            </p>
            <p class="text-white/80">Answer: <span class="font-medium">{{ currentSentence?.answer }}</span></p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="mt-4 px-8 py-3 bg-teal-500 rounded-xl font-bold text-white hover:bg-teal-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
