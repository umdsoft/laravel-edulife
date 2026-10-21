<script setup>
/**
 * Imtihonlar: upcoming / past Saturday sessions and placement windows.
 */
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { PlusIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Badge, Button, PageHeader, Pagination, Table, Tabs } from '@/Components/UI';
import { sessionStatus, dateTimeLabel, timeLabel } from '@/Components/Exam/Engine/sessionFormat';

const props = defineProps({
    tab: { type: String, default: 'upcoming' },
    sessions: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
});

const sectionTabs = [
    { key: 'sessions', label: 'Imtihonlar', href: route('teacher.exam.sessions.index'), active: true },
    { key: 'blueprints', label: 'Imtihon rejalari', href: route('teacher.exam.blueprints.index') },
];
const tabs = computed(() => [
    { key: 'upcoming', label: 'Kelgusi', count: props.counts.upcoming, href: route('teacher.exam.sessions.index'), active: props.tab === 'upcoming' },
    { key: 'past', label: "O'tgan", href: route('teacher.exam.sessions.index', { tab: 'past' }), active: props.tab === 'past' },
    { key: 'placement', label: 'Qabul testlari', href: route('teacher.exam.sessions.index', { tab: 'placement' }), active: props.tab === 'placement' },
]);

const columns = computed(() => [
    { key: 'title', label: 'Imtihon' },
    { key: 'starts_at', label: props.tab === 'placement' ? 'Turi' : 'Vaqti', hideBelow: 'sm' },
    { key: 'variants_count', label: 'Variantlar', numeric: true, hideBelow: 'md' },
    { key: 'finished_count', label: 'Topshirdi', numeric: true },
    ...(props.tab === 'placement' ? [] : [{ key: 'price', label: 'Narx', numeric: true, hideBelow: 'md' }]),
    { key: 'status', label: 'Holat' },
]);

// paid exams
const money = (v) => String(Math.round(Number(v) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const open = (row) => router.visit(route('teacher.exam.sessions.show', row.id));
</script>

<template>
    <Head title="Imtihonlar" />

    <TeacherLayout>
        <PageHeader title="Imtihonlar" description="Shanba imtihonlarini rejalashtiring, bank yetarliligini tekshiring va natijalarni kuzating.">
            <template #actions>
                <Button :icon-left="PlusIcon" :href="route('teacher.exam.sessions.create')">Imtihon rejalashtirish</Button>
            </template>
            <template #tabs>
                <Tabs :tabs="sectionTabs" model-value="sessions" label="Imtihonlar bo'limi" />
            </template>
        </PageHeader>

        <div class="space-y-4">
            <Tabs :tabs="tabs" :model-value="tab" variant="pills" label="Imtihonlar filtri" />

            <Table :columns="columns" :rows="sessions.data" clickable caption="Imtihonlar" @row-click="open"
                :empty-title="tab === 'upcoming' ? 'Rejalashtirilgan imtihon yo\'q' : 'Hali imtihon yo\'q'"
                :empty-description="tab === 'upcoming' ? 'Keyingi shanba uchun imtihon rejalashtiring.' : ''">
                <template #cell-title="{ row }">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg">{{ row.title }}</p>
                        <p class="mt-0.5 flex flex-wrap items-center gap-2 text-13 text-fg-subtle">
                            <Badge v-if="row.subject" :variant="row.subject.code === 'physics' ? 'physics' : 'math'" size="sm">{{ row.subject.name }}</Badge>
                            <span class="truncate">{{ row.blueprint_name }}</span>
                            <span v-if="row.starts_at" class="tabular-nums sm:hidden">{{ dateTimeLabel(row.starts_at) }}</span>
                        </p>
                    </div>
                </template>
                <template #cell-starts_at="{ row }">
                    <span v-if="row.starts_at" class="inline-flex items-center gap-1.5 tabular-nums text-fg-muted">
                        <CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />{{ dateTimeLabel(row.starts_at) }}–{{ timeLabel(row.ends_at) }}
                    </span>
                    <span v-else class="text-fg-muted">{{ row.restricted ? 'Qayta topshirish' : 'Doimiy ochiq' }}</span>
                </template>
                <template #cell-variants_count="{ value }">{{ value ?? 0 }}</template>
                <template #cell-finished_count="{ row }">{{ row.finished_count ?? 0 }}<span class="text-fg-subtle"> / {{ row.attempts_count ?? 0 }}</span></template>
                <template #cell-price="{ row }">
                    <span v-if="!row.price" class="text-fg-subtle">Bepul</span>
                    <span v-else class="block">
                        <span class="tabular-nums text-fg">{{ money(row.price) }} so'm</span>
                        <span class="block text-xs text-fg-subtle tabular-nums">
                            {{ row.purchases_count ?? 0 }} to'lagan · {{ row.via_subscription_count ?? 0 }} obuna
                        </span>
                    </span>
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="sessionStatus(value).variant" dot>{{ sessionStatus(value).label }}</Badge>
                </template>
                <template v-if="sessions.links && sessions.links.length > 3" #footer>
                    <Pagination :data="sessions" />
                </template>
            </Table>
        </div>
    </TeacherLayout>
</template>
