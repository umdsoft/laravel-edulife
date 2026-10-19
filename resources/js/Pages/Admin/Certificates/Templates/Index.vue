<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, Dropdown, DropdownItem, EmptyState, IconButton, Modal, PageHeader, Pagination,
} from '@/Components/UI';
import {
    PlusIcon, EllipsisVerticalIcon, EyeIcon, PencilSquareIcon, StarIcon, TrashIcon, DocumentTextIcon,
    RectangleStackIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/20/solid';

defineProps({
    templates: Object,
});

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (v) => {
    if (!v) return '—';
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? v : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};
const scopeOf = (t) => t.course?.title || t.direction?.name || 'Umumiy shablon';

const makeDefault = (t) => {
    router.patch(route('admin.certificate-templates.toggle-default', t.id), {}, { preserveScroll: true });
};

const deleteModal = reactive({ show: false, template: null, loading: false });
const confirmDelete = (t) => Object.assign(deleteModal, { show: true, template: t });
const destroy = () => {
    router.delete(route('admin.certificate-templates.destroy', deleteModal.template.id), {
        preserveScroll: true,
        onStart: () => (deleteModal.loading = true),
        onSuccess: () => (deleteModal.show = false),
        onFinish: () => (deleteModal.loading = false),
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Sertifikat shablonlari" />

        <PageHeader title="Sertifikat shablonlari" description="Sertifikat PDF fayllari shu shablonlar asosida yaratiladi"
            :breadcrumbs="[{ label: 'Sertifikatlar', href: route('admin.certificates.index') }, { label: 'Shablonlar' }]">
            <template #actions>
                <Button :icon-left="PlusIcon" :href="route('admin.certificate-templates.create')">Yangi shablon</Button>
            </template>
        </PageHeader>

        <div v-if="templates.data?.length" class="space-y-6">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <li v-for="template in templates.data" :key="template.id"
                    class="group flex flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-xs transition-shadow duration-150 hover:shadow-md">
                    <Link :href="route('admin.certificate-templates.show', template.id)"
                        class="focus-ring relative block aspect-[1.414] bg-surface-2" :aria-label="`${template.name} — ko'rish`">
                        <img v-if="template.thumbnail_path" :src="`/storage/${template.thumbnail_path}`" alt=""
                            class="h-full w-full object-cover" loading="lazy" />
                        <span v-else class="flex h-full w-full items-center justify-center text-fg-subtle">
                            <DocumentTextIcon class="h-10 w-10" aria-hidden="true" />
                        </span>
                        <span class="absolute left-2 top-2 flex flex-wrap gap-1">
                            <Badge v-if="template.is_default" variant="warning" :icon="StarSolidIcon">Asosiy</Badge>
                            <Badge v-if="!template.is_active" variant="neutral">Nofaol</Badge>
                        </span>
                    </Link>

                    <div class="flex flex-1 items-start gap-2 p-4">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold text-fg">
                                <Link :href="route('admin.certificate-templates.show', template.id)"
                                    class="focus-ring rounded-sm hover:text-brand">{{ template.name }}</Link>
                            </h3>
                            <p class="mt-0.5 truncate text-13 text-fg-subtle">{{ scopeOf(template) }}</p>
                            <p class="mt-3 flex items-center justify-between text-xs text-fg-subtle">
                                <span class="tabular-nums">{{ template.usage_count || 0 }} ta berilgan</span>
                                <span class="tabular-nums">{{ formatDate(template.created_at) }}</span>
                            </p>
                        </div>
                        <Dropdown align="right">
                            <template #trigger>
                                <IconButton :icon="EllipsisVerticalIcon" size="sm" :label="`${template.name}: amallar`" />
                            </template>
                            <DropdownItem :href="route('admin.certificate-templates.show', template.id)" :icon="EyeIcon">Ko'rish</DropdownItem>
                            <DropdownItem :href="route('admin.certificate-templates.edit', template.id)" :icon="PencilSquareIcon">
                                Tahrirlash
                            </DropdownItem>
                            <DropdownItem v-if="!template.is_default" :icon="StarIcon" @click="makeDefault(template)">
                                Asosiy qilish
                            </DropdownItem>
                            <DropdownItem divider />
                            <DropdownItem danger :icon="TrashIcon" :disabled="template.usage_count > 0"
                                :meta="template.usage_count > 0 ? 'ishlatilgan' : null" @click="confirmDelete(template)">
                                O'chirish
                            </DropdownItem>
                        </Dropdown>
                    </div>
                </li>
            </ul>

            <div v-if="templates.last_page > 1" class="overflow-hidden rounded-lg border border-border">
                <Pagination :data="templates" />
            </div>
        </div>

        <EmptyState v-else :icon="RectangleStackIcon" title="Shablonlar yo'q"
            description="PDF yoki rasm yuklab, birinchi sertifikat shablonini yarating" bordered>
            <Button :icon-left="PlusIcon" :href="route('admin.certificate-templates.create')">Yangi shablon</Button>
        </EmptyState>

        <Modal :show="deleteModal.show" size="sm" title="Shablonni o'chirish" @close="deleteModal.show = false">
            <p class="text-sm text-fg-muted">
                <span class="font-medium text-fg">{{ deleteModal.template?.name }}</span> shabloni va uning fayllari o'chiriladi.
                Bu amalni qaytarib bo'lmaydi.
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
