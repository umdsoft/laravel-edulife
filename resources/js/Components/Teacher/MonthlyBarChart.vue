<script setup>
/**
 * MonthlyBarChart — single-series bar chart (HTML/CSS, no chart lib).
 *   <MonthlyBarChart :data="[{ month: 'Jan', value: 12 }, …]" unit="ta" label="Kursga yozilishlar" />
 * Hovering a bar shows its value; a visually hidden table mirrors the data.
 */
import { computed } from 'vue';
import { ChartBarIcon } from '@heroicons/vue/24/outline';
import { axisLabelIndexes, formatNumber } from './format';

const props = defineProps({
    // [{ month: 'Jan', value: 12 }, ...]
    data: { type: Array, default: () => [] },
    unit: { type: String, default: '' },
    label: { type: String, default: 'Diagramma' },
    height: { type: String, default: 'h-56' },
});

const rows = computed(() => (props.data || []).map((d) => ({ label: String(d.month ?? ''), value: Number(d.value) || 0 })));
const maxValue = computed(() => Math.max(0, ...rows.value.map((r) => r.value)));
const total = computed(() => rows.value.reduce((s, r) => s + r.value, 0));

// Nice rounded top of the scale (1, 2, 5 × 10^n) so gridlines land on round numbers
const scaleMax = computed(() => {
    const m = maxValue.value;
    if (m <= 0) return 4;
    const pow = 10 ** Math.floor(Math.log10(m));
    const step = [1, 2, 2.5, 5, 10].find((s) => s * pow >= m) * pow;
    return Math.max(step, 4);
});
const ticks = computed(() => [1, 0.5, 0].map((f) => Math.round(scaleMax.value * f)));

const heightOf = (v) => (v <= 0 ? '0%' : `${Math.max((v / scaleMax.value) * 100, 1.5)}%`);
const labelled = computed(() => axisLabelIndexes(rows.value.length));
const showLabel = (i) => labelled.value.has(i);
const fmt = (v) => `${formatNumber(v)}${props.unit ? ` ${props.unit}` : ''}`;
</script>

<template>
    <figure class="relative" :aria-label="label">
        <div class="flex gap-3">
            <!-- Y axis -->
            <div :class="['flex w-8 shrink-0 flex-col justify-between text-right text-xs tabular-nums text-fg-subtle', height]"
                aria-hidden="true">
                <span v-for="t in ticks" :key="t" class="-translate-y-1/2 leading-none last:translate-y-0">{{ formatNumber(t) }}</span>
            </div>

            <div class="min-w-0 flex-1">
                <!-- Plot -->
                <div :class="['relative', height]">
                    <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                        <div v-for="t in ticks" :key="t" :class="['border-t', t === 0 ? 'border-border-strong' : 'border-dashed border-border']" />
                    </div>

                    <div class="absolute inset-0 flex items-end gap-0.5 sm:gap-1" aria-hidden="true">
                        <div v-for="(row, i) in rows" :key="i"
                            class="group relative flex h-full flex-1 items-end justify-center">
                            <div class="w-full max-w-[28px] rounded-t-[4px] bg-brand-600 transition-colors duration-150 group-hover:bg-brand-700"
                                :style="{ height: heightOf(row.value) }" />
                            <div
                                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded-sm bg-fg px-2 py-1 text-xs font-medium text-surface opacity-0 shadow-md transition-opacity duration-150 group-hover:opacity-100"
                                :style="{ bottom: heightOf(row.value) }">
                                <span class="text-surface/70">{{ row.label }}:</span> <span class="tabular-nums">{{ fmt(row.value) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="total === 0" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center">
                        <ChartBarIcon class="h-6 w-6 text-fg-subtle" aria-hidden="true" />
                        <p class="text-13 text-fg-subtle">Bu davr uchun ma'lumot yo'q</p>
                    </div>
                </div>

                <!-- X axis -->
                <div class="mt-2 flex gap-0.5 sm:gap-1" aria-hidden="true">
                    <!-- labels overflow their (narrow) column instead of being cut to "20..." -->
                    <span v-for="(row, i) in rows" :key="i" class="relative h-4 flex-1 text-xs text-fg-subtle">
                        <span v-if="showLabel(i)" :class="['absolute top-0 whitespace-nowrap tabular-nums',
                            i === rows.length - 1 && rows.length > 12 ? 'right-0' : 'left-1/2 -translate-x-1/2']">{{ row.label }}</span>
                    </span>
                </div>
            </div>
        </div>

        <table class="sr-only">
            <caption>{{ label }}</caption>
            <thead><tr><th scope="col">Davr</th><th scope="col">Qiymat</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows" :key="i"><td>{{ row.label }}</td><td>{{ fmt(row.value) }}</td></tr>
            </tbody>
        </table>
    </figure>
</template>
