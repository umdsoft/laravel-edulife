<script setup>
/**
 * StatCard — one KPI.
 *   <StatCard label="O'quvchilar" :value="stats.total_students" :icon="UsersIcon" tone="brand"
 *             :trend="12.5" trend-label="o'tgan oyga nisbatan" hint="Faol: 120" :href="route('teacher.students.index')" />
 * value: number (formatted with spaces, tabular) or string.  trend: number (%), sign decides colour.
 * tone (icon chip): neutral | brand | success | warning | danger | info | math | physics
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: null },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'brand' },
    hint: { type: String, default: null },
    trend: { type: Number, default: null },
    trendLabel: { type: String, default: null },
    // suffix after the value: "%", "so'm", "XP"
    unit: { type: String, default: null },
    href: { type: String, default: null },
    loading: Boolean,
});

const tones = {
    neutral: 'bg-surface-2 text-fg-muted',
    brand: 'bg-brand-50 text-brand',
    success: 'bg-success-50 text-success',
    warning: 'bg-warning-50 text-warning',
    danger: 'bg-danger-50 text-danger',
    info: 'bg-info-50 text-info',
    math: 'bg-math-50 text-math',
    physics: 'bg-physics-50 text-physics',
};

const display = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') return '—';
    if (typeof props.value === 'number') return new Intl.NumberFormat('ru-RU').format(props.value).replace(/ /g, ' ');
    return props.value;
});
const up = computed(() => props.trend !== null && props.trend >= 0);
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href || undefined" :class="[
        'block rounded-lg border border-border bg-surface p-5 shadow-xs',
        href ? 'focus-ring transition-shadow duration-150 hover:border-border-strong hover:shadow-md' : '',
    ]">
        <div class="flex items-start justify-between gap-3">
            <p class="text-13 font-medium text-fg-muted">{{ label }}</p>
            <span v-if="icon" :class="['inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md', tones[tone] || tones.brand]">
                <component :is="icon" class="h-5 w-5" aria-hidden="true" />
            </span>
        </div>

        <div v-if="loading" class="mt-2 h-8 w-24 animate-pulse rounded bg-surface-2 motion-reduce:animate-none" aria-hidden="true" />
        <p v-else class="mt-1 flex items-baseline gap-1.5 text-2xl font-semibold tracking-tight text-fg tabular-nums">
            {{ display }}
            <span v-if="unit" class="text-sm font-medium text-fg-subtle">{{ unit }}</span>
        </p>

        <div v-if="trend !== null || hint || $slots.default" class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-13">
            <span v-if="trend !== null" :class="['inline-flex items-center gap-0.5 font-medium tabular-nums', up ? 'text-success' : 'text-danger']">
                <ArrowTrendingUpIcon v-if="up" class="h-4 w-4" aria-hidden="true" />
                <ArrowTrendingDownIcon v-else class="h-4 w-4" aria-hidden="true" />
                {{ up ? '+' : '' }}{{ trend }}%
            </span>
            <span v-if="trendLabel" class="text-fg-subtle">{{ trendLabel }}</span>
            <span v-if="hint" class="text-fg-subtle">{{ hint }}</span>
            <slot />
        </div>
    </component>
</template>
