<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const items = ref(props.content?.question_formation || [
    {
        statement: 'She is a doctor.',
        question: 'Is she a doctor?',
        words: ['Is', 'she', 'a', 'doctor', '?'],
    },
    {
        statement: 'They are playing football.',
        question: 'Are they playing football?',
        words: ['Are', 'they', 'playing', 'football', '?'],
    },
    {
        statement: 'He has finished his homework.',
        question: 'Has he finished his homework?',
        words: ['Has', 'he', 'finished', 'his', 'homework', '?'],
    },
    {
        statement: 'You can swim.',
        question: 'Can you swim?',
        words: ['Can', 'you', 'swim', '?'],
    },
    {
        statement: 'She will come tomorrow.',
        question: 'Will she come tomorrow?',
        words: ['Will', 'she', 'come', 'tomorrow', '?'],
    },
])

const currentIndex = ref(0)
const selectedWords = ref([])
const availableWords = ref([])
const feedback = ref(null)

const currentItem = computed(() => items.value[currentIndex.value])

const shuffleWords = () => {
    availableWords.value = [...currentItem.value.words]
        .sort(() => Math.random() - 0.5)
        .map((word, index) => ({ id: index, word, used: false }))
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

const checkQuestion = () => {
    const userQuestion = selectedWords.value.map(w => w.word).join(' ')
    const correctQuestion = currentItem.value.question
    
    const normalizeQuestion = (q) => q.replace(/\s+/g, ' ').replace(/\s+\?/, '?').trim()
    
    if (normalizeQuestion(userQuestion) === normalizeQuestion(correctQuestion)) {
        feedback.value = 'correct'
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        setTimeout(() => nextItem(), 1500)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        
        setTimeout(() => {
            feedback.value = null
        }, 1000)
    }
}

const nextItem = () => {
    if (currentIndex.value < items.value.length - 1) {
        currentIndex.value++
        feedback.value = null
        shuffleWords()
    } else {
        emit('complete')
    }
}

const resetWords = () => {
    selectedWords.value.forEach(w => {
        const original = availableWords.value.find(aw => aw.id === w.id)
        if (original) original.used = false
    })
    selectedWords.value = []
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / items.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ items.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-fuchsia-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white/20 rounded-xl p-4 mb-4 mx-4">
            <p class="text-white/60 text-sm text-center mb-1">Change to a question:</p>
            <p class="text-white text-xl font-medium text-center">{{ currentItem?.statement }}</p>
        </div>
        
        <div class="w-full max-w-lg min-h-[80px] bg-white rounded-xl p-4 mb-6 mx-4 flex flex-wrap gap-2 items-center justify-center"
            :class="{
                'border-4 border-green-500': feedback === 'correct',
                'border-4 border-red-500 animate-shake': feedback === 'incorrect',
            }">
            <TransitionGroup name="word">
                <button v-for="(wordObj, index) in selectedWords" :key="wordObj.id"
                    @click="removeWord(index)"
                    class="px-4 py-2 bg-fuchsia-500 text-white rounded-lg font-medium hover:bg-fuchsia-600 transition-all">
                    {{ wordObj.word }}
                </button>
            </TransitionGroup>
            <p v-if="selectedWords.length === 0" class="text-gray-400 text-center">
                Arrange words to form a question
            </p>
        </div>
        
        <div class="w-full max-w-lg flex flex-wrap justify-center gap-2 mb-6 px-4">
            <button v-for="wordObj in availableWords" :key="wordObj.id"
                @click="selectWord(wordObj)"
                :disabled="wordObj.used"
                class="px-4 py-2 rounded-lg font-medium transition-all"
                :class="wordObj.used 
                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                    : 'bg-white text-gray-900 hover:bg-fuchsia-50'">
                {{ wordObj.word }}
            </button>
        </div>
        
        <div class="flex space-x-4">
            <button @click="resetWords" :disabled="feedback !== null"
                class="px-6 py-3 bg-white/20 rounded-xl text-white font-medium hover:bg-white/30 disabled:opacity-50">
                Reset
            </button>
            <button @click="checkQuestion" 
                :disabled="selectedWords.length === 0 || feedback !== null"
                class="px-8 py-3 bg-fuchsia-500 rounded-xl font-bold text-white hover:bg-fuchsia-600 disabled:opacity-50">
                Check
            </button>
        </div>
    </div>
</template>

<style scoped>
.word-enter-active, .word-leave-active { transition: all 0.3s ease; }
.word-enter-from, .word-leave-to { opacity: 0; transform: scale(0.8); }
.word-move { transition: transform 0.3s ease; }
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.3s ease-in-out; }
</style>
