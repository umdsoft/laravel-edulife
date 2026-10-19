<script setup>
/**
 * ProgressBar — linear progress.
 *   <ProgressBar :value="72" label="Kurs progressi" show-value />
 *   <ProgressBar :value="3" :max="10" tone="success" size="sm" />
 *   <ProgressBar indeterminate label="Yuklanmoqda" />
 * tone: brand | success | warning | danger | info | math | physics.  size: xs | sm | md | lg
 */
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    tone: { type: String, default: 'brand' },
    size: { type: String, default: 'md' },
    label: { type: String, default: null },
    showValue: Boolean,
    indeterminate: Boolean,
});

const pct = computed(() => {
    const m = props.max > 0 ? props.max : 100;
    return Math.min(100, Math.max(0, (Number(props.value) / m) * 100 || 0));
});
const heights = { xs: 'h-1', sm: 'h-1.5', md: 'h-2', lg: 'h-3' };
const tones = {
    brand: 'bg-brand-600', success: 'bg-success-600', warning: 'bg-warning-600', danger: 'bg-danger-600',
    info: 'bg-info-600', math: 'bg-math-600', physics: 'bg-physics-600',
};
</script>

<template>
    <div class="w-full">
        <div v-if="label || showValue" class="mb-1.5 flex items-center justify-between gap-2 text-13">
            <span v-if="label" class="text-fg-muted">{{ label }}</span>
            <span v-if="showValue && !indeterminate" class="ml-auto font-medium text-fg tabular-nums">{{ Math.round(pct) }}%</span>
        </div>
        <div role="progressbar" :aria-label="label || 'Progress'" :aria-valuemin="0" :aria-valuemax="max"
            :aria-valuenow="indeterminate ? undefined : value"
            :class="['relative w-full overflow-hidden rounded-full bg-surface-2', heights[size] || heights.md]">
            <div v-if="indeterminate"
                :class="['absolute inset-y-0 w-2/5 rounded-full animate-progress-indeterminate', tones[tone] || tones.brand]" />
            <div v-else :class="['h-full rounded-full transition-[width] duration-500 ease-out', tones[tone] || tones.brand]"
                :style="{ width: `${pct}%` }" />
        </div>
    </div>
</template>
