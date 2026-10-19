<template>
    <AdminLayout>

        <Head title="Yo'nalishlar" />

        <PageHeader title="Yo'nalishlar" description="Kurs yo'nalishlarini boshqarish">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi yo'nalish</Button>
            </template>
        </PageHeader>

        <Table :columns="columns" :rows="directions || []" caption="Yo'nalishlar ro'yxati">
            <template #cell-name="{ row }">
                <div class="flex min-w-[14rem] items-center gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-lg"
                        :style="row.color ? { backgroundColor: `${row.color}1F` } : null" aria-hidden="true">
                        {{ row.icon }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg">{{ row.name_uz }}</p>
                        <p class="truncate text-13 text-fg-subtle">{{ row.name_ru }} · {{ row.name_en }}</p>
                    </div>
                </div>
            </template>
            <template #cell-slug="{ value }">
                <code class="rounded-sm bg-surface-2 px-1.5 py-0.5 font-mono text-xs text-fg-muted">{{ value }}</code>
            </template>
            <template #cell-courses_count="{ value }">{{ formatNumber(value) }}</template>
            <template #cell-is_active="{ row }">
                <div class="flex items-center gap-2">
                    <Switch :id="`dir-active-${row.id}`" :model-value="!!row.is_active" size="sm"
                        @update:model-value="toggleStatus(row)" />
                    <label :for="`dir-active-${row.id}`" class="cursor-pointer text-13 text-fg-muted">
                        <span class="sr-only">{{ row.name_uz }}: </span>{{ row.is_active ? 'Faol' : 'Nofaol' }}
                    </label>
                </div>
            </template>
            <template #cell-actions="{ row }">
                <RowActions :label="`${row.name_uz}: amallar`">
                    <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                    <DropdownItem divider />
                    <DropdownItem danger :icon="TrashIcon" :disabled="row.courses_count > 0"
                        :meta="row.courses_count > 0 ? `${row.courses_count} ta kurs` : null" @click="confirmDelete(row)">
                        O'chirish
                    </DropdownItem>
                </RowActions>
            </template>
            <template #empty>
                <EmptyState :icon="FolderIcon" title="Yo'nalishlar yo'q" size="sm"
                    description="Kurslarni guruhlash uchun birinchi yo'nalishni yarating">
                    <Button size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi yo'nalish</Button>
                </EmptyState>
            </template>
        </Table>
        <p v-if="directions?.some((d) => d.courses_count > 0)" class="mt-3 text-13 text-fg-subtle">
            Kurslari bor yo'nalishni o'chirib bo'lmaydi, lekin uni nofaol qilish mumkin.
        </p>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Yo\'nalishni tahrirlash' : 'Yangi yo\'nalish'"
            size="2xl" :closeable="!formModal.loading" @close="closeFormModal">
            <form id="direction-form" class="space-y-5" @submit.prevent="submitForm">
                <div class="grid gap-4 sm:grid-cols-3">
                    <Input v-model="formModal.data.name_uz" label="Nomi (o'zbekcha)" :error="formModal.errors.name_uz"
                        required autofocus />
                    <Input v-model="formModal.data.name_ru" label="Nomi (ruscha)" :error="formModal.errors.name_ru"
                        required />
                    <Input v-model="formModal.data.name_en" label="Nomi (inglizcha)" :error="formModal.errors.name_en"
                        required />
                </div>

                <Input v-model="formModal.data.slug" label="Slug" :error="formModal.errors.slug" placeholder="physics"
                    hint="URL uchun: kichik lotin harflari va chiziqcha" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model="formModal.data.icon" label="Belgi (emoji)" :error="formModal.errors.icon"
                        placeholder="⚛️" required />
                    <FormField label="Rang" :error="formModal.errors.color" v-slot="{ id, describedBy, invalid }">
                        <div class="flex items-center gap-2">
                            <input :id="id" v-model="formModal.data.color" type="color" :aria-describedby="describedBy"
                                :aria-invalid="invalid || undefined"
                                class="focus-ring h-10 w-12 shrink-0 cursor-pointer rounded-md border border-border-strong bg-surface p-1" />
                            <Input v-model="formModal.data.color" aria-label="Rang kodi" placeholder="#4F46E5"
                                class="flex-1 font-mono" />
                        </div>
                    </FormField>
                </div>

                <Textarea v-model="formModal.data.description" label="Tavsif" :rows="3"
                    :error="formModal.errors.description" optional />
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                <Button type="submit" form="direction-form" :loading="formModal.loading">
                    {{ formModal.isEdit ? 'Yangilash' : 'Saqlash' }}
                </Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Yo'nalishni o'chirish"
            :message="`«${deleteModal.direction?.name_uz}» yo'nalishi o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deleteDirection" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FolderIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Button, DropdownItem, EmptyState, FormField, Input, Modal, PageHeader, Switch, Table, Textarea,
} from '@/Components/UI';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    directions: Array,
});

const columns = [
    { key: 'name', label: 'Nomi' },
    { key: 'slug', label: 'Slug', hideBelow: 'md' },
    { key: 'courses_count', label: 'Kurslar', numeric: true },
    { key: 'is_active', label: 'Holat' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const emptyData = () => ({
    name_uz: '',
    name_ru: '',
    name_en: '',
    slug: '',
    icon: '',
    color: '#7C3AED',
    description: '',
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
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
    formModal.data = emptyData();
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
        preserveScroll: true,
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
        preserveScroll: true,
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
