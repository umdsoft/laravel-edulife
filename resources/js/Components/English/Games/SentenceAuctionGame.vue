<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.auction_sentences || [
    { sentence: 'She don\'t like coffee.', correct: false, correction: 'She doesn\'t like coffee.' },
    { sentence: 'I have been to Paris last year.', correct: false, correction: 'I went to Paris last year.' },
    { sentence: 'The children are playing in the garden.', correct: true, correction: null },
    { sentence: 'He told me that he will come.', correct: false, correction: 'He told me that he would come.' },
])

const budget = ref(100)
const currentIndex = ref(0)
const bidAmount = ref(10)
const hasBid = ref(false)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const placeBid = () => {
    if (bidAmount.value > budget.value) return
    budget.value -= bidAmount.value
    hasBid.value = true
}

const selectAnswer = (isCorrect) => {
    if (feedback.value || !hasBid.value) return
    
    selectedAnswer.value = isCorrect
    
    if (isCorrect === currentSentence.value.correct) {
        feedback.value = 'correct'
        const winnings = bidAmount.value * 2
        budget.value += winnings
        emit('score', bidAmount.value)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextSentence(), 2500)
}

const skip = () => {
    nextSentence()
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1 && budget.value > 0) {
        currentIndex.value++
        hasBid.value = false
        selectedAnswer.value = null
        feedback.value = null
        bidAmount.value = Math.min(10, budget.value)
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / sentences.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg flex justify-between items-center mb-4 px-4">
            <div class="text-white">
                <span class="text-sm opacity-80">Budget:</span>
                <span class="text-2xl font-bold ml-2">💰 {{ budget }}</span>
            </div>
            <span class="text-white/80">{{ currentIndex + 1 }}/{{ sentences.length }}</span>
        </div>
        
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">🔨</div>
        <p class="text-white/80 mb-4">Is this sentence correct or incorrect?</p>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-xl text-gray-900 text-center leading-relaxed">
                "{{ currentSentence?.sentence }}"
            </p>
        </div>
        
        <div v-if="!hasBid" class="w-full max-w-lg px-4 mb-4">
            <p class="text-white/80 text-center mb-2">Place your bid:</p>
            <div class="flex items-center justify-center space-x-4">
                <button @click="bidAmount = Math.max(5, bidAmount - 5)"
                    class="w-10 h-10 bg-white/20 rounded-full text-white font-bold">-</button>
                <span class="text-3xl font-bold text-white">💰 {{ bidAmount }}</span>
                <button @click="bidAmount = Math.min(budget, bidAmount + 5)"
                    class="w-10 h-10 bg-white/20 rounded-full text-white font-bold">+</button>
            </div>
            <div class="flex justify-center space-x-4 mt-4">
                <button @click="placeBid" :disabled="bidAmount > budget"
                    class="px-8 py-3 bg-amber-500 rounded-xl font-bold text-white hover:bg-amber-600 disabled:opacity-50">
                    Place Bid
                </button>
                <button @click="skip" class="px-8 py-3 bg-white/20 rounded-xl font-bold text-white hover:bg-white/30">
                    Skip
                </button>
            </div>
        </div>
        
        <div v-else-if="!feedback" class="flex space-x-4">
            <button @click="selectAnswer(true)"
                class="px-8 py-4 bg-green-500 rounded-xl font-bold text-white hover:bg-green-600">
                ✓ Correct
            </button>
            <button @click="selectAnswer(false)"
                class="px-8 py-4 bg-red-500 rounded-xl font-bold text-white hover:bg-red-600">
                ✗ Incorrect
            </button>
        </div>
        
        <div v-if="feedback" class="mt-4 text-center max-w-lg">
            <p class="text-xl font-bold mb-2"
                :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? `🎉 You won ${bidAmount * 2} coins!` : `😔 You lost ${bidAmount} coins` }}
            </p>
            <p v-if="!currentSentence?.correct" class="text-white/80">
                Correction: <span class="text-yellow-400">{{ currentSentence?.correction }}</span>
            </p>
        </div>
    </div>
</template>
