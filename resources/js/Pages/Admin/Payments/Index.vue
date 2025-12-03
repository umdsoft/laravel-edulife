<template>
    <AdminLayout>

        <Head title="To'lovlar" />

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">To'lovlar</h1>
            <p class="text-gray-500">Barcha to'lovlarni ko'rish</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Jami to'lovlar</p>
                <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(summary.total_amount) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Tranzaksiyalar soni</p>
                <p class="text-3xl font-bold text-gray-900">{{ summary.total_count }} ta</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Foydalanuvchi yoki ID bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.type" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha turlar</option>
                    <option value="subscription">Obuna</option>
                    <option value="course">Kurs</option>
                    <option value="coins">COIN</option>
                </select>

                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha statuslar</option>
                    <option value="pending">Kutilmoqda</option>
                    <option value="completed">Tugallangan</option>
                    <option value="failed">Xato</option>
                    <option value="refunded">Qaytarilgan</option>
                </select>
            </div>
        </div>

        <!-- Payments Table -->
        <DataTable :columns="columns" :rows="payments.data" :loading="loading"
            empty-text="Hech qanday to'lov topilmadi">
            <template #default="{ rows }">
                <tr v-for="payment in rows" :key="payment.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-mono text-gray-600">
                        #{{ payment.transaction_id }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ payment.user_name }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ formatCurrency(payment.amount) }}
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getTypeBadgeVariant(payment.type)" size="sm">
                            {{ getTypeLabel(payment.type) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getStatusBadgeVariant(payment.status)">
                            {{ getStatusLabel(payment.status) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ payment.created_at }}
                    </td>
                    <td class="px-6 py-4">
                        <Link :href="route('admin.payments.show', payment.id)"
                            class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition inline-flex"
                            title="Ko'rish">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        </Link>
                    </td>
                </tr>
            </template>
        </DataTable>

        <!-- Pagination -->
        <Pagination :data="payments" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Badge from '@/Components/Admin/Badge.vue';

const props = defineProps({
    payments: Object,
    summary: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    status: props.filters?.status || '',
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'user', label: 'Foydalanuvchi' },
    { key: 'amount', label: 'Summa' },
    { key: 'type', label: 'Tur' },
    { key: 'status', label: 'Status' },
    { key: 'date', label: 'Sana' },
    { key: 'actions', label: '' },
];

const search = () => {
    router.get(route('admin.payments.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get(route('admin.payments.index'), filters, {
        preserveState: true,
    });
};

const getTypeLabel = (type) => {
    const labels = {
        subscription: 'Obuna',
        course: 'Kurs',
        coins: 'COIN',
    };
    return labels[type] || type;
};

const getTypeBadgeVariant = (type) => {
    const variants = {
        subscription: 'info',
        course: 'success',
        coins: 'warning',
    };
    return variants[type] || 'gray';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Kutilmoqda',
        completed: 'Tugallangan',
        failed: 'Xato',
        refunded: 'Qaytarilgan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        pending: 'warning',
        completed: 'success',
        failed: 'danger',
        refunded: 'gray',
    };
    return variants[status] || 'gray';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>
