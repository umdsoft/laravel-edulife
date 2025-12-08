<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const idioms = ref(props.content?.idioms || [
    { 
        idiom: 'Break a leg', 
        meaning: 'Good luck',
        options: ['Good luck', 'Be careful', 'Run fast', 'Stop moving'],
        example: 'Break a leg on your exam tomorrow!'
    },
    { 
        idiom: 'Piece of cake', 
        meaning: 'Very easy',
        options: ['Delicious food', 'Very easy', 'Small portion', 'Birthday party'],
        example: 'The test was a piece of cake.'
    },
    { 
        idiom: 'Hit the books', 
        meaning: 'Study hard',
        options: ['Read novels', 'Study hard', 'Throw books', 'Buy textbooks'],
        example: "I need to hit the books for tomorrow's exam."
    },
    { 
        idiom: 'Under the weather', 
        meaning: 'Feeling sick',
        options: ['Feeling sick', 'In the rain', 'Very cold', 'Outside'],
        example: "I'm feeling a bit under the weather today."
    },
    { 
        idiom: 'Cost an arm and a leg', 
        meaning: 'Very expensive',
        options: ['Free', 'Very expensive', 'Painful', 'Hard to find'],
        example: 'That new car cost an arm and a leg!'
    },
])

const currentIndex = ref(0)
const selectedAnswer = ref(null)
const feedback = ref(null)
const showExample = ref(false)

const currentIdiom = computed(() => idioms.value[currentIndex.value])

const selectAnswer = (option) => {
    if (feedback.value) return
    
    selectedAnswer.value = option
    
    if (option === currentIdiom.value.meaning) {
        feedback.value = 'correct'
        showExample.value = true
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        showExample.value = true
        playAudio('/sounds/incorrect.mp3')
    }
}

const nextIdiom = () => {
    if (currentIndex.value < idioms.value.length - 1) {
        currentIndex.value++
        selectedAnswer.value = null
        feedback.value = null
        showExample.value = false
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / idioms.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ idioms.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-amber-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-6 text-center min-w-[300px]">
            <div class="text-4xl mb-3">💬</div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">"{{ currentIdiom?.idiom }}"</h2>
            <p class="text-gray-500">What does this idiom mean?</p>
        </div>
        
        <div class="w-full max-w-lg space-y-3 px-4 mb-6">
            <button v-for="option in currentIdiom?.options" :key="option"
                @click="selectAnswer(option)"
                :disabled="feedback !== null"
                class="w-full p-4 rounded-xl font-medium transition-all text-left"
                :class="{
                    'bg-white text-gray-900 hover:bg-amber-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentIdiom?.meaning,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAnswer === option,
                    'opacity-50': feedback && option !== currentIdiom?.meaning && selectedAnswer !== option,
                }">
                {{ option }}
            </button>
        </div>
        
        <Transition name="fade">
            <div v-if="showExample" class="w-full max-w-lg mx-4">
                <div class="bg-white/20 rounded-xl p-4 mb-4">
                    <p class="text-white/60 text-sm mb-1">Example:</p>
                    <p class="text-white italic">"{{ currentIdiom?.example }}"</p>
                </div>
                
                <button @click="nextIdiom"
                    class="w-full py-3 bg-amber-500 rounded-xl font-bold text-white hover:bg-amber-600">
                    {{ currentIndex < idioms.length - 1 ? 'Next Idiom' : 'Finish' }}
                </button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(10px); }
</style>
