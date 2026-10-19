<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge, Button, EmptyState, IconButton, Input, PageHeader, Pagination, Select, StatCard, Table } from '@/Components/UI';
import {
    BanknotesIcon, ReceiptPercentIcon, MagnifyingGlassIcon, ChevronRightIcon, XMarkIcon, CreditCardIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    payments: Object,
    summary: Object,
    filters: Object,
});

const TYPES = {
    subscription: { label: 'Obuna', variant: 'info' },
    course: { label: 'Kurs', variant: 'brand' },
    coins: { label: 'Coin', variant: 'warning' },
};
const STATUSES = {
    pending: { label: 'Kutilmoqda', variant: 'warning' },
    completed: { label: 'Tugallangan', variant: 'success' },
    failed: { label: 'Xato', variant: 'danger' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
};
const typeOf = (t) => TYPES[t] || { label: t || '—', variant: 'neutral' };
const statusOf = (s) => STATUSES[s] || { label: s || '—', variant: 'neutral' };
const typeOptions = Object.entries(TYPES).map(([value, t]) => ({ value, label: t.label }));
const statusOptions = Object.entries(STATUSES).map(([value, s]) => ({ value, label: s.label }));

const money = (v) => Math.round(Number(v) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const loading = ref(false);
const filters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || null,
    status: props.filters?.status || null,
});
const hasFilters = computed(() => Boolean(filters.search || filters.type || filters.status));

const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== ''));
const visit = () => {
    router.get(route('admin.payments.index'), clean(filters), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};
let timer = null;
const onSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(visit, 350);
};
onBeforeUnmount(() => clearTimeout(timer));
const resetFilters = () => {
    Object.assign(filters, { search: '', type: null, status: null });
    visit();
};

const columns = [
    { key: 'transaction_id', label: 'Tranzaksiya', hideBelow: 'md' },
    { key: 'user_name', label: 'Foydalanuvchi' },
    { key: 'type', label: 'Tur', hideBelow: 'sm' },
    { key: 'status', label: 'Holat' },
    { key: 'created_at', label: 'Sana', hideBelow: 'lg' },
    { key: 'amount', label: 'Summa', numeric: true },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const openRow = (row) => router.visit(route('admin.payments.show', row.id));
</script>

<template>
    <AdminLayout>
        <Head title="To'lovlar" />

        <PageHeader title="To'lovlar" description="Platformadagi barcha to'lov tranzaksiyalari" />

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <StatCard label="Jami tushum" :value="money(summary?.total_amount)" unit="so'm" :icon="BanknotesIcon" tone="success"
                    hint="Faqat tugallangan to'lovlar" />
                <StatCard label="Tranzaksiyalar" :value="summary?.total_count || 0" unit="ta" :icon="ReceiptPercentIcon" tone="brand"
                    hint="Barcha holatlar" />
            </div>

            <div class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                    <Input v-model="filters.search" type="search" placeholder="Foydalanuvchi yoki tranzaksiya ID" aria-label="Qidirish"
                        :leading-icon="MagnifyingGlassIcon" class="sm:w-80" @input="onSearch" />
                    <Select v-model="filters.type" :options="typeOptions" placeholder="Barcha turlar" aria-label="Tur"
                        class="sm:w-44" @change="visit" />
                    <Select v-model="filters.status" :options="statusOptions" placeholder="Barcha holatlar" aria-label="Holat"
                        class="sm:w-44" @change="visit" />
                    <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="resetFilters">Tozalash</Button>
                </div>

                <Table :columns="columns" :rows="payments.data" :loading="loading" clickable caption="To'lovlar" @row-click="openRow">
                    <template #cell-transaction_id="{ value }">
                        <span class="font-mono text-13 text-fg-muted">{{ value ? `#${value}` : '—' }}</span>
                    </template>
                    <template #cell-user_name="{ row }">
                        <Link :href="route('admin.payments.show', row.id)" class="focus-ring rounded-sm font-medium text-fg hover:text-brand">
                            {{ row.user_name }}
                        </Link>
                        <p class="text-13 text-fg-subtle lg:hidden">
                            <span class="tabular-nums">{{ row.created_at }}</span>
                            <span class="sm:hidden"> · {{ typeOf(row.type).label }}</span>
                        </p>
                    </template>
                    <template #cell-type="{ value }">
                        <Badge :variant="typeOf(value).variant" size="sm">{{ typeOf(value).label }}</Badge>
                    </template>
                    <template #cell-status="{ value }">
                        <Badge :variant="statusOf(value).variant" dot>{{ statusOf(value).label }}</Badge>
                    </template>
                    <template #cell-created_at="{ value }"><span class="tabular-nums text-fg-muted">{{ value }}</span></template>
                    <template #cell-amount="{ value }">
                        <span class="whitespace-nowrap font-medium text-fg">{{ money(value) }} <span class="text-fg-subtle">so'm</span></span>
                    </template>
                    <template #cell-actions="{ row }">
                        <IconButton :icon="ChevronRightIcon" :href="route('admin.payments.show', row.id)" label="To'lovni ko'rish" size="sm" />
                    </template>
                    <template #empty>
                        <EmptyState v-if="hasFilters" :icon="MagnifyingGlassIcon" title="Hech narsa topilmadi"
                            description="Qidiruv yoki filtrlarni o'zgartirib ko'ring" size="sm">
                            <Button variant="secondary" size="sm" @click="resetFilters">Filtrlarni tozalash</Button>
                        </EmptyState>
                        <EmptyState v-else :icon="CreditCardIcon" title="Hozircha to'lov yo'q" size="sm" />
                    </template>
                    <template v-if="payments.last_page > 1" #footer>
                        <Pagination :data="payments" />
                    </template>
                </Table>
            </div>
        </div>
    </AdminLayout>
</template>
