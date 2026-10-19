<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { computed, ref } from 'vue';
import { PageHeader, Card, Badge, Tabs, Table, Avatar } from '@/Components/UI';
import { BeakerIcon, SparklesIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import { TrophyIcon as TrophyMini } from '@heroicons/vue/20/solid';

const props = defineProps({
    leaderboard: Array,
    period: String,
    filter: String,
    // The controller sends the rank as a plain number (or null); an object { rank, total_xp, … } is also accepted.
    userRank: [Object, Number],
});

const currentPeriod = ref(props.period);
const currentFilter = ref(props.filter);

const periods = [
    { value: 'weekly', label: 'Haftalik' },
    { value: 'monthly', label: 'Oylik' },
    { value: 'all_time', label: 'Umumiy' },
];

const page = usePage();
const authId = computed(() => page.props.auth?.user?.id);

const periodTabs = computed(() => periods.map((p) => ({
    key: p.value,
    label: p.label,
    href: route('student.lab.leaderboard', { period: p.value }),
    active: props.period === p.value,
})));

const rows = computed(() => props.leaderboard || []);
const myEntry = computed(() => rows.value.find((e) => e.user?.id === authId.value) || null);

const me = computed(() => {
    const r = props.userRank;
    const obj = r && typeof r === 'object' ? r : null;
    const rank = typeof r === 'number' ? r : (obj?.rank ?? myEntry.value?.rank ?? null);
    if (!rank && !obj && !myEntry.value) return null;
    return {
        rank,
        xp: obj?.total_xp ?? myEntry.value?.stats?.total_xp ?? null,
        labs: obj?.completed_experiments ?? myEntry.value?.stats?.labs_completed ?? null,
    };
});

const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Number(n) || 0);

const medalClass = (rank) => ({
    1: 'bg-warning-50 text-warning ring-1 ring-warning-100',
    2: 'bg-surface-2 text-fg-muted ring-1 ring-border',
    3: 'bg-surface-2 text-warning ring-1 ring-border',
}[rank] || 'text-fg-subtle');

const columns = [
    { key: 'rank', label: "O'rin", width: '72px' },
    { key: 'user', label: "O'quvchi" },
    { key: 'level', label: 'Daraja', numeric: true, hideBelow: 'sm' },
    { key: 'xp', label: 'XP', numeric: true },
    { key: 'labs', label: 'Tajribalar', numeric: true, hideBelow: 'md' },
];
</script>

<template>

    <Head title="Lab reytingi - Virtual laboratoriya" />

    <StudentLayout>
        <PageHeader title="Lab reytingi" description="Laboratoriya tajribalari bo'yicha eng faol o'quvchilar"
            :breadcrumbs="[{ label: 'Laboratoriya', href: route('student.lab.index') }, { label: 'Reyting' }]">
            <template #tabs>
                <Tabs :tabs="periodTabs" variant="pills" label="Reyting davri" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Current user's rank -->
            <Card v-if="me">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                        <TrophyIcon class="h-6 w-6" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-13 text-fg-muted">Sizning o'rningiz</p>
                        <p class="text-2xl font-semibold text-fg tabular-nums">{{ me.rank ? `#${me.rank}` : '—' }}</p>
                    </div>
                    <dl class="flex gap-6">
                        <div v-if="me.xp !== null">
                            <dt class="flex items-center gap-1 text-xs text-fg-subtle">
                                <SparklesIcon class="h-4 w-4 text-brand" aria-hidden="true" />XP
                            </dt>
                            <dd class="font-semibold text-fg tabular-nums">{{ fmt(me.xp) }}</dd>
                        </div>
                        <div v-if="me.labs !== null">
                            <dt class="flex items-center gap-1 text-xs text-fg-subtle">
                                <BeakerIcon class="h-4 w-4" aria-hidden="true" />Tajriba
                            </dt>
                            <dd class="font-semibold text-fg tabular-nums">{{ fmt(me.labs) }}</dd>
                        </div>
                    </dl>
                </div>
            </Card>

            <!-- Leaderboard -->
            <Table :columns="columns" :rows="rows" :row-key="(row) => row.user?.id ?? row.rank"
                caption="Laboratoriya reytingi" empty-title="Reyting hali bo'sh"
                empty-description="Bu davrda hali hech kim tajriba yakunlamagan.">
                <template #cell-rank="{ row }">
                    <span :class="['inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold tabular-nums', medalClass(row.rank)]">
                        <TrophyMini v-if="row.rank <= 3" class="h-4 w-4" aria-hidden="true" />
                        <template v-else>{{ row.rank }}</template>
                        <span v-if="row.rank <= 3" class="sr-only">{{ row.rank }}-o'rin</span>
                    </span>
                </template>
                <template #cell-user="{ row }">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar :src="row.user.avatar" :name="row.user.name" size="sm" />
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 font-medium text-fg">
                                <span class="truncate">{{ row.user.name }}</span>
                                <Badge v-if="row.user.id === authId" variant="brand" size="sm">Siz</Badge>
                            </p>
                            <p class="truncate text-13 text-fg-subtle">{{ row.user.school || "O'quvchi" }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-level="{ row }">{{ row.user.level ?? row.stats?.level ?? '—' }}</template>
                <template #cell-xp="{ row }">
                    <span class="font-semibold text-fg">{{ fmt(row.stats?.total_xp) }}</span>
                </template>
                <template #cell-labs="{ row }">{{ fmt(row.stats?.labs_completed ?? row.stats?.completed_experiments) }}</template>
            </Table>
        </div>
    </StudentLayout>
</template>
