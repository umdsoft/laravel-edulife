<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, Input, PageHeader, Pagination, Table, Tabs } from '@/Components/UI';
import { useServerFilters } from '@/Composables/useServerFilters';

const props = defineProps({
    tickets: Object, // paginator
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const STATUS = {
    open: { label: 'Ochiq', variant: 'info' },
    in_progress: { label: "Ko'rib chiqilmoqda", variant: 'brand' },
    waiting_user: { label: 'Javob kutilmoqda', variant: 'warning' },
    resolved: { label: 'Hal qilindi', variant: 'success' },
    closed: { label: 'Yopilgan', variant: 'neutral' },
};
const PRIORITY = { low: 'neutral', medium: 'info', high: 'warning', urgent: 'danger' };

const { filters, reset } = useServerFilters(
    { search: props.filters.search || '', status: props.filters.status || '' },
    route('admin.support.index'),
    { only: ['tickets', 'filters', 'counts'] },
);
const status = computed({
    get: () => filters.status || 'all',
    set: (v) => { filters.status = v === 'all' ? '' : v; },
});
const total = computed(() => Object.values(props.counts).reduce((s, n) => s + Number(n || 0), 0));
const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: total.value || undefined },
    ...Object.entries(STATUS).map(([key, s]) => ({ key, label: s.label, count: props.counts[key] || undefined })),
]);

const pad = (n) => String(n).padStart(2, '0');
const date = (v) => {
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? '—' : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const open = (row) => router.visit(route('admin.support.show', row.id));

const columns = [
    { key: 'subject', label: "So'rov" },
    { key: 'user', label: "O'quvchi", hideBelow: 'md' },
    { key: 'status', label: 'Holat' },
    { key: 'priority', label: 'Muhimlik', hideBelow: 'lg' },
    { key: 'updated_at', label: 'Yangilangan', hideBelow: 'sm' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Yordam so'rovlari" />

        <PageHeader title="Yordam so'rovlari" description="O'quvchilarning savol va muammolari. Javob o'quvchiga bildirishnoma bo'lib boradi.">
            <template #tabs>
                <Tabs v-model="status" :tabs="tabs" label="Holat bo'yicha" />
            </template>
        </PageHeader>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <Input v-model="filters.search" type="search" placeholder="Mavzu yoki raqam" :leading-icon="MagnifyingGlassIcon"
                    aria-label="Qidirish" class="sm:w-72" />
                <Button v-if="filters.search || filters.status" variant="ghost" @click="reset">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="tickets.data" clickable caption="Yordam so'rovlari" @row-click="open"
                empty-title="So'rovlar yo'q" empty-description="O'quvchilar yordam so'rovi yuborganda shu yerda ko'rinadi.">
                <template #cell-subject="{ row }">
                    <!-- a real link: keyboard, middle-click and "open in new tab" work -->
                    <Link :href="route('admin.support.show', row.id)" class="focus-ring rounded-sm font-medium text-fg hover:text-brand hover:underline"
                        @click.stop>{{ row.subject }}</Link>
                    <p class="text-13 text-fg-subtle"><span class="font-mono">{{ row.ticket_number }}</span> · {{ row.category_label }} · {{ row.messages_count }} xabar</p>
                </template>
                <template #cell-user="{ row }">
                    <div class="flex items-center gap-2">
                        <Avatar :src="row.user?.avatar" :name="row.user?.full_name" size="xs" />
                        <span class="truncate text-fg-muted">{{ row.user?.full_name }}</span>
                    </div>
                </template>
                <template #cell-status="{ value }"><Badge :variant="(STATUS[value] || {}).variant || 'neutral'" dot>{{ (STATUS[value] || {}).label || value }}</Badge></template>
                <template #cell-priority="{ row }"><Badge size="sm" :variant="PRIORITY[row.priority] || 'neutral'">{{ row.priority_label }}</Badge></template>
                <template #cell-updated_at="{ value }"><span class="text-13 tabular-nums text-fg-muted">{{ date(value) }}</span></template>
                <template #footer><Pagination :data="tickets" /></template>
            </Table>
        </div>
    </AdminLayout>
</template>
