<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Alert, Badge, Button, EmptyState, IconButton, Input, Modal, PageHeader, Select, Switch, Textarea,
} from '@/Components/UI';
import { PlusIcon, PencilSquareIcon, TrashIcon, RectangleStackIcon } from '@heroicons/vue/24/outline';
import { CheckIcon, StarIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    plans: Array,
});

const money = (v) => Math.round(Number(v) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const intervalLabel = (p) => {
    const unit = p.interval === 'year' ? 'yil' : 'oy';
    return Number(p.interval_count) > 1 ? `${p.interval_count} ${unit}` : unit;
};
const intervalOptions = [
    { value: 'month', label: 'Oy' },
    { value: 'year', label: 'Yil' },
];

const slugify = (s) => String(s || '')
    .toLowerCase()
    .replace(/[ʻʼ'`’]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const emptyData = () => ({
    name: '',
    slug: '',
    description: '',
    price: 0,
    original_price: null,
    interval: 'month',
    interval_count: 1,
    // one feature per line in the textarea; sent as an array
    features: '',
    is_featured: false,
    is_active: true,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
    planId: null,
    slugTouched: false,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    plan: null,
    error: null,
});

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.planId = null;
    formModal.slugTouched = false;
    formModal.data = emptyData();
    formModal.errors = {};
};

const openEditModal = (plan) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.planId = plan.id;
    formModal.slugTouched = true;
    formModal.data = {
        name: plan.name,
        slug: plan.slug,
        description: plan.description || '',
        // decimal columns arrive as "15000.00"
        price: Number(plan.price) || 0,
        original_price: plan.original_price === null || plan.original_price === undefined ? null : Number(plan.original_price),
        interval: plan.interval,
        interval_count: plan.interval_count,
        features: (plan.features || []).join('\n'),
        is_featured: plan.is_featured,
        is_active: plan.is_active,
    };
    formModal.errors = {};
};

// Suggest a slug from the name while creating (until the slug is edited by hand)
const onNameInput = (v) => {
    formModal.data.name = v;
    if (!formModal.isEdit && !formModal.slugTouched) formModal.data.slug = slugify(v);
};
const onSlugInput = (v) => {
    formModal.slugTouched = true;
    formModal.data.slug = v;
};

const closeFormModal = () => {
    formModal.show = false;
    formModal.errors = {};
};

const submitForm = () => {
    formModal.loading = true;
    formModal.errors = {};

    const url = formModal.isEdit
        ? route('admin.subscriptions.update-plan', formModal.planId)
        : route('admin.subscriptions.store-plan');
    const method = formModal.isEdit ? 'put' : 'post';

    const payload = {
        ...formModal.data,
        features: String(formModal.data.features || '').split('\n').map((f) => f.trim()).filter(Boolean),
    };

    router[method](url, payload, {
        preserveScroll: true,
        onSuccess: () => closeFormModal(),
        onError: (errors) => {
            // "features.2" errors belong to the features textarea
            const featureError = Object.entries(errors).find(([key]) => key.startsWith('features.'))?.[1];
            formModal.errors = featureError && !errors.features ? { ...errors, features: featureError } : errors;
        },
        onFinish: () => (formModal.loading = false),
    });
};

const confirmDelete = (plan) => {
    deleteModal.plan = plan;
    deleteModal.error = null;
    deleteModal.show = true;
};

