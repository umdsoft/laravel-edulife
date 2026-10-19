<template>
    <AdminLayout>

        <Head title="Hisobotlar" />

        <PageHeader title="Hisobotlar" description="Daromad, foydalanuvchilar va kurslar bo'yicha tahlil">
            <template #actions>
                <div role="group" aria-label="Davr" class="inline-flex rounded-md border border-border-strong bg-surface p-0.5 shadow-xs">
                    <button v-for="opt in periodOptions" :key="opt.value" type="button" :aria-pressed="period === opt.value"
                        :class="[
                            'focus-ring h-9 rounded-[8px] px-3 text-13 font-medium transition-colors duration-150',
                            period === opt.value ? 'bg-brand-50 text-brand' : 'text-fg-muted hover:bg-surface-2 hover:text-fg',
                        ]" @click="setPeriod(opt.value)">
                        {{ opt.label }}
                    </button>
                </div>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <section aria-label="Asosiy ko'rsatkichlar" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Jami daromad" :value="money(stats.total_revenue)" unit="so'm" :icon="BanknotesIcon"
                    tone="brand" :loading="loading" />
                <StatCard label="Davr daromadi" :value="money(stats.period_revenue)" unit="so'm"
                    :icon="ArrowTrendingUpIcon" tone="success" :hint="periodLabel" :loading="loading" />
                <StatCard label="Yangi foydalanuvchilar" :value="num(stats.new_users)" :icon="UserPlusIcon" tone="info"
                    :hint="`Jami: ${formatNumber(stats.total_users)}`" :loading="loading" />
                <StatCard label="Faol obunalar" :value="num(stats.active_subscriptions)" :icon="ArrowPathRoundedSquareIcon"
                    tone="warning" :loading="loading" />
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card title="Daromad dinamikasi" :description="`${periodLabel}, so'm`">
                    <div v-if="hasData(charts.revenue)" class="h-72">
                        <Line :key="`rev-${themeKey}`" :data="revenueChartData" :options="revenueOptions"
                            aria-label="Daromad dinamikasi grafigi" role="img" />
                    </div>
                    <EmptyState v-else :icon="ChartBarIcon" title="Ma'lumot yo'q"
                        description="Bu davrda to'lovlar bo'lmagan" size="sm" />
                    <table v-if="hasData(charts.revenue)" class="sr-only">
                        <caption>Daromad dinamikasi</caption>
                        <tbody><tr v-for="(label, i) in charts.revenue.labels" :key="i">
                            <th scope="row">{{ label }}</th>
                            <td>{{ formatMoney(charts.revenue.data[i]) }}</td>
                        </tr></tbody>
                    </table>
                </Card>

                <Card title="Foydalanuvchilar o'sishi" :description="`${periodLabel}, yangi ro'yxatdan o'tganlar`">
                    <div v-if="hasData(charts.users)" class="h-72">
                        <Bar :key="`usr-${themeKey}`" :data="usersChartData" :options="usersOptions"
                            aria-label="Foydalanuvchilar o'sishi grafigi" role="img" />
                    </div>
                    <EmptyState v-else :icon="ChartBarIcon" title="Ma'lumot yo'q"
                        description="Bu davrda yangi foydalanuvchilar bo'lmagan" size="sm" />
                    <table v-if="hasData(charts.users)" class="sr-only">
                        <caption>Foydalanuvchilar o'sishi</caption>
                        <tbody><tr v-for="(label, i) in charts.users.labels" :key="i">
                            <th scope="row">{{ label }}</th>
                            <td>{{ charts.users.data[i] }}</td>
                        </tr></tbody>
                    </table>
                </Card>
            </div>

            <Card title="Kurslar yo'nalishlar bo'yicha" description="Har bir yo'nalishdagi kurslar soni">
                <ul v-if="courseRows.length" class="space-y-4">
                    <li v-for="row in courseRows" :key="row.label" class="grid grid-cols-[minmax(0,10rem)_1fr_3rem] items-center gap-4 sm:grid-cols-[minmax(0,14rem)_1fr_3rem]">
                        <span class="truncate text-sm text-fg">{{ row.label }}</span>
                        <ProgressBar :value="row.value" :max="courseMax" size="md" />
                        <span class="text-right text-sm font-medium text-fg tabular-nums">{{ formatNumber(row.value) }}</span>
                    </li>
                </ul>
                <EmptyState v-else :icon="BookOpenIcon" title="Kurslar yo'q" size="sm" />
            </Card>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowPathRoundedSquareIcon, ArrowTrendingUpIcon, BanknotesIcon, BookOpenIcon, ChartBarIcon, UserPlusIcon,
} from '@heroicons/vue/24/outline';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Filler,
    Tooltip,
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Card, EmptyState, PageHeader, ProgressBar, StatCard } from '@/Components/UI';
import { formatMoney, formatNumber } from '@/Components/Admin/format';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Filler, Tooltip);

