<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const gridSize = computed(() => Math.min(12, 8 + (props.level?.level || 1)))
const words = ref([])
const grid = ref([])
const foundWords = ref([])
const selectedCells = ref([])
const isSelecting = ref(false)
const startCell = ref(null)

const directions = [
    { dx: 1, dy: 0 },
    { dx: 0, dy: 1 },
    { dx: 1, dy: 1 },
    { dx: -1, dy: 1 },
]

onMounted(() => {
    initializeGame()
})

const initializeGame = () => {
    words.value = props.content?.words?.slice(0, props.level?.word_count || 6).map(w => ({
        ...w,
        word: w.word.toUpperCase(),
        found: false,
        cells: [],
    })) || []
    
    grid.value = Array(gridSize.value).fill(null).map(() => 
        Array(gridSize.value).fill(null).map(() => ({
            letter: '',
            partOfWord: false,
            found: false,
        }))
    )
    
    words.value.forEach(word => {
        placeWord(word)
    })
    
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    grid.value.forEach(row => {
        row.forEach(cell => {
            if (!cell.letter) {
                cell.letter = letters[Math.floor(Math.random() * letters.length)]
            }
        })
    })
}

const placeWord = (wordObj) => {
    const word = wordObj.word
    let placed = false
    let attempts = 0
    
    while (!placed && attempts < 100) {
        attempts++
        const dir = directions[Math.floor(Math.random() * directions.length)]
        const startX = Math.floor(Math.random() * gridSize.value)
        const startY = Math.floor(Math.random() * gridSize.value)
        
        const endX = startX + dir.dx * (word.length - 1)
        const endY = startY + dir.dy * (word.length - 1)
        
        if (endX < 0 || endX >= gridSize.value || endY < 0 || endY >= gridSize.value) continue
        
        let canPlace = true
        const cells = []
        
        for (let i = 0; i < word.length; i++) {
            const x = startX + dir.dx * i
            const y = startY + dir.dy * i
            const cell = grid.value[y][x]
            
            if (cell.letter && cell.letter !== word[i]) {
                canPlace = false
                break
            }
            cells.push({ x, y })
        }
        
        if (canPlace) {
            cells.forEach((pos, i) => {
                grid.value[pos.y][pos.x].letter = word[i]
                grid.value[pos.y][pos.x].partOfWord = true
            })
            wordObj.cells = cells
            placed = true
        }
    }
}

const handleCellMouseDown = (row, col) => {
    isSelecting.value = true
    startCell.value = { row, col }
    selectedCells.value = [{ row, col }]
}

const handleCellMouseEnter = (row, col) => {
    if (!isSelecting.value || !startCell.value) return
    
    const dx = col - startCell.value.col
    const dy = row - startCell.value.row
    
    if (dx !== 0 && dy !== 0 && Math.abs(dx) !== Math.abs(dy)) return
    
    const length = Math.max(Math.abs(dx), Math.abs(dy)) + 1
    const stepX = dx === 0 ? 0 : dx / Math.abs(dx)
    const stepY = dy === 0 ? 0 : dy / Math.abs(dy)
    
    selectedCells.value = []
    for (let i = 0; i < length; i++) {
        selectedCells.value.push({
            row: startCell.value.row + stepY * i,
            col: startCell.value.col + stepX * i,
        })
    }
}

const handleCellMouseUp = () => {
    if (!isSelecting.value) return
    isSelecting.value = false
    
    const selectedWord = selectedCells.value
        .map(c => grid.value[c.row][c.col].letter)
        .join('')
    
    const matchedWord = words.value.find(w => 
        !w.found && (w.word === selectedWord || w.word === selectedWord.split('').reverse().join(''))
    )
    
    if (matchedWord) {
        matchedWord.found = true
        foundWords.value.push(matchedWord.word)
        selectedCells.value.forEach(c => {
            grid.value[c.row][c.col].found = true
        })
        emit('score', 20)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        if (foundWords.value.length === words.value.length) {
            setTimeout(() => emit('complete'), 1000)
        }
    } else {
        playAudio('/sounds/incorrect.mp3')
    }
    
    selectedCells.value = []
    startCell.value = null
}

const isCellSelected = (row, col) => {
    return selectedCells.value.some(c => c.row === row && c.col === col)
}

const progress = computed(() => Math.round((foundWords.value.length / words.value.length) * 100))
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg mb-4 px-4">
            <div class="flex justify-between text-sm text-white/80 mb-2">
                <span>Found {{ foundWords.length }} / {{ words.length }}</span>
                <span>{{ progress }}%</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${progress}%` }"></div>
            </div>
        </div>
        
        <div class="flex flex-wrap justify-center gap-2 mb-4 px-4">
            <span v-for="word in words" :key="word.word"
                class="px-3 py-1 rounded-full text-sm font-medium"
                :class="word.found ? 'bg-green-500 text-white line-through' : 'bg-white/20 text-white'">
                {{ word.word }}
            </span>
        </div>
        
        <div class="bg-white rounded-xl p-2 select-none" @mouseleave="handleCellMouseUp">
            <div v-for="(row, rowIndex) in grid" :key="rowIndex" class="flex">
                <div v-for="(cell, colIndex) in row" :key="colIndex"
                    @mousedown="handleCellMouseDown(rowIndex, colIndex)"
                    @mouseenter="handleCellMouseEnter(rowIndex, colIndex)"
                    @mouseup="handleCellMouseUp"
                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center font-bold text-sm cursor-pointer transition-all rounded"
                    :class="{
                        'bg-green-500 text-white': cell.found,
                        'bg-blue-500 text-white': isCellSelected(rowIndex, colIndex) && !cell.found,
                        'text-gray-900 hover:bg-gray-100': !cell.found && !isCellSelected(rowIndex, colIndex),
                    }">
                    {{ cell.letter }}
                </div>
            </div>
        </div>
        
        <p class="text-white/60 text-sm mt-4">Click and drag to select words</p>
    </div>
</template>
