<script setup>
defineProps({
    current: Number,
    total: Number,
    progress: Number,
})
</script>

<template>
    <div class="mb-6">
        <div class="flex justify-between text-sm text-white/80 mb-2">
            <span>{{ current }} / {{ total }}</span>
            <span>{{ progress }}%</span>
        </div>
        <div class="h-2 bg-white/20 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-300"
                :style="{ width: `${progress}%` }"/>
        </div>
    </div>
</template>
