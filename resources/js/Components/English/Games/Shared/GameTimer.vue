<script setup>
defineProps({
    time: Number,
    isWarning: Boolean,
    formatTime: Function,
})
</script>

<template>
    <div class="px-4 py-2 rounded-full font-mono font-bold transition-all"
        :class="isWarning ? 'bg-red-500 text-white animate-pulse' : 'bg-white/20 text-white'">
        {{ formatTime ? formatTime(time) : `${time}s` }}
    </div>
</template>
