<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const questions = ref(props.content?.phrasal_verbs || [
    { 
        sentence: 'I need to ___ early tomorrow for the meeting.',
        verb: 'get up',
        options: ['get up', 'get in', 'get out', 'get off'],
        meaning: 'to rise from bed'
    },
    { 
        sentence: 'Please ___ the lights before you leave.',
        verb: 'turn off',
        options: ['turn on', 'turn off', 'turn up', 'turn down'],
        meaning: 'to stop the flow of electricity'
    },
    { 
        sentence: 'We had to ___ the meeting until next week.',
        verb: 'put off',
        options: ['put on', 'put off', 'put up', 'put down'],
        meaning: 'to postpone'
    },
    { 
        sentence: "I can't ___ with this noise anymore!",
        verb: 'put up',
        options: ['put on', 'put off', 'put up', 'put down'],
        meaning: 'to tolerate'
    },
    { 
        sentence: 'She ___ her father in many ways.',
        verb: 'takes after',
        options: ['takes after', 'takes off', 'takes on', 'takes over'],
        meaning: 'to resemble a family member'
    },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)

const currentQuestion = computed(() => questions.value[currentIndex.value])
const displaySentence = computed(() => {
    return currentQuestion.value?.sentence.replace('___', '______')
})

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentQuestion.value.verb) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextQuestion(), 2500)
}

const nextQuestion = () => {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / questions.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ questions.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-teal-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-6 mx-4">
            <div class="text-3xl text-center mb-3">🔤</div>
            <p class="text-xl text-gray-900 text-center leading-relaxed">
                {{ displaySentence }}
            </p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button v-for="option in currentQuestion?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="p-4 rounded-xl font-medium transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-teal-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentQuestion?.verb,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentQuestion?.verb && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <Transition name="fade">
            <div v-if="feedback" class="w-full max-w-lg mt-6 px-4">
                <div class="p-4 rounded-xl"
                    :class="feedback === 'correct' ? 'bg-green-500/30' : 'bg-red-500/30'">
                    <p class="text-white font-medium mb-1">
                        {{ feedback === 'correct' ? '✓ Correct!' : '✗ Incorrect' }}
                    </p>
                    <p class="text-white/80 text-sm">
                        "{{ currentQuestion?.verb }}" means: {{ currentQuestion?.meaning }}
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
