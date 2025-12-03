<template>
    <AdminLayout>

        <Head title="Yutuqlar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Yutuqlar (Achievements)</h1>
                <p class="text-gray-500">Yutuqlarni boshqarish</p>
            </div>

            <Button variant="primary" @click="openCreateModal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi yutuq
            </Button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Sarlavha yoki kod bo'yicha qidirish..."
                    @update:model-value="search" />

                <select v-model="filters.category" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha kategoriyalar</option>
                    <option value="learning">O'qish</option>
                    <option value="testing">Test</option>
                    <option value="battle">Battle</option>
                    <option value="tournament">Turnir</option>
                    <option value="streak">Streak</option>
                    <option value="social">Ijtimoiy</option>
                    <option value="special">Maxsus</option>
                </select>

                <select v-model="filters.rarity" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha kamyobliklar</option>
                    <option value="common">Oddiy</option>
                    <option value="rare">Kam uchrayd</option>
                    <option value="epic">Epik</option>
                    <option value="legendary">Afsonaviy</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="achievements.data" :loading="loading"
            empty-text="Hech qanday yutuq topilmadi">
            <template #default="{ rows }">
                <tr v-for="achievement in rows" :key="achievement.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-center text-2xl">
                        {{ achievement.icon }}
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900">{{ achievement.title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ achievement.description }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 capitalize">
                        {{ getCategoryLabel(achievement.category) }}
                    </td>
                    <td class="px-6 py-4">
                        <Badge :variant="getRarityBadgeVariant(achievement.rarity)" size="sm">
                            {{ getRarityLabel(achievement.rarity) }}
                        </Badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 text-center">
                        {{ achievement.xp_reward }} XP
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 text-center">
                        {{ achievement.coin_reward }} 🪙
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div @click="toggleStatus(achievement)" class="inline-flex items-center cursor-pointer">
                            <div :class="[
                                'relative inline-block w-10 h-6 transition duration-200 ease-in-out rounded-full',
                                achievement.is_active ? 'bg-green-500' : 'bg-gray-300',
                            ]">
                                <span :class="[
                                    'absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200',
                                    achievement.is_active ? 'transform translate-x-4' : '',
                                ]"></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button @click="openEditModal(achievement)"
                                class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition"
                                title="Tahrirlash">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="confirmDelete(achievement)"
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
        <Pagination :data="achievements" />

        <!-- Create/Edit Modal -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Yutuqni tahrirlash' : 'Yangi yutuq'" size="lg"
            @close="closeFormModal">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <Input v-model="formModal.data.code" label="Kod" :error="formModal.errors.code" required />
                    <Input v-model="formModal.data.icon" label="Icon (emoji)" :error="formModal.errors.icon" required
                        maxlength="10" />
                </div>

                <Input v-model="formModal.data.title" label="Sarlavha" :error="formModal.errors.title" required />

                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                        Tavsif
                    </label>
                    <textarea v-model="formModal.data.description" rows="2"
                        class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Kategoriya *
                        </label>
                        <select v-model="formModal.data.category"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                            <option value="learning">O'qish</option>
                            <option value="testing">Test</option>
                            <option value="battle">Battle</option>
                            <option value="tournament">Turnir</option>
                            <option value="streak">Streak</option>
                            <option value="social">Ijtimoiy</option>
                            <option value="special">Maxsus</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Kamyoblik *
                        </label>
                        <select v-model="formModal.data.rarity"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                            <option value="common">Oddiy</option>
                            <option value="rare">Kam uchraydigan</option>
                            <option value="epic">Epik</option>
                            <option value="legendary">Afsonaviy</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model.number="formModal.data.xp_reward" type="number" label="XP mukofoti" required />
                    <Input v-model.number="formModal.data.coin_reward" type="number" label="COIN mukofoti" required />
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="formModal.data.is_hidden" type="checkbox"
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary" />
                        <span class="text-sm text-gray-700">Yashirin</span>
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
            :message="`${deleteModal.achievement?.title} yutuqini o'chirishni xohlaysizmi?`"
            @confirm="deleteAchievement" @cancel="deleteModal.show = false" />
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
    achievements: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    rarity: props.filters?.rarity || '',
});

const columns = [
    { key: 'icon', label: 'Icon', class: 'w-16' },
    { key: 'title', label: 'Sarlavha', class: 'w-1/3' },
    { key: 'category', label: 'Kategoriya' },
    { key: 'rarity', label: 'Kamyoblik' },
    { key: 'xp', label: 'XP', class: 'text-center' },
    { key: 'coin', label: 'COIN', class: 'text-center' },
    { key: 'active', label: 'Faol', class: 'text-center' },
    { key: 'actions', label: 'Amallar' },
];

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: {
        code: '',
        title: '',
        description: '',
        icon: '',
        category: 'learning',
        rarity: 'common',
        xp_reward: 0,
        coin_reward: 0,
        is_hidden: false,
        is_active: true,
    },
    achievementId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    achievement: null,
});

const search = () => {
    router.get('/admin/achievements', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/achievements', filters, {
        preserveState: true,
    });
};

const toggleStatus = (achievement) => {
    router.patch(`/admin/achievements/${achievement.id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = {
        code: '',
        title: '',
        description: '',
        icon: '',
        category: 'learning',
        rarity: 'common',
        xp_reward: 0,
        coin_reward: 0,
        is_hidden: false,
        is_active: true,
    };
    formModal.errors = {};
};

const openEditModal = (achievement) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.achievementId = achievement.id;
    formModal.data = {
        code: achievement.code,
        title: achievement.title,
        description: achievement.description,
        icon: achievement.icon,
        category: achievement.category,
        rarity: achievement.rarity,
        xp_reward: achievement.xp_reward,
        coin_reward: achievement.coin_reward,
        is_hidden: achievement.is_hidden,
        is_active: achievement.is_active,
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
        ? `/admin/achievements/${formModal.achievementId}`
        : '/admin/achievements';

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

const confirmDelete = (achievement) => {
    deleteModal.achievement = achievement;
    deleteModal.show = true;
};

const deleteAchievement = () => {
    deleteModal.loading = true;

    router.delete(`/admin/achievements/${deleteModal.achievement.id}`, {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.achievement = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

const getCategoryLabel = (category) => {
    const labels = {
        learning: 'O\'qish',
        testing: 'Test',
        battle: 'Battle',
        tournament: 'Turnir',
        streak: 'Streak',
        social: 'Ijtimoiy',
        special: 'Maxsus',
    };
    return labels[category] || category;
};

const getRarityLabel = (rarity) => {
    const labels = {
        common: 'Oddiy',
        rare: 'Kam uchraydigan',
        epic: 'Epik',
        legendary: 'Afsonaviy',
    };
    return labels[rarity] || rarity;
};

const getrarityBadgeVariant = (rarity) => {
    const variants = {
        common: 'gray',
        rare: 'info',
        epic: 'warning',
        legendary: 'success',
    };
    return variants[rarity] || 'gray';
};
</script>
