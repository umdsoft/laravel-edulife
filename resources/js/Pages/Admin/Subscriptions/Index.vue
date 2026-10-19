<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, EmptyState, Input, PageHeader, Pagination, Select, Table } from '@/Components/UI';
import { MagnifyingGlassIcon, RectangleStackIcon, XMarkIcon, CreditCardIcon } from '@heroicons/vue/24/outline';
import { ExclamationTriangleIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    subscriptions: Object,
    plans: Array,
    filters: Object,
});

const STATUSES = {
    active: { label: 'Faol', variant: 'success' },
    expired: { label: 'Tugagan', variant: 'neutral' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
};
const statusOf = (s) => STATUSES[s] || { label: s || '—', variant: 'neutral' };
const statusOptions = Object.entries(STATUSES).map(([value, s]) => ({ value, label: s.label }));
const planOptions = computed(() => (props.plans || []).map((p) => ({ value: p.id, label: p.name })));

const loading = ref(false);
const filters = reactive({
    search: props.filters?.search || '',
    plan_id: props.filters?.plan_id || null,
    status: props.filters?.status || null,
});
const hasFilters = computed(() => Boolean(filters.search || filters.plan_id || filters.status));

const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== ''));
const visit = () => {
    router.get(route('admin.subscriptions.index'), clean(filters), {
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
    Object.assign(filters, { search: '', plan_id: null, status: null });
    visit();
};

const columns = [
    { key: 'user_name', label: 'Foydalanuvchi' },
    { key: 'plan_name', label: 'Tarif', hideBelow: 'sm' },
    { key: 'started_at', label: 'Boshlangan', hideBelow: 'md' },
    { key: 'ends_at', label: 'Tugaydi' },
    { key: 'status', label: 'Holat' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Obunalar" />

        <PageHeader title="Obunalar" description="Foydalanuvchilarning faol va tugagan obunalari">
            <template #actions>
                <Button variant="secondary" :icon-left="RectangleStackIcon" :href="route('admin.subscriptions.plans')">Tariflar</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <Input v-model="filters.search" type="search" placeholder="Ism yoki telefon" aria-label="Qidirish"
                    :leading-icon="MagnifyingGlassIcon" class="sm:w-72" @input="onSearch" />
                <Select v-model="filters.plan_id" :options="planOptions" placeholder="Barcha tariflar" aria-label="Tarif"
                    class="sm:w-48" @change="visit" />
                <Select v-model="filters.status" :options="statusOptions" placeholder="Barcha holatlar" aria-label="Holat"
                    class="sm:w-48" @change="visit" />
                <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="resetFilters">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="subscriptions.data" :loading="loading" caption="Obunalar">
                <template #cell-user_name="{ row }">
                    <div class="flex items-center gap-3">
                        <Avatar :name="row.user_name" size="sm" />
                        <div class="min-w-0">
                            <Link v-if="row.user_id" :href="route('admin.users.show', row.user_id)"
                                class="focus-ring block truncate rounded-sm font-medium text-fg hover:text-brand">
                                {{ row.user_name }}
                            </Link>
                            <span v-else class="font-medium text-fg">{{ row.user_name }}</span>
                            <p class="truncate text-13 text-fg-subtle sm:hidden">{{ row.plan_name }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-plan_name="{ value }"><Badge variant="brand">{{ value }}</Badge></template>
                <template #cell-started_at="{ value }"><span class="tabular-nums text-fg-muted">{{ value || '—' }}</span></template>
                <template #cell-ends_at="{ row }">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="tabular-nums text-fg-muted">{{ row.ends_at || '—' }}</span>
                        <Badge v-if="row.is_expiring_soon" variant="warning" size="sm" :icon="ExclamationTriangleIcon">Tez tugaydi</Badge>
                    </div>
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="statusOf(value).variant" dot>{{ statusOf(value).label }}</Badge>
                </template>
                <template #empty>
                    <EmptyState v-if="hasFilters" :icon="MagnifyingGlassIcon" title="Hech narsa topilmadi"
                        description="Qidiruv yoki filtrlarni o'zgartirib ko'ring" size="sm">
                        <Button variant="secondary" size="sm" @click="resetFilters">Filtrlarni tozalash</Button>
                    </EmptyState>
                    <EmptyState v-else :icon="CreditCardIcon" title="Hozircha obuna yo'q" size="sm" />
                </template>
                <template v-if="subscriptions.last_page > 1" #footer>
                    <Pagination :data="subscriptions" />
                </template>
            </Table>
        </div>
    </AdminLayout>
</template>
