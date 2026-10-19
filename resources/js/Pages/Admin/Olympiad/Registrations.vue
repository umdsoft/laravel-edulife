<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, Input, PageHeader, Pagination, Select, Table } from '@/Components/UI';
import { useServerFilters } from '@/Composables/useServerFilters';

const props = defineProps({
    olympiad: Object,
    registrations: Object, // paginator
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const STATUSES = {
    pending_payment: { label: "To'lov kutilmoqda", variant: 'warning' },
    confirmed: { label: 'Tasdiqlangan', variant: 'success' },
    completed: { label: 'Qatnashgan', variant: 'info' },
    advanced: { label: 'Keyingi bosqichga', variant: 'brand' },
    cancelled: { label: 'Bekor qilingan', variant: 'neutral' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
    no_show: { label: 'Kelmagan', variant: 'neutral' },
    disqualified: { label: 'Chetlatilgan', variant: 'danger' },
};
const statusOf = (s) => STATUSES[s] || { label: s || '—', variant: 'neutral' };

const { filters, reset } = useServerFilters(
    { search: props.filters.search || '', status: props.filters.status || '' },
    route('admin.olympiads.registrations', props.olympiad.id),
    { only: ['registrations', 'filters'] },
);
const hasFilters = computed(() => !!(filters.search || filters.status));
const total = computed(() => Object.values(props.counts || {}).reduce((s, n) => s + Number(n || 0), 0));
const statusOptions = computed(() => Object.entries(STATUSES)
    .filter(([key]) => props.counts?.[key])
    .map(([value, s]) => ({ value, label: `${s.label} (${props.counts[value]})` })));

const money = (v) => (Number(v) > 0 ? `${new Intl.NumberFormat('ru-RU').format(Number(v)).replace(/\s/g, ' ')} so'm` : 'Bepul');

const columns = [
    { key: 'user_name', label: 'Ishtirokchi' },
    { key: 'school', label: 'Maktab', hideBelow: 'lg' },
    { key: 'status', label: 'Holat' },
    { key: 'final_price', label: "To'lov", numeric: true, hideBelow: 'md' },
    { key: 'registered_at', label: "Ro'yxatdan o'tgan", hideBelow: 'sm' },
];
</script>

<template>
    <AdminLayout>
        <Head :title="`${olympiad.title} — ishtirokchilar`" />

        <PageHeader title="Ro'yxatdan o'tganlar" :description="olympiad.title"
            :breadcrumbs="[
                { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
                { label: olympiad.title, href: route('admin.olympiads.show', olympiad.id) },
                { label: 'Ishtirokchilar' },
            ]">
            <template #meta>
                <Badge variant="neutral">Jami: {{ total }}{{ olympiad.max_participants ? ` / ${olympiad.max_participants}` : '' }}</Badge>
                <Badge v-if="counts.confirmed" variant="success">Tasdiqlangan: {{ counts.confirmed }}</Badge>
                <Badge v-if="counts.pending_payment" variant="warning">To'lov kutilmoqda: {{ counts.pending_payment }}</Badge>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <Input v-model="filters.search" type="search" placeholder="Ism, familiya yoki telefon" :leading-icon="MagnifyingGlassIcon"
                    aria-label="Ishtirokchi qidirish" class="sm:w-72" />
                <Select v-model="filters.status" :options="statusOptions" placeholder="Barcha holatlar" aria-label="Holat" class="sm:w-60" />
                <Button v-if="hasFilters" variant="ghost" @click="reset">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="registrations.data" caption="Ro'yxatdan o'tganlar"
                :empty-title="hasFilters ? 'Hech kim topilmadi' : 'Hali hech kim ro\'yxatdan o\'tmagan'"
                :empty-description="hasFilters ? 'Filtrlarni o\'zgartirib ko\'ring.' : 'Ro\'yxat ochilgach ishtirokchilar shu yerda ko\'rinadi.'">
                <template #cell-user_name="{ row }">
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar :name="row.user_name" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-fg">{{ row.user_name || '—' }}</p>
                            <p class="truncate text-13 tabular-nums text-fg-subtle">{{ row.user_phone || row.user_email }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-school="{ value }"><span class="text-fg-muted">{{ value || '—' }}</span></template>
                <template #cell-status="{ value }"><Badge :variant="statusOf(value).variant" dot>{{ statusOf(value).label }}</Badge></template>
                <template #cell-final_price="{ value }">{{ money(value) }}</template>
                <template #cell-registered_at="{ value }"><span class="tabular-nums text-fg-muted">{{ value || '—' }}</span></template>
                <template #footer><Pagination :data="registrations" /></template>
            </Table>
        </div>
    </AdminLayout>
</template>
