<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import {
    Badge, Button, Dropdown, DropdownItem, EmptyState, IconButton, Input, Modal, PageHeader, Select, Table,
} from '@/Components/UI';
import {
    PlusIcon, MagnifyingGlassIcon, EllipsisVerticalIcon, EyeIcon, PencilSquareIcon, SignalIcon,
    ClipboardDocumentCheckIcon, UsersIcon, TrophyIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon, TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    olympiads: Array,
    pagination: Object,
    filters: Object,
    currentFilters: Object,
});

// an olympiad nobody registered for / sat can be deleted (else: cancel it on its page)
const canDelete = (row) => !row.registrations_count && !row.attempts_count && !['live', 'grading'].includes(row.status);
const deleting = ref(null);
const deleteBusy = ref(false);
const destroy = () => {
    router.delete(route('admin.olympiads.destroy', deleting.value.id), {
        preserveScroll: true,
        onStart: () => (deleteBusy.value = true),
        onFinish: () => {
            deleteBusy.value = false;
            deleting.value = null;
        },
    });
};

const STATUS = {
    draft: { label: 'Qoralama', variant: 'neutral' },
    upcoming: { label: 'Tez kunda', variant: 'info' },
    registration_open: { label: "Ro'yxat ochiq", variant: 'success' },
    registration_closed: { label: "Ro'yxat yopiq", variant: 'neutral' },
    live: { label: 'Jonli', variant: 'danger' },
    grading: { label: 'Baholanmoqda', variant: 'warning' },
    results_ready: { label: 'Natijalar tayyor', variant: 'success' },
    completed: { label: 'Yakunlangan', variant: 'brand' },
    cancelled: { label: 'Bekor qilingan', variant: 'neutral' },
};
const statusOf = (s) => STATUS[s] || STATUS.draft;

// Local copy of the query filters (never mutate props)
const filters = reactive({
    type_id: props.currentFilters?.type_id ?? null,
    stage_id: props.currentFilters?.stage_id ?? null,
    status: props.currentFilters?.status ?? null,
    search: props.currentFilters?.search ?? '',
});

const typeOptions = computed(() => (props.filters?.types || []).map((t) => ({ value: t.id, label: t.display_name })));
const stageOptions = computed(() => (props.filters?.stages || []).map((s) => ({ value: s.id, label: s.display_name })));
const statusOptions = computed(() => (props.filters?.statuses || []).map((s) => ({ value: s, label: statusOf(s).label })));

const hasFilters = computed(() => Boolean(filters.type_id || filters.stage_id || filters.status || filters.search));

const loading = ref(false);
const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== ''));

const visit = (extra = {}) => {
    router.get(route('admin.olympiads.index'), clean({ ...filters, ...extra }), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};
const applyFilters = () => visit();

let searchTimer = null;
const onSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
};
onBeforeUnmount(() => clearTimeout(searchTimer));

const resetFilters = () => {
    Object.assign(filters, { type_id: null, stage_id: null, status: null, search: '' });
    applyFilters();
};

const goToPage = (page) => {
    if (!props.pagination || page < 1 || page > props.pagination.last_page) return;
    visit({ page });
};

