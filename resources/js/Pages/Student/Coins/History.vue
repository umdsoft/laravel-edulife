<script setup>
import { coinSourceLabel } from '@/Utils/coinSources';
import { Head } from '@inertiajs/vue3';
import { ArrowDownCircleIcon, ArrowUpCircleIcon, ShoppingBagIcon } from '@heroicons/vue/24/outline';
import { CircleStackIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, PageHeader, Pagination, StatCard, Table } from '@/Components/UI';

defineProps({
    balance: { type: Number, default: 0 },
    total_earned: { type: Number, default: 0 },
    total_spent: { type: Number, default: 0 },
    transactions: Object, // paginator
});

const sourceLabel = coinSourceLabel;

const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Math.abs(Number(n) || 0)).replace(/\s/g, ' ');
const pad = (n) => String(n).padStart(2, '0');
const formatDateTime = (v) => {
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? '—' : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const columns = [
    { key: 'description', label: 'Tavsif' },
    { key: 'source', label: 'Manba', hideBelow: 'md' },
    { key: 'amount', label: 'Miqdor', numeric: true },
    { key: 'balance_after', label: 'Balans', numeric: true, hideBelow: 'sm' },
    { key: 'created_at', label: 'Sana', hideBelow: 'lg' },
];
</script>

<template>
    <StudentLayout title="Coinlar tarixi">
        <Head title="Coinlar tarixi" />

        <PageHeader title="Coinlar tarixi" description="Qachon va nima uchun coin olganingiz yoki sarflaganingiz.">
            <template #actions>
                <Button variant="secondary" :icon-left="ShoppingBagIcon" :href="route('student.shop.index')">Coin do'kon</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard label="Balans" :value="balance" unit="coin" :icon="CircleStackIcon" tone="warning" />
                <StatCard label="Jami olingan" :value="total_earned" unit="coin" :icon="ArrowDownCircleIcon" tone="success" />
                <StatCard label="Jami sarflangan" :value="total_spent" unit="coin" :icon="ArrowUpCircleIcon" tone="info" />
            </div>

            <Table :columns="columns" :rows="transactions?.data || []" caption="Coin harakatlari"
                empty-title="Hali coin harakatlari yo'q" empty-description="Darslarni tugatsangiz va testlardan o'tsangiz, coinlar shu yerda ko'rinadi.">
                <template #cell-description="{ row }">
                    <p class="text-fg">{{ row.description || sourceLabel(row.source) }}</p>
                    <p class="text-13 tabular-nums text-fg-subtle lg:hidden">{{ formatDateTime(row.created_at) }}</p>
                </template>
                <template #cell-source="{ value }"><Badge size="sm" variant="neutral">{{ sourceLabel(value) }}</Badge></template>
                <template #cell-amount="{ value }">
                    <span :class="['font-semibold', Number(value) < 0 ? 'text-danger' : 'text-success']">
                        {{ Number(value) < 0 ? '−' : '+' }}{{ fmt(value) }}
                    </span>
                </template>
                <template #cell-balance_after="{ value }"><span class="text-fg-muted">{{ fmt(value) }}</span></template>
                <template #cell-created_at="{ value }"><span class="text-13 tabular-nums text-fg-muted">{{ formatDateTime(value) }}</span></template>
                <template #footer><Pagination :data="transactions" /></template>
            </Table>
        </div>
    </StudentLayout>
</template>
