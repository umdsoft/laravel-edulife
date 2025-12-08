<script setup>
defineProps({
    xp: Number,
    coins: Number,
})
</script>

<template>
    <div class="flex justify-center space-x-6">
        <div class="flex items-center px-4 py-2 bg-purple-500/20 rounded-full">
            <span class="text-lg mr-2">✨</span>
            <span class="font-bold text-purple-400">+{{ xp }} XP</span>
        </div>
        <div class="flex items-center px-4 py-2 bg-yellow-500/20 rounded-full">
            <span class="text-lg mr-2">🪙</span>
            <span class="font-bold text-yellow-400">+{{ coins }}</span>
        </div>
    </div>
</template>
