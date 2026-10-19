<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Button, Dropdown, DropdownItem, EmptyState, IconButton, Input, Modal, PageHeader, Pagination, Select, Table,
} from '@/Components/UI';
import {
    MagnifyingGlassIcon, RectangleStackIcon, EllipsisVerticalIcon, EyeIcon, ArrowDownTrayIcon, ArrowPathIcon,
    TrashIcon, XMarkIcon, AcademicCapIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    certificates: Object,
    filters: Object,
    templates: Array,
});

const loading = ref(false);
const filters = reactive({
    search: props.filters?.search || '',
    // kept so an existing ?course_id= link keeps working (no course list is provided for a picker)
    course_id: props.filters?.course_id || '',
    template_id: props.filters?.template_id || null,
});

const templateOptions = computed(() => (props.templates || []).map((t) => ({ value: t.id, label: t.name })));
const hasFilters = computed(() => Boolean(filters.search || filters.course_id || filters.template_id));

const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== ''));
const visit = () => {
    router.get(route('admin.certificates.index'), clean(filters), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

let timer = null;
const onSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(visit, 350);
};
onBeforeUnmount(() => clearTimeout(timer));

const resetFilters = () => {
    Object.assign(filters, { search: '', course_id: '', template_id: null });
    visit();
};

const columns = [
    { key: 'code', label: 'Kod' },
    { key: 'student_name', label: 'O\'quvchi' },
    { key: 'course_title', label: 'Kurs', hideBelow: 'md' },
    { key: 'template_name', label: 'Shablon', hideBelow: 'lg' },
    { key: 'issued_at', label: 'Berilgan', hideBelow: 'sm' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const openRow = (row) => router.visit(route('admin.certificates.show', row.id));

const regenerate = (cert) => {
    router.post(route('admin.certificates.regenerate', cert.id), {}, { preserveScroll: true });
};

const deleteModal = reactive({ show: false, cert: null, loading: false });
const confirmDelete = (cert) => Object.assign(deleteModal, { show: true, cert });
const destroy = () => {
    router.delete(route('admin.certificates.destroy', deleteModal.cert.id), {
        preserveScroll: true,
        onStart: () => (deleteModal.loading = true),
        onSuccess: () => (deleteModal.show = false),
        onFinish: () => (deleteModal.loading = false),
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Sertifikatlar" />

        <PageHeader title="Sertifikatlar" description="Kursni tugatgan o'quvchilarga berilgan sertifikatlar">
            <template #actions>
                <Button variant="secondary" :icon-left="RectangleStackIcon" :href="route('admin.certificate-templates.index')">
                    Shablonlar
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <Input v-model="filters.search" type="search" placeholder="Kod yoki o'quvchi ismi" aria-label="Qidirish"
                    :leading-icon="MagnifyingGlassIcon" class="sm:w-80" @input="onSearch" />
                <Select v-model="filters.template_id" :options="templateOptions" placeholder="Barcha shablonlar"
                    aria-label="Shablon" class="sm:w-56" @change="visit" />
                <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="resetFilters">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="certificates.data" :loading="loading" clickable caption="Sertifikatlar"
                @row-click="openRow">
                <template #cell-code="{ row }">
                    <Link :href="route('admin.certificates.show', row.id)"
                        class="focus-ring rounded-sm font-mono text-13 font-medium text-brand hover:underline">
                        {{ row.code }}
                    </Link>
                </template>
                <template #cell-student_name="{ row }">
                    <p class="font-medium text-fg">{{ row.student_name }}</p>
                    <p class="truncate text-13 text-fg-subtle md:hidden">{{ row.course_title }}</p>
                </template>
                <template #cell-course_title="{ value }"><span class="text-fg-muted">{{ value }}</span></template>
                <template #cell-template_name="{ value }"><span class="text-fg-muted">{{ value }}</span></template>
                <template #cell-issued_at="{ value }"><span class="tabular-nums text-fg-muted">{{ value || '—' }}</span></template>
                <template #cell-actions="{ row }">
                    <Dropdown align="right">
                        <template #trigger>
                            <IconButton :icon="EllipsisVerticalIcon" :label="`${row.code}: amallar`" />
                        </template>
                        <DropdownItem :href="route('admin.certificates.show', row.id)" :icon="EyeIcon">Ko'rish</DropdownItem>
                        <DropdownItem v-if="row.pdf_url" :href="route('admin.certificates.download', row.id)" external
                            :icon="ArrowDownTrayIcon">
                            PDF yuklab olish
                        </DropdownItem>
                        <DropdownItem :icon="ArrowPathIcon" @click="regenerate(row)">Qayta generatsiya</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </Dropdown>
                </template>
                <template #empty>
                    <EmptyState v-if="hasFilters" :icon="MagnifyingGlassIcon" title="Hech narsa topilmadi"
                        description="Qidiruv yoki filtrni o'zgartirib ko'ring" size="sm">
                        <Button variant="secondary" size="sm" @click="resetFilters">Filtrlarni tozalash</Button>
                    </EmptyState>
                    <EmptyState v-else :icon="AcademicCapIcon" title="Hozircha sertifikat berilmagan"
                        description="O'quvchilar kursni tugatgach sertifikatlar shu yerda paydo bo'ladi" size="sm" />
                </template>
                <template v-if="certificates.last_page > 1" #footer>
                    <Pagination :data="certificates" />
                </template>
            </Table>
        </div>

        <Modal :show="deleteModal.show" size="sm" title="Sertifikatni o'chirish" @close="deleteModal.show = false">
            <p class="text-sm text-fg-muted">
                <span class="font-mono font-medium text-fg">{{ deleteModal.cert?.code }}</span>
                ({{ deleteModal.cert?.student_name }}) sertifikati va uning PDF/QR fayllari o'chiriladi. Bu amalni qaytarib bo'lmaydi.
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
