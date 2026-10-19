<template>
    <AdminLayout>

        <Head title="Teglar" />

        <PageHeader title="Teglar" description="Kurslarni belgilash uchun teglar">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi teg</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <FilterBar :active="!!filters.search" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Teg nomi" class="sm:w-72"
                    @update:model-value="search" />
                <template v-if="tags.total !== undefined" #meta>Jami {{ formatNumber(tags.total) }} ta</template>
            </FilterBar>

            <Table :columns="columns" :rows="tags.data" :loading="loading" caption="Teglar ro'yxati">
                <template #cell-name="{ row }">
                    <span class="inline-flex items-center gap-2 font-medium text-fg">
                        <TagIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                        {{ row.name }}
                    </span>
                </template>
                <template #cell-slug="{ value }">
                    <code class="rounded-sm bg-surface-2 px-1.5 py-0.5 font-mono text-xs text-fg-muted">{{ value }}</code>
                </template>
                <template #cell-usage="{ row }">{{ formatNumber((row.courses_count || 0) + (row.posts_count || 0)) }}</template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.name}: amallar`">
                        <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="TagIcon" title="Teglar topilmadi" size="sm"
                        :description="filters.search ? 'Boshqa so\'z bilan qidirib ko\'ring' : 'Birinchi tegni yarating'">
                        <Button v-if="filters.search" variant="secondary" size="sm" @click="resetFilters">
                            Qidiruvni tozalash
                        </Button>
                        <Button v-else size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi teg</Button>
                    </EmptyState>
                </template>
                <template v-if="tags.links?.length > 3" #footer>
                    <Pagination :data="tags" class="!border-t-0" />
                </template>
            </Table>
        </div>

        <!-- Create / edit -->
        <Modal :show="showModal" :title="editingTag ? 'Tegni tahrirlash' : 'Yangi teg'" size="md"
            :closeable="!form.processing" @close="closeModal">
            <form id="tag-form" class="space-y-5" @submit.prevent="submit">
                <Input v-model="form.name" label="Nomi" :error="form.errors.name" required autofocus
                    hint="Slug nomdan avtomatik yaratiladi" />
                <Select v-model="form.type" label="Tur" :options="typeOptions" :error="form.errors.type" />
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="form.processing" @click="closeModal">Bekor qilish</Button>
                <Button type="submit" form="tag-form" :loading="form.processing">
                    {{ editingTag ? 'Saqlash' : 'Yaratish' }}
                </Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Tegni o'chirish"
            :message="`«${deleteModal.tag?.name}» tegi o'chiriladi va kurslardan olib tashlanadi.`"
            @confirm="deleteTag" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PencilSquareIcon, PlusIcon, TagIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Button, DropdownItem, EmptyState, Input, Modal, PageHeader, Pagination, Select, Table,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    tags: Object,
    filters: Object,
});

const loading = ref(false);
const showModal = ref(false);
const editingTag = ref(null);
const deleteModal = reactive({ show: false, loading: false, tag: null });

const filters = reactive({
    search: props.filters?.search || '',
});

const form = useForm({
    name: '',
    type: 'course',
});

const typeOptions = [
    { value: 'course', label: 'Kurs' },
    { value: 'blog', label: 'Blog' },
    { value: 'forum', label: 'Forum' },
];

const columns = [
    { key: 'name', label: 'Nomi' },
    { key: 'slug', label: 'Slug', hideBelow: 'sm' },
    { key: 'usage', label: 'Ishlatilgan', numeric: true },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const visit = (options = {}) => {
    router.get(route('admin.tags.index'), filters, {
        preserveState: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
        ...options,
    });
};

const search = () => visit({ preserveScroll: true });

const resetFilters = () => {
    filters.search = '';
    visit();
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
    form.type = tag.type || 'course';
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
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/tags', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (tag) => {
    deleteModal.tag = tag;
    deleteModal.show = true;
};

const deleteTag = () => {
    deleteModal.loading = true;
    router.delete(`/admin/tags/${deleteModal.tag.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.tag = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