const props = defineProps({
    stats: Object,
    charts: Object,
    filters: Object,
});

const period = ref(props.filters?.period || 'month');
const loading = ref(false);

const periodOptions = [
    { value: 'week', label: '7 kun' },
    { value: 'month', label: '30 kun' },
    { value: 'year', label: 'Yil' },
];
const PERIOD_LABELS = { week: 'So\'nggi 7 kun', month: 'So\'nggi 30 kun', year: 'So\'nggi 12 oy' };
const periodLabel = computed(() => PERIOD_LABELS[period.value] || PERIOD_LABELS.month);

const setPeriod = (value) => {
    if (value === period.value) return;
    period.value = value;
    router.get('/admin/reports', { period: period.value }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
    });
};

const num = (v) => Number(v) || 0;
const money = (v) => Math.round(Number(v) || 0);
const hasData = (series) => !!(series?.labels?.length && series?.data?.some((v) => Number(v) > 0));

// --- Theme-aware chart colours (read from CSS tokens; re-read when dark mode toggles) ---
const themeKey = ref(0);
const cssVar = (name, alpha = 1) => {
    if (typeof window === 'undefined') return 'rgb(0 0 0)';
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim() || '0 0 0';
    return alpha === 1 ? `rgb(${v})` : `rgb(${v} / ${alpha})`;
};
let observer = null;
onMounted(() => {
    observer = new MutationObserver(() => { themeKey.value += 1; });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onBeforeUnmount(() => observer?.disconnect());

const compact = (v) => {
    const n = Number(v) || 0;
    if (Math.abs(n) >= 1e9) return `${+(n / 1e9).toFixed(1)} mlrd`;
    if (Math.abs(n) >= 1e6) return `${+(n / 1e6).toFixed(1)} mln`;
    if (Math.abs(n) >= 1e3) return `${+(n / 1e3).toFixed(1)} ming`;
    return String(n);
};

const baseOptions = (formatValue) => {
    // eslint-disable-next-line no-unused-expressions
    themeKey.value;
    const grid = cssVar('--border');
    const tick = cssVar('--fg-subtle');
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: cssVar('--surface'),
                titleColor: cssVar('--fg'),
                bodyColor: cssVar('--fg-muted'),
                borderColor: cssVar('--border'),
                borderWidth: 1,
                padding: 10,
                displayColors: false,
                callbacks: { label: (ctx) => formatValue(ctx.parsed.y) },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: grid },
                ticks: { color: tick, font: { size: 12 }, callback: (v) => compact(v), maxTicksLimit: 5 },
            },
            x: {
                border: { color: grid },
                grid: { display: false },
                ticks: { color: tick, font: { size: 12 }, maxRotation: 0, autoSkipPadding: 12 },
            },
        },
    };
};

const revenueOptions = computed(() => baseOptions((v) => formatMoney(v)));
const usersOptions = computed(() => baseOptions((v) => `${formatNumber(v)} ta`));

const revenueChartData = computed(() => {
    // eslint-disable-next-line no-unused-expressions
    themeKey.value;
    return {
        labels: props.charts?.revenue?.labels || [],
        datasets: [
            {
                label: 'Daromad (so\'m)',
                data: props.charts?.revenue?.data || [],
                borderColor: cssVar('--brand-600'),
                backgroundColor: cssVar('--brand-600', 0.08),
                fill: true,
                borderWidth: 2,
                tension: 0.35,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointHitRadius: 12,
                pointBackgroundColor: cssVar('--brand-600'),
                pointBorderColor: cssVar('--surface'),
                pointBorderWidth: 2,
            },
        ],
    };
});

const usersChartData = computed(() => {
    // eslint-disable-next-line no-unused-expressions
    themeKey.value;
    return {
        labels: props.charts?.users?.labels || [],
        datasets: [
            {
                label: 'Yangi foydalanuvchilar',
                data: props.charts?.users?.data || [],
                backgroundColor: cssVar('--brand-600'),
                hoverBackgroundColor: cssVar('--brand-700'),
                borderRadius: 4,
                borderSkipped: 'bottom',
                maxBarThickness: 28,
            },
        ],
    };
});

const courseRows = computed(() => {
    const labels = props.charts?.courses?.labels || [];
    const data = props.charts?.courses?.data || [];
    return labels
        .map((label, i) => ({ label, value: Number(data[i]) || 0 }))
        .filter((r) => r.value > 0)
        .sort((a, b) => b.value - a.value);
});
const courseMax = computed(() => Math.max(1, ...courseRows.value.map((r) => r.value)));
</script>
