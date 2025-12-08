<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref(props.content?.defense_words || [
    { english: 'apple', uzbek: 'olma' },
    { english: 'book', uzbek: 'kitob' },
    { english: 'water', uzbek: 'suv' },
    { english: 'house', uzbek: 'uy' },
    { english: 'tree', uzbek: 'daraxt' },
    { english: 'sun', uzbek: 'quyosh' },
])

const enemies = ref([])
const score = ref(0)
const lives = ref(5)
const userInput = ref('')
const gameActive = ref(true)

let spawnInterval = null
let moveInterval = null

onMounted(() => {
    spawnEnemy()
    spawnInterval = setInterval(spawnEnemy, 3000)
    moveInterval = setInterval(moveEnemies, 100)
})

onUnmounted(() => {
    if (spawnInterval) clearInterval(spawnInterval)
    if (moveInterval) clearInterval(moveInterval)
})

const spawnEnemy = () => {
    if (!gameActive.value) return
    
    const randomWord = words.value[Math.floor(Math.random() * words.value.length)]
    enemies.value.push({
        id: Date.now(),
        ...randomWord,
        position: 0,
        lane: Math.floor(Math.random() * 3),
    })
}

const moveEnemies = () => {
    if (!gameActive.value) return
    
    enemies.value.forEach(enemy => {
        enemy.position += 0.5
        if (enemy.position >= 100) {
            enemies.value = enemies.value.filter(e => e.id !== enemy.id)
            lives.value--
            playAudio('/sounds/incorrect.mp3')
            
            if (lives.value <= 0) {
                gameActive.value = false
                if (spawnInterval) clearInterval(spawnInterval)
                if (moveInterval) clearInterval(moveInterval)
                emit('complete')
            }
        }
    })
}

const shoot = () => {
    if (!gameActive.value) return
    
    const input = userInput.value.toLowerCase().trim()
    const targetIndex = enemies.value.findIndex(
        e => e.english.toLowerCase() === input || e.uzbek.toLowerCase() === input
    )
    
    if (targetIndex !== -1) {
        enemies.value.splice(targetIndex, 1)
        score.value += 10
        emit('score', 10)
        emit('correct')
        playAudio('/sounds/correct.mp3')
    }
    
    userInput.value = ''
}

const handleKeydown = (e) => {
    if (e.key === 'Enter') {
        shoot()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg flex justify-between items-center mb-4 px-4">
            <div class="text-white">
                <span class="text-2xl font-bold">{{ score }}</span>
                <span class="text-white/60 ml-1">pts</span>
            </div>
            <div class="flex space-x-1">
                <span v-for="i in 5" :key="i" class="text-xl">
                    {{ i <= lives ? '🛡️' : '💔' }}
                </span>
            </div>
        </div>
        
        <div class="w-full max-w-lg h-48 bg-gradient-to-b from-transparent to-red-500/30 rounded-xl relative overflow-hidden mb-4 mx-4">
            <div class="absolute bottom-0 left-0 right-0 h-2 bg-red-500"></div>
            
            <div v-for="enemy in enemies" :key="enemy.id"
                class="absolute px-3 py-2 bg-white rounded-lg shadow-lg transition-all"
                :style="{ 
                    left: `${20 + enemy.lane * 30}%`, 
                    top: `${enemy.position}%`,
                    transform: 'translateX(-50%)'
                }">
                <p class="font-bold text-gray-900">{{ enemy.english }}</p>
                <p class="text-xs text-gray-500">{{ enemy.uzbek }}</p>
            </div>
            
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-4xl">🏰</div>
        </div>
        
        <div class="w-full max-w-lg px-4">
            <input
                v-model="userInput"
                @keydown="handleKeydown"
                :disabled="!gameActive"
                type="text"
                placeholder="Type the word to destroy enemy..."
                class="w-full px-4 py-3 text-lg rounded-xl border-2 focus:outline-none"
                :class="gameActive ? 'border-white/30 bg-white/10 text-white' : 'border-red-500 bg-red-500/20 text-white'"
            />
        </div>
        
        <div v-if="!gameActive" class="mt-6 text-center">
            <p class="text-2xl font-bold text-red-400">Game Over!</p>
            <p class="text-white mt-2">Final Score: {{ score }}</p>
        </div>
    </div>
</template>
