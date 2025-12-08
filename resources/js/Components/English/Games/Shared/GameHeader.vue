<script setup>
defineProps({
    title: String,
    currentIndex: Number,
    totalQuestions: Number,
    score: Number,
    streak: { type: Number, default: 0 },
})
defineEmits(['exit'])
</script>

<template>
    <div class="flex items-center justify-between mb-4">
        <button @click="$emit('exit')" class="p-2 rounded-full bg-white/20 hover:bg-white/30 transition-colors">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        
        <div class="flex items-center space-x-4">
            <!-- Streak -->
            <div v-if="streak >= 3" class="flex items-center px-3 py-1 bg-amber-500 rounded-full shadow-lg shadow-amber-500/30">
                <span class="text-lg">🔥</span>
                <span class="ml-1 font-bold text-white">{{ streak }}</span>
            </div>
            
            <!-- Score -->
            <div class="px-4 py-2 bg-white rounded-full shadow-md">
                <span class="font-bold text-gray-900">{{ score }}</span>
            </div>
        </div>
    </div>
</template>
