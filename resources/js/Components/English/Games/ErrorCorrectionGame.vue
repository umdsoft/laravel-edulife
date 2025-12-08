<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.error_sentences || [
    { 
        wrong: 'She go to school every day.', 
        correct: 'She goes to school every day.',
        errorWord: 'go',
        correctWord: 'goes',
        explanation: 'Third person singular (she/he/it) requires "goes" not "go".'
    },
    { 
        wrong: 'I have went to Paris last year.', 
        correct: 'I went to Paris last year.',
        errorWord: 'have went',
        correctWord: 'went',
        explanation: 'Simple past tense should be "went", not "have went".'
    },
    { 
        wrong: 'He is more taller than me.', 
        correct: 'He is taller than me.',
        errorWord: 'more taller',
        correctWord: 'taller',
        explanation: 'Don\'t use "more" with comparative adjectives ending in -er.'
    },
    { 
        wrong: 'I am agree with you.', 
        correct: 'I agree with you.',
        errorWord: 'am agree',
        correctWord: 'agree',
        explanation: '"Agree" is a verb, not an adjective. Don\'t use "am" before it.'
    },
    { 
        wrong: 'She suggested me to go.', 
        correct: 'She suggested that I go.',
        errorWord: 'suggested me to',
        correctWord: 'suggested that I',
        explanation: '"Suggest" is followed by "that + subject + verb", not "object + to + verb".'
    },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const showExplanation = ref(false)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const highlightedSentence = computed(() => {
    if (!currentSentence.value) return ''
    return currentSentence.value.wrong.replace(
        currentSentence.value.errorWord,
        `<span class="bg-red-200 px-1 rounded underline decoration-red-500 decoration-wavy">${currentSentence.value.errorWord}</span>`
    )
})

const checkAnswer = () => {
    if (feedback.value) return
    
    const userAnswer = userInput.value.trim().toLowerCase()
    const correctAnswer = currentSentence.value.correct.toLowerCase()
    
    if (userAnswer === correctAnswer) {
        feedback.value = 'correct'
        showExplanation.value = true
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        showExplanation.value = true
        playAudio('/sounds/incorrect.mp3')
    }
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1) {
        currentIndex.value++
        userInput.value = ''
        feedback.value = null
        showExplanation.value = false
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
                <div class="h-full bg-red-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <div class="flex items-center justify-center mb-4">
                <span class="text-3xl">❌</span>
            </div>
            <p class="text-xl text-gray-900 text-center leading-relaxed" v-html="highlightedSentence"></p>
            <p class="text-sm text-gray-500 text-center mt-2">Find and fix the error</p>
        </div>
        
        <div class="w-full max-w-lg px-4 mb-6">
            <input
                v-model="userInput"
                @keydown="handleKeydown"
                :disabled="feedback !== null"
                type="text"
                placeholder="Type the correct sentence..."
                class="w-full px-4 py-3 rounded-xl border-2 focus:outline-none text-center"
                :class="{
                    'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                    'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                    'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
                }"
            />
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="px-8 py-3 bg-white rounded-xl font-bold text-red-600 hover:bg-red-50 disabled:opacity-50 mb-6">
            Check
        </button>
        
        <Transition name="fade">
            <div v-if="showExplanation" class="w-full max-w-lg px-4">
                <div class="rounded-xl p-4 mb-4"
                    :class="feedback === 'correct' ? 'bg-green-500/30' : 'bg-red-500/30'">
                    <p class="text-white font-medium mb-2">
                        {{ feedback === 'correct' ? '✓ Correct!' : '✗ Not quite right' }}
                    </p>
                    <p class="text-white/80 text-sm mb-2">
                        Correct: <span class="font-medium">{{ currentSentence?.correct }}</span>
                    </p>
                    <p class="text-white/60 text-sm">
                        💡 {{ currentSentence?.explanation }}
                    </p>
                </div>
                
                <button @click="nextSentence"
                    class="w-full py-3 bg-white rounded-xl font-bold text-red-600 hover:bg-red-50">
                    {{ currentIndex < sentences.length - 1 ? 'Next Sentence' : 'Finish' }}
                </button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(10px); }
</style>
