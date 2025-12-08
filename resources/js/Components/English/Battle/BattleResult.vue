<script setup>
import { computed, onMounted } from 'vue'
import confetti from 'canvas-confetti'
import { TrophyIcon, ArrowTrendingUpIcon, ArrowTrendingDownIcon, MinusIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    results: Object,
    player: Object,
    opponent: Object,
})

const emit = defineEmits(['close'])

const isWinner = computed(() => props.results?.winner_id === props.player?.id)
const isDraw = computed(() => !props.results?.winner_id)

const playerEloChange = computed(() => {
    if (isWinner.value) return props.results?.elo_changes?.winner || 0
    if (isDraw.value) return 0
    return props.results?.elo_changes?.loser || 0
})

const resultText = computed(() => {
    if (isDraw.value) return 'Draw!'
    return isWinner.value ? 'Victory!' : 'Defeat'
})

const resultEmoji = computed(() => {
    if (isDraw.value) return '🤝'
    return isWinner.value ? '🏆' : '😔'
})

onMounted(() => {
    if (isWinner.value) {
        // Celebration confetti
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        })
    }
})
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-gradient-to-b from-purple-900 to-indigo-900 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl border border-purple-500/30">
            <!-- Result Header -->
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">{{ resultEmoji }}</div>
                <h2 
                    class="text-4xl font-bold"
                    :class="{
                        'text-yellow-400': isWinner,
                        'text-gray-400': isDraw,
                        'text-red-400': !isWinner && !isDraw,
                    }"
                >
                    {{ resultText }}
                </h2>
            </div>
            
            <!-- Score Summary -->
            <div class="flex items-center justify-center space-x-8 mb-8">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-2xl font-bold mb-2">
                        {{ player?.name?.[0]?.toUpperCase() }}
                    </div>
                    <p class="text-white font-medium">You</p>
                    <p class="text-3xl font-bold text-blue-400">{{ results?.player_score || 0 }}</p>
                </div>
                
                <div class="text-2xl text-purple-400">VS</div>
                
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-red-500 flex items-center justify-center text-2xl font-bold mb-2">
                        {{ opponent?.name?.[0]?.toUpperCase() }}
                    </div>
                    <p class="text-white font-medium">{{ opponent?.name }}</p>
                    <p class="text-3xl font-bold text-red-400">{{ results?.opponent_score || 0 }}</p>
                </div>
            </div>
            
            <!-- ELO Change -->
            <div class="bg-white/10 rounded-2xl p-4 mb-6">
                <div class="flex items-center justify-center space-x-3">
                    <component 
                        :is="playerEloChange > 0 ? ArrowTrendingUpIcon : playerEloChange < 0 ? ArrowTrendingDownIcon : MinusIcon"
                        class="w-6 h-6"
                        :class="{
                            'text-green-400': playerEloChange > 0,
                            'text-red-400': playerEloChange < 0,
                            'text-gray-400': playerEloChange === 0,
                        }"
                    />
                    <span 
                        class="text-2xl font-bold"
                        :class="{
                            'text-green-400': playerEloChange > 0,
                            'text-red-400': playerEloChange < 0,
                            'text-gray-400': playerEloChange === 0,
                        }"
                    >
                        {{ playerEloChange > 0 ? '+' : '' }}{{ playerEloChange }} ELO
                    </span>
                </div>
                <p class="text-center text-purple-300 text-sm mt-1">
                    New rating: {{ (player?.elo_rating || 1000) + playerEloChange }}
                </p>
            </div>
            
            <!-- Rewards -->
            <div v-if="isWinner" class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-yellow-500/20 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-400">+{{ results?.rewards?.xp || 50 }}</p>
                    <p class="text-sm text-yellow-300">XP Earned</p>
                </div>
                <div class="bg-yellow-500/20 rounded-xl p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-400">+{{ results?.rewards?.coins || 25 }}</p>
                    <p class="text-sm text-yellow-300">Coins</p>
                </div>
            </div>
            
            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="w-full py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white text-lg font-bold rounded-2xl hover:from-purple-600 hover:to-purple-700 transition-all"
            >
                Continue
            </button>
        </div>
    </div>
</template>
