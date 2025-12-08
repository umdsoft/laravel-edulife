<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const puzzles = ref(props.content?.jigsaw_puzzles || [
    { sentence: 'The quick brown fox jumps over the lazy dog.', pieces: ['The quick', 'brown fox', 'jumps over', 'the lazy dog.'] },
])

const currentPuzzleIndex = ref(0)
const shuffledPieces = ref([])
const placedPieces = ref([])
const draggedPiece = ref(null)
const feedback = ref(null)

const currentPuzzle = computed(() => puzzles.value[currentPuzzleIndex.value])

const initPuzzle = () => {
    shuffledPieces.value = [...currentPuzzle.value.pieces].sort(() => Math.random() - 0.5)
    placedPieces.value = new Array(currentPuzzle.value.pieces.length).fill(null)
}

initPuzzle()

const startDrag = (piece, fromSlot = null) => {
    draggedPiece.value = { piece, fromSlot }
}

const dropOnSlot = (slotIndex) => {
    if (!draggedPiece.value) return
    
    const { piece, fromSlot } = draggedPiece.value
    
    if (fromSlot !== null) {
        placedPieces.value[fromSlot] = null
    } else {
        shuffledPieces.value = shuffledPieces.value.filter(p => p !== piece)
    }
    
    if (placedPieces.value[slotIndex]) {
        shuffledPieces.value.push(placedPieces.value[slotIndex])
    }
    
    placedPieces.value[slotIndex] = piece
    draggedPiece.value = null
}

const returnToPool = () => {
    if (!draggedPiece.value) return
    const { piece, fromSlot } = draggedPiece.value
    if (fromSlot !== null) {
        placedPieces.value[fromSlot] = null
        shuffledPieces.value.push(piece)
    }
    draggedPiece.value = null
}

const checkPuzzle = () => {
    if (feedback.value) return
    
    const isCorrect = placedPieces.value.every((piece, index) => piece === currentPuzzle.value.pieces[index])
    
    if (isCorrect) {
        feedback.value = 'correct'
        emit('score', 30)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        setTimeout(() => nextPuzzle(), 1500)
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
        setTimeout(() => { feedback.value = null }, 1000)
    }
}

const nextPuzzle = () => {
    if (currentPuzzleIndex.value < puzzles.value.length - 1) {
        currentPuzzleIndex.value++
        feedback.value = null
        initPuzzle()
    } else {
        emit('complete')
    }
}

const progress = computed(() => Math.round(((currentPuzzleIndex.value + 1) / puzzles.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Puzzle {{ currentPuzzleIndex + 1 }} / {{ puzzles.length }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="text-3xl mb-2">🧩</div>
        <p class="text-white/80 mb-4">Arrange the pieces to form a sentence!</p>
        
        <div class="w-full max-w-lg bg-white rounded-2xl p-4 mb-4 mx-4">
            <div class="flex flex-wrap gap-2">
                <div v-for="(piece, index) in placedPieces" :key="index"
                    @drop.prevent="dropOnSlot(index)"
                    @dragover.prevent
                    class="min-w-24 h-12 border-2 border-dashed rounded-lg flex items-center justify-center transition-all"
                    :class="piece ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'">
                    <span v-if="piece" draggable="true" @dragstart="startDrag(piece, index)"
                        class="px-3 py-2 bg-indigo-500 text-white rounded cursor-move">
                        {{ piece }}
                    </span>
                    <span v-else class="text-gray-400 text-sm">{{ index + 1 }}</span>
                </div>
            </div>
        </div>
        
        <div class="w-full max-w-lg bg-white/10 rounded-xl p-4 mb-4 mx-4"
            @drop.prevent="returnToPool" @dragover.prevent>
            <p class="text-white/60 text-sm mb-2 text-center">Available pieces:</p>
            <div class="flex flex-wrap justify-center gap-2">
                <span v-for="piece in shuffledPieces" :key="piece"
                    draggable="true" @dragstart="startDrag(piece)"
                    class="px-4 py-2 bg-white rounded-lg font-medium text-gray-900 cursor-move hover:bg-indigo-50">
                    {{ piece }}
                </span>
            </div>
        </div>
        
        <button @click="checkPuzzle" :disabled="placedPieces.includes(null) || feedback !== null"
            class="px-8 py-3 bg-indigo-500 rounded-xl font-bold text-white hover:bg-indigo-600 disabled:opacity-50">
            Check Puzzle
        </button>
        
        <div v-if="feedback" class="mt-4 text-xl font-bold"
            :class="feedback === 'correct' ? 'text-green-400' : 'text-red-400'">
            {{ feedback === 'correct' ? '✓ Perfect!' : '✗ Try again!' }}
        </div>
    </div>
</template>
