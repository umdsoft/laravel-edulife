<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowTrendingUpIcon, BuildingLibraryIcon, TrophyIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Card, EmptyState, PageHeader, Select, Tabs } from '@/Components/UI';
import { formatDelta, formatPercent, formatWeek } from '@/Components/Exam/People/mastery';
import { routePath } from '@/Utils/nav';

const props = defineProps({
    subjects: { type: Array, default: () => [] },
    subject: { type: String, default: null },
    weeks: { type: Array, default: () => [] },
    week: { type: String, default: null },
    tab: { type: String, default: 'top' },
    board: { type: Object, default: null },
    me: { type: Object, default: () => ({}) },
});

const active = ref(props.tab);
const tabs = computed(() => [
    { key: 'top', label: 'Umumiy', icon: TrophyIcon },
    { key: 'growth', label: 'Eng ko\'p o\'sganlar', icon: ArrowTrendingUpIcon },
    { key: 'schools', label: 'Maktablar', icon: BuildingLibraryIcon },
]);
const subjectTabs = computed(() => props.subjects.map((s) => ({ key: s.code, label: s.name })));
const weekOptions = computed(() => props.weeks.map((w, i) => ({ value: w, label: i === 0 ? `${formatWeek(w)} (oxirgi)` : formatWeek(w) })));

const go = (params) => {
    router.get(routePath('student.exam.leaderboard') || '/student/exam/leaderboard',
        { subject: props.subject, week: props.week, tab: active.value, ...params },
        { preserveScroll: true, preserveState: true, replace: true });
};

const rows = computed(() => (props.board ? props.board[active.value] || [] : []));
const myLine = computed(() => (active.value === 'schools' ? props.me?.school : props.me?.[active.value]));
const isMe = (row) => row.uid && row.uid === props.me?.uid;
const medal = (rank) => ({ 1: 'bg-warning-50 text-warning ring-1 ring-warning-100', 2: 'bg-surface-2 text-fg ring-1 ring-border', 3: 'bg-warning-50 text-warning' }[rank] || 'text-fg-muted');
</script>

<template>
    <StudentLayout title="Reyting">
        <Head title="Imtihon reytingi" />

        <PageHeader title="Imtihon reytingi" description="Shanba imtihoni natijalari bo'yicha. Ism faqat rozilik bilan, aks holda taxallus ko'rinadi." />

        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <Tabs v-if="subjectTabs.length > 1" :model-value="subject" :tabs="subjectTabs" variant="pills" label="Fan"
                    @change="(code) => go({ subject: code, week: null })" />
                <Select v-if="weekOptions.length" :model-value="week" :options="weekOptions" label="Hafta" class="sm:w-60"
                    @change="(w) => go({ week: w })" />
            </div>

            <Card v-if="myLine" padding="sm" class="border-brand-200 bg-brand-50">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <span class="font-medium text-brand">Sizning o'rningiz</span>
                    <span class="text-fg"><span class="text-lg font-semibold tabular-nums">{{ myLine.rank }}</span>-o'rin</span>
                    <span v-if="active === 'top'" class="text-fg-muted tabular-nums">{{ formatPercent(myLine.percent) }} · {{ board?.participants }} ishtirokchi</span>
                    <span v-else-if="active === 'growth'" class="text-success tabular-nums">{{ formatDelta(myLine.delta) }} ball</span>
                    <span v-else class="text-fg-muted">{{ myLine.name }} · o'rtacha {{ formatPercent(myLine.average) }}</span>
                </div>
            </Card>

            <Card padding="none">
                <div class="border-b border-border px-4 pt-2 sm:px-5">
                    <Tabs v-model="active" :tabs="tabs" label="Reyting turi" />
                </div>

                <EmptyState v-if="!board" :icon="ChartBarIcon" title="Hali natijalar yo'q"
                    description="Birinchi shanba imtihonidan keyin reyting shu yerda paydo bo'ladi." class="py-10" />

                <template v-else>
                    <EmptyState v-if="!rows.length" :icon="active === 'schools' ? BuildingLibraryIcon : ArrowTrendingUpIcon" class="py-10"
                        :title="active === 'schools' ? 'Maktablar reytingi hali yo\'q' : 'Bu hafta o\'sganlar yo\'q'"
                        :description="active === 'schools' ? `Maktab reytingga kamida ${board.school_min} ta ishtirokchi bilan kiradi.` : 'O\'tgan haftaga nisbatan ball oshgan o\'quvchilar shu yerda bo\'ladi.'" />

                    <ol v-else class="divide-y divide-border">
                        <li v-for="row in rows" :key="row.uid || row.key"
                            :class="['flex items-center gap-3 px-4 py-3 sm:px-5', isMe(row) ? 'bg-brand-50' : '']">
                            <span :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold tabular-nums', medal(row.rank)]">
                                {{ row.rank }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-fg">
                                    {{ row.name }}
                                    <Badge v-if="isMe(row)" variant="brand" size="sm" class="ml-1.5">Siz</Badge>
                                </p>
                                <p v-if="active === 'schools'" class="text-xs text-fg-subtle tabular-nums">{{ row.participants }} ishtirokchi</p>
                                <p v-else class="text-xs text-fg-subtle tabular-nums">{{ String(row.score).replace('.', ',') }} / {{ String(row.max_score).replace('.', ',') }} ball</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p v-if="active === 'growth'" class="text-sm font-semibold text-success tabular-nums">{{ formatDelta(row.delta) }}</p>
                                <p v-else-if="active === 'schools'" class="text-sm font-semibold text-fg tabular-nums">{{ formatPercent(row.average) }}</p>
                                <p v-else class="text-sm font-semibold text-fg tabular-nums">{{ formatPercent(row.percent) }}</p>
                                <p v-if="active === 'growth'" class="text-xs text-fg-subtle tabular-nums">{{ formatPercent(row.percent) }}</p>
                                <p v-else-if="active === 'top' && row.delta > 0" class="text-xs text-success tabular-nums">{{ formatDelta(row.delta) }}</p>
                            </div>
                        </li>
                    </ol>
                </template>
            </Card>

            <p v-if="board" class="text-xs text-fg-subtle">
                Maktablar reytingida kamida {{ board.school_min }} ta ishtirokchisi bor maktablar ko'rsatiladi. Reyting har 10 daqiqada yangilanadi.
            </p>
        </div>
    </StudentLayout>
</template>