const deletePlan = () => {
    deleteModal.loading = true;
    deleteModal.error = null;
    router.delete(route('admin.subscriptions.destroy-plan', deleteModal.plan.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.plan = null;
        },
        onError: (errors) => {
            deleteModal.error = errors.error || Object.values(errors)[0] || "O'chirib bo'lmadi";
        },
        onFinish: () => (deleteModal.loading = false),
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Obuna tariflari" />

        <PageHeader title="Obuna tariflari" description="Narxlar va obuna davrlarini boshqaring"
            :breadcrumbs="[{ label: 'Obunalar', href: route('admin.subscriptions.index') }, { label: 'Tariflar' }]">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi tarif</Button>
            </template>
        </PageHeader>

        <ul v-if="plans?.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <li v-for="plan in plans" :key="plan.id" :class="[
                'flex min-w-0 flex-col rounded-lg border bg-surface p-5 shadow-xs',
                plan.is_featured ? 'border-brand-500 ring-1 ring-brand-500' : 'border-border',
            ]">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-semibold text-fg">{{ plan.name }}</h3>
                    <Badge v-if="plan.is_featured" variant="brand" size="sm" :icon="StarIcon">Tavsiya</Badge>
                    <Badge v-if="!plan.is_active" variant="neutral" size="sm">Nofaol</Badge>
                </div>
                <p class="mt-1 font-mono text-xs text-fg-subtle">{{ plan.slug }}</p>
                <p class="mt-3 text-sm text-fg-muted">{{ plan.description || "Tavsif yo'q" }}</p>

                <div class="mt-4">
                    <p class="flex flex-wrap items-baseline gap-x-1.5">
                        <span class="text-2xl font-semibold tracking-tight tabular-nums text-fg">{{ money(plan.price) }}</span>
                        <span class="text-sm text-fg-subtle">so'm / {{ intervalLabel(plan) }}</span>
                    </p>
                    <p v-if="Number(plan.original_price) > Number(plan.price)" class="mt-0.5 text-13 tabular-nums text-fg-muted">
                        Chegirmasiz: <span class="line-through">{{ money(plan.original_price) }} so'm</span>
                    </p>
                </div>

                <ul v-if="plan.features?.length" class="mt-4 space-y-2">
                    <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start gap-2 text-sm text-fg-muted">
                        <CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                        <span>{{ feature }}</span>
                    </li>
                </ul>

                <div class="mt-auto pt-5">
                    <div class="flex items-center gap-2 border-t border-border pt-4">
                        <Button variant="secondary" size="sm" class="min-w-0 flex-1" :icon-left="PencilSquareIcon" @click="openEditModal(plan)">
                            Tahrirlash
                        </Button>
                        <IconButton :icon="TrashIcon" variant="danger" size="sm" :label="`${plan.name} tarifini o'chirish`"
                            @click="confirmDelete(plan)" />
                    </div>
                </div>
            </li>
        </ul>

        <EmptyState v-else :icon="RectangleStackIcon" title="Tariflar yo'q" description="Birinchi obuna tarifini yarating" bordered>
            <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi tarif</Button>
        </EmptyState>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Tarifni tahrirlash' : 'Yangi tarif'" size="lg" @close="closeFormModal">
            <form id="plan-form" class="space-y-5" @submit.prevent="submitForm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Input :model-value="formModal.data.name" label="Nomi" required :error="formModal.errors.name"
                        @update:model-value="onNameInput" />
                    <Input :model-value="formModal.data.slug" label="Slug" required hint="URL uchun: lotin harflari va chiziqcha"
                        class="[&_input]:font-mono" :error="formModal.errors.slug" @update:model-value="onSlugInput" />
                </div>

                <Textarea v-model="formModal.data.description" label="Tavsif" optional :rows="2" :error="formModal.errors.description" />

                <Textarea v-model="formModal.data.features" label="Imkoniyatlar" optional :rows="5"
                    hint="Har bir imkoniyat alohida qatorda, masalan: Barcha kurslar" :error="formModal.errors.features" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model.number="formModal.data.price" type="number" min="0" inputmode="numeric" label="Narx" required
                        hint="Bir davr uchun" :error="formModal.errors.price">
                        <template #trailing><span class="mr-2 text-13 text-fg-subtle">so'm</span></template>
                    </Input>
                    <Input v-model.number="formModal.data.original_price" type="number" min="0" inputmode="numeric" label="Chegirmasiz narx"
                        optional hint="Chegirmani ko'rsatish uchun (narxdan katta)" :error="formModal.errors.original_price">
                        <template #trailing><span class="mr-2 text-13 text-fg-subtle">so'm</span></template>
                    </Input>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Select v-model="formModal.data.interval" label="Davr" :options="intervalOptions" :error="formModal.errors.interval" />
                    <Input v-model.number="formModal.data.interval_count" type="number" min="1" inputmode="numeric" label="Davrlar soni"
                        required :error="formModal.errors.interval_count" />
                </div>

                <div class="space-y-3 border-t border-border pt-4">
                    <Switch v-model="formModal.data.is_featured" label="Tavsiya etilgan" description="Tariflar sahifasida ajratib ko'rsatiladi" />
                    <Switch v-model="formModal.data.is_active" label="Faol" description="Nofaol tarifni yangi foydalanuvchilar tanlay olmaydi" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                    <Button type="submit" form="plan-form" :loading="formModal.loading">
                        {{ formModal.isEdit ? 'Saqlash' : 'Yaratish' }}
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Delete -->
        <Modal :show="deleteModal.show" size="sm" title="Tarifni o'chirish" @close="deleteModal.show = false">
            <div class="space-y-3">
                <p class="text-sm text-fg-muted">
                    <span class="font-medium text-fg">{{ deleteModal.plan?.name }}</span> tarifi o'chiriladi. Faol obunasi bor tariflarni
                    o'chirib bo'lmaydi.
                </p>
                <Alert v-if="deleteModal.error" variant="danger">{{ deleteModal.error }}</Alert>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="deleteModal.loading" @click="deleteModal.show = false">Bekor qilish</Button>
                    <Button variant="danger" :icon-left="TrashIcon" :loading="deleteModal.loading" @click="deletePlan">O'chirish</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