const columns = [
    { key: 'title', label: 'Olimpiada' },
    { key: 'type', label: 'Turi', hideBelow: 'md' },
    { key: 'status', label: 'Holat' },
    { key: 'olympiad_start_at', label: 'Boshlanish', hideBelow: 'lg' },
    { key: 'registrations_count', label: "Ro'yxat", numeric: true, hideBelow: 'sm' },
    { key: 'attempts_count', label: 'Urinish', numeric: true, hideBelow: 'md' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const openRow = (row) => router.visit(route('admin.olympiads.show', row.id));
</script>

<template>
    <AdminLayout>
        <Head title="Olimpiadalar" />

        <PageHeader title="Olimpiadalar" description="Olimpiadalarni yarating, jonli kuzating va natijalarni baholang">
            <template #actions>
                <Button :icon-left="PlusIcon" :href="route('admin.olympiads.create')">Yangi olimpiada</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <!-- Filters -->
            <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
                <Input v-model="filters.search" type="search" placeholder="Nomi bo'yicha qidirish"
                    :leading-icon="MagnifyingGlassIcon" aria-label="Qidirish" class="md:w-72" @input="onSearch"
                    @keyup.enter="applyFilters" />
                <Select v-model="filters.type_id" :options="typeOptions" placeholder="Barcha turlar" aria-label="Turi"
                    class="md:w-48" @change="applyFilters" />
                <Select v-model="filters.stage_id" :options="stageOptions" placeholder="Barcha bosqichlar"
                    aria-label="Bosqich" class="md:w-48" @change="applyFilters" />
                <Select v-model="filters.status" :options="statusOptions" placeholder="Barcha holatlar"
                    aria-label="Holat" class="md:w-48" @change="applyFilters" />
                <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="resetFilters">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="olympiads || []" :loading="loading" clickable caption="Olimpiadalar ro'yxati"
                @row-click="openRow">
                <template #cell-title="{ row }">
                    <div class="min-w-0">
                        <Link :href="route('admin.olympiads.show', row.id)"
                            class="focus-ring rounded-sm font-medium text-fg hover:text-brand">
                            {{ row.title }}
                        </Link>
                        <p class="mt-0.5 truncate text-13 text-fg-subtle">
                            {{ row.stage || 'Bosqich belgilanmagan' }}
                            <span class="md:hidden"> · {{ row.type }}</span>
                        </p>
                    </div>
                </template>

                <template #cell-type="{ row }">
                    <Badge variant="neutral">
                        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: row.type_color || 'rgb(var(--brand-600))' }"
                            aria-hidden="true" />
                        {{ row.type }}
                    </Badge>
                </template>

                <template #cell-status="{ row }">
                    <Badge :variant="statusOf(row.status).variant" dot>{{ statusOf(row.status).label }}</Badge>
                </template>

                <template #cell-olympiad_start_at="{ value }">
                    <span class="tabular-nums text-fg-muted">{{ value || '—' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <Button v-if="row.status === 'live'" size="sm" variant="soft" :icon-left="SignalIcon"
                            :href="route('admin.olympiads.monitor', row.id)" class="hidden sm:inline-flex">
                            Monitoring
                        </Button>
                        <Button v-else-if="row.status === 'grading'" size="sm" variant="soft"
                            :icon-left="ClipboardDocumentCheckIcon" :href="route('admin.olympiads.grading.index', row.id)"
                            class="hidden sm:inline-flex">
                            Baholash
                        </Button>
                        <Dropdown align="right">
                            <template #trigger>
                                <IconButton :icon="EllipsisVerticalIcon" :label="`${row.title}: amallar`" />
                            </template>
                            <DropdownItem :href="route('admin.olympiads.show', row.id)" :icon="EyeIcon">Ko'rish</DropdownItem>
                            <DropdownItem :href="route('admin.olympiads.edit', row.id)" :icon="PencilSquareIcon">Tahrirlash</DropdownItem>
                            <DropdownItem :href="route('admin.olympiads.registrations', row.id)" :icon="UsersIcon">
                                Ro'yxatdagilar
                            </DropdownItem>
                            <template v-if="row.status === 'live' || ['grading', 'completed'].includes(row.status)">
                                <DropdownItem divider />
                                <DropdownItem v-if="row.status === 'live'" :href="route('admin.olympiads.monitor', row.id)"
                                    :icon="SignalIcon">
                                    Jonli monitoring
                                </DropdownItem>
                                <DropdownItem v-if="['grading', 'completed'].includes(row.status)"
                                    :href="route('admin.olympiads.grading.index', row.id)" :icon="ClipboardDocumentCheckIcon">
                                    Baholash paneli
                                </DropdownItem>
                            </template>
                            <template v-if="canDelete(row)">
                                <DropdownItem divider />
                                <DropdownItem :icon="TrashIcon" danger @click="deleting = row">O'chirish</DropdownItem>
                            </template>
                        </Dropdown>
                    </div>
                </template>

                <template #empty>
                    <EmptyState v-if="hasFilters" :icon="MagnifyingGlassIcon" title="Hech narsa topilmadi"
                        description="Filtrlarni o'zgartirib ko'ring" size="sm">
                        <Button variant="secondary" size="sm" @click="resetFilters">Filtrlarni tozalash</Button>
                    </EmptyState>
                    <EmptyState v-else :icon="TrophyIcon" title="Hozircha olimpiada yo'q"
                        description="Birinchi olimpiadani yarating — u qoralama sifatida saqlanadi" size="sm">
                        <Button size="sm" :icon-left="PlusIcon" :href="route('admin.olympiads.create')">Yangi olimpiada</Button>
                    </EmptyState>
                </template>

                <template v-if="pagination && pagination.total > 0" #footer>
                    <div class="flex flex-col-reverse items-center justify-between gap-3 px-4 py-3 sm:flex-row">
                        <p class="text-13 text-fg-subtle">
                            Jami <span class="tabular-nums text-fg">{{ pagination.total }}</span> ta olimpiada
                        </p>
                        <nav v-if="pagination.last_page > 1" class="flex items-center gap-1" aria-label="Sahifalar">
                            <Button size="sm" variant="ghost" :icon-left="ChevronLeftIcon"
                                :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">
                                <span class="hidden sm:inline">Oldingi</span>
                            </Button>
                            <span class="px-2 text-13 tabular-nums text-fg-muted" aria-current="page">
                                {{ pagination.current_page }} / {{ pagination.last_page }}
                            </span>
                            <Button size="sm" variant="ghost" :icon-right="ChevronRightIcon"
                                :disabled="pagination.current_page >= pagination.last_page"
                                @click="goToPage(pagination.current_page + 1)">
                                <span class="hidden sm:inline">Keyingi</span>
                            </Button>
                        </nav>
                    </div>
                </template>
            </Table>
        </div>
        <Modal :show="!!deleting" size="md" title="Olimpiadani o'chirish" @close="deleting = null">
            <p class="text-sm text-fg-muted">
                «<span class="font-medium text-fg">{{ deleting?.title }}</span>» va uning bo'limlari o'chiriladi.
                Unda ro'yxatdan o'tganlar va urinishlar yo'q.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="deleteBusy" @click="deleting = null">Yopish</Button>
                    <Button variant="danger" :loading="deleteBusy" @click="destroy">O'chirish</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
