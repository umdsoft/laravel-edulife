<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.speech || [
    { direct: 'He said, "I am happy."', indirect: 'He said that he was happy.', direction: 'to_indirect' },
    { direct: 'She said, "I will come tomorrow."', indirect: 'She said that she would come the next day.', direction: 'to_indirect' },
    { direct: '"I am going to the market," she said.', indirect: 'She said that she was going to the market.', direction: 'to_indirect' },
    { direct: 'He said, "I have finished my work."', indirect: 'He said that he had finished his work.', direction: 'to_indirect' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    const userAnswer = userInput.value.trim().toLowerCase()
    const correctAnswer = currentSentence.value.indirect.toLowerCase()
    
    const similarity = calculateSimilarity(userAnswer, correctAnswer)
    
    if (similarity >= 0.85) {
        feedback.value = 'correct'
        emit('score', 25)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextSentence(), 3000)
}

const calculateSimilarity = (str1, str2) => {
    const s1 = str1.replace(/[.,!?"']/g, '').split(' ')
    const s2 = str2.replace(/[.,!?"']/g, '').split(' ')
    let matches = 0
    s1.forEach(word => {
        if (s2.includes(word)) matches++
    })
    return matches / Math.max(s1.length, s2.length)
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1) {
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

const progress = computed(() => Math.round(((currentIndex.value + 1) / sentences.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ sentences.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-rose-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-rose-500 rounded-xl px-4 py-2 mb-4">
            <span class="text-white font-medium">Direct → Indirect (Reported) Speech</span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-gray-500 text-sm text-center mb-2">Direct Speech:</p>
            <p class="text-xl text-gray-900 text-center font-medium">{{ currentSentence?.direct }}</p>
        </div>
        
        <textarea
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            rows="2"
            placeholder="Write in indirect (reported) speech..."
            class="w-full max-w-lg px-4 py-3 text-center rounded-xl border-2 focus:outline-none resize-none mx-4"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        ></textarea>
        
        <div v-if="feedback" class="mt-4 text-center max-w-lg">
            <p class="text-lg font-medium mb-2"
                :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : '✗ Not quite right' }}
            </p>
            <p class="text-white/80 text-sm">Correct answer:</p>
            <p class="text-white font-medium">{{ currentSentence?.indirect }}</p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="mt-4 px-8 py-3 bg-rose-500 rounded-xl font-bold text-white hover:bg-rose-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
