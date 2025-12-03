<template>
    <AdminLayout>

        <Head title="Teglar" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Teglar</h1>
            <Button @click="openCreateModal">Yangi Teg</Button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Qidirish..." @update:model-value="search" />

                <select v-model="filters.type" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha turlar</option>
                    <option value="course">Kurslar</option>
                    <option value="blog">Blog</option>
                    <option value="forum">Forum</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="tags.data" :loading="loading" empty-text="Teglar topilmadi">
            <template #default="{ rows }">
                <tr v-for="tag in rows" :key="tag.id" class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ tag.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ tag.slug }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="{
                            'bg-blue-100 text-blue-800': tag.type === 'course',
                            'bg-green-100 text-green-800': tag.type === 'blog',
                            'bg-purple-100 text-purple-800': tag.type === 'forum',
                        }">
                            {{ tag.type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ (tag.courses_count || 0) + (tag.posts_count || 0) }}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button @click="openEditModal(tag)" class="text-blue-600 hover:text-blue-800 transition-colors"
                            title="Tahrirlash">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="deleteTag(tag)" class="text-red-600 hover:text-red-800 transition-colors"
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

        <Pagination :data="tags" />

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ editingTag ? 'Tegni tahrirlash' : 'Yangi teg' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <Input v-model="form.name" label="Nom" :error="form.errors.name" required />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tur</label>
                        <select v-model="form.type"
                            class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20">
                            <option value="course">Kurs</option>
                            <option value="blog">Blog</option>
                            <option value="forum">Forum</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <Button type="button" variant="secondary" @click="closeModal">Bekor qilish</Button>
                        <Button :loading="form.processing">{{ editingTag ? 'Saqlash' : 'Yaratish' }}</Button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';

const props = defineProps({
    tags: Object,
    filters: Object,
});

const loading = ref(false);
const showModal = ref(false);
const editingTag = ref(null);

const filters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
});

const form = useForm({
    name: '',
    type: 'course',
});

const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'slug', label: 'Slug' },
    { key: 'type', label: 'Tur' },
    { key: 'usage', label: 'Ishlatilishi' },
    { key: 'actions', label: '' },
];

const search = () => {
    router.get('/admin/tags', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/tags', filters, {
        preserveState: true,
    });
};

const openCreateModal = () => {
    editingTag.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (tag) => {
    editingTag.value = tag;
    form.name = tag.name;
    form.type = tag.type;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (editingTag.value) {
        form.put(`/admin/tags/${editingTag.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/tags', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteTag = (tag) => {
    if (confirm('Tegni o\'chirmoqchimisiz?')) {
        router.delete(`/admin/tags/${tag.id}`);
    }
};
</script>
