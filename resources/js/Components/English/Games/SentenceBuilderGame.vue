<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const sentences = ref(props.content?.sentences || [
    { sentence: 'I am a student', translation: 'Men talabaman' },
    { sentence: 'She likes coffee', translation: 'U qahvani yoqtiradi' },
    { sentence: 'The weather is nice today', translation: 'Bugun ob-havo yaxshi' },
    { sentence: 'We are learning English', translation: 'Biz ingliz tilini o\'rganmoqdamiz' },
])
const currentIndex = ref(0)
const selectedWords = ref([])
const availableWords = ref([])
const feedback = ref(null)

const currentSentence = computed(() => sentences.value[currentIndex.value])

const shuffleWords = () => {
    const words = currentSentence.value.sentence.split(' ')
    availableWords.value = words
        .map((word, index) => ({ id: index, word, used: false }))
        .sort(() => Math.random() - 0.5)
    selectedWords.value = []
}

shuffleWords()

const selectWord = (wordObj) => {
    if (wordObj.used || feedback.value) return
    
    wordObj.used = true
    selectedWords.value.push(wordObj)
    playAudio('/sounds/click.mp3')
}

const removeWord = (index) => {
    if (feedback.value) return
    
    const removed = selectedWords.value.splice(index, 1)[0]
    const original = availableWords.value.find(w => w.id === removed.id)
    if (original) original.used = false
}

const checkSentence = () => {
    const userSentence = selectedWords.value.map(w => w.word).join(' ')
    const correctSentence = currentSentence.value.sentence
    
    if (userSentence.toLowerCase() === correctSentence.toLowerCase()) {
        feedback.value = 'correct'
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        setTimeout(() => nextSentence(), 1500)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        
        setTimeout(() => {
            feedback.value = null
        }, 1000)
    }
}

const nextSentence = () => {
    if (currentIndex.value < sentences.value.length - 1) {
        currentIndex.value++
        feedback.value = null
        shuffleWords()
    } else {
        emit('complete')
    }
}

const resetSentence = () => {
    selectedWords.value.forEach(w => {
        const original = availableWords.value.find(aw => aw.id === w.id)
        if (original) original.used = false
    })
    selectedWords.value = []
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / sentences.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Sentence {{ currentIndex + 1 }} / {{ sentences.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white/10 rounded-xl px-6 py-3 mb-6">
            <p class="text-white/60 text-sm">Translate:</p>
            <p class="text-white text-lg font-medium">{{ currentSentence?.translation }}</p>
        </div>
        
        <div class="w-full max-w-lg min-h-[80px] bg-white rounded-xl p-4 mb-6 flex flex-wrap gap-2"
            :class="{
                'border-4 border-green-500': feedback === 'correct',
                'border-4 border-red-500': feedback === 'incorrect',
            }">
            <TransitionGroup name="word">
                <button
                    v-for="(wordObj, index) in selectedWords"
                    :key="wordObj.id"
                    @click="removeWord(index)"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-all"
                >
                    {{ wordObj.word }}
                </button>
            </TransitionGroup>
            <p v-if="selectedWords.length === 0" class="text-gray-400 w-full text-center">
                Click words below to build the sentence
            </p>
        </div>
        
        <div class="w-full max-w-lg flex flex-wrap justify-center gap-2 mb-6">
            <button
                v-for="wordObj in availableWords"
                :key="wordObj.id"
                @click="selectWord(wordObj)"
                :disabled="wordObj.used"
                class="px-4 py-2 rounded-lg font-medium transition-all"
                :class="wordObj.used 
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                    : 'bg-white text-gray-900 hover:bg-gray-100'"
            >
                {{ wordObj.word }}
            </button>
        </div>
        
        <div v-if="feedback" class="mb-4 text-lg font-medium"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedback === 'correct' ? '✓ Perfect!' : '✗ Try again!' }}
        </div>
        
        <div class="flex space-x-4">
            <button @click="resetSentence" :disabled="feedback !== null"
                class="px-6 py-3 bg-white/20 rounded-xl text-white font-medium hover:bg-white/30 disabled:opacity-50">
                Reset
            </button>
            <button @click="checkSentence" 
                :disabled="selectedWords.length === 0 || feedback !== null"
                class="px-8 py-3 bg-green-500 rounded-xl font-bold text-white hover:bg-green-600 disabled:opacity-50">
                Check
            </button>
        </div>
    </div>
</template>

<style scoped>
.word-enter-active, .word-leave-active { transition: all 0.3s ease; }
.word-enter-from, .word-leave-to { opacity: 0; transform: scale(0.8); }
.word-move { transition: transform 0.3s ease; }
</style>
