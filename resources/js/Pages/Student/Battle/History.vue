<script setup>
import { Head } from '@inertiajs/vue3';
import { BoltIcon, ChartBarIcon, TrophyIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Button, PageHeader, Pagination, StatCard, Table } from '@/Components/UI';

defineProps({
    battles: Object, // paginator of { id, opponent, direction, my_score, opponent_score, result, elo_change, ended_at }
    stats: { type: Object, default: () => ({}) },
});

const RESULT = {
    win: { label: "G'alaba", variant: 'success' },
    loss: { label: "Mag'lubiyat", variant: 'danger' },
    draw: { label: 'Durang', variant: 'neutral' },
};

const pad = (n) => String(n).padStart(2, '0');
const date = (v) => {
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? '—' : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};

const columns = [
    { key: 'opponent', label: 'Raqib' },
    { key: 'score', label: 'Hisob', numeric: true },
    { key: 'result', label: 'Natija' },
    { key: 'elo_change', label: 'ELO', numeric: true, hideBelow: 'sm' },
    { key: 'ended_at', label: 'Sana', hideBelow: 'md' },
];
</script>

<template>
    <StudentLayout title="Janglar tarixi">
        <Head title="Janglar tarixi" />

        <PageHeader title="Janglar tarixi"
            :breadcrumbs="[{ label: 'Janglar', href: route('student.battles.index') }, { label: 'Tarix' }]">
            <template #actions>
                <Button :icon-left="BoltIcon" :href="route('student.battles.index')">Yangi jang</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="ELO reyting" :value="stats.elo_rating" :icon="ChartBarIcon" tone="brand" />
                <StatCard label="G'alabalar" :value="stats.battles_won" :icon="TrophyIcon" tone="success" />
                <StatCard label="Mag'lubiyatlar" :value="stats.battles_lost" :icon="XCircleIcon" tone="danger" />
                <StatCard label="G'alaba ulushi" :value="stats.win_rate" unit="%" :icon="BoltIcon" tone="info" />
            </div>

            <Table :columns="columns" :rows="battles?.data || []" caption="O'tkazilgan janglar"
                empty-title="Hali jang o'tkazmagansiz" empty-description="Raqib toping va bilimingizni sinang.">
                <template #cell-opponent="{ row }">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar :src="row.opponent?.avatar" :name="row.opponent?.name || 'Raqib'" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-fg">{{ row.opponent?.name || 'Raqib' }}</p>
                            <p class="truncate text-13 text-fg-subtle">{{ row.direction || 'Aralash' }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-score="{ row }"><span class="font-semibold">{{ row.my_score }} : {{ row.opponent_score }}</span></template>
                <template #cell-result="{ value }"><Badge :variant="(RESULT[value] || RESULT.draw).variant" dot>{{ (RESULT[value] || RESULT.draw).label }}</Badge></template>
                <template #cell-elo_change="{ value }">
                    <span :class="value >= 0 ? 'text-success' : 'text-danger'">{{ value >= 0 ? '+' : '' }}{{ value }}</span>
                </template>
                <template #cell-ended_at="{ value }"><span class="tabular-nums text-fg-muted">{{ date(value) }}</span></template>
                <template #footer><Pagination :data="battles" /></template>
            </Table>
        </div>
    </StudentLayout>
</template>
