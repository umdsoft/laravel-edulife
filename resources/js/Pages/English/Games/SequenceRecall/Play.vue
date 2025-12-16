<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import axios from 'axios'

const props = defineProps({
    level: {
        type: Object,
        required: true
    },
    config: {
        type: Object,
        default: () => ({})
    },
    gameModes: {
        type: Array,
        default: () => []
    },
    sequenceTypes: {
        type: Array,
        default: () => []
    },
    powerups: {
        type: Array,
        default: () => []
    }
})

// Game state
const gameState = ref('idle')
const sessionId = ref(null)
const score = ref(0)
const currentRound = ref(0)
const totalRounds = ref(props.level.rounds || 5)

// Mode selection
const selectedMode = ref('words')
const selectedType = ref('forward')

// Sequence state
const currentSequence = ref([])
const shuffledOptions = ref([])
const showingIndex = ref(-1)
const userAnswer = ref([])
const usedPowerups = ref([])

// Result state
const lastResult = ref(null)
const finalResult = ref(null)

// Timer
const recallTimeLeft = ref(30)
let recallTimer = null
let showingTimer = null

const availablePowerups = computed(() => {
    return props.powerups.filter(p => !usedPowerups.value.includes(p.id))
})

function getModeIcon(modeId) {
    const mode = props.gameModes.find(m => m.id === modeId)
    return mode?.icon || '🎮'
}

function getTypeIcon(typeId) {
    const type = props.sequenceTypes.find(t => t.id === typeId)
    return type?.icon || '➡️'
}

function getModeBgGradient(modeId) {
    const gradients = {
        'words': 'from-purple-500 to-indigo-600',
        'numbers': 'from-blue-500 to-cyan-600',
        'images': 'from-pink-500 to-rose-600',
        'mixed': 'from-amber-500 to-orange-600'
    }
    return gradients[modeId] || 'from-purple-500 to-indigo-600'
}

function getTypeBgGradient(typeId) {
    const gradients = {
        'forward': 'from-emerald-500 to-teal-600',
        'reverse': 'from-violet-500 to-purple-600',
        'alphabetical': 'from-orange-500 to-red-600',
        'random': 'from-pink-500 to-fuchsia-600'
    }
    return gradients[typeId] || 'from-emerald-500 to-teal-600'
}

// Start game
async function startGame() {
    try {
        const response = await axios.post(
            route('student.english.games.sequence-recall.start', { level: props.level.level_number }),
            {
                mode: selectedMode.value,
                sequence_type: selectedType.value
            }
        )

        if (response.data.success) {
            sessionId.value = response.data.data.session_id
            currentRound.value = 1
            totalRounds.value = response.data.data.total_rounds || props.level.rounds
            score.value = 0
            usedPowerups.value = []

            // Load first sequence
            loadSequence(response.data.data.current_sequence)
        }
    } catch (error) {
        console.error('Failed to start game:', error)
    }
}

// Load and display sequence
function loadSequence(sequenceData) {
    currentSequence.value = sequenceData.items || []
    shuffledOptions.value = [...currentSequence.value].sort(() => Math.random() - 0.5)
    userAnswer.value = []
    showingIndex.value = -1
    gameState.value = 'showing'

    // Start showing sequence
    showSequence()
}

// Show sequence items one by one
function showSequence() {
    const displayTime = props.config.display_time_per_item || 1500

    showingTimer = setInterval(() => {
        if (showingIndex.value < currentSequence.value.length - 1) {
            showingIndex.value++
        } else {
            clearInterval(showingTimer)
            // Wait a moment then switch to recall
            setTimeout(() => {
                startRecall()
            }, 1000)
        }
    }, displayTime)
}

// Start recall phase
function startRecall() {
    gameState.value = 'recalling'
    recallTimeLeft.value = props.level.time_limit || 30

    recallTimer = setInterval(() => {
        recallTimeLeft.value--
        if (recallTimeLeft.value <= 0) {
            clearInterval(recallTimer)
            submitAnswer()
        }
    }, 1000)
}

// Add item to answer
function addToAnswer(item) {
    if (!isItemSelected(item) && userAnswer.value.length < currentSequence.value.length) {
        userAnswer.value.push(item)
    }
}

// Remove item from answer
function removeFromAnswer(index) {
    userAnswer.value.splice(index, 1)
}

// Check if item is selected
function isItemSelected(item) {
    return userAnswer.value.some(a => a.id === item.id)
}

// Clear answer
function clearAnswer() {
    userAnswer.value = []
}

