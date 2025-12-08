<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const ladders = ref(props.content?.word_ladders || [
    {
        start: 'cat',
        end: 'dog',
        steps: ['cat', 'cot', 'cog', 'dog'],
        hints: ['A small bed', 'A wheel tooth', 'Final word']
    },
    {
        start: 'head',
        end: 'tail',
        steps: ['head', 'heal', 'teal', 'tell', 'tall', 'tail'],
        hints: ['To cure', 'Blue-green color', 'To say', 'Not short', 'Final word']
    },
])

const currentLadderIndex = ref(0)
const currentStepIndex = ref(1)
const userInput = ref('')
const feedback = ref(null)
const completedSteps = ref([])

const currentLadder = computed(() => ladders.value[currentLadderIndex.value])
const currentStep = computed(() => currentLadder.value?.steps[currentStepIndex.value])
const currentHint = computed(() => currentLadder.value?.hints[currentStepIndex.value - 1])

const displaySteps = computed(() => {
    const steps = []
    currentLadder.value?.steps.forEach((step, index) => {
        if (index === 0) {
            steps.push({ word: step, status: 'start' })
        } else if (index < currentStepIndex.value) {
            steps.push({ word: completedSteps.value[index - 1] || step, status: 'completed' })
        } else if (index === currentStepIndex.value) {
            steps.push({ word: '????', status: 'current' })
        } else if (index === currentLadder.value.steps.length - 1) {
            steps.push({ word: step, status: 'end' })
        } else {
            steps.push({ word: '????', status: 'locked' })
        }
    })
    return steps
})

const checkStep = () => {
    if (feedback.value) return
    
    const userWord = userInput.value.toLowerCase().trim()
    const correctWord = currentStep.value.toLowerCase()
    
    if (userWord === correctWord) {
        feedback.value = 'correct'
        completedSteps.value.push(userWord)
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        setTimeout(() => {
            if (currentStepIndex.value < currentLadder.value.steps.length - 1) {
                currentStepIndex.value++
                userInput.value = ''
                feedback.value = null
            } else {
                nextLadder()
            }
        }, 1000)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        setTimeout(() => {
            feedback.value = null
            userInput.value = ''
        }, 1000)
    }
}

const nextLadder = () => {
    if (currentLadderIndex.value < ladders.value.length - 1) {
        currentLadderIndex.value++
        currentStepIndex.value = 1
        completedSteps.value = []
        userInput.value = ''
        feedback.value = null
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkStep()
    }
}

const progress = computed(() => {
    const totalSteps = ladders.value.reduce((sum, l) => sum + l.steps.length - 2, 0)
    let completed = 0
    for (let i = 0; i < currentLadderIndex.value; i++) {
        completed += ladders.value[i].steps.length - 2
    }
    completed += currentStepIndex.value - 1
    return Math.round((completed / totalSteps) * 100)
})
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-md mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Ladder {{ currentLadderIndex + 1 }} / {{ ladders.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-500 to-blue-500 rounded-full transition-all" 
                    :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="flex flex-col items-center space-y-2 mb-6">
            <div v-for="(step, index) in displaySteps" :key="'step-' + index"
                class="px-6 py-3 rounded-xl font-mono text-xl font-bold tracking-widest transition-all"
                :class="{
                    'bg-green-500 text-white': step.status === 'start',
                    'bg-blue-500 text-white': step.status === 'completed',
                    'bg-yellow-500 text-gray-900 animate-pulse': step.status === 'current',
                    'bg-white/20 text-white/50': step.status === 'locked',
                    'bg-red-500 text-white': step.status === 'end',
                }">
                {{ step.word.toUpperCase() }}
            </div>
        </div>
        
        <div class="bg-white/20 rounded-xl px-4 py-2 mb-4">
            <p class="text-white/60 text-sm">Hint: <span class="text-white">{{ currentHint }}</span></p>
        </div>
        
        <p class="text-white/60 text-sm mb-4">Change only ONE letter from the previous word</p>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            :maxlength="currentStep?.length"
            placeholder="Type the word..."
            class="w-full max-w-xs px-4 py-3 text-xl text-center font-mono tracking-widest rounded-xl border-2 focus:outline-none uppercase"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <button @click="checkStep" :disabled="!userInput || feedback !== null"
            class="mt-4 px-8 py-3 bg-white rounded-xl font-bold text-blue-600 hover:bg-blue-50 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
