<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import confetti from 'canvas-confetti'
import {
    ClockIcon,
    BoltIcon,
    TrophyIcon,
    XMarkIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    SparklesIcon,
    FireIcon,
    StarIcon,
    PlayIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    battle: Object,
    currentRound: Object,
    player: Object,
    opponent: Object,
    isPlayer1: Boolean,
})

// State
const currentQuestion = ref(null)
const selectedAnswer = ref(null)
const hasAnswered = ref(false)
const roundNumber = ref(1)
const totalRounds = ref(10)
const playerScore = ref(0)
const opponentScore = ref(0)
const playerAnswered = ref(false)
const opponentAnswered = ref(false)
const isComplete = ref(false)
const results = ref(null)
const showingFeedback = ref(false)
const lastAnswerCorrect = ref(false)
const lastPoints = ref(0)
const timeLeft = ref(15)
const timerInterval = ref(null)
const isLoading = ref(true)
const battleStatus = ref('waiting')
const waitingMessage = ref('Raqib kutilmoqda...')
const roundResults = ref([])
const streak = ref(0)
const showStreakAnimation = ref(false)

// Timer
const startTimer = () => {
    timeLeft.value = props.battle?.settings?.time_per_question
        ? Math.floor(props.battle.settings.time_per_question / 1000)
        : 15

    if (timerInterval.value) clearInterval(timerInterval.value)

    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--
        } else {
            handleTimeUp()
        }
    }, 1000)
}

const stopTimer = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
        timerInterval.value = null
    }
}

// Computed
const progressPercentage = computed(() => (roundNumber.value / totalRounds.value) * 100)
const timerPercentage = computed(() => (timeLeft.value / 15) * 100)
const timerColor = computed(() => {
    if (timeLeft.value <= 3) return 'from-red-500 to-red-600'
    if (timeLeft.value <= 7) return 'from-yellow-500 to-orange-500'
    return 'from-green-500 to-emerald-500'
})

const isWinner = computed(() => {
    if (!results.value) return false
    return results.value.player_score > results.value.opponent_score
})
const isDraw = computed(() => {
    if (!results.value) return false
    return results.value.player_score === results.value.opponent_score
})

// Load question from backend or props
const loadQuestion = async () => {
    isLoading.value = true

    if (props.currentRound?.question) {
        currentQuestion.value = props.currentRound.question
        roundNumber.value = props.currentRound.round_number || 1
        totalRounds.value = props.battle?.total_rounds || props.battle?.settings?.rounds || 10
        isLoading.value = false
        startTimer()
        return
    }

    // If no question provided, generate sample questions for demo
    await generateDemoQuestion()
}

