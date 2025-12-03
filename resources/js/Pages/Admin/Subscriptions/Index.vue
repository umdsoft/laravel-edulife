<template>
    <AdminLayout>

        <Head title="Foydalanuvchi obunalari" />

        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Foydalanuvchi obunalari</h1>
                    <p class="text-gray-500">Faol va tugagan obunalar</p>
                </div>
                <Link href="/admin/subscription-plans"
                    class="inline-flex items-center gap-2 bg-primary text-white hover:bg-primary/90 font-medium px-4 py-2 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                Tariflar
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Foydalanuvchi bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.plan_id" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha tariflar</option>
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                        {{ plan.name }}
                    </option>
                </select>

                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha statuslar</option>
                    <option value="active">Faol</option>
                    <option value="expired">Tugagan</option>
                    <option value="cancelled">Bekor qilingan</option>
                </select>
            </div>
        </div>

        <!-- Subscriptions Table -->
        <DataTable :columns="columns" :rows="subscriptions.data" :loading="loading"
            empty-text="Hech qanday obuna topilmadi">
            <template #default="{ rows }">
                <tr v-for="subscription in rows" :key="subscription.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <Link :href="`/admin/users/${subscription.user_id}`"
                            class="text-sm font-medium text-primary hover:text-primary/80">
                        {{ subscription.user_name }}
                        </Link>
                    </td>
                    <td class="px-6 py-4">
                        <Badge variant="info" size="sm">
                            {{ subscription.plan_name }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ subscription.started_at }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <div class="flex items-center gap-2">
                            {{ subscription.ends_at }}
                            <span v-if="subscription.is_expiring_soon" class="text-yellow-500"
                                title="Tez orada tugaydi">⚠️</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getStatusBadgeVariant(subscription.status)">
                            {{ getStatusLabel(subscription.status) }}
                        </Badge>
                    </td>
                </tr>
            </template>
        </DataTable>

        <!-- Pagination -->
        <Pagination :data="subscriptions" />
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
    subscriptions: Object,
    plans: Array,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    plan_id: props.filters?.plan_id || '',
    status: props.filters?.status || '',
});

const columns = [
    { key: 'user', label: 'Foydalanuvchi' },
    { key: 'plan', label: 'Tarif' },
    { key: 'started', label: 'Boshlandi' },
    { key: 'ends', label: 'Tugaydi' },
    { key: 'status', label: 'Status' },
];

const search = () => {
    router.get('/admin/subscriptions', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/subscriptions', filters, {
        preserveState: true,
    });
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Faol',
        expired: 'Tugagan',
        cancelled: 'Bekor qilingan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        active: 'success',
        expired: 'gray',
        cancelled: 'danger',
    };
    return variants[status] || 'gray';
};
</script>
