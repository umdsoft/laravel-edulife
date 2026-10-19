<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

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

// Presentation
const SIZE = 64;
const STROKE = 4;
const radius = (SIZE - STROKE) / 2;
const circumference = 2 * Math.PI * radius;
const ratio = computed(() => (props.duration > 0 ? Math.max(0, Math.min(1, timeLeft.value / props.duration)) : 0));
const offset = computed(() => circumference * (1 - ratio.value));
const tone = computed(() => {
    if (timeLeft.value <= 3) return { ring: 'text-danger-600', text: 'text-danger' };
    if (timeLeft.value <= 5) return { ring: 'text-warning-600', text: 'text-warning' };
    return { ring: 'text-brand-600', text: 'text-fg' };
});
</script>

<template>
    <div class="relative flex h-16 w-16 items-center justify-center" role="timer"
        :aria-label="`Qolgan vaqt: ${timeLeft} soniya`">
        <svg class="h-full w-full -rotate-90" :viewBox="`0 0 ${SIZE} ${SIZE}`" aria-hidden="true">
            <circle :cx="SIZE / 2" :cy="SIZE / 2" :r="radius" stroke="currentColor" :stroke-width="STROKE" fill="none"
                class="text-surface-3" />
            <circle :cx="SIZE / 2" :cy="SIZE / 2" :r="radius" stroke="currentColor" :stroke-width="STROKE" fill="none"
                stroke-linecap="round"
                :class="['transition-[stroke-dashoffset] duration-1000 ease-linear motion-reduce:transition-none', tone.ring]"
                :stroke-dasharray="circumference" :stroke-dashoffset="offset" />
        </svg>
        <span :class="['absolute text-xl font-semibold tabular-nums', tone.text]" aria-hidden="true">{{ timeLeft }}</span>
    </div>
</template>
