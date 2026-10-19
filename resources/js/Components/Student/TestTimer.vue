<script setup>
/**
 * Countdown pill for an attempt. API unchanged: expiresAt, warningThreshold, dangerThreshold, emits expired.
 * Calm by default, amber under the warning threshold, red under the danger threshold.
 * Screen readers get one announcement when each threshold is crossed (not every second).
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { ClockIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    expiresAt: {
        type: String,
        required: true,
    },
    warningThreshold: {
        type: Number,
        default: 300, // 5 minutes
    },
    dangerThreshold: {
        type: Number,
        default: 60, // 1 minute
    },
});

const emit = defineEmits(['expired']);

const now = ref(new Date());
const timer = ref(null);
const announcement = ref('');

const remainingSeconds = computed(() => {
    const expiry = new Date(props.expiresAt);
    const diff = Math.floor((expiry - now.value) / 1000);
    return Math.max(0, diff);
});

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

const formattedTime = computed(() => {
    const total = remainingSeconds.value;
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const mm = m.toString().padStart(2, '0');
    const ss = s.toString().padStart(2, '0');
    return h > 0 ? `${h}:${mm}:${ss}` : `${mm}:${ss}`;
});

const state = computed(() => {
    if (remainingSeconds.value <= props.dangerThreshold) return 'danger';
    if (remainingSeconds.value <= props.warningThreshold) return 'warning';
    return 'normal';
});

const stateClass = computed(() => ({
    normal: 'border-border bg-surface text-fg',
    warning: 'border-warning-600/40 bg-warning-50 text-warning',
    danger: 'border-danger-600/50 bg-danger-50 text-danger',
}[state.value]));

watch(state, (s, prev) => {
    if (s === prev) return;
    if (s === 'warning') announcement.value = `Diqqat: ${Math.ceil(remainingSeconds.value / 60)} daqiqa qoldi`;
    if (s === 'danger') announcement.value = 'Diqqat: 1 daqiqadan kam vaqt qoldi';
});
</script>

<template>
    <div role="timer" :aria-label="`Qolgan vaqt: ${formattedTime}`"
        :class="['inline-flex h-9 items-center gap-1.5 rounded-full border px-3 text-sm font-semibold tabular-nums transition-colors duration-150', stateClass]">
        <ClockIcon :class="['h-4 w-4', state === 'danger' ? 'animate-pulse motion-reduce:animate-none' : '', state === 'normal' ? 'text-fg-subtle' : '']"
            aria-hidden="true" />
        <span aria-hidden="true">{{ formattedTime }}</span>
        <span class="sr-only" aria-live="assertive">{{ announcement }}</span>
    </div>
</template>
