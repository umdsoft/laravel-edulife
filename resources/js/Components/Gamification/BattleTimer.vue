<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    duration: {
        type: Number,
        default: 10
    },
    active: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['timeout']);

const timeLeft = ref(props.duration);
const timerInterval = ref(null);

const startTimer = () => {
    timeLeft.value = props.duration;
    clearInterval(timerInterval.value);

    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            clearInterval(timerInterval.value);
            emit('timeout');
        }
    }, 1000);
};

watch(() => props.active, (newVal) => {
    if (newVal) {
        startTimer();
    } else {
        clearInterval(timerInterval.value);
    }
});

onMounted(() => {
    if (props.active) {
        startTimer();
    }
});

onUnmounted(() => {
    clearInterval(timerInterval.value);
});
</script>

<template>
    <div class="relative w-16 h-16 flex items-center justify-center">
        <svg class="w-full h-full transform -rotate-90">
            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-200" />
            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none"
                class="text-purple-600 transition-all duration-1000 ease-linear" :stroke-dasharray="175.93"
                :stroke-dashoffset="175.93 * (1 - timeLeft / duration)" />
        </svg>
        <span class="absolute text-xl font-bold text-gray-900">{{ timeLeft }}</span>
    </div>
</template>
