<script setup>
/**
 * Imtihon rejalari (blueprints): placement and weekly plans per subject with their bank status.
 */
import { Head, router } from '@inertiajs/vue3';
import { PlusIcon, CalendarDaysIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Badge, Button, PageHeader, Table, Tabs } from '@/Components/UI';

defineProps({
    blueprints: { type: Array, default: () => [] },
});

const tabs = [
    { key: 'sessions', label: 'Imtihonlar', href: route('teacher.exam.sessions.index') },
    { key: 'blueprints', label: 'Imtihon rejalari', href: route('teacher.exam.blueprints.index'), active: true },
];

const columns = [
    { key: 'name', label: 'Reja' },
    { key: 'purpose', label: 'Turi', hideBelow: 'sm' },
    { key: 'total_questions', label: 'Savollar', numeric: true },
    { key: 'duration_min', label: 'Vaqt', numeric: true, hideBelow: 'md' },
    { key: 'bank_status', label: 'Bank' },
];

const PURPOSE = { weekly: 'Shanba imtihoni', placement: 'Qabul testi', open_regional: 'Ochiq imtihon' };
const BANK = {
    ok: { label: 'Yetarli', variant: 'success' },
    fallback: { label: "Qo'shni qiyinlik bilan", variant: 'warning' },
    short: { label: 'Yetarli emas', variant: 'danger' },
};
const open = (row) => router.visit(route('teacher.exam.blueprints.edit', row.id));
</script>

<template>
    <Head title="Imtihon rejalari" />

    <TeacherLayout>
        <PageHeader title="Imtihon rejalari" description="Har imtihon qaysi mavzudan, qanday qiyinlikda nechta savol olishini belgilaydi.">
            <template #actions>
                <Button :icon-left="PlusIcon" :href="route('teacher.exam.blueprints.create')">Yangi reja</Button>
            </template>
            <template #tabs>
                <Tabs :tabs="tabs" model-value="blueprints" label="Imtihonlar bo'limi" />
            </template>
        </PageHeader>

        <Table :columns="columns" :rows="blueprints" clickable caption="Imtihon rejalari" @row-click="open"
            empty-title="Hali reja yo'q" empty-description="Qabul testi va shanba imtihoni uchun reja yarating.">
            <template #cell-name="{ row }">
                <div class="min-w-0">
                    <p class="truncate font-medium text-fg">{{ row.name }}</p>
                    <p class="mt-0.5 flex items-center gap-2 text-13 text-fg-subtle">
                        <Badge v-if="row.subject" :variant="row.subject.code === 'physics' ? 'physics' : 'math'" size="sm">{{ row.subject.name }}</Badge>
                        <span class="sm:hidden">{{ PURPOSE[row.purpose] }}</span>
                        <Badge v-if="!row.is_active" size="sm" variant="neutral">Faol emas</Badge>
                    </p>
                </div>
            </template>
            <template #cell-purpose="{ value }">
                <span class="inline-flex items-center gap-1.5 text-fg-muted">
                    <component :is="value === 'placement' ? ClipboardDocumentListIcon : CalendarDaysIcon" class="h-4 w-4" aria-hidden="true" />
                    {{ PURPOSE[value] || value }}
                </span>
            </template>
            <template #cell-duration_min="{ value }">{{ value }} daq</template>
            <template #cell-bank_status="{ value }">
                <Badge :variant="(BANK[value] || BANK.short).variant" dot>{{ (BANK[value] || BANK.short).label }}</Badge>
            </template>
        </Table>
    </TeacherLayout>
</template>
