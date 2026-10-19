<script setup>
import { reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, Dropdown, DropdownItem, EmptyState, IconButton, Input, Modal, PageHeader, Pagination, Select,
    Switch, Table, Textarea,
} from '@/Components/UI';
import {
    PlusIcon, PaperAirplaneIcon, EllipsisVerticalIcon, PencilSquareIcon, TrashIcon, RectangleStackIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    templates: Object,
});

const AUDIENCES = {
    all: { label: 'Barchaga', variant: 'brand' },
    students: { label: "O'quvchilarga", variant: 'info' },
    teachers: { label: 'Ustozlarga', variant: 'success' },
};
const audienceOf = (v) => AUDIENCES[v] || { label: v, variant: 'neutral' };
const audienceOptions = [
    { value: 'all', label: 'Barcha foydalanuvchilar' },
    { value: 'students', label: "Faqat o'quvchilar" },
    { value: 'teachers', label: 'Faqat ustozlar' },
];

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
    { key: 'name', label: 'Nomi' },
    { key: 'title', label: 'Sarlavha', hideBelow: 'md' },
    { key: 'type', label: 'Kimga', hideBelow: 'sm' },
    { key: 'is_active', label: 'Holat' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
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
        form.put(route('admin.notifications.update-template', editingTemplate.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.notifications.store-template'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteModal = reactive({ show: false, template: null, loading: false });
const deleteTemplate = (template) => Object.assign(deleteModal, { show: true, template });
const destroy = () => {
    router.delete(route('admin.notifications.destroy-template', deleteModal.template.id), {
        preserveScroll: true,
        onStart: () => (deleteModal.loading = true),
        onSuccess: () => (deleteModal.show = false),
        onFinish: () => (deleteModal.loading = false),
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Xabarnoma shablonlari" />

        <PageHeader title="Xabarnoma shablonlari" description="Tez-tez yuboriladigan xabarlar uchun tayyor matnlar">
            <template #actions>
                <Button variant="secondary" :icon-left="PaperAirplaneIcon" :href="route('admin.notifications.send-page')">Xabar yuborish</Button>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi shablon</Button>
            </template>
        </PageHeader>

        <Table :columns="columns" :rows="templates.data" caption="Xabarnoma shablonlari">
            <template #cell-name="{ row }">
                <p class="font-medium text-fg">{{ row.name }}</p>
                <p class="truncate text-13 text-fg-subtle md:hidden">{{ row.title }}</p>
            </template>
            <template #cell-title="{ row }">
                <p class="max-w-xs truncate text-fg">{{ row.title }}</p>
                <p class="max-w-xs truncate text-13 text-fg-subtle">{{ row.message }}</p>
            </template>
            <template #cell-type="{ value }">
                <Badge :variant="audienceOf(value).variant" size="sm">{{ audienceOf(value).label }}</Badge>
            </template>
            <template #cell-is_active="{ value }">
                <Badge :variant="value ? 'success' : 'neutral'" dot>{{ value ? 'Faol' : 'Nofaol' }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <Dropdown align="right">
                    <template #trigger>
                        <IconButton :icon="EllipsisVerticalIcon" :label="`${row.name}: amallar`" />
                    </template>
                    <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                    <DropdownItem divider />
                    <DropdownItem danger :icon="TrashIcon" @click="deleteTemplate(row)">O'chirish</DropdownItem>
                </Dropdown>
            </template>
            <template #empty>
                <EmptyState :icon="RectangleStackIcon" title="Shablonlar yo'q" description="Tez yuborish uchun birinchi shablonni yarating" size="sm">
                    <Button size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi shablon</Button>
                </EmptyState>
            </template>
            <template v-if="templates.last_page > 1" #footer>
                <Pagination :data="templates" />
            </template>
        </Table>

        <!-- Create / edit -->
        <Modal :show="showModal" size="lg" :title="editingTemplate ? 'Shablonni tahrirlash' : 'Yangi shablon'" @close="closeModal">
            <form id="template-form" class="space-y-5" @submit.prevent="submit">
                <Input v-model="form.name" label="Shablon nomi" required hint="Faqat adminlar ko'radi" :error="form.errors.name" />
                <Input v-model="form.title" label="Xabar sarlavhasi" required :error="form.errors.title" />
                <Textarea v-model="form.message" label="Xabar matni" required :rows="4" autoresize :error="form.errors.message" />
                <Select v-model="form.type" label="Kimga" :options="audienceOptions" :error="form.errors.type" />
                <Switch v-if="editingTemplate" v-model="form.is_active" label="Faol" description="Nofaol shablon yuborish sahifasida ko'rinmaydi" />
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="form.processing" @click="closeModal">Bekor qilish</Button>
                    <Button type="submit" form="template-form" :loading="form.processing">{{ editingTemplate ? 'Saqlash' : 'Yaratish' }}</Button>
                </div>
            </template>
        </Modal>

        <!-- Delete -->
        <Modal :show="deleteModal.show" size="sm" title="Shablonni o'chirish" @close="deleteModal.show = false">
            <p class="text-sm text-fg-muted">
                <span class="font-medium text-fg">{{ deleteModal.template?.name }}</span> shabloni o'chiriladi. Bu amalni qaytarib bo'lmaydi.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="deleteModal.loading" @click="deleteModal.show = false">Bekor qilish</Button>
                    <Button variant="danger" :icon-left="TrashIcon" :loading="deleteModal.loading" @click="destroy">O'chirish</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
