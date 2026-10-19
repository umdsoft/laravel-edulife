<script setup>
/**
 * Admin StatsCard — kept for backward compatibility; renders the UI kit StatCard.
 *   <StatsCard title="Foydalanuvchilar" :value="stats.users.total" :trend="12" color="blue" />
 * color: blue | green | purple | yellow (picks icon + tone; `icon` / `tone` override).
 * formatAsCurrency → "12 500 so'm".
 */
import { computed } from 'vue';
import { UsersIcon, AcademicCapIcon, BookOpenIcon, BanknotesIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import StatCard from '@/Components/UI/StatCard.vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    color: {
        type: String,
        default: 'blue',
    },
    trend: [String, Number],
    trendLabel: { type: String, default: null },
    formatAsCurrency: Boolean,
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: null },
    hint: { type: String, default: null },
    href: { type: String, default: null },
    loading: Boolean,
});

const PRESETS = {
    blue: { icon: UsersIcon, tone: 'brand' },
    green: { icon: AcademicCapIcon, tone: 'success' },
    purple: { icon: BookOpenIcon, tone: 'math' },
    yellow: { icon: BanknotesIcon, tone: 'warning' },
};
const preset = computed(() => PRESETS[props.color] || { icon: ChartBarIcon, tone: 'neutral' });

const numericValue = computed(() => {
    const n = Number(props.value);
    return props.value !== null && props.value !== undefined && props.value !== '' && Number.isFinite(n)
        ? (props.formatAsCurrency ? Math.round(n) : n)
        : props.value;
});

const trendNumber = computed(() => {
    if (props.trend === null || props.trend === undefined || props.trend === '') return null;
    const n = Number(props.trend);
    return Number.isFinite(n) ? n : null;
});
</script>

<template>
    <StatCard :label="title || ''" :value="numericValue" :unit="formatAsCurrency ? 'so\'m' : null"
        :icon="icon || preset.icon" :tone="tone || preset.tone" :trend="trendNumber" :trend-label="trendLabel"
        :hint="hint" :href="href" :loading="loading" />
</template>
