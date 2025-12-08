<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const evolutions = ref(props.content?.word_evolutions || [
    {
        base: 'act',
        stages: [
            { word: 'act', level: 1, hint: 'Base word - to do something' },
            { word: 'action', level: 2, hint: 'Add -ion (noun)' },
            { word: 'active', level: 3, hint: 'Add -ive (adjective)' },
            { word: 'activity', level: 4, hint: 'Add -ity (noun)' },
        ]
    },
])

const currentEvolutionIndex = ref(0)
const currentStageIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const showHint = ref(false)

const currentEvolution = computed(() => evolutions.value[currentEvolutionIndex.value])
const currentStage = computed(() => currentEvolution.value?.stages[currentStageIndex.value])
const completedStages = computed(() => currentEvolution.value?.stages.slice(0, currentStageIndex.value) || [])

const checkAnswer = () => {
    if (feedback.value) return
    
    if (userInput.value.toLowerCase().trim() === currentStage.value.word.toLowerCase()) {
        feedback.value = 'correct'
        emit('score', currentStage.value.level * 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        setTimeout(() => {
            if (currentStageIndex.value < currentEvolution.value.stages.length - 1) {
                currentStageIndex.value++
            } else if (currentEvolutionIndex.value < evolutions.value.length - 1) {
                currentEvolutionIndex.value++
                currentStageIndex.value = 0
            } else {
                emit('complete')
                return
            }
            userInput.value = ''
            feedback.value = null
            showHint.value = false
        }, 1000)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        setTimeout(() => { feedback.value = null }, 1000)
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="text-3xl mb-2">🧬</div>
        <p class="text-white/80 mb-4">Evolve the word through different forms!</p>
        
        <div class="flex items-center space-x-2 mb-6 overflow-x-auto px-4">
            <div v-for="(stage, index) in currentEvolution?.stages" :key="index"
                class="flex items-center">
                <div class="px-4 py-2 rounded-xl font-medium text-sm whitespace-nowrap"
                    :class="{
                        'bg-green-500 text-white': index < currentStageIndex,
                        'bg-yellow-500 text-gray-900 animate-pulse': index === currentStageIndex,
                        'bg-white/20 text-white/50': index > currentStageIndex,
                    }">
                    {{ index < currentStageIndex ? stage.word : `Lv.${stage.level}` }}
                </div>
                <span v-if="index < currentEvolution?.stages.length - 1" class="text-white/50 mx-1">→</span>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-4 text-center">
            <p class="text-gray-500 text-sm mb-2">Level {{ currentStage?.level }}</p>
            <p class="text-xl text-gray-900">{{ currentStage?.hint }}</p>
            <p v-if="currentStageIndex > 0" class="text-gray-500 mt-2">
                Previous: <span class="font-bold">{{ completedStages[completedStages.length - 1]?.word }}</span>
            </p>
        </div>
        
        <button @click="showHint = !showHint" class="text-white/60 hover:text-white text-sm mb-4">
            {{ showHint ? 'Hide hint' : 'Show hint' }} 💡
        </button>
        <p v-if="showHint" class="text-yellow-400 text-sm mb-4">
            First letters: {{ currentStage?.word.slice(0, 3) }}...
        </p>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Type the evolved word..."
            class="w-full max-w-sm px-4 py-3 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <button @click="checkAnswer" :disabled="!userInput || feedback !== null"
            class="mt-4 px-8 py-3 bg-purple-500 rounded-xl font-bold text-white hover:bg-purple-600 disabled:opacity-50">
            Evolve! 🧬
        </button>
    </div>
</template>