// Submit answer
async function submitAnswer() {
    if (recallTimer) {
        clearInterval(recallTimer)
    }

    const answerTime = (props.level.time_limit || 30) - recallTimeLeft.value
    const answerIds = userAnswer.value.map(item => item.id)

    try {
        const response = await axios.post(
            route('student.english.games.sequence-recall.check'),
            {
                session_id: sessionId.value,
                answer: answerIds,
                time: answerTime
            }
        )

        if (response.data.success) {
            lastResult.value = response.data.data
            score.value = response.data.data.total_score || score.value
            gameState.value = 'result'
        }
    } catch (error) {
        console.error('Failed to check answer:', error)
    }
}

// Next round
async function nextRound() {
    if (currentRound.value >= totalRounds.value) {
        completeGame()
        return
    }

    try {
        const response = await axios.post(
            route('student.english.games.sequence-recall.next-sequence'),
            { session_id: sessionId.value }
        )

        if (response.data.success) {
            currentRound.value++
            loadSequence(response.data.data)
        }
    } catch (error) {
        console.error('Failed to get next sequence:', error)
    }
}

// Complete game
async function completeGame() {
    try {
        const response = await axios.post(
            route('student.english.games.sequence-recall.complete'),
            { session_id: sessionId.value }
        )

        if (response.data.success) {
            finalResult.value = response.data.data
            gameState.value = 'complete'
        }
    } catch (error) {
        console.error('Failed to complete game:', error)
    }
}

// Use powerup
async function usePowerup(powerupId) {
    try {
        const response = await axios.post(
            route('student.english.games.sequence-recall.powerup'),
            {
                session_id: sessionId.value,
                powerup_id: powerupId
            }
        )

        if (response.data.success) {
            usedPowerups.value.push(powerupId)
            applyPowerupEffect(powerupId, response.data.data)
        }
    } catch (error) {
        console.error('Failed to use powerup:', error)
    }
}

// Apply powerup effect
function applyPowerupEffect(powerupId, data) {
    switch (powerupId) {
        case 'extra_view':
            // Show sequence again
            showingIndex.value = -1
            gameState.value = 'showing'
            showSequence()
            break
        case 'hint':
            // Show first item
            if (currentSequence.value.length > 0 && userAnswer.value.length === 0) {
                userAnswer.value.push(currentSequence.value[0])
            }
            break
        case 'extra_time':
            recallTimeLeft.value += 15
            break
        case 'remove_option':
            // Remove one wrong option
            const correctIds = currentSequence.value.map(i => i.id)
            const wrongOptions = shuffledOptions.value.filter(o => !correctIds.includes(o.id))
            if (wrongOptions.length > 0) {
                const toRemove = wrongOptions[0]
                shuffledOptions.value = shuffledOptions.value.filter(o => o.id !== toRemove.id)
            }
            break
    }
}

// Restart game
function restartGame() {
    gameState.value = 'idle'
    sessionId.value = null
    score.value = 0
    currentRound.value = 0
    usedPowerups.value = []
    lastResult.value = null
    finalResult.value = null
}

function goBack() {
    router.visit('/student/english/games/sequence-recall')
}

function nextLevel() {
    const nextLevelNumber = props.level.level_number + 1
    if (nextLevelNumber <= 10) {
        router.visit(`/student/english/games/sequence-recall/play/${nextLevelNumber}`)
    } else {
        router.visit('/student/english/games/sequence-recall')
    }
}

// Helper functions
function getSequenceTypeInstruction() {
    switch (selectedType.value) {
        case 'forward':
            return 'Ko\'rsatilgan tartibda eslang'
        case 'reverse':
            return 'Teskari tartibda eslang'
        case 'alphabetical':
            return 'Alifbo tartibida eslang'
        default:
            return 'Ketma-ketlikni eslang'
    }
}

function getRecallInstruction() {
    switch (selectedType.value) {
        case 'forward':
            return 'So\'zlarni ko\'rsatilgan tartibda tanlang'
        case 'reverse':
            return 'So\'zlarni teskari tartibda tanlang'
        case 'alphabetical':
            return 'So\'zlarni alifbo tartibida tanlang'
        default:
            return 'So\'zlarni to\'g\'ri tartibda tanlang'
    }
}

function getWordById(id) {
    const item = currentSequence.value.find(i => i.id === id)
    return item ? item.word : id
}

// Cleanup
onUnmounted(() => {
    if (showingTimer) clearInterval(showingTimer)
    if (recallTimer) clearInterval(recallTimer)
})
</script>

