<script setup>
/**
 * Legacy student ProgressBar (percentage/color/height/showLabel). Prefer UI/ProgressBar in new code;
 * this keeps the old API working with design tokens.
 */
import { computed } from 'vue';

const props = defineProps({
    percentage: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 100,
    },
    color: {
        type: String,
        default: 'purple', // purple, blue, green, yellow
    },
    height: {
        type: String,
        default: 'h-2',
    },
    showLabel: {
        type: Boolean,
        default: false,
    },
});

const colorClasses = {
    purple: 'bg-brand-600',
    blue: 'bg-info-600',
    green: 'bg-success-600',
    yellow: 'bg-warning-600',
};

const pct = computed(() => Math.max(0, Math.min(100, props.percentage || 0)));
</script>

<template>
    <div class="w-full">
        <div v-if="showLabel" class="mb-1.5 flex justify-between text-13">
            <span class="text-fg-muted">Progress</span>
            <span class="font-medium text-fg tabular-nums">{{ Math.round(pct) }}%</span>
        </div>
        <div class="w-full overflow-hidden rounded-full bg-surface-2" :class="height" role="progressbar"
            :aria-valuenow="Math.round(pct)" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full transition-[width] duration-500 ease-out"
                :class="colorClasses[color] || colorClasses.purple" :style="{ width: `${pct}%` }" />
        </div>
    </div>
</template>
