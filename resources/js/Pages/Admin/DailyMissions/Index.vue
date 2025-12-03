<template>
    <AdminLayout>

        <Head title="Kunlik vazifalar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kunlik vazifalar</h1>
                <p class="text-gray-500">Har kuni yangilanadigan vazifalar</p>
            </div>

            <Button variant="primary" @click="openCreateModal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi vazifa
            </Button>
        </div>

        <!-- Missions Table -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                            Icon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vazifa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tur
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Maqsad</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">XP
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            COIN</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Qiyinlik</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Faol</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="mission in missions" :key="mission.id"
                        class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-center text-2xl">{{ mission.icon }}</td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ mission.title }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ mission.description }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ getTypeLabel(mission.type) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium text-center">{{ mission.target_count }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-center">{{ mission.xp_reward }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 text-center">{{ mission.coin_reward }}</td>
                        <td class="px-6 py-4 text-center">
                            <Badge :variant="getDifficultyBadgeVariant(mission.difficulty)" size="sm">
                                {{ getDifficultyLabel(mission.difficulty) }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div @click="toggleStatus(mission)" class="inline-flex items-center cursor-pointer">
                                <div :class="[
                                    'relative inline-block w-10 h-6 transition duration-200 ease-in-out rounded-full',
                                    mission.is_active ? 'bg-green-500' : 'bg-gray-300',
                                ]">
                                    <span :class="[
                                        'absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200',
                                        mission.is_active ? 'transform translate-x-4' : '',
                                    ]"></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="openEditModal(mission)"
                                    class="p-2 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition"
                                    title="Tahrirlash">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="confirmDelete(mission)"
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
                </tbody>
            </table>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Vazifani tahrirlash' : 'Yangi vazifa'" size="lg"
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
                            Tur *
                        </label>
                        <select v-model="formModal.data.type"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                            <option value="lessons">Darslar</option>
                            <option value="tests">Testlar</option>
                            <option value="battles">Battlelar</option>
                            <option value="battle_wins">Battle g'alabalar</option>
                            <option value="watch_time">Ko'rish vaqti (soniya)</option>
                            <option value="login">Login</option>
                            <option value="streak">Streak</option>
                        </select>
                    </div>

                    <Input v-model.number="formModal.data.target_count" type="number" label="Maqsad" required />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <Input v-model.number="formModal.data.xp_reward" type="number" label="XP mukofoti" required />
                    <Input v-model.number="formModal.data.coin_reward" type="number" label="COIN mukofoti" required />

                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Qiyinlik *
                        </label>
                        <select v-model="formModal.data.difficulty"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                            <option value="easy">Oson</option>
                            <option value="medium">O'rta</option>
                            <option value="hard">Qiyin</option>
                        </select>
                    </div>
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
            :message="`${deleteModal.mission?.title} vazifasini o'chirishni xohlaysizmi?`" @confirm="deleteMission"
            @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Admin/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/Admin/Modal.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';

const props = defineProps({
    missions: Array,
});

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
        type: 'lessons',
        target_count: 1,
        xp_reward: 0,
        coin_reward: 0,
        difficulty: 'easy',
        is_active: true,
    },
    missionId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    mission: null,
});

const toggleStatus = (mission) => {
    router.patch(`/admin/daily-missions/${mission.id}/toggle-status`, {}, {
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
        type: 'lessons',
        target_count: 1,
        xp_reward: 0,
        coin_reward: 0,
        difficulty: 'easy',
        is_active: true,
    };
    formModal.errors = {};
};

const openEditModal = (mission) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.missionId = mission.id;
    formModal.data = { ...mission };
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
        ? `/admin/daily-missions/${formModal.missionId}`
        : '/admin/daily-missions';

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

const confirmDelete = (mission) => {
    deleteModal.mission = mission;
    deleteModal.show = true;
};

const deleteMission = () => {
    deleteModal.loading = true;

    router.delete(`/admin/daily-missions/${deleteModal.mission.id}`, {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.mission = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

const getTypeLabel = (type) => {
    const labels = {
        lessons: 'Darslar',
        tests: 'Testlar',
        battles: 'Battlelar',
        battle_wins: 'Battle g\'alabalar',
        watch_time: 'Ko\'rish vaqti',
        login: 'Login',
        streak: 'Streak',
    };
    return labels[type] || type;
};

const getDifficultyLabel = (difficulty) => {
    const labels = {
        easy: 'Oson',
        medium: 'O\'rta',
        hard: 'Qiyin',
    };
    return labels[difficulty] || difficulty;
};

const getDifficultyBadgeVariant = (difficulty) => {
    const variants = {
        easy: 'success',
        medium: 'warning',
        hard: 'danger',
    };
    return variants[difficulty] || 'gray';
};
</script>
