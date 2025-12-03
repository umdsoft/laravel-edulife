<template>
    <AdminLayout>

        <Head title="Izohlar" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Izohlar</h1>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Qidirish..." @update:model-value="search" />

                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha holatlar</option>
                    <option value="pending">Kutilmoqda</option>
                    <option value="approved">Tasdiqlangan</option>
                    <option value="rejected">Rad etilgan</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="reviews.data" :loading="loading" empty-text="Izohlar topilmadi">
            <template #default="{ rows }">
                <tr v-for="review in rows" :key="review.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ review.user?.full_name || review.user?.first_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ review.course?.title }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center text-yellow-400">
                            <span class="font-bold text-gray-900 mr-1">{{ review.rating }}</span>
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="{
                            'bg-yellow-100 text-yellow-800': review.status === 'pending',
                            'bg-green-100 text-green-800': review.status === 'approved',
                            'bg-red-100 text-red-800': review.status === 'rejected',
                        }">
                            {{ review.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ new Date(review.created_at).toLocaleDateString() }}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button @click="openModal(review)" class="text-blue-600 hover:text-blue-800 transition-colors"
                            title="Ko'rish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        <button @click="deleteReview(review)" class="text-red-600 hover:text-red-800 transition-colors"
                            title="O'chirish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </template>
        </DataTable>

        <Pagination :data="reviews" />

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6" v-if="selectedReview">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Izoh tafsilotlari</h2>

                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500 block">Foydalanuvchi</span>
                        <span class="text-gray-900 font-medium">{{ selectedReview.user?.full_name ||
                            selectedReview.user?.first_name }}</span>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500 block">Kurs</span>
                        <span class="text-gray-900">{{ selectedReview.course?.title }}</span>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500 block">Reyting</span>
                        <div class="flex items-center text-yellow-400">
                            <span class="font-bold text-gray-900 mr-1">{{ selectedReview.rating }}</span>
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500 block">Izoh</span>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-lg mt-1">{{ selectedReview.content }}</p>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <Button type="button" variant="secondary" @click="closeModal">Yopish</Button>
                        <Button v-if="selectedReview.status === 'pending'" variant="danger"
                            @click="reject(selectedReview)">
                            Rad etish
                        </Button>
                        <Button v-if="selectedReview.status === 'pending'" variant="primary"
                            @click="approve(selectedReview)">
                            Tasdiqlash
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    reviews: Object,
    filters: Object,
});

const loading = ref(false);
const showModal = ref(false);
const selectedReview = ref(null);

const filters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const columns = [
    { key: 'user', label: 'Foydalanuvchi' },
    { key: 'course', label: 'Kurs' },
    { key: 'rating', label: 'Reyting' },
    { key: 'status', label: 'Holat' },
    { key: 'date', label: 'Sana' },
    { key: 'actions', label: '' },
];

const search = () => {
    router.get('/admin/reviews', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/reviews', filters, {
        preserveState: true,
    });
};

const openModal = (review) => {
    selectedReview.value = review;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedReview.value = null;
};

const approve = (review) => {
    router.patch(`/admin/reviews/${review.id}/approve`, {}, {
        onSuccess: () => closeModal(),
    });
};

const reject = (review) => {
    router.patch(`/admin/reviews/${review.id}/reject`, {}, {
        onSuccess: () => closeModal(),
    });
};

const deleteReview = (review) => {
    if (confirm('Izohni o\'chirmoqchimisiz?')) {
        router.delete(`/admin/reviews/${review.id}`);
    }
};
</script>
