<template>
    <AdminLayout>

        <Head title="Yo'nalishlar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Yo'nalishlar</h1>
                <p class="text-gray-500">Kurs yo'nalishlarini boshqarish</p>
            </div>

            <Button variant="primary" @click="openCreateModal">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi yo'nalish
            </Button>
        </div>

        <!-- Directions Table -->
        <DataTable :columns="columns" :rows="directions" :loading="loading"
            empty-text="Hech qanday yo'nalish topilmadi">
            <template #default="{ rows }">
                <tr v-for="direction in rows" :key="direction.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 w-16">
                        <svg class="w-6 h-6 text-gray-400 cursor-move" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                        </svg>
                    </td>
                    <td class="px-6 py-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                            :style="{ backgroundColor: direction.color + '20' }">
                            {{ direction.icon }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ direction.name_uz }}</p>
                            <p class="text-xs text-gray-500">{{ direction.name_ru }} / {{ direction.name_en }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <code class="px-2 py-1 bg-gray-100 rounded text-xs">{{ direction.slug }}</code>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        {{ direction.courses_count }} ta
                    </td>
                    <td class="px-6 py-4">
                        <button @click="toggleStatus(direction)"
                            class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors focus:outline-none"
                            :class="direction.is_active ? 'bg-primary' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="direction.is_active ? 'translate-x-6' : 'translate-x-1'" />
                        </button>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button @click="openEditModal(direction)"
                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                title="Tahrirlash">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="confirmDelete(direction)"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                title="O'chirish" :disabled="direction.courses_count > 0"
                                :class="{ 'opacity-50 cursor-not-allowed': direction.courses_count > 0 }">
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

        <!-- Create/Edit Modal -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Yo\'nalishni tahrirlash' : 'Yangi yo\'nalish'"
            size="lg" @close="closeFormModal">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <Input v-model="formModal.data.name_uz" label="Nomi (O'zbek)" :error="formModal.errors.name_uz"
                        required />
                    <Input v-model="formModal.data.name_ru" label="Nomi (Rus)" :error="formModal.errors.name_ru"
                        required />
                    <Input v-model="formModal.data.name_en" label="Nomi (Ingliz)" :error="formModal.errors.name_en"
                        required />
                </div>

                <Input v-model="formModal.data.slug" label="Slug" :error="formModal.errors.slug"
                    placeholder="dasturlash" required />

                <div class="grid grid-cols-2 gap-4">
                    <Input v-model="formModal.data.icon" label="Icon (emoji)" :error="formModal.errors.icon"
                        placeholder="💻" required />
                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Rang
                        </label>
                        <input v-model="formModal.data.color" type="color"
                            class="w-full h-11 bg-gray-50 border-0 rounded-xl cursor-pointer" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                        Tavsif (ixtiyoriy)
                    </label>
                    <textarea v-model="formModal.data.description" rows="3"
                        class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary/20"></textarea>
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
            :message="`${deleteModal.direction?.name_uz} yo'nalishini o'chirishni xohlaysizmi?`"
            @confirm="deleteDirection" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/Admin/Modal.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';

const props = defineProps({
    directions: Array,
});

const loading = false;

const columns = [
    { key: 'drag', label: '', class: 'w-16' },
    { key: 'icon', label: 'Icon' },
    { key: 'name', label: 'Nomi' },
    { key: 'slug', label: 'Slug' },
    { key: 'courses', label: 'Kurslar' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Amallar' },
];

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: {
        name_uz: '',
        name_ru: '',
        name_en: '',
        slug: '',
        icon: '',
        color: '#7C3AED',
        description: '',
    },
    directionId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    direction: null,
});

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = {
        name_uz: '',
        name_ru: '',
        name_en: '',
        slug: '',
        icon: '',
        color: '#7C3AED',
        description: '',
    };
    formModal.errors = {};
};

const openEditModal = (direction) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.directionId = direction.id;
    formModal.data = {
        name_uz: direction.name_uz,
        name_ru: direction.name_ru,
        name_en: direction.name_en,
        slug: direction.slug,
        icon: direction.icon,
        color: direction.color,
        description: direction.description || '',
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
        ? route('admin.directions.update', formModal.directionId)
        : route('admin.directions.store');

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

const toggleStatus = (direction) => {
    router.patch(route('admin.directions.toggle-status', direction.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (direction) => {
    if (direction.courses_count > 0) {
        return;
    }
    deleteModal.direction = direction;
    deleteModal.show = true;
};

const deleteDirection = () => {
    deleteModal.loading = true;

    router.delete(route('admin.directions.destroy', deleteModal.direction.id), {
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.direction = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
