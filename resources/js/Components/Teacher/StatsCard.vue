<script setup>
/**
 * StatsCard — legacy teacher KPI card, now a thin wrapper around the UI kit StatCard.
 * Prefer `@/Components/UI/StatCard.vue` in new code.
 * `icon` takes a heroicon component (old emoji strings are ignored).
 */
import { computed } from 'vue';
import StatCard from '@/Components/UI/StatCard.vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: { type: [Object, Function, String], default: null },
    trend: String,
    trendUp: Boolean,
    description: String,
    tone: { type: String, default: 'brand' },
    href: { type: String, default: null },
});

const iconComponent = computed(() => (typeof props.icon === 'string' ? null : props.icon));
const hint = computed(() => [props.trend, props.description].filter(Boolean).join(' · ') || null);
</script>

<template>
    <StatCard :label="title || ''" :value="value" :icon="iconComponent" :tone="tone" :hint="hint" :href="href" />
</template>
