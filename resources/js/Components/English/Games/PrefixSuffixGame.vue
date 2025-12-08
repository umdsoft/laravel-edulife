<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref(props.content?.prefix_suffix || [
    { base: 'happy', affix: 'un', type: 'prefix', result: 'unhappy', meaning: 'not happy' },
    { base: 'teach', affix: 'er', type: 'suffix', result: 'teacher', meaning: 'person who teaches' },
    { base: 'possible', affix: 'im', type: 'prefix', result: 'impossible', meaning: 'not possible' },
    { base: 'care', affix: 'ful', type: 'suffix', result: 'careful', meaning: 'full of care' },
    { base: 'agree', affix: 'dis', type: 'prefix', result: 'disagree', meaning: 'not agree' },
    { base: 'hope', affix: 'less', type: 'suffix', result: 'hopeless', meaning: 'without hope' },
    { base: 'write', affix: 're', type: 'prefix', result: 'rewrite', meaning: 'write again' },
    { base: 'friend', affix: 'ship', type: 'suffix', result: 'friendship', meaning: 'state of being friends' },
])

const currentIndex = ref(0)
const options = ref([])
const selectedAffix = ref(null)
const feedback = ref(null)

const currentWord = computed(() => words.value[currentIndex.value])

const setupOptions = () => {
    const prefixes = ['un', 'im', 'dis', 're', 'pre', 'mis']
    const suffixes = ['er', 'ful', 'less', 'tion', 'ly', 'ship']
    const pool = currentWord.value.type === 'prefix' ? prefixes : suffixes
    
    const wrongOptions = pool.filter(a => a !== currentWord.value.affix).slice(0, 3)
    options.value = [currentWord.value.affix, ...wrongOptions].sort(() => Math.random() - 0.5)
}

setupOptions()

const selectAffix = (option) => {
    if (feedback.value) return
    
    selectedAffix.value = option
    
    if (option === currentWord.value.affix) {
        feedback.value = 'correct'
        emit('score', 15)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => nextWord(), 2000)
}

const nextWord = () => {
    if (currentIndex.value < words.value.length - 1) {
        currentIndex.value++
        selectedAffix.value = null
        feedback.value = null
        setupOptions()
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentIndex.value + 1) / words.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-8">
        <div class="w-full max-w-lg mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ currentIndex + 1 }} / {{ words.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="px-4 py-2 rounded-full mb-4"
            :class="currentWord?.type === 'prefix' ? 'bg-blue-500' : 'bg-green-500'">
            <span class="text-white font-medium">
                {{ currentWord?.type === 'prefix' ? '⬅️ Prefix' : 'Suffix ➡️' }}
            </span>
        </div>
        
        <div class="bg-white rounded-2xl p-6 mb-6 text-center">
            <p class="text-gray-500 text-sm mb-3">Add a {{ currentWord?.type }} to make a new word:</p>
            <div class="flex items-center justify-center space-x-2">
                <div v-if="currentWord?.type === 'prefix'" 
                    class="w-16 h-16 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <span class="text-2xl text-indigo-400">?</span>
                </div>
                <div class="px-6 py-4 bg-gray-100 rounded-xl">
                    <span class="text-3xl font-bold text-gray-900">{{ currentWord?.base }}</span>
                </div>
                <div v-if="currentWord?.type === 'suffix'" 
                    class="w-16 h-16 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <span class="text-2xl text-indigo-400">?</span>
                </div>
            </div>
            <p class="text-gray-400 text-sm mt-3">Meaning: {{ currentWord?.meaning }}</p>
        </div>
        
        <div class="flex flex-wrap justify-center gap-3 px-4">
            <button v-for="option in options" :key="option"
                @click="selectAffix(option)"
                :disabled="feedback !== null"
                class="px-6 py-3 rounded-xl font-bold text-lg transition-all"
                :class="{
                    'bg-white text-gray-900 hover:bg-indigo-50': !feedback,
                    'bg-green-500 text-white': feedback && option === currentWord?.affix,
                    'bg-red-500 text-white': feedback === 'incorrect' && selectedAffix === option,
                    'opacity-50': feedback && option !== currentWord?.affix && selectedAffix !== option,
                }">
                {{ currentWord?.type === 'prefix' ? option + '-' : '-' + option }}
            </button>
        </div>
        
        <div v-if="feedback" class="mt-6 text-center">
            <p class="text-2xl font-bold text-white">
                {{ currentWord?.type === 'prefix' ? currentWord?.affix : '' }}{{ currentWord?.base }}{{ currentWord?.type === 'suffix' ? currentWord?.affix : '' }} = 
                <span class="text-green-400">{{ currentWord?.result }}</span>
            </p>
        </div>
    </div>
</template>
