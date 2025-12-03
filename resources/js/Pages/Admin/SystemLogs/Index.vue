<template>
    <AdminLayout>

        <Head title="Tizim loglari" />

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Tizim loglari</h1>
            <p class="text-gray-500">Tizimdagi barcha harakatlar tarixi</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <SearchInput v-model="filters.search" placeholder="Log tavsifi bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.event" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha hodisalar</option>
                    <option value="created">Yaratildi</option>
                    <option value="updated">Yangilandi</option>
                    <option value="deleted">O'chirildi</option>
                    <option value="login">Kirish</option>
                </select>
            </div>
        </div>

        <!-- Logs Table -->
        <DataTable :columns="columns" :rows="logs.data" :loading="loading" empty-text="Hech qanday log topilmadi">
            <template #default="{ rows }">
                <tr v-for="log in rows" :key="log.id" class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ log.description }}
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getEventBadgeVariant(log.event)" size="sm">
                            {{ log.event }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ log.causer }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ log.subject_type }} #{{ log.subject_id }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                        {{ log.created_at }}
                    </td>
                </tr>
            </template>
        </DataTable>

        <!-- Pagination -->
        <Pagination :data="logs" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Badge from '@/Components/Admin/Badge.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    event: props.filters?.event || '',
});

const columns = [
    { key: 'description', label: 'Tavsif', class: 'w-1/3' },
    { key: 'event', label: 'Hodisa' },
    { key: 'causer', label: 'Bajaruvchi' },
    { key: 'subject', label: 'Obyekt' },
    { key: 'date', label: 'Vaqt' },
];

const search = () => {
    router.get('/admin/system-logs', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/system-logs', filters, {
        preserveState: true,
    });
};

const getEventBadgeVariant = (event) => {
    const variants = {
        created: 'success',
        updated: 'info',
        deleted: 'danger',
        login: 'warning',
    };
    return variants[event] || 'gray';
};
</script>