const generateDemoQuestion = async () => {
    const demoQuestions = [
        {
            question: '"Accomplish" so\'zining ma\'nosi nima?',
            options: ['Muvaffaqiyatli bajarmoq', 'Yo\'qotmoq', 'Kutmoq', 'Tashlamoq'],
            correct_answer: 'Muvaffaqiyatli bajarmoq',
            question_type: 'vocabulary_meaning',
            difficulty: 'medium'
        },
        {
            question: 'She ___ to the store yesterday.',
            options: ['go', 'goes', 'went', 'going'],
            correct_answer: 'went',
            question_type: 'grammar_correct_form',
            difficulty: 'easy'
        },
        {
            question: '"Happy" so\'zining antonimi qaysi?',
            options: ['Joyful', 'Sad', 'Excited', 'Pleased'],
            correct_answer: 'Sad',
            question_type: 'vocabulary_antonym',
            difficulty: 'easy'
        },
        {
            question: 'I have been ___ English for 5 years.',
            options: ['learn', 'learned', 'learning', 'learns'],
            correct_answer: 'learning',
            question_type: 'grammar_correct_form',
            difficulty: 'medium'
        },
        {
            question: '"Innovation" so\'zining sinonimi qaysi?',
            options: ['Tradition', 'Novelty', 'Routine', 'Custom'],
            correct_answer: 'Novelty',
            question_type: 'vocabulary_synonym',
            difficulty: 'hard'
        },
        {
            question: 'Which sentence is correct?',
            options: [
                'He don\'t like coffee',
                'He doesn\'t likes coffee',
                'He doesn\'t like coffee',
                'He not like coffee'
            ],
            correct_answer: 'He doesn\'t like coffee',
            question_type: 'grammar_error_find',
            difficulty: 'medium'
        },
        {
            question: '"Determine" so\'zining ma\'nosi nima?',
            options: ['Aniqlamoq', 'O\'chirmoq', 'Yig\'lamoq', 'Uxlamoq'],
            correct_answer: 'Aniqlamoq',
            question_type: 'vocabulary_meaning',
            difficulty: 'medium'
        },
        {
            question: 'If I ___ rich, I would travel the world.',
            options: ['am', 'was', 'were', 'be'],
            correct_answer: 'were',
            question_type: 'grammar_correct_form',
            difficulty: 'hard'
        },
        {
            question: '"Efficient" so\'zining ma\'nosi nima?',
            options: ['Sekin', 'Samarali', 'Qimmat', 'Og\'ir'],
            correct_answer: 'Samarali',
            question_type: 'vocabulary_meaning',
            difficulty: 'medium'
        },
        {
            question: 'The book ___ by Shakespeare.',
            options: ['wrote', 'was wrote', 'was written', 'written'],
            correct_answer: 'was written',
            question_type: 'grammar_correct_form',
            difficulty: 'medium'
        }
    ]

    // Shuffle and pick based on round
    const questionIndex = (roundNumber.value - 1) % demoQuestions.length
    currentQuestion.value = demoQuestions[questionIndex]
    isLoading.value = false
    startTimer()
}

// Submit answer
const submitAnswer = async (optionIndex) => {
    if (hasAnswered.value || selectedAnswer.value !== null) return

    selectedAnswer.value = optionIndex
    hasAnswered.value = true
    playerAnswered.value = true
    stopTimer()

    const selectedOption = currentQuestion.value?.options[optionIndex]
    const isCorrect = selectedOption === currentQuestion.value?.correct_answer
    lastAnswerCorrect.value = isCorrect

    // Calculate points
    let points = 0
    if (isCorrect) {
        const basePoints = 10
        const timeBonus = Math.max(0, Math.floor(timeLeft.value * 0.5))
        points = basePoints + timeBonus

        // Streak bonus
        streak.value++
        if (streak.value >= 3) {
            points += streak.value * 2
            showStreakAnimation.value = true
            setTimeout(() => showStreakAnimation.value = false, 1500)
        }
    } else {
        streak.value = 0
    }

    lastPoints.value = points
    playerScore.value += points

    // Play sound effects
    if (isCorrect) {
        playSound('correct')
    } else {
        playSound('wrong')
    }

    // Store round result
    roundResults.value.push({
        round: roundNumber.value,
        correct: isCorrect,
        points: points,
        timeLeft: timeLeft.value
    })

    // Submit to API
    try {
        if (props.battle?.id && props.currentRound?.id) {
            await axios.post(`/api/v1/english/battles/${props.battle.id}/rounds/${props.currentRound.id}/answer`, {
                answer: selectedOption,
                time_ms: (15 - timeLeft.value) * 1000,
            })
        }
    } catch (error) {
        console.error('Failed to submit answer:', error)
    }

    // Simulate opponent answering (for demo)
    simulateOpponent()

    // Show feedback then proceed
    showingFeedback.value = true
    setTimeout(() => {
        proceedToNextRound()
    }, 2000)
}

const simulateOpponent = () => {
    // Simulate opponent answering after random delay
    const delay = Math.random() * 3000 + 500
    setTimeout(() => {
        opponentAnswered.value = true
        // Random opponent score
        const opponentCorrect = Math.random() > 0.4
        if (opponentCorrect) {
            opponentScore.value += Math.floor(Math.random() * 10) + 8
        }
    }, delay)
}

