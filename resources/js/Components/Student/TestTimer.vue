<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    expiresAt: {
        type: String,
        required: true
    },
    warningThreshold: {
        type: Number,
        default: 300 // 5 minutes
    },
    dangerThreshold: {
        type: Number,
        default: 60 // 1 minute
    }
});

const emit = defineEmits(['expired']);

const now = ref(new Date());
const timer = ref(null);

const updateTime = () => {
    now.value = new Date();
    if (remainingSeconds.value <= 0) {
        clearInterval(timer.value);
        emit('expired');
    }
};

onMounted(() => {
    timer.value = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    if (timer.value) clearInterval(timer.value);
});

const remainingSeconds = computed(() => {
    const expiry = new Date(props.expiresAt);
    const diff = Math.floor((expiry - now.value) / 1000);
    return Math.max(0, diff);
});

const formattedTime = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60);
    const seconds = remainingSeconds.value % 60;
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const statusColor = computed(() => {
    if (remainingSeconds.value <= props.dangerThreshold) return 'text-red-600 animate-pulse';
    if (remainingSeconds.value <= props.warningThreshold) return 'text-yellow-600';
    return 'text-gray-700 dark:text-gray-300';
});
</script>

<template>
    <div class="flex items-center gap-2 font-mono text-xl font-bold" :class="statusColor">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ formattedTime }}</span>
    </div>
</template>
