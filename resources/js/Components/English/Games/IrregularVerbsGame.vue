<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const verbs = ref(props.content?.irregular_verbs || [
    { base: 'go', past: 'went', participle: 'gone' },
    { base: 'eat', past: 'ate', participle: 'eaten' },
    { base: 'drink', past: 'drank', participle: 'drunk' },
    { base: 'swim', past: 'swam', participle: 'swum' },
    { base: 'write', past: 'wrote', participle: 'written' },
])

const currentIndex = ref(0)
const testForm = ref('past')
const userInput = ref('')
const feedback = ref(null)

const currentVerb = computed(() => verbs.value[currentIndex.value])
const correctAnswer = computed(() => 
    testForm.value === 'past' ? currentVerb.value?.past : currentVerb.value?.participle
)

const setupQuestion = () => {
    testForm.value = Math.random() > 0.5 ? 'past' : 'participle'
}

setupQuestion()

const checkAnswer = () => {
    if (feedback.value) return
    
    if (userInput.value.toLowerCase().trim() === correctAnswer.value.toLowerCase()) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextVerb(), 2000)
}

const nextVerb = () => {
    if (currentIndex.value < verbs.value.length - 1) {
        currentIndex.value++
        userInput.value = ''
        feedback.value = null
        setupQuestion()
    } else {
        emit('complete')
    }
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && userInput.value) {
        checkAnswer()
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / verbs.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ verbs.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-orange-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">📚</div>
        <p class="text-white/80 mb-6">Irregular Verbs</p>
        
        <div class="bg-white rounded-2xl p-6 mb-6 text-center">
            <p class="text-gray-500 text-sm mb-2">Base form:</p>
            <p class="text-4xl font-bold text-orange-600 mb-4">{{ currentVerb?.base }}</p>
            
            <div class="flex justify-center space-x-4">
                <div class="px-4 py-2 rounded-lg" :class="testForm === 'past' ? 'bg-orange-100' : 'bg-gray-100'">
                    <p class="text-xs text-gray-500">Past Simple</p>
                    <p class="font-bold" :class="testForm === 'past' ? 'text-orange-600' : 'text-gray-900'">
                        {{ testForm === 'past' ? '?' : currentVerb?.past }}
                    </p>
                </div>
                <div class="px-4 py-2 rounded-lg" :class="testForm === 'participle' ? 'bg-orange-100' : 'bg-gray-100'">
                    <p class="text-xs text-gray-500">Past Participle</p>
                    <p class="font-bold" :class="testForm === 'participle' ? 'text-orange-600' : 'text-gray-900'">
                        {{ testForm === 'participle' ? '?' : currentVerb?.participle }}
                    </p>
                </div>
            </div>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            :placeholder="`Type the ${testForm === 'past' ? 'past simple' : 'past participle'}...`"
            class="w-full max-w-sm px-4 py-3 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white placeholder-white/50': !feedback,
                'border-green-500 bg-green-500/20 text-white': feedback === 'correct',
                'border-red-500 bg-red-500/20 text-white': feedback === 'incorrect',
            }"
        />
        
        <div v-if="feedback" class="mt-4 text-center">
            <p class="text-lg font-medium" :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
                {{ feedback === 'correct' ? '✓ Correct!' : '✗ Incorrect' }}
            </p>
            <p class="text-white mt-2">{{ currentVerb?.base }} → {{ currentVerb?.past }} → {{ currentVerb?.participle }}</p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput"
            class="mt-4 px-8 py-3 bg-orange-500 rounded-xl font-bold text-white hover:bg-orange-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