const handleTimeUp = () => {
    if (!hasAnswered.value) {
        submitAnswer(-1) // Timeout - no answer
    }
}

const proceedToNextRound = () => {
    showingFeedback.value = false

    if (roundNumber.value >= totalRounds.value) {
        completeBattle()
        return
    }

    // Reset for next round
    roundNumber.value++
    selectedAnswer.value = null
    hasAnswered.value = false
    playerAnswered.value = false
    opponentAnswered.value = false

    // Load next question
    generateDemoQuestion()
}

const completeBattle = async () => {
    stopTimer()

    // Determine winner based on actual scores
    const playerWon = playerScore.value > opponentScore.value
    const opponentWon = opponentScore.value > playerScore.value
    const draw = playerScore.value === opponentScore.value

    // Check if this is an AI battle (no coins, no ELO change)
    const isAI = isAIBattle.value

    // Calculate ELO change based on result (only for non-AI battles)
    let eloChange = 0
    if (!isAI) {
        if (playerWon) {
            eloChange = Math.floor(Math.random() * 15) + 10 // +10 to +25
        } else if (opponentWon) {
            eloChange = -(Math.floor(Math.random() * 10) + 5) // -5 to -15
        }
    }

    // Calculate rewards based on battle type
    let xpEarned = 0
    let coinsEarned = 0

    if (isAI) {
        // AI battles: Only XP, no coins
        if (playerWon) {
            xpEarned = 30
        } else if (opponentWon) {
            xpEarned = 10
        } else {
            xpEarned = 15
        }
        coinsEarned = 0
    } else {
        // Real battles: XP + coins
        if (playerWon) {
            xpEarned = 50
            coinsEarned = 25
        } else if (opponentWon) {
            xpEarned = 15
            coinsEarned = 5
        } else {
            xpEarned = 25
            coinsEarned = 10
        }
    }

    results.value = {
        winner_id: draw ? null : (playerWon ? props.player?.id : props.opponent?.id),
        player_score: playerScore.value,
        opponent_score: opponentScore.value,
        player_won: playerWon,
        opponent_won: opponentWon,
        is_draw: draw,
        is_ai_battle: isAI,
        elo_change: eloChange,
        new_elo: (props.player?.elo_rating || 1000) + eloChange,
        rewards: {
            xp: xpEarned,
            coins: coinsEarned
        },
        round_results: roundResults.value,
        accuracy: Math.round((roundResults.value.filter(r => r.correct).length / roundResults.value.length) * 100),
        avg_time: Math.round(roundResults.value.reduce((sum, r) => sum + (15 - r.timeLeft), 0) / roundResults.value.length * 10) / 10
    }

    isComplete.value = true

    // Trigger confetti only for winner
    if (playerWon) {
        triggerConfetti()
        playSound('victory')
    } else if (opponentWon) {
        playSound('defeat')
    }

    // Sync rewards to backend with full stats
    try {
        if (props.battle?.id) {
            const correctAnswers = roundResults.value.filter(r => r.correct).length
            const avgTime = roundResults.value.length > 0
                ? roundResults.value.reduce((sum, r) => sum + (15 - r.timeLeft), 0) / roundResults.value.length
                : 0

            // The completeBattle API will handle coin/XP distribution and save to DB
            await axios.post(`/api/v1/english/battles/${props.battle.id}/complete`, {
                player_score: playerScore.value,
                opponent_score: opponentScore.value,
                player_correct: correctAnswers,
                total_rounds: totalRounds.value,
                accuracy: results.value.accuracy,
                avg_time: Math.round(avgTime * 10) / 10
            })
        }
    } catch (error) {
        console.error('Failed to complete battle:', error)
    }
}

