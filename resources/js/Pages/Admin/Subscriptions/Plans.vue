<template>
    <AdminLayout>

        <Head title="Obuna tariflari" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Obuna tariflari</h1>
                <p class="text-gray-500">Obuna rejalarini boshqarish</p>
            </div>

            <Button variant="primary" @click="openCreateModal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi tarif
            </Button>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="plan in plans" :key="plan.id" class="bg-white rounded-2xl shadow-sm p-6 relative"
                :class="{ 'ring-2 ring-primary': plan.is_featured }">
                <div v-if="plan.is_featured" class="absolute top-4 right-4">
                    <span class="text-2xl">⭐</span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ plan.description || 'Tavsif yo\'q' }}</p>

                <div class="mb-4">
                    <p class="text-3xl font-bold text-gray-900">
                        {{ formatCurrency(plan.price) }}
                        <span class="text-base font-normal text-gray-500">/{{ plan.interval === 'month' ? 'oy' : 'yil'
                            }}</span>
                    </p>
                    <p v-if="plan.annual_price" class="text-sm text-gray-500 mt-1">
                        Yillik: {{ formatCurrency(plan.annual_price) }}
                    </p>
                </div>

                <div v-if="plan.features && plan.features.length > 0" class="mb-4 space-y-2">
                    <div v-for="(feature, index) in plan.features" :key="index"
                        class="flex items-start gap-2 text-sm text-gray-700">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <span>{{ feature }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                    <button @click="openEditModal(plan)"
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-sm font-medium transition">
                        Tahrirlash
                    </button>
                    <button @click="confirmDelete(plan)"
                        class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-medium transition">
                        O'chirish
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Tarifni tahrirlash' : 'Yangi tarif'" size="lg"
            @close="closeFormModal">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <Input v-model="formModal.data.name" label="Nomi" :error="formModal.errors.name" required />
                    <Input v-model="formModal.data.slug" label="Slug" :error="formModal.errors.slug" required />
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                        Tavsif
                    </label>
                    <textarea v-model="formModal.data.description" rows="2"
                        class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model.number="formModal.data.price" type="number" label="Oylik narx (UZS)"
                        :error="formModal.errors.price" required />
                    <Input v-model.number="formModal.data.annual_price" type="number" label="Yillik narx (UZS)" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Davr
                        </label>
                        <select v-model="formModal.data.interval"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                            <option value="month">Oy</option>
                            <option value="year">Yil</option>
                        </select>
                    </div>
                    <Input v-model.number="formModal.data.interval_count" type="number" label="Davr soni"
                        :error="formModal.errors.interval_count" required />
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="formModal.data.is_featured" type="checkbox"
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary" />
                        <span class="text-sm text-gray-700">Featured (⭐ bilan)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="formModal.data.is_active" type="checkbox"
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary" />
                        <span class="text-sm text-gray-700">Faol</span>
                    </label>
                </div>
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
            :message="`${deleteModal.plan?.name} tarifini o'chirishni xohlaysizmi?`" @confirm="deletePlan"
            @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/Admin/Modal.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';

const props = defineProps({
    plans: Array,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: {
        name: '',
        slug: '',
        description: '',
        price: 0,
        annual_price: null,
        interval: 'month',
        interval_count: 1,
        is_featured: false,
        is_active: true,
    },
    planId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    plan: null,
});

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = {
        name: '',
        slug: '',
        description: '',
        price: 0,
        annual_price: null,
        interval: 'month',
        interval_count: 1,
        is_featured: false,
        is_active: true,
    };
    formModal.errors = {};
};

const openEditModal = (plan) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.planId = plan.id;
    formModal.data = {
        name: plan.name,
        slug: plan.slug,
        description: plan.description || '',
        price: plan.price,
        annual_price: plan.annual_price,
        interval: plan.interval,
        interval_count: plan.interval_count,
        is_featured: plan.is_featured,
        is_active: plan.is_active,
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
        ? `/admin/subscription-plans/${formModal.planId}`
        : '/admin/subscription-plans';

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

const confirmDelete = (plan) => {
    deleteModal.plan = plan;
    deleteModal.show = true;
};

const deletePlan = () => {
    deleteModal.loading = true;

    router.delete(`/admin/subscription-plans/${deleteModal.plan.id}`, {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.plan = null;
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
