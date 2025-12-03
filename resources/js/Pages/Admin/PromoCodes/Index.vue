<template>
    <AdminLayout>

        <Head title="Promo kodlar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Promo kodlar</h1>
                <p class="text-gray-500">Chegirma kodlarini boshqarish</p>
            </div>

            <Button variant="primary" @click="openCreateModal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi kod
            </Button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Kod bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.type" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha turlar</option>
                    <option value="percent">Foiz (%)</option>
                    <option value="fixed">Fix miqdor</option>
                </select>

                <select v-model="filters.status" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha statuslar</option>
                    <option value="active">Faol</option>
                    <option value="inactive">Faol emas</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="promoCodes.data" :loading="loading"
            empty-text="Hech qanday promo kod topilmadi">
            <template #default="{ rows }">
                <tr v-for="code in rows" :key="code.id" class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <span class="font-mono font-bold text-primary">{{ code.code }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="code.type === 'percent' ? 'warning' : 'info'" size="sm">
                            {{ code.type === 'percent' ? '%' : 'Fix' }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900">
                        {{ code.type === 'percent' ? `${code.value}%` : formatCurrency(code.value) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-center">
                        {{ code.used_count }} / {{ code.max_uses || '∞' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ code.expires_at || '-' }}
                    </td>
                    <td class="px-6 py-4">
                        <Badge v-if="code.is_expired" variant="danger">
                            Muddati o'tgan
                        </Badge>
                        <Badge v-else-if="code.is_maxed_out" variant="gray">
                            Limitga yetgan
                        </Badge>
                        <Badge v-else-if="code.is_active" variant="success">
                            Faol
                        </Badge>
                        <Badge v-else variant="gray">
                            Faol emas
                        </Badge>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button @click="openEditModal(code)"
                                class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition"
                                title="Tahrirlash">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="confirmDelete(code)"
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
        <Pagination :data="promoCodes" />

        <!-- Create/Edit Modal -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Promo kodni tahrirlash' : 'Yangi promo kod'" size="lg"
            @close="closeFormModal">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <Input v-model="formModal.data.code" label="Kod (avtomatik uppercase)"
                        :error="formModal.errors.code" required maxlength="20" />

                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Tur *
                        </label>
                        <select v-model="formModal.data.type"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                            <option value="percent">Foiz (%)</option>
                            <option value="fixed">Fix miqdor (UZS)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model.number="formModal.data.value" type="number"
                        :label="formModal.data.type === 'percent' ? 'Foiz (%)' : 'Miqdor (UZS)'"
                        :error="formModal.errors.value" required />
                    <Input v-model.number="formModal.data.min_amount" type="number" label="Minimal summa (UZS)" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model.number="formModal.data.max_uses" type="number"
                        label="Max foydalanish (bo'sh = cheksiz)" />
                    <Input v-model.number="formModal.data.max_uses_per_user" type="number" label="User uchun max" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model="formModal.data.starts_at" type="date" label="Boshlanish sanasi" />
                    <Input v-model="formModal.data.expires_at" type="date" label="Tugash sanasi" />
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="formModal.data.is_active" type="checkbox"
                        class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary" />
                    <span class="text-sm text-gray-700">Faol</span>
                </label>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <Button variant="secondary" @click="closeFormModal" :disabled="formModal.loading">
                        Bekor qilish
                    </Button>
                    <Button variant="primary" @click="submitForm" :loading="formModal.loading">
                        {{ formModal.isEdit ? 'Yangilash' : 'Saqlash' }}
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Delete Confirmation -->
        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading"
            :message="`${deleteModal.code?.code} kodini o'chirishni xohlaysizmi?`" @confirm="deleteCode"
            @cancel="deleteModal.show = false" />
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
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/Admin/Modal.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';

const props = defineProps({
    promoCodes: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    status: props.filters?.status || '',
});

const columns = [
    { key: 'code', label: 'Kod' },
    { key: 'type', label: 'Tur' },
    { key: 'value', label: 'Qiymat' },
    { key: 'used', label: 'Foydalanilgan', class: 'text-center' },
    { key: 'expires', label: 'Muddati' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Amallar' },
];

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: {
        code: '',
        type: 'percent',
        value: 0,
        min_amount: null,
        max_uses: null,
        max_uses_per_user: 1,
        starts_at: null,
        expires_at: null,
        is_active: true,
    },
    codeId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    code: null,
});

const search = () => {
    router.get('/admin/promo-codes', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/promo-codes', filters, {
        preserveState: true,
    });
};

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = {
        code: '',
        type: 'percent',
        value: 0,
        min_amount: null,
        max_uses: null,
        max_uses_per_user: 1,
        starts_at: null,
        expires_at: null,
        is_active: true,
    };
    formModal.errors = {};
};

const openEditModal = (code) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.codeId = code.id;
    formModal.data = {
        code: code.code,
        type: code.type,
        value: code.value,
        min_amount: code.min_amount,
        max_uses: code.max_uses,
        max_uses_per_user: code.max_uses_per_user || 1,
        starts_at: code.starts_at,
        expires_at: code.expires_at,
        is_active: code.is_active,
    };
    formModal.errors = {};
};

const closeFormModal = () => {
    formModal.show = false;
    formModal.errors = {};
};

const submitForm = () => {
    formModal.loading = true;
    formModal.errors = {};

    const url = formModal.isEdit
        ? `/admin/promo-codes/${formModal.codeId}`
        : '/admin/promo-codes';

    const method = formModal.isEdit ? 'put' : 'post';

    router[method](url, formModal.data, {
        onSuccess: () => {
            closeFormModal();
        },
        onError: (errors) => {
            formModal.errors = errors;
        },
        onFinish: () => {
            formModal.loading = false;
        },
    });
};

const confirmDelete = (code) => {
    deleteModal.code = code;
    deleteModal.show = true;
};

const deleteCode = () => {
    deleteModal.loading = true;

    router.delete(`/admin/promo-codes/${deleteModal.code.id}`, {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.code = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>
