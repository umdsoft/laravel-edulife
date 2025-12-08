<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const cards = ref([])
const flippedCards = ref([])
const matchedPairs = ref([])
const moves = ref(0)
const isLocked = ref(false)

onMounted(() => {
    initializeGame()
})

const initializeGame = () => {
    const pairCount = props.level?.word_count || 6
    const selectedWords = props.content?.words?.slice(0, pairCount) || []
    
    const pairs = []
    selectedWords.forEach((word, index) => {
        pairs.push({
            id: `word-${index}`,
            pairId: index,
            content: word.word,
            type: 'word',
            isFlipped: false,
            isMatched: false,
        })
        pairs.push({
            id: `trans-${index}`,
            pairId: index,
            content: word.translation_uz,
            type: 'translation',
            isFlipped: false,
            isMatched: false,
        })
    })
    
    cards.value = pairs.sort(() => Math.random() - 0.5)
}

const flipCard = (card) => {
    if (isLocked.value || card.isFlipped || card.isMatched || flippedCards.value.length >= 2) return
    
    card.isFlipped = true
    flippedCards.value.push(card)
    playAudio('/sounds/flip.mp3')
    
    if (flippedCards.value.length === 2) {
        moves.value++
        checkMatch()
    }
}

const checkMatch = () => {
    isLocked.value = true
    const [card1, card2] = flippedCards.value
    
    if (card1.pairId === card2.pairId && card1.type !== card2.type) {
        setTimeout(() => {
            card1.isMatched = true
            card2.isMatched = true
            matchedPairs.value.push(card1.pairId)
            flippedCards.value = []
            isLocked.value = false
            
            emit('score', 15)
            emit('correct')
            playAudio('/sounds/match.mp3')
            
            if (matchedPairs.value.length === cards.value.length / 2) {
                setTimeout(() => emit('complete'), 500)
            }
        }, 500)
    } else {
        setTimeout(() => {
            card1.isFlipped = false
            card2.isFlipped = false
            flippedCards.value = []
            isLocked.value = false
            playAudio('/sounds/incorrect.mp3')
        }, 1000)
    }
}

const gridCols = computed(() => {
    const count = cards.value.length
    if (count <= 8) return 4
    if (count <= 12) return 4
    return 6
})

const progress = computed(() => Math.round((matchedPairs.value.length / (cards.value.length / 2)) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Pairs: {{ matchedPairs.length }} / {{ cards.length / 2 }}</span>
                <span>Moves: {{ moves }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="grid gap-2 p-4" :style="{ gridTemplateColumns: `repeat(${gridCols}, minmax(0, 1fr))` }">
            <div v-for="card in cards" :key="card.id"
                @click="flipCard(card)"
                class="relative w-16 h-20 sm:w-20 sm:h-24 cursor-pointer perspective-1000">
                <div class="relative w-full h-full transition-transform duration-500 transform-style-3d"
                    :class="{ 'rotate-y-180': card.isFlipped || card.isMatched }">
                    <div class="absolute inset-0 backface-hidden bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <span class="text-3xl">❓</span>
                    </div>
                    <div class="absolute inset-0 backface-hidden rotate-y-180 rounded-xl flex items-center justify-center p-2 text-center"
                        :class="{
                            'bg-white text-gray-900': !card.isMatched,
                            'bg-green-500 text-white': card.isMatched,
                        }">
                        <span class="text-xs sm:text-sm font-medium leading-tight">{{ card.content }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.perspective-1000 { perspective: 1000px; }
.transform-style-3d { transform-style: preserve-3d; }
.backface-hidden { backface-visibility: hidden; }
.rotate-y-180 { transform: rotateY(180deg); }
</style>
