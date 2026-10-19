<template>
    <AdminLayout>

        <Head title="COIN paketlari" />

        <PageHeader title="COIN paketlari" description="O'quvchilar sotib oladigan COIN paketlarini boshqarish">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi paket</Button>
            </template>
        </PageHeader>

        <div v-if="packages?.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="pkg in packages" :key="pkg.id" :class="[
                'flex flex-col rounded-lg border bg-surface shadow-xs transition-shadow duration-150 hover:shadow-sm',
                pkg.is_featured ? 'border-brand-200 ring-1 ring-brand-200' : 'border-border',
                pkg.is_active ? '' : 'opacity-75',
            ]">
                <div class="flex-1 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-warning-50 text-warning">
                            <CircleStackIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="flex flex-wrap justify-end gap-1.5">
                            <Badge v-if="pkg.is_featured" variant="brand" size="sm" :icon="SparklesIcon">Tavsiya</Badge>
                            <Badge v-if="pkg.badge" variant="warning" size="sm">{{ pkg.badge }}</Badge>
                        </div>
                    </div>

                    <h2 class="mt-4 text-base font-semibold text-fg">{{ pkg.name }}</h2>
                    <p class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-2xl font-semibold tracking-tight text-fg tabular-nums">{{ formatNumber(pkg.total_coins) }}</span>
                        <span class="text-sm font-medium text-fg-subtle">COIN</span>
                    </p>

                    <dl class="mt-4 space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-fg-muted">Asosiy</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ formatNumber(pkg.coins) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-fg-muted">Bonus</dt>
                            <dd :class="['font-medium tabular-nums', Number(pkg.bonus_coins) > 0 ? 'text-success' : 'text-fg-subtle']">
                                {{ Number(pkg.bonus_coins) > 0 ? `+${formatNumber(pkg.bonus_coins)}` : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between border-t border-border pt-2">
                            <dt class="text-fg-muted">Narx</dt>
                            <dd class="text-right">
                                <span class="font-semibold text-fg tabular-nums">{{ formatMoney(pkg.price) }}</span>
                                <span v-if="Number(pkg.original_price) > 0"
                                    class="ml-1.5 text-13 text-fg-subtle line-through tabular-nums">
                                    {{ formatMoney(pkg.original_price) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-border px-5 py-3">
                    <div class="flex items-center gap-2">
                        <Switch :id="`pkg-active-${pkg.id}`" :model-value="!!pkg.is_active" size="sm"
                            @update:model-value="toggleStatus(pkg)" />
                        <label :for="`pkg-active-${pkg.id}`" class="cursor-pointer text-13 text-fg-muted">
                            <span class="sr-only">{{ pkg.name }}: </span>{{ pkg.is_active ? 'Faol' : 'Nofaol' }}
                        </label>
                    </div>
                    <RowActions :label="`${pkg.name}: amallar`" size="sm">
                        <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(pkg)">Tahrirlash</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(pkg)">O'chirish</DropdownItem>
                    </RowActions>
                </div>
            </article>
        </div>

        <Card v-else>
            <EmptyState :icon="CircleStackIcon" title="Paketlar yo'q"
                description="O'quvchilar COIN sotib olishi uchun birinchi paketni yarating">
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi paket</Button>
            </EmptyState>
        </Card>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Paketni tahrirlash' : 'Yangi paket'" size="2xl"
            :closeable="!formModal.loading" @close="closeFormModal">
            <form id="coin-package-form" class="space-y-5" @submit.prevent="submitForm">
                <Input v-model="formModal.data.name" label="Paket nomi" :error="formModal.errors.name" required autofocus />

                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model.number="formModal.data.coins" type="number" min="1" inputmode="numeric"
                        label="Asosiy COIN" :error="formModal.errors.coins" required />
                    <Input v-model.number="formModal.data.bonus_coins" type="number" min="0" inputmode="numeric"
                        label="Bonus COIN" :error="formModal.errors.bonus_coins" optional />
                    <Input v-model.number="formModal.data.price" type="number" min="0" inputmode="numeric"
                        label="Narx (so'm)" :error="formModal.errors.price" required />
                    <Input v-model.number="formModal.data.original_price" type="number" min="0" inputmode="numeric"
                        label="Asl narx (so'm)" hint="Chegirmani ko'rsatish uchun" :error="formModal.errors.original_price"
                        optional />
                </div>

                <Input v-model="formModal.data.badge" label="Yorliq" placeholder="Masalan: ENG FOYDALI"
                    :error="formModal.errors.badge" optional />

                <div class="grid gap-4 rounded-md border border-border p-4 sm:grid-cols-2">
                    <Checkbox v-model="formModal.data.is_featured" label="Tavsiya etilgan"
                        description="Paket ajratib ko'rsatiladi" />
                    <Checkbox v-model="formModal.data.is_active" label="Faol" description="Do'konda ko'rinadi" />
                </div>
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                <Button type="submit" form="coin-package-form" :loading="formModal.loading">
                    {{ formModal.isEdit ? 'Yangilash' : 'Saqlash' }}
                </Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Paketni o'chirish"
            :message="`«${deleteModal.pkg?.name}» paketi o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deletePackage" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CircleStackIcon, PencilSquareIcon, PlusIcon, SparklesIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, Card, Checkbox, DropdownItem, EmptyState, Input, Modal, PageHeader, Switch,
} from '@/Components/UI';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatMoney, formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    packages: Array,
});

const emptyData = () => ({
    name: '',
    coins: 100,
    bonus_coins: 0,
    price: 0,
    original_price: null,
    badge: '',
    is_featured: false,
    is_active: true,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
    pkgId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    pkg: null,
});

const toggleStatus = (pkg) => {
    router.patch(`/admin/coin-packages/${pkg.id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = emptyData();
    formModal.errors = {};
};

const openEditModal = (pkg) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.pkgId = pkg.id;
    formModal.data = {
        name: pkg.name,
        coins: pkg.coins,
        bonus_coins: pkg.bonus_coins,
        price: pkg.price,
        original_price: pkg.original_price,
        badge: pkg.badge,
        is_featured: !!pkg.is_featured,
        is_active: !!pkg.is_active,
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
        ? `/admin/coin-packages/${formModal.pkgId}`
        : '/admin/coin-packages';

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

const confirmDelete = (pkg) => {
    deleteModal.pkg = pkg;
    deleteModal.show = true;
};

const deletePackage = () => {
    deleteModal.loading = true;

    router.delete(`/admin/coin-packages/${deleteModal.pkg.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.pkg = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
