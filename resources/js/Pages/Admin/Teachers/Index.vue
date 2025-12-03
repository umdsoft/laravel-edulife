<template>
    <AdminLayout>

        <Head title="O'qituvchilar" />

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">O'qituvchilar</h1>
            <p class="text-gray-500">Barcha o'qituvchilarni boshqarish</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Ism yoki telefon bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.level" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha darajalar</option>
                    <option value="new">Yangi</option>
                    <option value="verified">Tasdiqlangan</option>
                    <option value="featured">Featured</option>
                    <option value="top">Top</option>
                </select>

                <select v-model="filters.is_verified" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha</option>
                    <option value="verified">Tasdiqlangan</option>
                    <option value="unverified">Tasdiqlanmagan</option>
                </select>
            </div>
        </div>

        <!-- Teachers Table -->
        <DataTable :columns="columns" :rows="teachers.data" :loading="loading"
            empty-text="Hech qanday o'qituvchi topilmadi">
            <template #default="{ rows }">
                <tr v-for="teacher in rows" :key="teacher.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-medium text-gray-600">
                                {{ getInitials(teacher.full_name) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ teacher.full_name }}</p>
                                <p class="text-xs text-gray-500">{{ teacher.phone }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 text-center">
                        {{ teacher.total_courses }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 text-center">
                        {{ formatNumber(teacher.total_students) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <span class="text-yellow-500">⭐</span>
                            <span class="text-sm font-medium text-gray-900">{{ teacher.avg_rating }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getLevelBadgeVariant(teacher.level)" size="sm">
                            {{ getLevelLabel(teacher.level) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span v-if="teacher.is_verified" class="text-green-500 text-xl" title="Tasdiqlangan">✅</span>
                        <span v-else class="text-gray-400 text-xl" title="Tasdiqlanmagan">⏳</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <Link :href="`/admin/teachers/${teacher.id}`"
                                class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition"
                                title="Ko'rish">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            </Link>
                        </div>
                    </td>
                </tr>
            </template>
        </DataTable>

        <!-- Pagination -->
        <Pagination :data="teachers" />
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
    teachers: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    level: props.filters?.level || '',
    is_verified: props.filters?.is_verified || '',
});

const columns = [
    { key: 'name', label: 'O\'qituvchi', class: 'w-1/4' },
    { key: 'courses', label: 'Kurslar', class: 'text-center' },
    { key: 'students', label: 'Talabalar', class: 'text-center' },
    { key: 'rating', label: 'Rating', class: 'text-center' },
    { key: 'level', label: 'Daraja' },
    { key: 'verified', label: 'Tasdiqlangan', class: 'text-center' },
    { key: 'actions', label: 'Amallar' },
];

const search = () => {
    router.get('/admin/teachers', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/teachers', filters, {
        preserveState: true,
    });
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getLevelLabel = (level) => {
    const labels = {
        new: 'Yangi',
        verified: 'Tasdiqlangan',
        featured: 'Featured',
        top: 'Top',
    };
    return labels[level] || level;
};

const getLevelBadgeVariant = (level) => {
    const variants = {
        new: 'gray',
        verified: 'info',
        featured: 'warning',
        top: 'success',
    };
    return variants[level] || 'gray';
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('uz-UZ').format(num);
};
</script>
