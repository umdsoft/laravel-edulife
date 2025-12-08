<template>
    <AdminLayout>

        <Head title="Foydalanuvchilar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Foydalanuvchilar</h1>
                <p class="text-gray-500">Barcha foydalanuvchilarni boshqarish</p>
            </div>

            <Link :href="route('admin.users.create')"
                class="inline-flex items-center gap-2 bg-primary text-white font-medium px-4 py-2 rounded-xl hover:bg-primary/90 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Yangi foydalanuvchi
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <SearchInput v-model="filters.search" placeholder="Ism, telefon, email bo'yicha qidirish..."
                    @update:model-value="search" />

                <!-- Role Filter -->
                <select v-model="filters.role" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha rollar</option>
                    <option value="student">Student</option>
                    <option value="teacher">O'qituvchi</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>

                <!-- Status Filter -->
                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha statuslar</option>
                    <option value="active">Faol</option>
                    <option value="inactive">Nofaol</option>
                    <option value="blocked">Bloklangan</option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <DataTable :columns="columns" :rows="users.data" :loading="loading"
            empty-text="Hech qanday foydalanuvchi topilmadi">
            <template #default="{ rows }">
                <tr v-for="user in rows" :key="user.id" class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-medium text-gray-600">
                                {{ getInitials(user.full_name) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ user.full_name }}</p>
                                <p class="text-xs text-gray-500">{{ user.email || 'Email yo\'q' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ user.phone }}
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getRoleBadgeVariant(user.role)">
                            {{ getRoleLabel(user.role) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getStatusBadgeVariant(user.status)">
                            {{ getStatusLabel(user.status) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-yellow-600">
                        {{ user.coin_balance }} 🪙
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ user.created_at }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.users.show', user.id)"
                                class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition"
                                title="Ko'rish">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            </Link>
                            <Link :href="route('admin.users.edit', user.id)"
                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                title="Tahrirlash">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            </Link>
                            <button @click="confirmDelete(user)"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                title="O'chirish">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
        </DataTable>

        <!-- Pagination -->
        <Pagination :data="users" @page-change="changePage" />

        <!-- Delete Confirmation -->
        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading"
            :message="`${deleteModal.user?.full_name} foydalanuvchisini o'chirishni xohlaysizmi?`" @confirm="deleteUser"
            @cancel="deleteModal.show = false" />
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
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const loading = ref(false);
const deleteModal = reactive({
    show: false,
    loading: false,
    user: null,
});

const filters = reactive({
    search: props.filters?.search || '',
    role: props.filters?.role || '',
    status: props.filters?.status || '',
});

const columns = [
    { key: 'name', label: 'Foydalanuvchi', class: 'w-1/4' },
    { key: 'phone', label: 'Telefon' },
    { key: 'role', label: 'Rol' },
    { key: 'status', label: 'Status' },
    { key: 'coin_balance', label: 'Coins' },
    { key: 'created_at', label: 'Sana' },
    { key: 'actions', label: 'Amallar' },
];

const search = () => {
    router.get(route('admin.users.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get(route('admin.users.index'), filters, {
        preserveState: true,
    });
};

const changePage = (page) => {
    router.get(route('admin.users.index'), { ...filters, page }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmDelete = (user) => {
    deleteModal.user = user;
    deleteModal.show = true;
};

const deleteUser = () => {
    deleteModal.loading = true;

    router.delete(route('admin.users.destroy', deleteModal.user.id), {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.user = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

// Helpers
const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getRoleLabel = (role) => {
    const labels = {
        student: 'Student',
        teacher: 'O\'qituvchi',
        admin: 'Admin',
        super_admin: 'Super Admin',
    };
    return labels[role] || role;
};

const getRoleBadgeVariant = (role) => {
    const variants = {
        student: 'info',
        teacher: 'success',
        admin: 'warning',
        super_admin: 'danger',
    };
    return variants[role] || 'gray';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Faol',
        inactive: 'Nofaol',
        blocked: 'Bloklangan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        active: 'success',
        inactive: 'gray',
        blocked: 'danger',
    };
    return variants[status] || 'gray';
};
</script>
