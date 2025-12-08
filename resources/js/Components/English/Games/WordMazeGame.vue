<script setup>
import { ref, computed } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const mazes = ref(props.content?.word_mazes || [
    {
        grid: [
            ['C', 'A', 'T', 'X', 'Z'],
            ['X', 'X', 'X', 'X', 'Z'],
            ['D', 'O', 'G', 'X', 'Z'],
            ['X', 'X', 'X', 'X', 'Z'],
            ['B', 'I', 'R', 'D', 'Z'],
        ],
        words: ['CAT', 'DOG', 'BIRD'],
    },
])

const currentMazeIndex = ref(0)
const selectedCells = ref([])
const currentWord = ref('')
const foundWords = ref([])
const feedback = ref(null)

const currentMaze = computed(() => mazes.value[currentMazeIndex.value])

const toggleCell = (row, col) => {
    const cellKey = `${row}-${col}`
    const index = selectedCells.value.findIndex(c => c.key === cellKey)
    
    if (index === -1) {
        if (selectedCells.value.length > 0) {
            const last = selectedCells.value[selectedCells.value.length - 1]
            const rowDiff = Math.abs(row - last.row)
            const colDiff = Math.abs(col - last.col)
            if (rowDiff > 1 || colDiff > 1) return
        }
        
        selectedCells.value.push({ row, col, key: cellKey })
        currentWord.value += currentMaze.value.grid[row][col]
    } else if (index === selectedCells.value.length - 1) {
        selectedCells.value.pop()
        currentWord.value = currentWord.value.slice(0, -1)
    }
}

const checkWord = () => {
    if (currentMaze.value.words.includes(currentWord.value) && !foundWords.value.includes(currentWord.value)) {
        foundWords.value.push(currentWord.value)
        emit('score', currentWord.value.length * 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        feedback.value = 'correct'
        
        if (foundWords.value.length === currentMaze.value.words.length) {
            setTimeout(() => nextMaze(), 1000)
        }
    } else {
        feedback.value = 'incorrect'
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        selectedCells.value = []
        currentWord.value = ''
        feedback.value = null
    }, 500)
}

const clearSelection = () => {
    selectedCells.value = []
    currentWord.value = ''
}

const nextMaze = () => {
    if (currentMazeIndex.value < mazes.value.length - 1) {
        currentMazeIndex.value++
        foundWords.value = []
        selectedCells.value = []
        currentWord.value = ''
    } else {
        emit('complete')
    }
}

const isCellSelected = (row, col) => {
    return selectedCells.value.some(c => c.row === row && c.col === col)
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="text-3xl mb-2">🔍</div>
        <p class="text-white/80 mb-4">Find the hidden words!</p>
        
        <div class="flex flex-wrap justify-center gap-2 mb-4">
            <span v-for="word in currentMaze?.words" :key="word"
                class="px-3 py-1 rounded-full text-sm font-medium"
                :class="foundWords.includes(word) ? 'bg-green-500 text-white line-through' : 'bg-white/20 text-white'">
                {{ word }}
            </span>
        </div>
        
        <div class="bg-white rounded-xl px-6 py-3 mb-4 min-w-32 text-center">
            <span class="text-xl font-bold text-gray-900">{{ currentWord || '...' }}</span>
        </div>
        
        <div class="grid gap-1 p-4 bg-white/10 rounded-xl mb-4">
            <div v-for="(row, rowIndex) in currentMaze?.grid" :key="rowIndex" class="flex gap-1">
                <button v-for="(cell, colIndex) in row" :key="colIndex"
                    @click="toggleCell(rowIndex, colIndex)"
                    class="w-12 h-12 rounded-lg font-bold text-lg transition-all"
                    :class="{
                        'bg-white text-gray-900': !isCellSelected(rowIndex, colIndex),
                        'bg-blue-500 text-white': isCellSelected(rowIndex, colIndex),
                    }">
                    {{ cell }}
                </button>
            </div>
        </div>
        
        <div class="flex space-x-4">
            <button @click="clearSelection"
                class="px-6 py-3 bg-white/20 rounded-xl font-bold text-white hover:bg-white/30">
                Clear
            </button>
            <button @click="checkWord" :disabled="currentWord.length < 2"
                class="px-6 py-3 bg-blue-500 rounded-xl font-bold text-white hover:bg-blue-600 disabled:opacity-50">
                Check
            </button>
        </div>
    </div>
</template>