const triggerConfetti = () => {
    const duration = 3000
    const end = Date.now() + duration

    const frame = () => {
        confetti({
            particleCount: 3,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: ['#FFD700', '#FFA500', '#FF6B6B']
        })
        confetti({
            particleCount: 3,
            angle: 120,
            spread: 55,
            origin: { x: 1 },
            colors: ['#FFD700', '#FFA500', '#FF6B6B']
        })

        if (Date.now() < end) {
            requestAnimationFrame(frame)
        }
    }
    frame()
}

const playSound = (type) => {
    // Sound effects - could be implemented with Web Audio API
    console.log(`Playing ${type} sound`)
}

const exitBattle = () => {
    router.visit('/student/english/battle')
}

const playAgain = () => {
    router.visit('/student/english/battle')
}

// Check if AI battle
const isAIBattle = computed(() => props.opponent?.is_ai === true || props.battle?.battle_type === 'practice')

// Initialize
onMounted(() => {
    battleStatus.value = props.battle?.status || 'in_progress'
    playerScore.value = props.player?.score || 0
    opponentScore.value = props.opponent?.score || 0

    // For AI battles, start immediately
    if (isAIBattle.value) {
        battleStatus.value = 'in_progress'
        loadQuestion()
        return
    }

    if (battleStatus.value === 'waiting' || !props.opponent) {
        waitingMessage.value = 'Raqib kutilmoqda...'
        // Start polling or WebSocket for opponent
        setTimeout(() => {
            battleStatus.value = 'in_progress'
            loadQuestion()
        }, 2000)
    } else {
        loadQuestion()
    }
})

onUnmounted(() => {
    stopTimer()
})
</script>

