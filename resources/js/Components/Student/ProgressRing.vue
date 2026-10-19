<script setup>
/**
 * Legacy student ProgressRing (progress/size/strokeWidth/color). Prefer UI/ProgressRing in new code.
 * `color` is a text-* class for the progress stroke (default brand).
 */
import { computed } from 'vue';

const props = defineProps({
    progress: {
        type: Number,
        default: 0,
    },
    size: {
        type: Number,
        default: 60,
    },
    strokeWidth: {
        type: Number,
        default: 4,
    },
    color: {
        type: String,
        default: 'text-brand',
    },
});

const pct = computed(() => Math.max(0, Math.min(100, props.progress || 0)));
const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value - (pct.value / 100) * circumference.value);
// Old callers passed purple classes; map them onto the brand token
const strokeClass = computed(() => (/purple|indigo/.test(props.color) ? 'text-brand' : props.color));
</script>

<template>
    <div class="relative flex items-center justify-center" :style="{ width: size + 'px', height: size + 'px' }"
        role="progressbar" :aria-valuenow="Math.round(pct)" aria-valuemin="0" aria-valuemax="100">
        <svg class="h-full w-full -rotate-90" :viewBox="`0 0 ${size} ${size}`" aria-hidden="true">
            <circle class="text-surface-2" :stroke-width="strokeWidth" stroke="currentColor" fill="transparent" :r="radius"
                :cx="size / 2" :cy="size / 2" />
            <circle :class="[strokeClass, 'transition-[stroke-dashoffset] duration-700 ease-out']" :stroke-width="strokeWidth"
                :stroke-dasharray="circumference" :stroke-dashoffset="offset" stroke-linecap="round" stroke="currentColor"
                fill="transparent" :r="radius" :cx="size / 2" :cy="size / 2" />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
            <slot>
                <span class="text-xs font-semibold text-fg tabular-nums">{{ Math.round(pct) }}%</span>
            </slot>
        </div>
    </div>
</template>
