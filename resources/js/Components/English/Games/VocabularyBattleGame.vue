<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAudio } from '@/Composables/useAudio'

const props = defineProps({
    content: Object,
    level: Object,
})

const emit = defineEmits(['score', 'correct', 'complete'])

const { playAudio } = useAudio()

const words = ref(props.content?.battle_words || [
    { word: 'happy', meaning: 'feeling joy', damage: 10 },
    { word: 'angry', meaning: 'feeling mad', damage: 15 },
    { word: 'beautiful', meaning: 'very pretty', damage: 20 },
    { word: 'intelligent', meaning: 'very smart', damage: 25 },
])

const playerHP = ref(100)
const bossHP = ref(100)
const currentWord = ref(null)
const userInput = ref('')
const feedback = ref(null)
const battleLog = ref([])
const gameOver = ref(false)

let bossAttackTimer = null

onMounted(() => {
    nextWord()
    bossAttackTimer = setInterval(bossAttack, 5000)
})

onUnmounted(() => {
    if (bossAttackTimer) clearInterval(bossAttackTimer)
})

const nextWord = () => {
    currentWord.value = words.value[Math.floor(Math.random() * words.value.length)]
}

const bossAttack = () => {
    if (gameOver.value) return
    
    const damage = Math.floor(Math.random() * 15) + 5
    playerHP.value = Math.max(0, playerHP.value - damage)
    battleLog.value.unshift({ type: 'boss', text: `Boss attacks! -${damage} HP`, time: Date.now() })
    
    if (playerHP.value <= 0) {
        endGame(false)
    }
}

const attack = () => {
    if (feedback.value || gameOver.value) return
    
    if (userInput.value.toLowerCase().trim() === currentWord.value.word.toLowerCase()) {
        feedback.value = 'correct'
        bossHP.value = Math.max(0, bossHP.value - currentWord.value.damage)
        battleLog.value.unshift({ type: 'player', text: `${currentWord.value.word}! -${currentWord.value.damage} Boss HP`, time: Date.now() })
        emit('score', currentWord.value.damage)
        emit('correct')
        playAudio('/sounds/correct.mp3')
        
        if (bossHP.value <= 0) {
            endGame(true)
        }
    } else {
        feedback.value = 'incorrect'
        battleLog.value.unshift({ type: 'miss', text: 'Attack missed!', time: Date.now() })
        playAudio('/sounds/incorrect.mp3')
    }
    
    setTimeout(() => {
        feedback.value = null
        userInput.value = ''
        if (!gameOver.value) nextWord()
    }, 500)
}

const endGame = (won) => {
    gameOver.value = true
    if (bossAttackTimer) clearInterval(bossAttackTimer)
    if (won) {
        emit('score', 100)
        playAudio('/sounds/win.mp3')
    }
    setTimeout(() => emit('complete'), 2000)
}

const handleKeydown = (e) => {
    if (e.key === 'Enter') {
        attack()
    }
}
</script>

<template>
    <div class="flex flex-col items-center py-4">
        <div class="w-full max-w-lg px-4 mb-6">
            <div class="flex items-center mb-3">
                <span class="text-2xl mr-3">👹</span>
                <div class="flex-1">
                    <div class="flex justify-between text-sm text-white/80 mb-1">
                        <span>Boss</span>
                        <span>{{ bossHP }}/100</span>
                    </div>
                    <div class="h-4 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-red-500 rounded-full transition-all" :style="{ width: `${bossHP}%` }"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center">
                <span class="text-2xl mr-3">🧙</span>
                <div class="flex-1">
                    <div class="flex justify-between text-sm text-white/80 mb-1">
                        <span>You</span>
                        <span>{{ playerHP }}/100</span>
                    </div>
                    <div class="h-4 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: `${playerHP}%` }"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="!gameOver" class="bg-white rounded-2xl p-6 mb-4 text-center">
            <p class="text-gray-500 text-sm mb-2">Type this word to attack!</p>
            <p class="text-3xl font-bold text-gray-900">{{ currentWord?.meaning }}</p>
            <p class="text-sm text-orange-500 mt-2">⚔️ Damage: {{ currentWord?.damage }}</p>
        </div>
        
        <input v-if="!gameOver"
            v-model="userInput"
            @keydown="handleKeydown"
            type="text"
            placeholder="Type the word..."
            class="w-full max-w-sm px-4 py-3 text-xl text-center rounded-xl border-2 focus:outline-none"
            :class="{
                'border-white/30 bg-white/10 text-white': !feedback,
                'border-green-500 bg-green-500/20': feedback === 'correct',
                'border-red-500 bg-red-500/20': feedback === 'incorrect',
            }"
        />
        
        <button v-if="!gameOver" @click="attack" :disabled="!userInput"
            class="mt-4 px-8 py-3 bg-orange-500 rounded-xl font-bold text-white hover:bg-orange-600 disabled:opacity-50">
            ⚔️ Attack!
        </button>
        
        <div class="w-full max-w-lg mt-4 px-4 max-h-24 overflow-y-auto">
            <p v-for="log in battleLog.slice(0, 5)" :key="log.time"
                class="text-sm py-1"
                :class="{
                    'text-green-400': log.type === 'player',
                    'text-red-400': log.type === 'boss',
                    'text-yellow-400': log.type === 'miss',
                }">
                {{ log.text }}
            </p>
        </div>
        
        <div v-if="gameOver" class="text-center">
            <p class="text-4xl mb-2">{{ bossHP <= 0 ? '🏆' : '💀' }}</p>
            <p class="text-2xl font-bold" :class="bossHP <= 0 ? 'text-green-400' : 'text-red-400'">
                {{ bossHP <= 0 ? 'Victory!' : 'Defeat...' }}
            </p>
        </div>
    </div>
</template>
