<template>
    <AdminLayout>

        <Head title="Bank Hisoblari" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Bank Hisoblari</h1>
            <Link href="/admin/teacher-payouts" class="text-gray-500 hover:text-gray-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            To'lovlar
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="flex gap-4">
                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha holatlar</option>
                    <option value="pending">Tasdiqlash kutilmoqda</option>
                    <option value="verified">Tasdiqlangan</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="accounts.data" :loading="loading" empty-text="Hisoblar topilmadi">
            <template #default="{ rows }">
                <tr v-for="account in rows" :key="account.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ account.teacher?.full_name || account.teacher?.first_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ account.bank_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                        {{ account.account_number }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ account.card_holder_name }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="{
                            'bg-yellow-100 text-yellow-800': account.status === 'pending',
                            'bg-green-100 text-green-800': account.status === 'verified',
                        }">
                            {{ account.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button v-if="account.status === 'pending'" @click="verify(account)"
                            class="text-green-600 hover:text-green-800 transition-colors" title="Tasdiqlash">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </template>
        </DataTable>

        <Pagination :data="accounts" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    accounts: Object,
    filters: Object,
});

const loading = ref(false);
const filters = reactive({
    status: props.filters?.status || '',
});

const columns = [
    { key: 'teacher', label: 'O\'qituvchi' },
    { key: 'bank', label: 'Bank' },
    { key: 'account', label: 'Hisob raqami' },
    { key: 'holder', label: 'Egasi' },
    { key: 'status', label: 'Holat' },
    { key: 'actions', label: '' },
];

const filter = () => {
    router.get('/admin/teacher-payouts/bank-accounts', filters, {
        preserveState: true,
    });
};

const verify = (account) => {
    if (confirm('Bu bank hisobini tasdiqlamoqchimisiz?')) {
        router.patch(`/admin/teacher-payouts/bank-accounts/${account.id}/verify`);
    }
};
</script>
