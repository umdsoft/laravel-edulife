<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, StatCard, Table } from '@/Components/UI';
import { ArrowDownTrayIcon, ChartBarIcon, StarIcon, TrophyIcon, UsersIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    olympiad: Object,
    leaderboard: { type: Array, default: () => [] },
    statistics: { type: Object, default: () => ({}) },
});

const breadcrumbs = computed(() => [
    { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
    { label: props.olympiad.title, href: route('admin.olympiads.show', props.olympiad.id) },
    { label: 'Monitoring', href: route('admin.olympiads.monitor', props.olympiad.id) },
    { label: "To'liq reyting" },
]);

const columns = [
    { key: 'rank', label: "O'rin", width: '1%' },
    { key: 'user_name', label: 'Ishtirokchi' },
    { key: 'score', label: 'Ball', numeric: true },
    { key: 'score_percent', label: 'Foiz', numeric: true },
    { key: 'answers', label: "To'g'ri / javob", numeric: true, hideBelow: 'sm' },
    { key: 'time_spent', label: 'Vaqt', numeric: true, hideBelow: 'md' },
];

const num = (v) => (v === null || v === undefined || v === '' ? '—' : v);
const participantHref = (attemptId) => route('admin.olympiads.monitor.participant', { olympiad: props.olympiad.id, attempt: attemptId });
</script>

<template>
    <AdminLayout>
        <Head :title="`To'liq reyting — ${olympiad.title}`" />

        <PageHeader title="To'liq reyting" :description="olympiad.title" :breadcrumbs="breadcrumbs">
            <template #actions>
                <Button variant="secondary" :icon-left="ArrowDownTrayIcon" :href="route('admin.olympiads.grading.export', olympiad.id)" external>
                    CSV yuklab olish
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <StatCard label="Ishtirokchilar" :value="num(statistics.total_participants)" :icon="UsersIcon" tone="brand" />
                <StatCard label="O'rtacha natija" :value="num(statistics.average_score)" unit="%" :icon="ChartBarIcon" tone="info" />
                <StatCard label="Eng yuqori ball" :value="num(statistics.highest_score)" :icon="StarIcon" tone="success"
                    class="col-span-2 lg:col-span-1" />
            </div>

            <Card padding="none">
                <Table :columns="columns" :rows="leaderboard" row-key="id">
                    <template #cell-rank="{ row }">
                        <span v-if="row.rank" :class="[
                            'inline-flex h-7 min-w-7 items-center justify-center rounded-full px-1.5 text-xs font-semibold tabular-nums',
                            row.rank <= 3 ? 'bg-brand-50 text-brand' : 'bg-surface-2 text-fg-muted',
                        ]">{{ row.rank }}</span>
                        <span v-else class="text-fg-subtle">—</span>
                    </template>
                    <template #cell-user_name="{ row }">
                        <span class="flex flex-wrap items-center gap-2">
                            <Link v-if="row.attempt_id" :href="participantHref(row.attempt_id)"
                                class="focus-ring rounded-sm font-medium text-fg hover:text-brand">{{ row.user_name }}</Link>
                            <span v-else class="font-medium text-fg">{{ row.user_name }}</span>
                            <Badge v-if="row.is_disqualified" variant="danger" size="sm">Diskvalifikatsiya</Badge>
                        </span>
                    </template>
                    <template #cell-score="{ row }">
                        <span class="font-semibold text-fg">{{ row.score }}</span>
                        <span v-if="row.max_score" class="text-fg-subtle"> / {{ row.max_score }}</span>
                    </template>
                    <template #cell-score_percent="{ row }">{{ row.score_percent }}%</template>
                    <template #cell-answers="{ row }">{{ row.questions_correct }} / {{ row.questions_answered }}</template>
                    <template #empty>
                        <EmptyState :icon="TrophyIcon" title="Hozircha natija yo'q"
                            description="Ishtirokchilar javob bera boshlagach reyting shu yerda ko'rinadi" size="sm" />
                    </template>
                </Table>
            </Card>
        </div>
    </AdminLayout>
</template>
