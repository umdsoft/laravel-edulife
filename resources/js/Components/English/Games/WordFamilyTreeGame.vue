<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const wordFamilies = ref(props.content?.word_families || [
    {
        root: 'act',
        meaning: 'to do',
        family: [
            { word: 'action', type: 'noun', meaning: 'something done' },
            { word: 'active', type: 'adjective', meaning: 'doing things' },
            { word: 'activity', type: 'noun', meaning: 'things to do' },
            { word: 'actor', type: 'noun', meaning: 'person who acts' },
        ]
    },
])

const currentFamilyIndex = ref(0)
const currentWordIndex = ref(0)
const userInput = ref('')
const feedback = ref(null)
const completedWords = ref([])

const currentFamily = computed(() => wordFamilies.value[currentFamilyIndex.value])
const currentWord = computed(() => currentFamily.value?.family[currentWordIndex.value])

const checkAnswer = () => {
    if (feedback.value) return
    
    if (userInput.value.toLowerCase().trim() === currentWord.value.word.toLowerCase()) {
        feedback.value = 'correct'
        completedWords.value.push(currentWord.value.word)
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextWord(), 1500)
}

const nextWord = () => {
    if (currentWordIndex.value < currentFamily.value.family.length - 1) {
        currentWordIndex.value++
        userInput.value = ''
        feedback.value = null
    } else if (currentFamilyIndex.value < wordFamilies.value.length - 1) {
        currentFamilyIndex.value++
        currentWordIndex.value = 0
        completedWords.value = []
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
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="text-3xl mb-2">🌳</div>
        <p class="text-white/80 mb-4">Build the word family tree!</p>
        
        <div class="bg-amber-500 rounded-2xl px-8 py-4 mb-4">
            <p class="text-white/80 text-sm text-center">Root word:</p>
            <p class="text-2xl font-bold text-white">{{ currentFamily?.root }}</p>
            <p class="text-white/80 text-sm">({{ currentFamily?.meaning }})</p>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 mb-4">
            <div v-for="word in completedWords" :key="word"
                class="px-3 py-2 bg-green-500 text-white rounded-lg text-sm">
                ✓ {{ word }}
            </div>
        </div>
        
        <div class="w-full max-w-md bg-white rounded-2xl p-6 mb-4 mx-4">
            <div class="flex justify-between items-center mb-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-sm">{{ currentWord?.type }}</span>
                <span class="text-gray-500 text-sm">{{ currentWordIndex + 1 }}/{{ currentFamily?.family.length }}</span>
            </div>
            <p class="text-xl text-gray-900 text-center">{{ currentWord?.meaning }}</p>
        </div>
        
        <input
            v-model="userInput"
            @keydown="handleKeydown"
            :disabled="feedback !== null"
            type="text"
            placeholder="Type the word..."
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
            <p v-if="feedback === 'incorrect'" class="text-white mt-1">Answer: {{ currentWord?.word }}</p>
        </div>
        
        <button v-if="!feedback" @click="checkAnswer" :disabled="!userInput"
            class="mt-4 px-8 py-3 bg-amber-500 rounded-xl font-bold text-white hover:bg-amber-600 disabled:opacity-50">
            Check
        </button>
    </div>
</template>
