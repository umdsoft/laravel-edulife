<script setup>
/**
 * GrowthChart — weekly exam percent over time, one line per subject.
 *   <GrowthChart :history="[{ subject: 'math', week_start: '2026-10-19', label: '19.10', percent: 62.5 }]" />
 * Colours come from the CSS tokens (math = indigo, physics = teal) and follow dark mode.
 * An sr-only table repeats the data for screen readers.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend);

const props = defineProps({
    history: { type: Array, default: () => [] },
    names: { type: Object, default: () => ({ math: 'Matematika', physics: 'Fizika' }) },
    height: { type: String, default: 'h-64' },
});

const themeKey = ref(0);
let observer = null;
onMounted(() => {
    observer = new MutationObserver(() => { themeKey.value += 1; });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onBeforeUnmount(() => observer?.disconnect());

const cssVar = (name, alpha = 1) => {
    if (typeof window === 'undefined') return 'rgb(0 0 0)';
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim() || '0 0 0';
    return alpha === 1 ? `rgb(${v})` : `rgb(${v} / ${alpha})`;
};

const weeks = computed(() => [...new Set(props.history.map((r) => r.week_start))].sort());
const subjects = computed(() => [...new Set(props.history.map((r) => r.subject))]);
const label = (iso) => {
    const [, m, d] = String(iso).split('-');
    return `${d}.${m}`;
};

const data = computed(() => {
    // eslint-disable-next-line no-unused-expressions
    themeKey.value;
    return {
        labels: weeks.value.map(label),
        datasets: subjects.value.map((code) => {
            const token = code === 'physics' ? '--physics-600' : '--math-600';
            const byWeek = Object.fromEntries(props.history.filter((r) => r.subject === code).map((r) => [r.week_start, r.percent]));
            return {
                label: props.names[code] || code,
                data: weeks.value.map((w) => (w in byWeek ? byWeek[w] : null)),
                borderColor: cssVar(token),
                backgroundColor: cssVar(token, 0.12),
                pointBackgroundColor: cssVar(token),
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2,
                tension: 0.3,
                spanGaps: true,
                fill: subjects.value.length === 1,
            };
        }),
    };
});

const options = computed(() => {
    // eslint-disable-next-line no-unused-expressions
    themeKey.value;
    const grid = cssVar('--border');
    const tick = cssVar('--fg-subtle');
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: subjects.value.length > 1,
                position: 'bottom',
                labels: { color: tick, usePointStyle: true, boxWidth: 8, font: { size: 12 } },
            },
            tooltip: {
                backgroundColor: cssVar('--surface'),
                titleColor: cssVar('--fg'),
                bodyColor: cssVar('--fg-muted'),
                borderColor: cssVar('--border'),
                borderWidth: 1,
                padding: 10,
                callbacks: { label: (ctx) => `${ctx.dataset.label}: ${String(ctx.parsed.y).replace('.', ',')}%` },
            },
        },
        scales: {
            y: {
                min: 0,
                max: 100,
                border: { display: false },
                grid: { color: grid },
                ticks: { color: tick, font: { size: 12 }, stepSize: 25, callback: (v) => `${v}%` },
            },
            x: {
                border: { color: grid },
                grid: { display: false },
                ticks: { color: tick, font: { size: 12 }, maxRotation: 0, autoSkipPadding: 12 },
            },
        },
    };
});
</script>

<template>
    <div>
        <div :class="height">
            <Line :key="`growth-${themeKey}`" :data="data" :options="options" aria-hidden="true" />
        </div>
        <table class="sr-only">
            <caption>Haftalik natijalar, foizda</caption>
            <thead><tr><th scope="col">Hafta</th><th v-for="s in subjects" :key="s" scope="col">{{ names[s] || s }}</th></tr></thead>
            <tbody>
                <tr v-for="w in weeks" :key="w">
                    <th scope="row">{{ label(w) }}</th>
                    <td v-for="s in subjects" :key="s">
                        {{ history.find((r) => r.subject === s && r.week_start === w)?.percent ?? '—' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
