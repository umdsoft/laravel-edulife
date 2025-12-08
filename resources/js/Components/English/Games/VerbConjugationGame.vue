<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const verbs = ref(props.content?.verbs || [
    { 
        infinitive: 'to be', 
        conjugations: [
            { pronoun: 'I', form: 'am' },
            { pronoun: 'You', form: 'are' },
            { pronoun: 'He/She', form: 'is' },
            { pronoun: 'We', form: 'are' },
            { pronoun: 'They', form: 'are' },
        ]
    },
    { 
        infinitive: 'to have', 
        conjugations: [
            { pronoun: 'I', form: 'have' },
            { pronoun: 'You', form: 'have' },
            { pronoun: 'He/She', form: 'has' },
            { pronoun: 'We', form: 'have' },
            { pronoun: 'They', form: 'have' },
        ]
    },
])

const currentVerbIndex = ref(0)
const currentConjIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const completedVerbs = ref(0)

const currentVerb = computed(() => verbs.value[currentVerbIndex.value])
const currentConj = computed(() => currentVerb.value?.conjugations[currentConjIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    const isCorrect = userInput.value.toLowerCase().trim() === currentConj.value.form.toLowerCase()
    
    if (isCorrect) {
        feedback.value = 'correct'
        emit('score', 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextConjugation(), 1500)
}

const nextConjugation = () => {
    userInput.value = ''
    feedback.value = null
    
    if (currentConjIndex.value < currentVerb.value.conjugations.length - 1) {
        currentConjIndex.value++
    } else if (currentVerbIndex.value < verbs.value.length - 1) {
        currentVerbIndex.value++
        currentConjIndex.value = 0
        completedVerbs.value++
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
    }
}

const overallProgress = computed(() => {
    const totalConj = verbs.value.reduce((sum, v) => sum + v.conjugations.length, 0)
    const completed = completedVerbs.value * (currentVerb.value?.conjugations.length || 0) + currentConjIndex.value
    return Math.round((completed / totalConj) * 100)
})
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Verb: {{ currentVerb?.infinitive }}</span>
                <span>{{ currentConjIndex + 1 }} / {{ currentVerb?.conjugations.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full transition-all" :style="{ width: `${overallProgress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-6 text-center min-w-[280px]">
            <p class="text-gray-500 text-sm mb-2">Conjugate:</p>
            <h2 class="text-3xl font-bold text-purple-600 mb-4">{{ currentVerb?.infinitive }}</h2>
            
            <div class="bg-purple-50 rounded-xl p-4">
                <p class="text-gray-600 mb-1">For:</p>
                <p class="text-2xl font-bold text-gray-900">{{ currentConj?.pronoun }}</p>
            </div>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Type conjugation..."
            autocomplete="off"
            class="w-full max-w-sm px-6 py-4 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <div v-if="feedback" class="mt-4 text-lg font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedback === 'correct' ? '✓ Correct!' : `✗ Answer: ${currentConj?.form}` }}
        </div>
        
        <button @click="checkAnswer" :disabled="!userInput || feedback !== null"
            class="mt-6 px-8 py-3 bg-purple-500 rounded-xl font-bold text-white hover:bg-purple-600 disabled:opacity-50">
            Check
        </button>
        
        <div class="mt-8 bg-white/10 rounded-xl p-4">
            <h4 class="text-white/60 text-sm mb-2">Completed:</h4>
            <div class="flex flex-wrap gap-2">
                <span v-for="(conj, i) in currentVerb?.conjugations.slice(0, currentConjIndex)" :key="i"
                    class="px-3 py-1 bg-green-500/30 rounded-full text-white text-sm">
                    {{ conj.pronoun }}: {{ conj.form }}
                </span>
            </div>
        </div>
    </div>
</template>
