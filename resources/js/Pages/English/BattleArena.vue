<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useWebSocket } from '@/Composables/useWebSocket'
import { useTimer } from '@/Composables/useTimer'
import { useAudio } from '@/Composables/useAudio'
import BattleResult from '@/Components/English/Battle/BattleResult.vue'
import { ClockIcon, BoltIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    battle: Object,
    currentRound: Object,
    player: Object,
    opponent: Object,
})

const ws = useWebSocket()
const audio = useAudio()
const { time, formattedTime, start: startTimer, stop: stopTimer, reset: resetTimer } = useTimer({ 
    initialTime: 15, 
    countdown: true 
})

const battleChannel = ref(null)
const currentQuestion = ref(null)
const selectedAnswer = ref(null)
const hasAnswered = ref(false)
const roundNumber = ref(1)
const totalRounds = ref(5)
const playerScore = ref(0)
const opponentScore = ref(0)
const playerAnswered = ref(false)
const opponentAnswered = ref(false)
const isComplete = ref(false)
const results = ref(null)
const showingFeedback = ref(false)
const lastAnswerCorrect = ref(false)

const progressPercentage = computed(() => (roundNumber.value / totalRounds.value) * 100)

const submitAnswer = async (answerIndex) => {
    if (hasAnswered.value || selectedAnswer.value !== null) return
    
    selectedAnswer.value = answerIndex
    hasAnswered.value = true
    playerAnswered.value = true
    stopTimer()
    
    const isCorrect = currentQuestion.value?.options[answerIndex] === currentQuestion.value?.correct_answer
    lastAnswerCorrect.value = isCorrect
    
    if (isCorrect) {
        playerScore.value += 10 + time.value // Bonus for speed
        audio.playCorrect()
    } else {
        audio.playWrong()
    }
    
    try {
        await axios.post(`/api/v1/english/battles/${props.battle.id}/answer`, {
            round: roundNumber.value,
            answer_index: answerIndex,
            time_taken: 15 - time.value,
        })
    } catch (error) {
        console.error('Failed to submit answer:', error)
    }
}

const handleTimeUp = () => {
    if (!hasAnswered.value) {
        submitAnswer(-1) // No answer
    }
}

const nextRound = () => {
    showingFeedback.value = false
    selectedAnswer.value = null
    hasAnswered.value = false
    playerAnswered.value = false
    opponentAnswered.value = false
}

// Setup WebSocket listeners
onMounted(() => {
    currentQuestion.value = props.currentRound?.question
    
    battleChannel.value = ws.joinBattleChannel(props.battle.id)
    
    // Listen for opponent answers
    battleChannel.value.listen('BattleAnswerSubmitted', (event) => {
        if (event.user_id !== props.player.id) {
            opponentAnswered.value = true
        }
    })
    
    // Listen for new rounds
    battleChannel.value.listen('BattleRoundStarted', (event) => {
        roundNumber.value = event.round
        currentQuestion.value = event.question
        nextRound()
        resetTimer()
        startTimer()
    })
    
    // Listen for battle completion
    battleChannel.value.listen('BattleCompleted', (event) => {
        stopTimer()
        results.value = event
        isComplete.value = true
        
        if (event.winner_id === props.player.id) {
            audio.playSuccess()
        }
    })
    
    startTimer()
})

// Watch timer for timeout
watch(time, (val) => {
    if (val <= 0) {
        handleTimeUp()
    }
})

onUnmounted(() => {
    stopTimer()
    if (battleChannel.value) {
        ws.leaveChannel(battleChannel.value)
    }
})

const exitBattle = () => {
    router.visit('/student/english/battle')
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-purple-900 via-purple-800 to-indigo-900 text-white">
        <!-- Battle Result Modal -->
        <BattleResult
            v-if="isComplete"
            :results="results"
            :player="player"
            :opponent="opponent"
            @close="exitBattle"
        />
        
        <!-- Header -->
        <header class="px-4 pt-4 pb-2">
            <!-- Round Progress -->
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-purple-300">Round {{ roundNumber }}/{{ totalRounds }}</span>
                <div class="flex-1 mx-4 h-2 bg-purple-700/50 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-yellow-400 to-yellow-500 transition-all duration-300"
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>
            </div>
            
            <!-- Players -->
            <div class="flex items-center justify-between">
                <!-- Player -->
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-xl font-bold border-2 border-blue-400">
                        {{ player?.name?.[0]?.toUpperCase() }}
                    </div>
                    <div>
                        <p class="font-medium">You</p>
                        <p class="text-2xl font-bold text-blue-400">{{ playerScore }}</p>
                    </div>
                    <div v-if="playerAnswered" class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                        ✓
                    </div>
                </div>
                
                <!-- VS -->
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex flex-col items-center justify-center">
                        <ClockIcon class="w-5 h-5" />
                        <span class="text-xl font-bold font-mono">{{ formattedTime }}</span>
                    </div>
                </div>
                
                <!-- Opponent -->
                <div class="flex items-center space-x-3 flex-row-reverse">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-xl font-bold border-2 border-red-400">
                        {{ opponent?.name?.[0]?.toUpperCase() }}
                    </div>
                    <div class="text-right">
                        <p class="font-medium">{{ opponent?.name }}</p>
                        <p class="text-2xl font-bold text-red-400">{{ opponentScore }}</p>
                    </div>
                    <div v-if="opponentAnswered" class="w-6 h-6 rounded-full bg-red-500 flex items-center justify-center">
                        ✓
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Question -->
        <main class="px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <!-- Question Card -->
                <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-6 mb-8">
                    <p class="text-xl font-medium text-center leading-relaxed">
                        {{ currentQuestion?.question || 'Loading question...' }}
                    </p>
                </div>
                
                <!-- Options -->
                <div class="grid grid-cols-1 gap-3">
                    <button
                        v-for="(option, index) in currentQuestion?.options"
                        :key="index"
                        @click="submitAnswer(index)"
                        :disabled="hasAnswered"
                        class="p-4 rounded-2xl font-medium text-lg transition-all duration-200"
                        :class="{
                            'bg-white/20 hover:bg-white/30 active:scale-98': !hasAnswered,
                            'bg-green-500/80': hasAnswered && option === currentQuestion?.correct_answer,
                            'bg-red-500/80': hasAnswered && selectedAnswer === index && option !== currentQuestion?.correct_answer,
                            'bg-white/10 opacity-50': hasAnswered && selectedAnswer !== index && option !== currentQuestion?.correct_answer,
                        }"
                    >
                        <span class="flex items-center">
                            <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center mr-3 text-sm">
                                {{ String.fromCharCode(65 + index) }}
                            </span>
                            {{ option }}
                        </span>
                    </button>
                </div>
                
                <!-- Speed Bonus Indicator -->
                <div v-if="hasAnswered && lastAnswerCorrect" class="mt-6 text-center">
                    <div class="inline-flex items-center space-x-2 bg-yellow-500/20 rounded-full px-4 py-2">
                        <BoltIcon class="w-5 h-5 text-yellow-400" />
                        <span class="text-yellow-400 font-medium">+{{ time }} speed bonus!</span>
                    </div>
                </div>
                
                <!-- Waiting for opponent -->
                <div v-if="hasAnswered && !opponentAnswered" class="mt-6 text-center text-purple-300">
                    Waiting for opponent...
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.active\:scale-98:active {
    transform: scale(0.98);
}
</style>
