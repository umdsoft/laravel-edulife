<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const gridSize = 10
const grid = ref([])
const clues = ref({ across: [], down: [] })
const selectedCell = ref(null)
const selectedDirection = ref('across')
const solvedWords = ref([])

onMounted(() => {
    initializeGame()
})

const initializeGame = () => {
    grid.value = Array(gridSize).fill(null).map((_, row) =>
        Array(gridSize).fill(null).map((_, col) => ({
            row,
            col,
            letter: '',
            userLetter: '',
            isBlack: true,
            number: null,
            acrossClue: null,
            downClue: null,
        }))
    )
    
    const words = props.content?.words?.slice(0, 6) || []
    
    if (words[0]) {
        const word = words[0].word.toUpperCase()
        const startRow = 4
        const startCol = Math.floor((gridSize - word.length) / 2)
        
        clues.value.across.push({
            number: 1,
            clue: words[0].translation_uz,
            word: word,
            row: startRow,
            col: startCol,
        })
        
        for (let i = 0; i < word.length; i++) {
            const cell = grid.value[startRow][startCol + i]
            cell.letter = word[i]
            cell.isBlack = false
            cell.acrossClue = 1
            if (i === 0) cell.number = 1
        }
    }
    
    if (words[1] && words[0]) {
        const word = words[1].word.toUpperCase()
        const crossIndex = word.split('').findIndex(c => 
            words[0].word.toUpperCase().includes(c)
        )
        
        if (crossIndex >= 0) {
            const crossChar = word[crossIndex]
            const firstWord = words[0].word.toUpperCase()
            const crossCol = Math.floor((gridSize - firstWord.length) / 2) + 
                firstWord.indexOf(crossChar)
            const startRow = 4 - crossIndex
            
            clues.value.down.push({
                number: 2,
                clue: words[1].translation_uz,
                word: word,
                row: startRow,
                col: crossCol,
            })
            
            for (let i = 0; i < word.length; i++) {
                const cell = grid.value[startRow + i][crossCol]
                cell.letter = word[i]
                cell.isBlack = false
                cell.downClue = 2
                if (i === 0 && !cell.number) cell.number = 2
            }
        }
    }
}

const selectCell = (cell) => {
    if (cell.isBlack) return
    
    if (selectedCell.value?.row === cell.row && selectedCell.value?.col === cell.col) {
        selectedDirection.value = selectedDirection.value === 'across' ? 'down' : 'across'
    } else {
        selectedCell.value = cell
    }
}

const handleInput = (e) => {
    if (!selectedCell.value) return
    
    const key = e.key.toUpperCase()
    
    if (/^[A-Z]$/.test(key)) {
        selectedCell.value.userLetter = key
        moveToNextCell()
        checkWord()
    } else if (e.key === 'Backspace') {
        if (selectedCell.value.userLetter) {
            selectedCell.value.userLetter = ''
        } else {
            moveToPrevCell()
        }
    } else if (e.key === 'ArrowRight') {
        moveCell(0, 1)
    } else if (e.key === 'ArrowLeft') {
        moveCell(0, -1)
    } else if (e.key === 'ArrowDown') {
        moveCell(1, 0)
    } else if (e.key === 'ArrowUp') {
        moveCell(-1, 0)
    }
}

const moveToNextCell = () => {
    if (selectedDirection.value === 'across') {
        moveCell(0, 1)
    } else {
        moveCell(1, 0)
    }
}

const moveToPrevCell = () => {
    if (selectedDirection.value === 'across') {
        moveCell(0, -1)
    } else {
        moveCell(-1, 0)
    }
}

const moveCell = (rowDelta, colDelta) => {
    if (!selectedCell.value) return
    
    const newRow = selectedCell.value.row + rowDelta
    const newCol = selectedCell.value.col + colDelta
    
    if (newRow >= 0 && newRow < gridSize && newCol >= 0 && newCol < gridSize) {
        const newCell = grid.value[newRow][newCol]
        if (!newCell.isBlack) {
            selectedCell.value = newCell
        }
    }
}

const checkWord = () => {
    clues.value.across.forEach(clue => {
        if (solvedWords.value.includes(`across-${clue.number}`)) return
        
        let isComplete = true
        for (let i = 0; i < clue.word.length; i++) {
            const cell = grid.value[clue.row][clue.col + i]
            if (cell.userLetter !== clue.word[i]) {
                isComplete = false
                break
            }
        }
        
        if (isComplete) {
            solvedWords.value.push(`across-${clue.number}`)
            emit('score', 25)
            emit('correct')
            playAudio('/sounds/correct.mp3')
        }
    })
    
    clues.value.down.forEach(clue => {
        if (solvedWords.value.includes(`down-${clue.number}`)) return
        
        let isComplete = true
        for (let i = 0; i < clue.word.length; i++) {
            const cell = grid.value[clue.row + i][clue.col]
            if (cell.userLetter !== clue.word[i]) {
                isComplete = false
                break
            }
        }
        
        if (isComplete) {
            solvedWords.value.push(`down-${clue.number}`)
            emit('score', 25)
            emit('correct')
            playAudio('/sounds/correct.mp3')
        }
    })
    
    const totalWords = clues.value.across.length + clues.value.down.length
    if (solvedWords.value.length === totalWords) {
        setTimeout(() => emit('complete'), 1000)
    }
}

const isSelected = (cell) => {
    return selectedCell.value?.row === cell.row && selectedCell.value?.col === cell.col
}
</script>

<template>
    <div class="flex flex-col lg:flex-row items-start justify-center gap-6 py-4 px-4" @keydown="handleInput" tabindex="0">
        <div class="bg-white p-2 rounded-xl">
            <div v-for="(row, rowIndex) in grid" :key="rowIndex" class="flex">
                <div v-for="(cell, colIndex) in row" :key="colIndex"
                    @click="selectCell(cell)"
                    class="w-8 h-8 border border-gray-300 flex items-center justify-center relative cursor-pointer"
                    :class="{
                        'bg-black': cell.isBlack,
                        'bg-blue-200': isSelected(cell),
                        'bg-green-200': cell.userLetter === cell.letter && cell.letter,
                    }">
                    <span v-if="cell.number" class="absolute top-0 left-0.5 text-[8px] text-gray-500">
                        {{ cell.number }}
                    </span>
                    <span v-if="!cell.isBlack" class="text-sm font-bold">
                        {{ cell.userLetter }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col gap-4 text-white">
            <div>
                <h3 class="font-bold mb-2">Across →</h3>
                <div v-for="clue in clues.across" :key="'across-' + clue.number" class="text-sm mb-1"
                    :class="{ 'line-through opacity-50': solvedWords.includes(`across-${clue.number}`) }">
                    {{ clue.number }}. {{ clue.clue }}
                </div>
            </div>
            <div>
                <h3 class="font-bold mb-2">Down ↓</h3>
                <div v-for="clue in clues.down" :key="'down-' + clue.number" class="text-sm mb-1"
                    :class="{ 'line-through opacity-50': solvedWords.includes(`down-${clue.number}`) }">
                    {{ clue.number }}. {{ clue.clue }}
                </div>
            </div>
        </div>
    </div>
</template>
