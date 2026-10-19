<script setup>
/**
 * ProgressRing — circular progress (scores, completion).
 *   <ProgressRing :value="84" :size="64" tone="success" />
 *   <ProgressRing :value="7" :max="10" :size="96" :stroke="8">7/10</ProgressRing>   (slot = centre content)
 */
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    size: { type: Number, default: 48 },
    stroke: { type: Number, default: 5 },
    tone: { type: String, default: 'brand' },
    showValue: { type: Boolean, default: true },
    label: { type: String, default: null },
});

const pct = computed(() => {
    const m = props.max > 0 ? props.max : 100;
    return Math.min(100, Math.max(0, (Number(props.value) / m) * 100 || 0));
});
const r = computed(() => (props.size - props.stroke) / 2);
const c = computed(() => 2 * Math.PI * r.value);
const offset = computed(() => c.value * (1 - pct.value / 100));
const tones = {
    brand: 'text-brand-600', success: 'text-success-600', warning: 'text-warning-600', danger: 'text-danger-600',
    info: 'text-info-600', math: 'text-math-600', physics: 'text-physics-600',
};
const fontSize = computed(() => (props.size >= 88 ? 'text-xl' : props.size >= 64 ? 'text-base' : 'text-xs'));
</script>

<template>
    <div class="relative inline-flex shrink-0 items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }"
        role="progressbar" :aria-label="label || 'Progress'" :aria-valuemin="0" :aria-valuemax="max" :aria-valuenow="value">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" class="-rotate-90" aria-hidden="true">
            <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke-width="stroke" class="stroke-surface-2" />
            <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke-width="stroke" stroke-linecap="round"
                stroke="currentColor" :stroke-dasharray="c" :stroke-dashoffset="offset"
                :class="['transition-[stroke-dashoffset] duration-700 ease-out', tones[tone] || tones.brand]" />
        </svg>
        <span v-if="showValue || $slots.default"
            :class="['absolute inset-0 flex items-center justify-center font-semibold text-fg tabular-nums', fontSize]">
            <slot>{{ Math.round(pct) }}%</slot>
        </span>
    </div>
</template>
