<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const transformations = ref(props.content?.transformations || [
    { original: 'He is taller than me.', type: 'comparison', keyword: 'as...as', answer: 'I am not as tall as him.', hint: 'Use negative form with "as...as"' },
    { original: 'She started learning English 5 years ago.', type: 'duration', keyword: 'for', answer: 'She has been learning English for 5 years.', hint: 'Use present perfect continuous' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const showHint = ref(false)

const currentTransform = computed(() => transformations.value[currentIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    const userAnswer = userInput.value.toLowerCase().trim().replace(/[.,!?]/g, '')
    const correctAnswer = currentTransform.value.answer.toLowerCase().replace(/[.,!?]/g, '')
    
    const similarity = calculateSimilarity(userAnswer, correctAnswer)
    
    if (similarity >= 0.85 && userAnswer.includes(currentTransform.value.keyword.toLowerCase().split('...')[0])) {
        feedback.value = 'correct'
        emit('score', 25)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextTransform(), 3000)
}

const calculateSimilarity = (str1, str2) => {
    const words1 = str1.split(' ')
    const words2 = str2.split(' ')
    let matches = 0
    words1.forEach(w => { if (words2.includes(w)) matches++ })
    return matches / Math.max(words1.length, words2.length)
}

const nextTransform = () => {
    if (currentIndex.value < transformations.value.length - 1) {
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

const progress = computed(() => Math.round(((currentIndex.value + 1) / transformations.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ transformations.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-violet-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">🔄</div>
        <p class="text-white/80 mb-4">Rewrite the sentence using the keyword</p>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-gray-500 text-sm text-center mb-2">Original:</p>
            <p class="text-xl text-gray-900 text-center">{{ currentTransform?.original }}</p>
        </div>
        
        <div class="bg-violet-500 rounded-xl px-6 py-3 mb-4">
            <span class="text-white font-bold text-lg">Use: "{{ currentTransform?.keyword }}"</span>
        </div>
        
        <button @click="showHint = !showHint" class="text-white/60 hover:text-white text-sm mb-2">
            {{ showHint ? 'Hide hint' : 'Show hint' }} 💡
        </button>
        <p v-if="showHint" class="text-yellow-400 text-sm mb-4">{{ currentTransform?.hint }}</p>
        
        <textarea
            v-model="userInput"
            :disabled="feedback !== null"
            rows="2"
            placeholder="Write the transformed sentence..."
            class="w-full max-w-lg px-4 py-3 rounded-xl border-2 focus:outline-none resize-none mx-4"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        ></textarea>
        
        <div v-if="feedback" class="mt-4 text-center max-w-lg">
            <p class="text-lg font-medium mb-2" :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : '✗ Not quite' }}
            </p>
            <p class="text-white/80 text-sm">Expected answer:</p>
            <p class="text-white font-medium">{{ currentTransform?.answer }}</p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="mt-4 px-8 py-3 bg-violet-500 rounded-xl font-bold text-white hover:bg-violet-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
