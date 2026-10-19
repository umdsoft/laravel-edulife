<script setup>
/**
 * StatsCard — legacy student stat tile. Kept for backward compatibility (title/value/icon/color);
 * prefer UI/StatCard in new code. `icon` may be a heroicon component; string icons (old emoji API)
 * are ignored in favour of the #icon slot / a neutral chip.
 */
import { computed } from 'vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: [String, Object, Function],
    color: {
        type: String,
        default: 'purple',
    },
});

const tones = {
    purple: 'bg-brand-50 text-brand',
    brand: 'bg-brand-50 text-brand',
    blue: 'bg-info-50 text-info',
    green: 'bg-success-50 text-success',
    yellow: 'bg-warning-50 text-warning',
    orange: 'bg-warning-50 text-warning',
    red: 'bg-danger-50 text-danger',
};

const iconComponent = computed(() => (props.icon && typeof props.icon !== 'string' ? props.icon : ChartBarIcon));
</script>

<template>
    <div class="rounded-lg border border-border bg-surface p-4 shadow-xs sm:p-5">
        <div class="flex items-center gap-3">
            <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-md', tones[color] || tones.purple]">
                <slot name="icon">
                    <component :is="iconComponent" class="h-5 w-5" aria-hidden="true" />
                </slot>
            </span>
            <div class="min-w-0">
                <p class="truncate text-13 font-medium text-fg-muted">{{ title }}</p>
                <p class="text-xl font-semibold tracking-tight text-fg tabular-nums">{{ value }}</p>
            </div>
        </div>
    </div>
</template>
