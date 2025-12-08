<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.active_passive || [
    { active: 'The cat chased the mouse.', passive: 'The mouse was chased by the cat.', direction: 'to_passive' },
    { active: 'She wrote a letter.', passive: 'A letter was written by her.', direction: 'to_passive' },
    { active: 'The chef cooks the meal.', passive: 'The meal is cooked by the chef.', direction: 'to_active' },
    { active: 'They built the house.', passive: 'The house was built by them.', direction: 'to_passive' },
    { active: 'The teacher explains the lesson.', passive: 'The lesson is explained by the teacher.', direction: 'to_active' },
])

const currentIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])
const sourceText = computed(() => 
    currentSentence.value?.direction === 'to_passive' 
        ? currentSentence.value?.active 
        : currentSentence.value?.passive
)
const targetText = computed(() => 
    currentSentence.value?.direction === 'to_passive' 
        ? currentSentence.value?.passive 
        : currentSentence.value?.active
)

const checkAnswer = () => {
    if (feedback.value) return
    
    const userAnswer = userInput.value.trim().toLowerCase().replace(/[.,!?]/g, '')
    const correctAnswer = targetText.value.toLowerCase().replace(/[.,!?]/g, '')
    
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
                <div class="h-full bg-violet-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="flex items-center space-x-2 mb-4">
            <span class="px-3 py-1 rounded-full text-sm"
                :class="currentSentence?.direction === 'to_passive' ? 'bg-blue-500 text-white' : 'bg-gray-500 text-white'">
                {{ currentSentence?.direction === 'to_passive' ? 'Active' : 'Passive' }}
            </span>
            <span class="text-white">→</span>
            <span class="px-3 py-1 rounded-full text-sm"
                :class="currentSentence?.direction === 'to_passive' ? 'bg-violet-500 text-white' : 'bg-green-500 text-white'">
                {{ currentSentence?.direction === 'to_passive' ? 'Passive' : 'Active' }}
            </span>
        </div>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-6 mb-4 mx-4">
            <p class="text-gray-500 text-sm text-center mb-2">
                {{ currentSentence?.direction === 'to_passive' ? 'Active Voice:' : 'Passive Voice:' }}
            </p>
            <p class="text-xl text-gray-900 text-center font-medium">{{ sourceText }}</p>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            :placeholder="currentSentence?.direction === 'to_passive' ? 'Write in passive voice...' : 'Write in active voice...'"
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
            <p class="text-white/80">Answer: <span class="font-medium">{{ targetText }}</span></p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput.trim()"
            class="mt-4 px-8 py-3 bg-violet-500 rounded-xl font-bold text-white hover:bg-violet-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
