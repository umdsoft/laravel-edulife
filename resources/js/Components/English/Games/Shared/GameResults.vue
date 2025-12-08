<script setup>
defineProps({
    score: Number,
    correct: Number,
    total: Number,
    accuracy: Number,
    stars: Number,
    timeSpent: Number,
    xpEarned: Number,
    coinsEarned: Number,
})
defineEmits(['playAgain', 'continue'])
</script>

<template>
    <div class="text-center py-8">
        <!-- Stars -->
        <div class="flex justify-center mb-4">
            <span v-for="i in 3" :key="i" class="text-5xl mx-1 transition-all duration-300"
                :class="i <= stars ? 'animate-bounce' : 'opacity-30'"
                :style="{ animationDelay: `${i * 0.1}s` }">
                {{ i <= stars ? '⭐' : '☆' }}
            </span>
        </div>
        
        <!-- Score -->
        <p class="text-4xl font-bold text-white mb-2">{{ score }}</p>
        <p class="text-white/60 mb-6">points</p>
        
        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-2xl font-bold text-green-400">{{ correct }}</p>
                <p class="text-white/60 text-sm">Correct</p>
            </div>
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-2xl font-bold text-white">{{ accuracy }}%</p>
                <p class="text-white/60 text-sm">Accuracy</p>
            </div>
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-2xl font-bold text-blue-400">{{ timeSpent }}s</p>
                <p class="text-white/60 text-sm">Time</p>
            </div>
        </div>
        
        <!-- Rewards -->
        <div class="flex justify-center space-x-6 mb-8">
            <div class="flex items-center">
                <span class="text-2xl mr-2">✨</span>
                <span class="text-xl font-bold text-purple-400">+{{ xpEarned }} XP</span>
            </div>
            <div class="flex items-center">
                <span class="text-2xl mr-2">🪙</span>
                <span class="text-xl font-bold text-yellow-400">+{{ coinsEarned }}</span>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex justify-center space-x-4">
            <button @click="$emit('playAgain')"
                class="px-6 py-3 bg-white/20 rounded-xl font-bold text-white hover:bg-white/30 transition-colors">
                Play Again
            </button>
            <button @click="$emit('continue')"
                class="px-6 py-3 bg-white rounded-xl font-bold text-indigo-600 hover:bg-indigo-50 transition-colors">
                Continue →
            </button>
        </div>
    </div>
</template>
