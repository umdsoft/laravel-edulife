<template>
    <AdminLayout>

        <Head title="Xabarnoma Shablonlari" />

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Shablonlar</h1>
                <p class="text-gray-500">Tez-tez yuboriladigan xabarlar uchun shablonlar</p>
            </div>
            <div class="flex gap-2">
                <Link href="/admin/notifications/send"
                    class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl transition-colors">
                Xabar Yuborish
                </Link>
                <Button @click="openCreateModal">Yangi Shablon</Button>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="templates.data" :loading="loading" empty-text="Shablonlar topilmadi">
            <template #default="{ rows }">
                <tr v-for="template in rows" :key="template.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ template.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ template.title }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="{
                            'bg-blue-100 text-blue-800': template.type === 'all',
                            'bg-green-100 text-green-800': template.type === 'teachers',
                            'bg-purple-100 text-purple-800': template.type === 'students',
                        }">
                            {{ template.type }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                            {{ template.is_active ? 'Faol' : 'Nofaol' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button @click="openEditModal(template)"
                            class="text-blue-600 hover:text-blue-800 transition-colors" title="Tahrirlash">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="deleteTemplate(template)"
                            class="text-red-600 hover:text-red-800 transition-colors" title="O'chirish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </template>
        </DataTable>

        <Pagination :data="templates" />

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ editingTemplate ? 'Shablonni tahrirlash' : 'Yangi shablon' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <Input v-model="form.name" label="Shablon nomi" :error="form.errors.name" required />

                    <Input v-model="form.title" label="Xabar sarlavhasi" :error="form.errors.title" required />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Xabar matni</label>
                        <textarea v-model="form.message" rows="4"
                            class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20"
                            required></textarea>
                        <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kimga</label>
                        <select v-model="form.type"
                            class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20">
                            <option value="all">Barchaga</option>
                            <option value="teachers">O'qituvchilarga</option>
                            <option value="students">Talabalarga</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active"
                            class="rounded text-primary focus:ring-primary" />
                        <span class="text-sm text-gray-700">Faol</span>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <Button type="button" variant="secondary" @click="closeModal">Bekor qilish</Button>
                        <Button :loading="form.processing">{{ editingTemplate ? 'Saqlash' : 'Yaratish' }}</Button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

const props = defineProps({
    templates: Object,
});

const loading = ref(false);
const showModal = ref(false);
const editingTemplate = ref(null);

const form = useForm({
    name: '',
    title: '',
    message: '',
    type: 'all',
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'title', label: 'Sarlavha' },
    { key: 'type', label: 'Kimga' },
    { key: 'status', label: 'Holat' },
    { key: 'actions', label: '' },
];

const openCreateModal = () => {
    editingTemplate.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (template) => {
    editingTemplate.value = template;
    form.name = template.name;
    form.title = template.title;
    form.message = template.message;
    form.type = template.type;
    form.is_active = Boolean(template.is_active);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (editingTemplate.value) {
        form.put(`/admin/notifications/templates/${editingTemplate.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/notifications/templates', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTemplate = (template) => {
    if (confirm('Shablonni o\'chirmoqchimisiz?')) {
        router.delete(`/admin/notifications/templates/${template.id}`);
    }
};
</script>