<template>
    <Head :title="`Battle - Round ${roundNumber}`" />

    <div class="min-h-screen bg-gradient-to-b from-purple-900 via-purple-800 to-indigo-900 text-white overflow-hidden">
        <!-- Battle Result Modal -->
        <Transition
            enter-active-class="transition-all duration-500"
            enter-from-class="opacity-0 scale-90"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-90"
        >
            <div v-if="isComplete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-md p-4">
                <div class="bg-gradient-to-b from-purple-900 to-indigo-900 rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-purple-500/30">
                    <!-- Result Header -->
                    <div class="text-center mb-8">
                        <div class="text-7xl mb-4 animate-bounce">
                            {{ isWinner ? '🏆' : isDraw ? '🤝' : '😔' }}
                        </div>
                        <h2
                            class="text-5xl font-black mb-2"
                            :class="{
                                'text-yellow-400': isWinner,
                                'text-gray-400': isDraw,
                                'text-red-400': !isWinner && !isDraw,
                            }"
                        >
                            {{ isWinner ? 'VICTORY!' : isDraw ? 'DRAW!' : 'DEFEAT' }}
                        </h2>
                        <p class="text-purple-300">
                            {{ isWinner ? 'Tabriklaymiz! Siz g\'alaba qozondingiz!' : isDraw ? 'Durrang!' : 'Keyingi safar omad!' }}
                        </p>
                    </div>

                    <!-- Score Summary -->
                    <div class="flex items-center justify-center gap-8 mb-8">
                        <div class="text-center">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-3xl font-bold mb-2 ring-4 ring-blue-400/50">
                                {{ player?.name?.[0]?.toUpperCase() || 'Y' }}
                            </div>
                            <p class="text-white font-medium mb-1">Siz</p>
                            <p class="text-4xl font-black text-blue-400">{{ results?.player_score || playerScore }}</p>
                        </div>

                        <div class="text-3xl font-bold text-purple-400">VS</div>

                        <div class="text-center">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-3xl font-bold mb-2 ring-4 ring-red-400/50">
                                {{ opponent?.name?.[0]?.toUpperCase() || 'R' }}
                            </div>
                            <p class="text-white font-medium mb-1">{{ opponent?.name || 'Raqib' }}</p>
                            <p class="text-4xl font-black text-red-400">{{ results?.opponent_score || opponentScore }}</p>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        <div class="bg-white/10 rounded-xl p-3 text-center">
                            <p class="text-2xl font-bold text-white">{{ results?.accuracy || 0 }}%</p>
                            <p class="text-xs text-purple-300">Aniqlik</p>
                        </div>
                        <div class="bg-white/10 rounded-xl p-3 text-center">
                            <p class="text-2xl font-bold text-white">{{ results?.avg_time || 0 }}s</p>
                            <p class="text-xs text-purple-300">O'rtacha vaqt</p>
                        </div>
                        <div class="bg-white/10 rounded-xl p-3 text-center">
                            <p class="text-2xl font-bold text-white">{{ roundResults.filter(r => r.correct).length }}/{{ totalRounds }}</p>
                            <p class="text-xs text-purple-300">To'g'ri</p>
                        </div>
                    </div>

                    <!-- ELO Change (only for non-AI battles) -->
                    <div v-if="!results?.is_ai_battle" class="bg-white/10 rounded-2xl p-4 mb-6">
                        <div class="flex items-center justify-center gap-3">
                            <component
                                :is="results?.elo_change > 0 ? ArrowTrendingUpIcon : (results?.elo_change < 0 ? ArrowTrendingDownIcon : null)"
                                v-if="results?.elo_change !== 0"
                                class="w-8 h-8"
                                :class="results?.elo_change > 0 ? 'text-green-400' : 'text-red-400'"
                            />
                            <span
                                class="text-3xl font-black"
                                :class="{
                                    'text-green-400': results?.elo_change > 0,
                                    'text-red-400': results?.elo_change < 0,
                                    'text-gray-400': results?.elo_change === 0
                                }"
                            >
                                {{ results?.elo_change > 0 ? '+' : '' }}{{ results?.elo_change || 0 }} ELO
                            </span>
                        </div>
                        <p class="text-center text-purple-300 text-sm mt-2">
                            Yangi reyting: <span class="font-bold text-white">{{ results?.new_elo || 1000 }}</span>
                        </p>
                    </div>

                    <!-- AI Battle Notice -->
                    <div v-else class="bg-purple-500/20 rounded-2xl p-4 mb-6 border border-purple-500/30">
                        <p class="text-center text-purple-200">
                            <span class="text-2xl">🤖</span> AI battle - mashq rejimi
                        </p>
                        <p class="text-center text-purple-300 text-sm mt-1">
                            ELO o'zgarmaydi, faqat XP beriladi
                        </p>
                    </div>

                    <!-- Rewards -->
                    <div class="grid gap-4 mb-8" :class="results?.is_ai_battle ? 'grid-cols-1' : 'grid-cols-2'">
                        <div class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 rounded-xl p-4 text-center border border-yellow-500/30">
                            <SparklesIcon class="w-8 h-8 mx-auto mb-2 text-yellow-400" />
                            <p class="text-3xl font-black text-yellow-400">+{{ results?.rewards?.xp || 0 }}</p>
                            <p class="text-sm text-yellow-300">XP</p>
                        </div>
                        <div v-if="!results?.is_ai_battle" class="bg-gradient-to-br from-amber-500/20 to-yellow-500/20 rounded-xl p-4 text-center border border-amber-500/30">
                            <svg class="w-8 h-8 mx-auto mb-2 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <text x="12" y="16" text-anchor="middle" fill="#000" font-size="10" font-weight="bold">$</text>
                            </svg>
                            <p class="text-3xl font-black text-amber-400">+{{ results?.rewards?.coins || 0 }}</p>
                            <p class="text-sm text-amber-300">Coin</p>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="grid grid-cols-2 gap-4">
                        <button
                            @click="exitBattle"
                            class="py-4 bg-white/10 text-white font-bold rounded-2xl hover:bg-white/20 transition-all"
                        >
                            Chiqish
                        </button>
                        <button
                            @click="playAgain"
                            class="py-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-bold rounded-2xl hover:from-purple-600 hover:to-pink-600 transition-all flex items-center justify-center gap-2"
                        >
                            <PlayIcon class="w-5 h-5" />
                            Yana o'ynash
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Streak Animation -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0 -translate-y-10"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-y-10"
        >
            <div v-if="showStreakAnimation" class="fixed top-1/4 left-1/2 -translate-x-1/2 z-40">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-full px-6 py-3 flex items-center gap-2 shadow-lg animate-pulse">
                    <FireIconSolid class="w-8 h-8 text-yellow-300" />
                    <span class="text-2xl font-black text-white">{{ streak }}x STREAK!</span>
                    <FireIconSolid class="w-8 h-8 text-yellow-300" />
                </div>
            </div>
        </Transition>

        <!-- Header -->
        <header class="px-4 pt-4 pb-2">
            <!-- Round Progress -->
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-purple-300">Round {{ roundNumber }}/{{ totalRounds }}</span>
                <div class="flex-1 mx-4 h-3 bg-purple-700/50 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-yellow-400 to-orange-500 transition-all duration-500 rounded-full"
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>
                <div v-if="streak >= 2" class="flex items-center gap-1 text-orange-400">
                    <FireIconSolid class="w-5 h-5" />
                    <span class="font-bold">{{ streak }}x</span>
                </div>
            </div>

            <!-- Players -->
            <div class="flex items-center justify-between">
                <!-- Player -->
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-xl font-bold ring-4 ring-blue-400/50 shadow-lg shadow-blue-500/30">
                            {{ player?.name?.[0]?.toUpperCase() || 'Y' }}
                        </div>
                        <div v-if="playerAnswered" class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center ring-2 ring-purple-900">
                            <CheckCircleIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-blue-200">Siz</p>
                        <p class="text-3xl font-black text-blue-400">{{ playerScore }}</p>
                    </div>
                </div>

                <!-- Timer -->
                <div class="relative">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br flex flex-col items-center justify-center shadow-xl"
                         :class="timerColor">
                        <ClockIcon class="w-5 h-5 mb-0.5" />
                        <span class="text-2xl font-black font-mono">{{ timeLeft }}</span>
                    </div>
                    <!-- Timer ring -->
                    <svg class="absolute inset-0 w-20 h-20 -rotate-90">
                        <circle
                            cx="40"
                            cy="40"
                            r="36"
                            stroke="rgba(255,255,255,0.2)"
                            stroke-width="4"
                            fill="none"
                        />
                        <circle
                            cx="40"
                            cy="40"
                            r="36"
                            stroke="white"
                            stroke-width="4"
                            fill="none"
                            stroke-linecap="round"
                            :stroke-dasharray="226"
                            :stroke-dashoffset="226 - (timerPercentage / 100) * 226"
                            class="transition-all duration-1000"
                        />
                    </svg>
                </div>

                <!-- Opponent -->
                <div class="flex items-center gap-3 flex-row-reverse">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-xl font-bold ring-4 ring-red-400/50 shadow-lg shadow-red-500/30">
                            {{ opponent?.name?.[0]?.toUpperCase() || 'R' }}
                        </div>
                        <div v-if="opponentAnswered" class="absolute -bottom-1 -left-1 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center ring-2 ring-purple-900">
                            <CheckCircleIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-red-200">{{ opponent?.name || 'Raqib' }}</p>
                        <p class="text-3xl font-black text-red-400">{{ opponentScore }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Question Area -->
        <main class="px-4 py-6 flex-1">
            <div class="max-w-2xl mx-auto">
                <!-- Loading State -->
                <div v-if="isLoading" class="text-center py-20">
                    <div class="inline-block animate-spin rounded-full h-16 w-16 border-4 border-purple-400 border-t-transparent mb-4"></div>
                    <p class="text-purple-300 text-lg">Savol yuklanmoqda...</p>
                </div>

                <!-- Question Card -->
                <div v-else class="space-y-6">
                    <!-- Question Type Badge -->
                    <div class="flex justify-center">
                        <span class="px-4 py-1.5 bg-purple-500/30 rounded-full text-sm font-medium text-purple-200 border border-purple-400/30">
                            {{ currentQuestion?.question_type === 'vocabulary_meaning' ? '📚 So\'z ma\'nosi' :
                               currentQuestion?.question_type === 'grammar_correct_form' ? '✍️ Grammatika' :
                               currentQuestion?.question_type === 'vocabulary_antonym' ? '↔️ Antonim' :
                               currentQuestion?.question_type === 'vocabulary_synonym' ? '🔄 Sinonim' :
                               '❓ Savol' }}
                        </span>
                    </div>

                    <!-- Question Text -->
                    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 border border-white/10 shadow-xl">
                        <p class="text-2xl font-semibold text-center leading-relaxed">
                            {{ currentQuestion?.question }}
                        </p>
                    </div>

                    <!-- Options -->
                    <div class="grid grid-cols-1 gap-3">
                        <button
                            v-for="(option, index) in currentQuestion?.options"
                            :key="index"
                            @click="submitAnswer(index)"
                            :disabled="hasAnswered"
                            class="group relative p-5 rounded-2xl font-medium text-lg transition-all duration-300 text-left overflow-hidden"
                            :class="{
                                'bg-white/15 hover:bg-white/25 hover:scale-[1.02] active:scale-[0.98] cursor-pointer': !hasAnswered,
                                'bg-green-500 ring-4 ring-green-400/50 scale-[1.02]': hasAnswered && option === currentQuestion?.correct_answer,
                                'bg-red-500 ring-4 ring-red-400/50': hasAnswered && selectedAnswer === index && option !== currentQuestion?.correct_answer,
                                'bg-white/5 opacity-50': hasAnswered && selectedAnswer !== index && option !== currentQuestion?.correct_answer,
                                'cursor-not-allowed': hasAnswered,
                            }"
                        >
                            <div class="flex items-center gap-4">
                                <span
                                    class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg transition-all"
                                    :class="{
                                        'bg-white/20 group-hover:bg-white/30': !hasAnswered,
                                        'bg-white/30': hasAnswered && option === currentQuestion?.correct_answer,
                                        'bg-white/20': hasAnswered && option !== currentQuestion?.correct_answer,
                                    }"
                                >
                                    {{ String.fromCharCode(65 + index) }}
                                </span>
                                <span class="flex-1">{{ option }}</span>
                                <CheckCircleIcon v-if="hasAnswered && option === currentQuestion?.correct_answer" class="w-7 h-7 text-white" />
                                <XCircleIcon v-if="hasAnswered && selectedAnswer === index && option !== currentQuestion?.correct_answer" class="w-7 h-7 text-white" />
                            </div>
                        </button>
                    </div>

                    <!-- Feedback -->
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 translate-y-4"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="hasAnswered" class="text-center py-4">
                            <div v-if="lastAnswerCorrect" class="inline-flex items-center gap-3 bg-green-500/20 rounded-full px-6 py-3 border border-green-500/30">
                                <CheckCircleIcon class="w-6 h-6 text-green-400" />
                                <span class="text-green-400 font-bold text-lg">To'g'ri! +{{ lastPoints }} ball</span>
                                <BoltIcon v-if="timeLeft > 10" class="w-5 h-5 text-yellow-400" />
                            </div>
                            <div v-else class="inline-flex items-center gap-3 bg-red-500/20 rounded-full px-6 py-3 border border-red-500/30">
                                <XCircleIcon class="w-6 h-6 text-red-400" />
                                <span class="text-red-400 font-bold text-lg">Noto'g'ri</span>
                            </div>

                            <div v-if="!opponentAnswered" class="mt-4 text-purple-300 animate-pulse">
                                Raqib javob bermoqda...
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </main>

        <!-- Bottom safe area -->
        <div class="h-20"></div>
    </div>
</template>

<style scoped>
@keyframes pulse-fast {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.animate-pulse-fast {
    animation: pulse-fast 0.5s ease-in-out infinite;
}
</style>
