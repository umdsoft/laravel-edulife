<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Alert, Badge, Button, Dropdown, DropdownItem, EmptyState, IconButton, Input, Modal, PageHeader, Pagination, Select,
    Switch, Table,
} from '@/Components/UI';
import {
    PlusIcon, MagnifyingGlassIcon, EllipsisVerticalIcon, PencilSquareIcon, TrashIcon, PauseCircleIcon,
    PlayCircleIcon, XMarkIcon, TicketIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    promoCodes: Object,
    filters: Object,
});

const loading = ref(false);
const filters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || null,
    status: props.filters?.status || null,
});
const hasFilters = computed(() => Boolean(filters.search || filters.type || filters.status));

const typeOptions = [
    { value: 'percentage', label: 'Foiz (%)' },
    { value: 'fixed', label: "Belgilangan summa (so'm)" },
];
const statusOptions = [
    { value: 'active', label: 'Faol' },
    { value: 'inactive', label: 'Faol emas' },
];

const money = (v) => Math.round(Number(v) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const clean = (obj) => Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== null && v !== undefined && v !== ''));
const visit = () => {
    router.get(route('admin.promo-codes.index'), clean(filters), {
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
    Object.assign(filters, { search: '', type: null, status: null });
    visit();
};

const columns = [
    { key: 'code', label: 'Kod' },
    { key: 'value', label: 'Chegirma', numeric: true },
    { key: 'used', label: 'Ishlatilgan', numeric: true, hideBelow: 'sm' },
    { key: 'expires_at', label: 'Muddati', hideBelow: 'md' },
    { key: 'status', label: 'Holat' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const stateOf = (c) => {
    if (c.is_expired) return { label: "Muddati o'tgan", variant: 'danger' };
    if (c.is_maxed_out) return { label: 'Limit tugagan', variant: 'warning' };
    if (c.is_active) return { label: 'Faol', variant: 'success' };
    return { label: 'Faol emas', variant: 'neutral' };
};

// Backend sends dd.mm.yyyy; <input type="date"> needs yyyy-mm-dd
const toInputDate = (v) => {
    if (!v) return null;
    const m = String(v).match(/^(\d{2})\.(\d{2})\.(\d{4})/);
    return m ? `${m[3]}-${m[2]}-${m[1]}` : v;
};

const emptyData = () => ({
    code: '',
    type: 'percentage',
    value: 0,
    min_amount: null,
    max_uses: null,
    max_uses_per_user: 1,
    starts_at: null,
    expires_at: null,
    is_active: true,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
    codeId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    code: null,
});

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.codeId = null;
    formModal.data = emptyData();
    formModal.errors = {};
};

const openEditModal = (code) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.codeId = code.id;
    formModal.data = {
        code: code.code,
        type: code.type,
        value: code.value,
        min_amount: code.min_amount,
        max_uses: code.max_uses,
        max_uses_per_user: code.max_uses_per_user || 1,
        starts_at: toInputDate(code.starts_at),
        expires_at: toInputDate(code.expires_at),
        is_active: code.is_active,
    };
    formModal.errors = {};
};

const closeFormModal = () => {
    formModal.show = false;
    formModal.errors = {};
};

// The code must be uppercase (validated server-side) — normalise as the admin types
const onCodeInput = (v) => {
    formModal.data.code = String(v || '').toUpperCase().replace(/\s+/g, '');
};

const submitForm = () => {
    formModal.loading = true;
    formModal.errors = {};

    const url = formModal.isEdit
        ? route('admin.promo-codes.update', formModal.codeId)
        : route('admin.promo-codes.store');
    const method = formModal.isEdit ? 'put' : 'post';

    router[method](url, formModal.data, {
        preserveScroll: true,
        onSuccess: () => closeFormModal(),
        onError: (errors) => (formModal.errors = errors),
        onFinish: () => (formModal.loading = false),
    });
};

const toggleStatus = (code) => {
    router.patch(route('admin.promo-codes.toggle-status', code.id), {}, { preserveScroll: true });
};

const confirmDelete = (code) => {
    deleteModal.code = code;
    deleteModal.show = true;
};

const deleteCode = () => {
    deleteModal.loading = true;
    router.delete(route('admin.promo-codes.destroy', deleteModal.code.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.code = null;
        },
        onFinish: () => (deleteModal.loading = false),
    });
};

const usedPercent = (c) => (c.max_uses ? Math.min(100, Math.round((c.used_count / c.max_uses) * 100)) : null);
</script>

<template>
    <AdminLayout>
        <Head title="Promo kodlar" />

        <PageHeader title="Promo kodlar" description="Chegirma kodlarini yarating va ularning ishlatilishini kuzating">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi kod</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <Alert variant="info">
                Promo kodlarni o'quvchilar Payme / Click to'lov oynasidagi «Promokod» maydoniga (kurs, obuna, coin paketi)
                yoki pullik olimpiadaga ro'yxatdan o'tishda «Chegirma kodi» maydoniga kiritadi. Ishlatilish to'lov o'tgandan keyin hisoblanadi.
            </Alert>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <Input v-model="filters.search" type="search" placeholder="Kod bo'yicha qidirish" aria-label="Qidirish"
                    :leading-icon="MagnifyingGlassIcon" class="sm:w-72" @input="onSearch" />
                <Select v-model="filters.type" :options="typeOptions" placeholder="Barcha turlar" aria-label="Tur"
                    class="sm:w-56" @change="visit" />
                <Select v-model="filters.status" :options="statusOptions" placeholder="Barcha holatlar" aria-label="Holat"
                    class="sm:w-44" @change="visit" />
                <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="resetFilters">Tozalash</Button>
            </div>

            <Table :columns="columns" :rows="promoCodes.data" :loading="loading" caption="Promo kodlar">
                <template #cell-code="{ row }">
                    <span class="font-mono text-13 font-semibold tracking-wide text-fg">{{ row.code }}</span>
                    <p class="text-xs text-fg-subtle">
                        {{ row.type === 'percentage' ? 'Foiz' : 'Belgilangan summa' }}
                        <template v-if="row.min_amount"> · min. {{ money(row.min_amount) }} so'm</template>
                    </p>
                </template>
                <template #cell-value="{ row }">
                    <span class="whitespace-nowrap font-medium text-fg">
                        <template v-if="row.type === 'percentage'">{{ row.value }}%</template>
                        <template v-else>{{ money(row.value) }} <span class="text-fg-subtle">so'm</span></template>
                    </span>
                </template>
                <template #cell-used="{ row }">
                    <div class="ml-auto w-24">
                        <p class="text-sm text-fg">{{ row.used_count || 0 }} <span class="text-fg-subtle">/ {{ row.max_uses || '∞' }}</span></p>
                        <div v-if="usedPercent(row) !== null" class="mt-1 h-1 w-full overflow-hidden rounded-full bg-surface-2" aria-hidden="true">
                            <div class="h-full rounded-full bg-brand-600" :style="{ width: `${usedPercent(row)}%` }" />
                        </div>
                    </div>
                </template>
                <template #cell-expires_at="{ row }">
                    <span class="tabular-nums text-fg-muted">{{ row.expires_at || 'Muddatsiz' }}</span>
                    <p v-if="row.starts_at" class="text-xs tabular-nums text-fg-subtle">{{ row.starts_at }} dan</p>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="stateOf(row).variant" dot>{{ stateOf(row).label }}</Badge>
                </template>
                <template #cell-actions="{ row }">
                    <Dropdown align="right">
                        <template #trigger>
                            <IconButton :icon="EllipsisVerticalIcon" :label="`${row.code}: amallar`" />
                        </template>
                        <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                        <DropdownItem :icon="row.is_active ? PauseCircleIcon : PlayCircleIcon" @click="toggleStatus(row)">
                            {{ row.is_active ? "To'xtatish" : 'Faollashtirish' }}
                        </DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </Dropdown>
                </template>
                <template #empty>
                    <EmptyState v-if="hasFilters" :icon="MagnifyingGlassIcon" title="Hech narsa topilmadi"
                        description="Qidiruv yoki filtrlarni o'zgartirib ko'ring" size="sm">
                        <Button variant="secondary" size="sm" @click="resetFilters">Filtrlarni tozalash</Button>
                    </EmptyState>
                    <EmptyState v-else :icon="TicketIcon" title="Promo kodlar yo'q" description="Birinchi chegirma kodini yarating" size="sm">
                        <Button size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi kod</Button>
                    </EmptyState>
                </template>
                <template v-if="promoCodes.last_page > 1" #footer>
                    <Pagination :data="promoCodes" />
                </template>
            </Table>
        </div>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Promo kodni tahrirlash' : 'Yangi promo kod'" size="lg"
            @close="closeFormModal">
            <form id="promo-form" class="space-y-5" @submit.prevent="submitForm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <Input :model-value="formModal.data.code" label="Kod" required maxlength="20" autocomplete="off"
                        hint="Lotin bosh harflari va raqamlar" class="[&_input]:font-mono [&_input]:uppercase"
                        :error="formModal.errors.code" @update:model-value="onCodeInput" />
                    <Select v-model="formModal.data.type" label="Tur" required :options="typeOptions" :error="formModal.errors.type" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model.number="formModal.data.value" type="number" min="0" inputmode="decimal"
                        :label="formModal.data.type === 'percentage' ? 'Chegirma foizi' : 'Chegirma summasi'" required
                        :error="formModal.errors.value">
                        <template #trailing>
                            <span class="mr-2 text-13 text-fg-subtle">{{ formModal.data.type === 'percentage' ? '%' : "so'm" }}</span>
                        </template>
                    </Input>
                    <Input v-model.number="formModal.data.min_amount" type="number" min="0" inputmode="numeric" label="Minimal xarid"
                        optional :error="formModal.errors.min_amount">
                        <template #trailing><span class="mr-2 text-13 text-fg-subtle">so'm</span></template>
                    </Input>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model.number="formModal.data.max_uses" type="number" min="1" inputmode="numeric" label="Umumiy limit"
                        optional hint="Bo'sh — cheksiz" :error="formModal.errors.max_uses" />
                    <Input v-model.number="formModal.data.max_uses_per_user" type="number" min="1" inputmode="numeric"
                        label="Bir foydalanuvchiga" :error="formModal.errors.max_uses_per_user" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model="formModal.data.starts_at" type="date" label="Boshlanish sanasi" optional
                        :error="formModal.errors.starts_at" />
                    <Input v-model="formModal.data.expires_at" type="date" label="Tugash sanasi" optional
                        :error="formModal.errors.expires_at" />
                </div>

                <Switch v-model="formModal.data.is_active" label="Faol" description="O'chirilgan kodni foydalanuvchilar qo'llay olmaydi" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                    <Button type="submit" form="promo-form" :loading="formModal.loading">
                        {{ formModal.isEdit ? 'Saqlash' : 'Yaratish' }}
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Delete -->
        <Modal :show="deleteModal.show" size="sm" title="Promo kodni o'chirish" @close="deleteModal.show = false">
            <p class="text-sm text-fg-muted">
                <span class="font-mono font-semibold text-fg">{{ deleteModal.code?.code }}</span> kodi o'chiriladi va endi qo'llanilmaydi.
                Bu amalni qaytarib bo'lmaydi.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="deleteModal.loading" @click="deleteModal.show = false">Bekor qilish</Button>
                    <Button variant="danger" :icon-left="TrashIcon" :loading="deleteModal.loading" @click="deleteCode">O'chirish</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
