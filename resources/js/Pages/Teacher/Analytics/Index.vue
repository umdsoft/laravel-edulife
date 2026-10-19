<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { EyeIcon, UsersIcon, CheckBadgeIcon, ClockIcon, ChartBarIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Card from '@/Components/UI/Card.vue';
import Table from '@/Components/UI/Table.vue';
import Button from '@/Components/UI/Button.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import MonthlyBarChart from '@/Components/Teacher/MonthlyBarChart.vue';
import { formatNumber } from '@/Components/Teacher/format';

const props = defineProps({
    stats: Object,
    coursePerformance: Array,
    dailyEnrollments: Array,
    trafficSources: Array,
});

// total_watch_time is in seconds
const watchHours = computed(() => Math.round(((Number(props.stats?.total_watch_time) || 0) / 3600) * 10) / 10);
const daily = computed(() => (props.dailyEnrollments || []).map((d) => ({ month: d.date, value: d.count })));
const dailyTotal = computed(() => daily.value.reduce((s, d) => s + (Number(d.value) || 0), 0));

const columns = [
    { key: 'title', label: 'Kurs' },
    { key: 'enrollments', label: 'O\'quvchilar', numeric: true },
    { key: 'rating', label: 'Reyting', numeric: true, hideBelow: 'sm' },
    { key: 'completion_rate', label: 'Tugatish', width: '180px', hideBelow: 'md' },
    { key: 'active_students', label: 'Faol', numeric: true },
];
</script>

<template>
    <Head title="Statistika" />

    <TeacherLayout>
        <PageHeader title="Statistika" description="Kurslaringiz qanchalik ko'rilayotgani, o'qilayotgani va tugatilayotgani.">
            <template #actions>
                <Button variant="secondary" :icon-left="ArrowDownTrayIcon" :href="route('teacher.analytics.export')" external download>CSV yuklab olish</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Jami ko'rishlar" :value="Number(stats.total_views) || 0" :icon="EyeIcon" tone="info" />
                <StatCard label="Yozilishlar" :value="Number(stats.total_enrollments) || 0" :icon="UsersIcon" tone="brand" />
                <StatCard label="Tugatish ko'rsatkichi" :value="Number(stats.completion_rate) || 0" unit="%" :icon="CheckBadgeIcon" tone="success" />
                <StatCard label="Ko'rish vaqti" :value="watchHours" unit="soat" :icon="ClockIcon" tone="warning" />
            </div>

            <Card title="Kunlik yozilishlar" description="So'nggi 30 kun">
                <template #actions>
                    <span class="text-13 text-fg-subtle">Jami <span class="font-medium tabular-nums text-fg">{{ formatNumber(dailyTotal) }}</span></span>
                </template>
                <MonthlyBarChart :data="daily" unit="ta" label="So'nggi 30 kundagi yozilishlar" height="h-48" />
            </Card>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="Kurslar samaradorligi" description="Faqat nashr qilingan kurslar" padding="none">
                    <Table :columns="columns" :rows="coursePerformance || []" :bordered="false"
                        empty-title="Nashr qilingan kurs yo'q" empty-description="Kurs nashr qilingach, natijalari shu yerda ko'rinadi.">
                        <template #cell-title="{ value }">
                            <span class="font-medium text-fg">{{ value }}</span>
                        </template>
                        <template #cell-enrollments="{ value }">{{ formatNumber(value) }}</template>
                        <template #cell-rating="{ value }">
                            <span class="inline-flex items-center gap-1">
                                <StarIcon class="h-4 w-4 text-warning" aria-hidden="true" />{{ Number(value || 0).toFixed(1) }}
                            </span>
                        </template>
                        <template #cell-completion_rate="{ value }">
                            <div class="flex items-center gap-2">
                                <ProgressBar :value="Number(value) || 0" size="xs" tone="success" class="flex-1" />
                                <span class="w-10 text-right text-13 tabular-nums text-fg-muted">{{ value }}%</span>
                            </div>
                        </template>
                        <template #cell-active_students="{ value }">
                            <span class="font-medium">{{ formatNumber(value) }}</span>
                        </template>
                    </Table>
                </Card>

                <Card title="Trafik manbalari" description="Kurs sahifasi ko'rishlari, so'nggi 30 kun">
                    <ul v-if="trafficSources?.length" class="space-y-4">
                        <li v-for="source in trafficSources" :key="source.key">
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="text-fg">{{ source.source }}</span>
                                <span class="tabular-nums text-fg-muted">{{ formatNumber(source.count) }} · {{ source.percent }}%</span>
                            </div>
                            <ProgressBar :value="Number(source.percent) || 0" size="sm" />
                        </li>
                    </ul>
                    <EmptyState v-else :icon="ChartBarIcon" size="sm" title="Hali ko'rishlar yo'q"
                        description="O'quvchilar kurs sahifangizni ochganda manbalar shu yerda ko'rinadi." />
                </Card>
            </div>
        </div>
    </TeacherLayout>
</template>
