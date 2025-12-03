<template>
    <AdminLayout>

        <Head title="COIN Paketlari" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">COIN Paketlari</h1>
                <p class="text-gray-500">COIN sotib olish paketlarini boshqarish</p>
            </div>

            <Button variant="primary" @click="openCreateModal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi paket
            </Button>
        </div>

        <!-- Packages Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="pkg in packages" :key="pkg.id" class="bg-white rounded-2xl shadow-sm p-6 relative"
                :class="{ 'ring-2 ring-primary': pkg.is_featured }">
                <div v-if="pkg.badge"
                    class="absolute top-4 right-4 bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded-lg uppercase">
                    {{ pkg.badge }}
                </div>

                <div class="flex items-center justify-center w-16 h-16 bg-yellow-50 rounded-full mx-auto mb-4 text-3xl">
                    🪙
                </div>

                <h3 class="text-xl font-bold text-center text-gray-900 mb-1">{{ pkg.name }}</h3>
                <p class="text-center text-gray-500 mb-4">{{ formatNumber(pkg.total_coins) }} COIN</p>

                <div class="text-center mb-6">
                    <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(pkg.price) }}</p>
                    <p v-if="pkg.original_price" class="text-sm text-gray-400 line-through">
                        {{ formatCurrency(pkg.original_price) }}
                    </p>
                </div>

                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Asosiy:</span>
                        <span class="font-medium">{{ formatNumber(pkg.coins) }}</span>
                    </div>
                    <div v-if="pkg.bonus_coins" class="flex justify-between text-sm text-green-600">
                        <span>Bonus:</span>
                        <span class="font-medium">+{{ formatNumber(pkg.bonus_coins) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                    <button @click="openEditModal(pkg)"
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-sm font-medium transition">
                        Tahrirlash
                    </button>
                    <button @click="toggleStatus(pkg)" :class="[
                        'px-4 py-2 rounded-xl text-sm font-medium transition',
                        pkg.is_active ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'
                    ]" :title="pkg.is_active ? 'Faol' : 'Faol emas'">
                        {{ pkg.is_active ? '✅' : '🚫' }}
                    </button>
                    <button @click="confirmDelete(pkg)"
                        class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-medium transition"
                        title="O'chirish">
                        🗑️
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Paketni tahrirlash' : 'Yangi paket'" size="lg"
            @close="closeFormModal">
            <form @submit.prevent="submitForm" class="space-y-4">
                <Input v-model="formModal.data.name" label="Paket nomi" :error="formModal.errors.name" required />

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model.number="formModal.data.coins" type="number" label="Asosiy COIN"
                        :error="formModal.errors.coins" required />
                    <Input v-model.number="formModal.data.bonus_coins" type="number" label="Bonus COIN" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model.number="formModal.data.price" type="number" label="Narx (UZS)"
                        :error="formModal.errors.price" required />
                    <Input v-model.number="formModal.data.original_price" type="number"
                        label="Asl narx (Chegirma uchun)" />
                </div>

                <Input v-model="formModal.data.badge" label="Badge (masalan: BEST VALUE)" placeholder="Ixtiyoriy" />

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="formModal.data.is_featured" type="checkbox"
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary" />
                        <span class="text-sm text-gray-700">Featured (ajratib ko'rsatish)</span>
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
            :message="`${deleteModal.pkg?.name} paketini o'chirishni xohlaysizmi?`" @confirm="deletePackage"
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
    packages: Array,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: {
        name: '',
        coins: 100,
        bonus_coins: 0,
        price: 0,
        original_price: null,
        badge: '',
        is_featured: false,
        is_active: true,
    },
    pkgId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    pkg: null,
});

const toggleStatus = (pkg) => {
    router.patch(`/admin/coin-packages/${pkg.id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = {
        name: '',
        coins: 100,
        bonus_coins: 0,
        price: 0,
        original_price: null,
        badge: '',
        is_featured: false,
        is_active: true,
    };
    formModal.errors = {};
};

const openEditModal = (pkg) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.pkgId = pkg.id;
    formModal.data = {
        name: pkg.name,
        coins: pkg.coins,
        bonus_coins: pkg.bonus_coins,
        price: pkg.price,
        original_price: pkg.original_price,
        badge: pkg.badge,
        is_featured: pkg.is_featured,
        is_active: pkg.is_active,
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
        ? `/admin/coin-packages/${formModal.pkgId}`
        : '/admin/coin-packages';

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

const confirmDelete = (pkg) => {
    deleteModal.pkg = pkg;
    deleteModal.show = true;
};

const deletePackage = () => {
    deleteModal.loading = true;

    router.delete(`/admin/coin-packages/${deleteModal.pkg.id}`, {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.pkg = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('uz-UZ').format(num);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>
