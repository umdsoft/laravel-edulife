<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref([])
const currentIndex = ref(0)
const score = ref(0)
const streak = ref(0)
const timeLeft = ref(60)
const isGameActive = ref(false)
const options = ref([])
const feedback = ref(null)

let timer = null

onMounted(() => {
    words.value = (props.content?.words || []).sort(() => Math.random() - 0.5)
    generateOptions()
    startGame()
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const currentWord = computed(() => words.value[currentIndex.value])

const generateOptions = () => {
    if (!currentWord.value) return
    
    const otherWords = words.value.filter((_, i) => i !== currentIndex.value)
    const wrongOptions = otherWords.sort(() => Math.random() - 0.5).slice(0, 3)
    
    options.value = [...wrongOptions, currentWord.value]
        .sort(() => Math.random() - 0.5)
        .map(w => ({ id: w.id, translation: w.translation_uz }))
}

const startGame = () => {
    isGameActive.value = true
    timer = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            endGame()
        }
    }, 1000)
}

const endGame = () => {
    isGameActive.value = false
    if (timer) clearInterval(timer)
    emit('complete')
}

const selectOption = (option) => {
    if (!isGameActive.value || feedback.value) return
    
    const isCorrect = option.id === currentWord.value.id
    
    if (isCorrect) {
        feedback.value = 'correct'
        streak.value++
        const points = 10 + Math.min(streak.value * 2, 10)
        score.value += points
        emit('score', points)
        emit('correct')
    } else {
        feedback.value = 'incorrect'
        streak.value = 0
    }
    
    setTimeout(() => {
        feedback.value = null
        if (currentIndex.value < words.value.length - 1) {
            currentIndex.value++
            generateOptions()
        } else {
            words.value = words.value.sort(() => Math.random() - 0.5)
            currentIndex.value = 0
            generateOptions()
        }
    }, 300)
}
</script>

<template>
    <div class="flex flex-col items-center py-6">
        <div class="w-full max-w-lg flex justify-between items-center mb-6 px-4">
            <div class="text-white">
                <span class="text-3xl font-bold">{{ score }}</span>
                <span class="text-white/60 ml-1">pts</span>
            </div>
            
            <div v-if="streak > 1" class="px-3 py-1 bg-orange-500 rounded-full">
                <span class="text-white font-bold">🔥 {{ streak }}x</span>
            </div>
            
            <div class="px-4 py-2 rounded-full font-mono font-bold text-xl"
                :class="timeLeft <= 10 ? 'bg-red-500 text-white animate-pulse' : 'bg-white text-gray-900'">
                {{ timeLeft }}s
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-8 mb-6 min-w-[280px] text-center"
            :class="{
                'ring-4 ring-green-500': feedback === 'correct',
                'ring-4 ring-red-500 animate-shake': feedback === 'incorrect',
            }">
            <p class="text-3xl font-bold text-gray-900">{{ currentWord?.word }}</p>
            <p class="text-gray-500 mt-2">{{ currentWord?.phonetic }}</p>
        </div>
        
        <div class="w-full max-w-lg grid grid-cols-2 gap-3 px-4">
            <button
                v-for="option in options"
                :key="option.id"
                @click="selectOption(option)"
                :disabled="!isGameActive"
                class="p-4 rounded-xl font-medium text-lg transition-all transform active:scale-95"
                :class="{
                    'bg-white text-gray-900 hover:bg-blue-50': feedback !== 'correct' || option.id !== currentWord?.id,
                    'bg-green-500 text-white': feedback === 'correct' && option.id === currentWord?.id,
                }"
            >
                {{ option.translation }}
            </button>
        </div>
        
        <p class="text-white/60 text-sm mt-6">
            Select the correct translation as fast as you can!
        </p>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.3s ease-in-out; }
</style>
