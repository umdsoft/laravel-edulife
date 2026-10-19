<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, EmptyState, IconButton, Input, Modal, PageHeader, Pagination, Select, Table } from '@/Components/UI';
import { MagnifyingGlassIcon, XMarkIcon, CodeBracketIcon, ClipboardDocumentListIcon, CpuChipIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const EVENTS = {
    created: { label: 'Yaratildi', variant: 'success' },
    updated: { label: 'Yangilandi', variant: 'info' },
    deleted: { label: "O'chirildi", variant: 'danger' },
    login: { label: 'Kirish', variant: 'warning' },
};
const eventOf = (e) => EVENTS[e] || { label: e || '—', variant: 'neutral' };
const eventOptions = Object.entries(EVENTS).map(([value, e]) => ({ value, label: e.label }));

const loading = ref(false);
const filters = reactive({
    search: props.filters?.search || '',
    event: props.filters?.event || null,
});
const hasFilters = computed(() => Boolean(filters.search || filters.event));

const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== ''));
const visit = () => {
    router.get(route('admin.system-logs.index'), clean(filters), {
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
    Object.assign(filters, { search: '', event: null });
    visit();
};

// "Y-m-d H:i:s" → "dd.mm.yyyy HH:mm" (seconds kept in the tooltip)
const formatDateTime = (v) => {
    const m = String(v || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return m ? `${m[3]}.${m[2]}.${m[1]} ${m[4]}:${m[5]}` : v || '—';
};

const hasProps = (p) => p && typeof p === 'object' && Object.keys(p).length > 0;

const columns = [
    { key: 'description', label: 'Tavsif' },
    { key: 'event', label: 'Hodisa', hideBelow: 'sm' },
    { key: 'causer', label: 'Bajaruvchi', hideBelow: 'md' },
    { key: 'subject', label: 'Obyekt', hideBelow: 'lg' },
    { key: 'created_at', label: 'Vaqt', hideBelow: 'sm' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const detail = ref(null);
</script>

<template>
    <AdminLayout>
        <Head title="Tizim loglari" />

        <PageHeader title="Tizim loglari" description="Tizimdagi harakatlar tarixi: kim, qachon va nimani o'zgartirdi" />

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <Input v-model="filters.search" type="search" placeholder="Tavsif bo'yicha qidirish" aria-label="Qidirish"
                    :leading-icon="MagnifyingGlassIcon" class="sm:w-80" @input="onSearch" />
                <Select v-model="filters.event" :options="eventOptions" placeholder="Barcha hodisalar" aria-label="Hodisa"
                    class="sm:w-48" @change="visit" />
                <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="resetFilters">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="logs.data" :loading="loading" dense caption="Tizim loglari">
                <template #cell-description="{ row }">
                    <p class="max-w-md text-fg">{{ row.description }}</p>
                    <p class="text-xs text-fg-subtle sm:hidden">
                        {{ eventOf(row.event).label }} · <span class="tabular-nums">{{ formatDateTime(row.created_at) }}</span>
                    </p>
                </template>
                <template #cell-event="{ value }">
                    <Badge :variant="eventOf(value).variant" size="sm">{{ eventOf(value).label }}</Badge>
                </template>
                <template #cell-causer="{ value }">
                    <div class="flex items-center gap-2">
                        <span v-if="value === 'System'" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-surface-2 text-fg-subtle">
                            <CpuChipIcon class="h-4 w-4" aria-hidden="true" />
                        </span>
                        <Avatar v-else :name="value" size="xs" />
                        <span class="truncate text-fg-muted">{{ value === 'System' ? 'Tizim' : value }}</span>
                    </div>
                </template>
                <template #cell-subject="{ row }">
                    <span v-if="row.subject_type" class="font-mono text-xs text-fg-muted">{{ row.subject_type }} #{{ row.subject_id }}</span>
                    <span v-else class="text-fg-subtle">—</span>
                </template>
                <template #cell-created_at="{ value }">
                    <time :datetime="value" :title="value" class="whitespace-nowrap tabular-nums text-fg-muted">{{ formatDateTime(value) }}</time>
                </template>
                <template #cell-actions="{ row }">
                    <IconButton v-if="hasProps(row.properties)" :icon="CodeBracketIcon" size="sm" label="Tafsilotlar" @click="detail = row" />
                </template>
                <template #empty>
                    <EmptyState v-if="hasFilters" :icon="MagnifyingGlassIcon" title="Hech narsa topilmadi"
                        description="Qidiruv yoki filtrni o'zgartirib ko'ring" size="sm">
                        <Button variant="secondary" size="sm" @click="resetFilters">Filtrlarni tozalash</Button>
                    </EmptyState>
                    <EmptyState v-else :icon="ClipboardDocumentListIcon" title="Loglar yo'q" size="sm" />
                </template>
                <template v-if="logs.last_page > 1" #footer>
                    <Pagination :data="logs" />
                </template>
            </Table>
        </div>

        <Modal :show="Boolean(detail)" size="xl" title="Log tafsilotlari" :description="detail?.description" @close="detail = null">
            <dl v-if="detail" class="mb-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-fg-muted">Hodisa</dt>
                    <dd><Badge :variant="eventOf(detail.event).variant" size="sm">{{ eventOf(detail.event).label }}</Badge></dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-fg-muted">Vaqt</dt>
                    <dd class="font-medium tabular-nums">{{ detail.created_at }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-fg-muted">Bajaruvchi</dt>
                    <dd class="font-medium">{{ detail.causer === 'System' ? 'Tizim' : detail.causer }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-fg-muted">Obyekt</dt>
                    <dd class="font-mono text-13">{{ detail.subject_type ? `${detail.subject_type} #${detail.subject_id}` : '—' }}</dd>
                </div>
            </dl>
            <pre v-if="detail" class="max-h-96 overflow-auto rounded-md border border-border bg-surface-2 p-4 font-mono text-xs text-fg">{{ JSON.stringify(detail.properties, null, 2) }}</pre>
        </Modal>
    </AdminLayout>
</template>
