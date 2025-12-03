<template>
    <AdminLayout>

        <Head title="O'qituvchi To'lovlari" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">To'lovlar</h1>
            <Link href="/admin/teacher-payouts/bank-accounts"
                class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl transition-colors">
            Bank Hisoblari
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="flex gap-4">
                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha holatlar</option>
                    <option value="pending">Kutilmoqda</option>
                    <option value="paid">To'langan</option>
                    <option value="cancelled">Bekor qilingan</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="payouts.data" :loading="loading" empty-text="To'lovlar topilmadi">
            <template #default="{ rows }">
                <tr v-for="payout in rows" :key="payout.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ payout.teacher?.full_name || payout.teacher?.first_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">
                        {{ formatCurrency(payout.amount) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ payout.payment_method }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="{
                            'bg-yellow-100 text-yellow-800': payout.status === 'pending',
                            'bg-green-100 text-green-800': payout.status === 'paid',
                            'bg-red-100 text-red-800': payout.status === 'cancelled',
                        }">
                            {{ payout.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ new Date(payout.created_at).toLocaleDateString() }}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button v-if="payout.status === 'pending'" @click="markPaid(payout)"
                            class="text-green-600 hover:text-green-800 transition-colors"
                            title="To'landi deb belgilash">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button v-if="payout.status === 'pending'" @click="cancel(payout)"
                            class="text-red-600 hover:text-red-800 transition-colors" title="Bekor qilish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </template>
        </DataTable>

        <Pagination :data="payouts" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    payouts: Object,
    filters: Object,
});

const loading = ref(false);
const filters = reactive({
    status: props.filters?.status || '',
});

const columns = [
    { key: 'teacher', label: 'O\'qituvchi' },
    { key: 'amount', label: 'Summa' },
    { key: 'method', label: 'Usul' },
    { key: 'status', label: 'Holat' },
    { key: 'date', label: 'Sana' },
    { key: 'actions', label: '' },
];

const filter = () => {
    router.get('/admin/teacher-payouts', filters, {
        preserveState: true,
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', { style: 'currency', currency: 'UZS' }).format(amount);
};

const markPaid = (payout) => {
    if (confirm('Bu to\'lovni to\'landi deb belgilamoqchimisiz?')) {
        router.patch(`/admin/teacher-payouts/${payout.id}/mark-paid`);
    }
};

const cancel = (payout) => {
    if (confirm('Bu to\'lovni bekor qilmoqchimisiz?')) {
        router.patch(`/admin/teacher-payouts/${payout.id}/cancel`);
    }
};
</script>