<template>
    <Head :title="`Ketma-ketlik eslash - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Mode Selection Screen -->
            <div v-if="gameState === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-5xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-purple-500 via-violet-500 to-indigo-600 rounded-3xl mb-6 shadow-2xl shadow-purple-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🧠</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini va turini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-purple-600 dark:text-purple-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.rounds || 5 }} raund</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.time_limit || 30 }}s</span>
                        </div>
                    </div>

                    <!-- Mode Selection -->
                    <div class="mb-8">
                        <h3 class="text-center text-lg font-bold text-gray-700 dark:text-gray-300 mb-4">O'yin rejimi</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <button v-for="mode in gameModes" :key="mode.id"
                                    @click="selectedMode = mode.id"
                                    class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl overflow-hidden"
                                    :class="selectedMode === mode.id ? 'border-2 border-purple-400 dark:border-purple-500 ring-4 ring-purple-100 dark:ring-purple-900/50' : 'border-2 border-gray-100 dark:border-gray-700'">

                                <div class="relative z-10 p-6">
                                    <div :class="['w-16 h-16 rounded-2xl flex items-center justify-center text-4xl mb-3 mx-auto bg-gradient-to-br shadow-lg', getModeBgGradient(mode.id)]">
                                        {{ mode.icon }}
                                    </div>
                                    <h4 class="font-bold text-gray-800 dark:text-white text-sm">{{ mode.name }}</h4>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Type Selection -->
                    <div class="mb-8">
                        <h3 class="text-center text-lg font-bold text-gray-700 dark:text-gray-300 mb-4">Ketma-ketlik turi</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <button v-for="type in sequenceTypes" :key="type.id"
                                    @click="selectedType = type.id"
                                    class="px-6 py-3 rounded-xl transition-all duration-300 font-medium shadow-lg hover:shadow-xl hover:scale-105"
                                    :class="selectedType === type.id
                                        ? 'bg-gradient-to-r text-white ' + getTypeBgGradient(type.id)
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-2 border-gray-100 dark:border-gray-700'">
                                {{ type.icon }} {{ type.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Start Button -->
                    <div class="text-center">
                        <button @click="startGame"
                                class="px-12 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xl font-bold rounded-xl hover:opacity-90 transition-all shadow-2xl hover:shadow-purple-500/50 hover:scale-105">
                            O'yinni boshlash
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-10 text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Showing Sequence Screen -->
            <div v-else-if="gameState === 'showing'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-5xl w-full px-4">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full text-sm font-semibold mb-4">
                                <span>👀</span> Diqqat bilan kuzating!
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Ketma-ketlikni eslang!</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ getSequenceTypeInstruction() }}</p>
                        </div>

                        <!-- Sequence Display -->
                        <div class="flex flex-wrap justify-center gap-4 mb-8">
                            <div v-for="(item, index) in currentSequence" :key="index" class="relative">
                                <div class="w-24 h-24 md:w-32 md:h-32 rounded-2xl flex items-center justify-center text-center p-2 transition-all duration-500 shadow-xl border-2"
                                     :class="showingIndex >= index
                                         ? 'bg-gradient-to-br from-purple-500 to-indigo-600 transform scale-110 border-purple-400'
                                         : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600'">
                                    <span v-if="showingIndex >= index" class="text-white font-bold text-lg md:text-xl drop-shadow-md">
                                        {{ item.word }}
                                    </span>
                                </div>
                                <div v-if="showingIndex >= index"
                                     class="absolute -top-2 -left-2 w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-lg">
                                    {{ index + 1 }}
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-purple-500 via-violet-500 to-indigo-500 h-3 rounded-full transition-all duration-300"
                                 :style="{ width: `${((showingIndex + 1) / currentSequence.length) * 100}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recall Phase Screen -->
            <div v-else-if="gameState === 'recalling'" class="max-w-6xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="goBack" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg">
                            <span class="text-2xl">🧠</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Raund {{ currentRound }}/{{ totalRounds }}</span>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-100 dark:border-purple-800/50">
                            <span class="text-purple-500">⭐</span>
                            <span class="text-purple-600 dark:text-purple-400 font-bold">{{ score }} ball</span>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ userAnswer.length }}/{{ currentSequence.length }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Tanlangan</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ currentRound }}/{{ totalRounds }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Raund</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            recallTimeLeft < 10
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="recallTimeLeft < 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ recallTimeLeft }}s</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>
                </div>

                <!-- Game Content -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Instruction -->
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">{{ getRecallInstruction() }}</h2>
                        <p class="text-gray-500 dark:text-gray-400">To'g'ri tartibda so'zlarni tanlang</p>
                    </div>

                    <!-- Selected Items Display -->
                    <div class="mb-6">
                        <div class="flex flex-wrap justify-center gap-2 min-h-[80px] p-5 bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-2xl border-2 border-gray-100 dark:border-gray-600/50 shadow-inner">
                            <div v-for="(item, index) in userAnswer" :key="index"
                                 class="group relative px-5 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl flex items-center gap-3 shadow-lg hover:shadow-xl transition-all">
                                <span class="text-xs text-purple-200 font-bold">{{ index + 1 }}.</span>
                                <span class="font-bold">{{ item.word }}</span>
                                <button @click="removeFromAnswer(index)"
                                        class="ml-1 w-5 h-5 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="userAnswer.length === 0" class="text-gray-400 dark:text-gray-500 italic self-center">
                                So'zlarni tanlang...
                            </div>
                        </div>
                    </div>

                    <!-- Available Options -->
                    <div class="flex flex-wrap justify-center gap-3 mb-6">
                        <button v-for="(item, index) in shuffledOptions" :key="index"
                                @click="addToAnswer(item)"
                                :disabled="isItemSelected(item)"
                                class="px-6 py-4 rounded-xl font-bold text-lg transition-all duration-300 shadow-lg border-2"
                                :class="isItemSelected(item)
                                    ? 'bg-gray-100 dark:bg-gray-700/50 text-gray-400 dark:text-gray-500 cursor-not-allowed border-gray-200 dark:border-gray-600 opacity-50'
                                    : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-white hover:bg-purple-50 dark:hover:bg-purple-900/20 border-gray-200 dark:border-gray-600 hover:border-purple-400 hover:scale-105 hover:shadow-xl'">
                            {{ item.word }}
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap justify-center gap-4 mb-6">
                        <button @click="clearAnswer"
                                class="px-8 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all shadow-md hover:shadow-lg hover:scale-105">
                            Tozalash
                        </button>
                        <button @click="submitAnswer"
                                :disabled="userAnswer.length !== currentSequence.length"
                                class="px-10 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl hover:scale-105">
                            Tekshirish
                        </button>
                    </div>

                    <!-- Powerups -->
                    <div v-if="availablePowerups.length > 0" class="flex flex-wrap justify-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button v-for="powerup in availablePowerups" :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="usedPowerups.includes(powerup.id)"
                                class="px-5 py-2.5 bg-white dark:bg-gray-700 rounded-xl flex items-center gap-2 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-600 hover:border-amber-400">
                            <span class="text-2xl">{{ powerup.icon }}</span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium text-sm">{{ powerup.name }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Result Phase Screen -->
            <div v-else-if="gameState === 'result'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Emoji -->
                    <div class="mb-6">
                        <span class="text-7xl">{{ lastResult?.is_correct ? '🎉' : '💪' }}</span>
                    </div>

                    <!-- Title -->
                    <h2 class="text-3xl font-bold mb-2"
                        :class="lastResult?.is_correct ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">
                        {{ lastResult?.is_correct ? 'Ajoyib!' : 'Yaxshi harakat!' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">
                        {{ lastResult?.correct_count || 0 }} / {{ currentSequence.length }} to'g'ri
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">+{{ lastResult?.points_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl p-5 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Jami</div>
                        </div>
                    </div>

                    <!-- Correct Sequence -->
                    <div class="mb-8 p-5 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-100 dark:border-green-800/50">
                        <h3 class="text-gray-700 dark:text-gray-300 font-bold mb-3">To'g'ri javob:</h3>
                        <div class="flex flex-wrap justify-center gap-2">
                            <div v-for="(item, index) in lastResult?.correct_answer || []" :key="index"
                                 class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg flex items-center gap-2 border border-green-200 dark:border-green-800 font-medium">
                                <span class="text-xs opacity-70 font-bold">{{ index + 1 }}.</span>
                                <span>{{ getWordById(item) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <button @click="nextRound"
                            class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        {{ currentRound < totalRounds ? 'Keyingi raund' : 'Natijalarni ko\'rish' }}
                    </button>
                </div>
            </div>

            <!-- Game Complete Screen -->
            <div v-else-if="gameState === 'complete'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ finalResult?.stars === 3 ? '🏆' : finalResult?.stars === 2 ? '🎉' : finalResult?.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= (finalResult?.stars || 0) ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ finalResult?.stars === 3 ? 'Mukammal!' : finalResult?.stars === 2 ? 'Ajoyib!' : finalResult?.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ finalResult?.accuracy || 0 }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ finalResult?.total_score || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Jami Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ finalResult?.xp_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ finalResult?.coins_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ finalResult?.accuracy || 0 }}%</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Aniqlik</div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="finalResult?.new_achievements?.length" class="mb-8 p-5 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl border border-yellow-100 dark:border-yellow-800/50">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Yangi yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div v-for="achievement in finalResult.new_achievements" :key="achievement.id"
                                 class="bg-yellow-100 dark:bg-yellow-900/30 rounded-xl px-4 py-2 flex items-center gap-2 border border-yellow-200 dark:border-yellow-800">
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-yellow-700 dark:text-yellow-400 font-medium">{{ achievement.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <button v-if="(finalResult?.stars || 0) > 0 && level.level_number < 10" @click="nextLevel" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Keyingi →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.animate-shake {
    animation: shake 0.3s ease-in-out;
}
</style>
