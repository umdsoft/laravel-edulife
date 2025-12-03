<template>
    <AdminLayout>

        <Head title="Kurslar" />

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Kurslar</h1>
            <p class="text-gray-500">Barcha kurslarni ko'rish va boshqarish</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Kurs nomi bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.direction_id" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha yo'nalishlar</option>
                    <option v-for="direction in directions" :key="direction.id" :value="direction.id">
                        {{ direction.name_uz }}
                    </option>
                </select>

                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha statuslar</option>
                    <option value="draft">Qoralama</option>
                    <option value="pending">Kutilmoqda</option>
                    <option value="published">Nashr qilingan</option>
                    <option value="rejected">Rad etilgan</option>
                </select>
            </div>
        </div>

        <!-- Courses Table -->
        <DataTable :columns="columns" :rows="courses.data" :loading="loading" empty-text="Hech qanday kurs topilmadi">
            <template #default="{ rows }">
                <tr v-for="course in rows" :key="course.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img v-if="course.thumbnail" :src="course.thumbnail" :alt="course.title"
                                class="w-16 h-12 object-cover rounded-lg" />
                            <div v-else
                                class="w-16 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-2xl">
                                📚
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ course.title }}</p>
                                <p class="text-xs text-gray-500">{{ course.direction_name }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ course.teacher_name }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ formatCurrency(course.price) }}
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getStatusBadgeVariant(course.status)">
                            {{ getStatusLabel(course.status) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ course.created_at }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.courses.show', course.id)"
                                class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition"
                                title="Ko'rish">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            </Link>
                            <button @click="confirmDelete(course)"
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
        <Pagination :data="courses" />

        <!-- Delete Confirmation -->
        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading"
            :message="`${deleteModal.course?.title} kursini o'chirishni xohlaysizmi?`" @confirm="deleteCourse"
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
    courses: Object,
    directions: Array,
    filters: Object,
});

const loading = ref(false);
const deleteModal = reactive({
    show: false,
    loading: false,
    course: null,
});

const filters = reactive({
    search: props.filters?.search || '',
    direction_id: props.filters?.direction_id || '',
    status: props.filters?.status || '',
});

const columns = [
    { key: 'title', label: 'Kurs', class: 'w-1/3' },
    { key: 'teacher', label: 'O\'qituvchi' },
    { key: 'price', label: 'Narx' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Sana' },
    { key: 'actions', label: 'Amallar' },
];

const search = () => {
    router.get(route('admin.courses.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get(route('admin.courses.index'), filters, {
        preserveState: true,
    });
};

const confirmDelete = (course) => {
    deleteModal.course = course;
    deleteModal.show = true;
};

const deleteCourse = () => {
    deleteModal.loading = true;

    router.delete(route('admin.courses.destroy', deleteModal.course.id), {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.course = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Qoralama',
        pending: 'Kutilmoqda',
        published: 'Nashr qilingan',
        rejected: 'Rad etilgan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        draft: 'gray',
        pending: 'warning',
        published: 'success',
        rejected: 'danger',
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
