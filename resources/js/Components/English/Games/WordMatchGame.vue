<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref([])
const translations = ref([])
const selectedWord = ref(null)
const selectedTranslation = ref(null)
const matchedPairs = ref([])
const wrongPair = ref(null)
const attempts = ref(0)

onMounted(() => {
    const pairs = props.content?.words?.slice(0, props.level?.word_count || 6) || []
    
    words.value = [...pairs].sort(() => Math.random() - 0.5).map((w, i) => ({
        id: w.id,
        word: w.word,
        index: i,
    }))
    
    translations.value = [...pairs].sort(() => Math.random() - 0.5).map((w, i) => ({
        id: w.id,
        translation: w.translation_uz,
        index: i,
    }))
})

const isWordMatched = (wordId) => matchedPairs.value.includes(wordId)
const isTranslationMatched = (transId) => matchedPairs.value.includes(transId)

const selectWord = (word) => {
    if (isWordMatched(word.id) || wrongPair.value) return
    selectedWord.value = word
    checkMatch()
}

const selectTranslation = (trans) => {
    if (isTranslationMatched(trans.id) || wrongPair.value) return
    selectedTranslation.value = trans
    checkMatch()
}

const checkMatch = () => {
    if (!selectedWord.value || !selectedTranslation.value) return
    
    attempts.value++
    
    if (selectedWord.value.id === selectedTranslation.value.id) {
        matchedPairs.value.push(selectedWord.value.id)
        emit('correct')
        emit('score', 15)
        playAudio('/sounds/match.mp3')
        
        selectedWord.value = null
        selectedTranslation.value = null
        
        if (matchedPairs.value.length === words.value.length) {
            setTimeout(() => emit('complete'), 500)
        }
    } else {
        wrongPair.value = {
            word: selectedWord.value,
            translation: selectedTranslation.value,
        }
        playAudio('/sounds/incorrect.mp3')
        
        setTimeout(() => {
            wrongPair.value = null
            selectedWord.value = null
            selectedTranslation.value = null
        }, 800)
    }
}

const progress = computed(() => Math.round((matchedPairs.value.length / words.value.length) * 100))
</script>

<template>
    <div class="py-8">
        <div class="max-w-2xl mx-auto mb-6 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>{{ matchedPairs.length }} / {{ words.length }} matched</span>
                <span>Attempts: {{ attempts }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all"
                    :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-6 max-w-2xl mx-auto px-4">
            <div class="space-y-3">
                <h3 class="text-white/60 text-sm font-medium mb-2 text-center">English</h3>
                <button
                    v-for="word in words"
                    :key="'word-' + word.id"
                    @click="selectWord(word)"
                    :disabled="isWordMatched(word.id)"
                    class="w-full p-4 rounded-xl font-medium transition-all transform"
                    :class="{
                        'bg-white text-gray-900 hover:scale-105': !isWordMatched(word.id) && selectedWord?.id !== word.id,
                        'bg-blue-500 text-white scale-105': selectedWord?.id === word.id,
                        'bg-green-500 text-white opacity-60': isWordMatched(word.id),
                        'bg-red-500 text-white animate-shake': wrongPair?.word?.id === word.id,
                    }"
                >
                    {{ word.word }}
                </button>
            </div>
            
            <div class="space-y-3">
                <h3 class="text-white/60 text-sm font-medium mb-2 text-center">O'zbek</h3>
                <button
                    v-for="trans in translations"
                    :key="'trans-' + trans.id"
                    @click="selectTranslation(trans)"
                    :disabled="isTranslationMatched(trans.id)"
                    class="w-full p-4 rounded-xl font-medium transition-all transform"
                    :class="{
                        'bg-white text-gray-900 hover:scale-105': !isTranslationMatched(trans.id) && selectedTranslation?.id !== trans.id,
                        'bg-blue-500 text-white scale-105': selectedTranslation?.id === trans.id,
                        'bg-green-500 text-white opacity-60': isTranslationMatched(trans.id),
                        'bg-red-500 text-white animate-shake': wrongPair?.translation?.id === trans.id,
                    }"
                >
                    {{ trans.translation }}
                </button>
            </div>
        </div>
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
