<template>
    <AdminLayout>
        <Head title="Turnirlar" />

        <PageHeader title="Turnirlar" description="Battle turnirlarini yarating: o'quvchilar ro'yxat ochiq bo'lganda qatnashadi">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi turnir</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <div v-if="tournaments?.length" class="flex flex-wrap items-center gap-x-4 gap-y-1 text-13 text-fg-subtle">
                <span>Jami <span class="font-medium text-fg tabular-nums">{{ tournaments.length }}</span> ta</span>
                <span>Ro'yxat ochiq <span class="font-medium text-fg tabular-nums">{{ openCount }}</span> ta</span>
            </div>

            <Table :columns="columns" :rows="tournaments || []" caption="Turnirlar ro'yxati">
                <template #cell-name="{ row }">
                    <div class="min-w-[14rem]">
                        <p class="font-medium text-fg">{{ row.name }}</p>
                        <p class="text-13 text-fg-subtle">{{ row.direction }} · {{ typeLabel(row.type) }}</p>
                    </div>
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="statusMeta(value).variant" dot>{{ statusMeta(value).label }}</Badge>
                </template>
                <template #cell-starts_at="{ value }">
                    <span class="whitespace-nowrap tabular-nums text-fg-muted">{{ formatLocal(value) }}</span>
                </template>
                <template #cell-participants_count="{ row }">
                    <span class="tabular-nums">{{ formatNumber(row.participants_count) }} / {{ formatNumber(row.max_participants) }}</span>
                </template>
                <template #cell-entry_fee_coins="{ value }">{{ value ? formatNumber(value) : 'Bepul' }}</template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.name}: amallar`">
                        <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="FlagIcon" title="Turnirlar yo'q" size="sm"
                        description="Birinchi turnirni yarating va holatini «Ro'yxat ochiq» qiling">
                        <Button size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi turnir</Button>
                    </EmptyState>
                </template>
            </Table>
        </div>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Turnirni tahrirlash' : 'Yangi turnir'" size="2xl"
            :closeable="!formModal.loading" @close="closeFormModal">
            <form id="tournament-form" class="space-y-5" @submit.prevent="submitForm">
                <Input v-model="formModal.data.name" label="Nomi" :error="formModal.errors.name" required autofocus />
                <Textarea v-model="formModal.data.description" label="Tavsif" optional :rows="2" :error="formModal.errors.description" />

                <div class="grid gap-4 sm:grid-cols-3">
                    <Select v-model="formModal.data.direction_id" label="Fan" :options="directions" placeholder="Tanlang"
                        :error="formModal.errors.direction_id" required />
                    <Select v-model="formModal.data.type" label="Turi" :options="typeOptions" :error="formModal.errors.type" required />
                    <Select v-model="formModal.data.status" label="Holat" :options="statusOptions" :error="formModal.errors.status"
                        required />
                </div>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium text-fg">Vaqtlar</legend>
                    <p class="text-13 text-fg-subtle">Toshkent vaqti bo'yicha.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Input v-model="formModal.data.registration_starts_at" type="datetime-local" label="Ro'yxat boshlanishi"
                            optional :error="formModal.errors.registration_starts_at" />
                        <Input v-model="formModal.data.registration_ends_at" type="datetime-local" label="Ro'yxat tugashi"
                            optional :error="formModal.errors.registration_ends_at" />
                        <Input v-model="formModal.data.starts_at" type="datetime-local" label="Boshlanish" required
                            :error="formModal.errors.starts_at" />
                        <Input v-model="formModal.data.ends_at" type="datetime-local" label="Tugash" optional
                            :error="formModal.errors.ends_at" />
                    </div>
                </fieldset>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium text-fg">Ishtirok</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Input v-model.number="formModal.data.min_participants" type="number" min="2" inputmode="numeric"
                            label="Minimal ishtirokchilar" required :error="formModal.errors.min_participants" />
                        <Input v-model.number="formModal.data.max_participants" type="number" min="2" inputmode="numeric"
                            label="Maksimal ishtirokchilar" required :error="formModal.errors.max_participants" />
                        <Input v-model.number="formModal.data.min_elo" type="number" min="0" inputmode="numeric" label="Minimal ELO"
                            optional hint="Bo'sh: cheklovsiz" :error="formModal.errors.min_elo" />
                        <Input v-model.number="formModal.data.max_elo" type="number" min="0" inputmode="numeric" label="Maksimal ELO"
                            optional hint="Bo'sh: cheklovsiz" :error="formModal.errors.max_elo" />
                    </div>
                </fieldset>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium text-fg">Coin va bellashuv</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Input v-model.number="formModal.data.entry_fee_coins" type="number" min="0" inputmode="numeric"
                            label="Kirish to'lovi" hint="0 — bepul" required :error="formModal.errors.entry_fee_coins">
                            <template #trailing><span class="mr-2 text-13 text-fg-subtle">coin</span></template>
                        </Input>
                        <Input v-model.number="formModal.data.prize_pool_coins" type="number" min="0" inputmode="numeric"
                            label="Sovrin jamg'armasi" required :error="formModal.errors.prize_pool_coins">
                            <template #trailing><span class="mr-2 text-13 text-fg-subtle">coin</span></template>
                        </Input>
                        <Input v-model.number="formModal.data.questions_per_match" type="number" min="1" inputmode="numeric"
                            label="Bellashuvdagi savollar" required :error="formModal.errors.questions_per_match" />
                        <Input v-model.number="formModal.data.time_per_question" type="number" min="5" inputmode="numeric"
                            label="Savol uchun vaqt" required :error="formModal.errors.time_per_question">
                            <template #trailing><span class="mr-2 text-13 text-fg-subtle">soniya</span></template>
                        </Input>
                    </div>
                </fieldset>

                <Alert v-if="formModal.isEdit && formModal.data.status === 'cancelled' && formModal.originalStatus !== 'cancelled'"
                    variant="warning">
                    Turnir bekor qilinganda ro'yxatdan o'tgan o'quvchilarga kirish to'lovi qaytariladi.
                </Alert>
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                <Button type="submit" form="tournament-form" :loading="formModal.loading">
                    {{ formModal.isEdit ? 'Saqlash' : 'Yaratish' }}
                </Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Turnirni o'chirish"
            :message="`«${deleteModal.tournament?.name}» turniri o'chiriladi. Ishtirokchilari bor turnirni o'chirib bo'lmaydi.`"
            @confirm="deleteTournament" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { FlagIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Alert, Badge, Button, DropdownItem, EmptyState, Input, Modal, PageHeader, Select, Table, Textarea,
} from '@/Components/UI';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    tournaments: Array,
    directions: Array,
});

const columns = [
    { key: 'name', label: 'Turnir' },
    { key: 'status', label: 'Holat' },
    { key: 'starts_at', label: 'Boshlanish', hideBelow: 'md' },
    { key: 'participants_count', label: 'Ishtirokchilar', numeric: true, hideBelow: 'sm' },
    { key: 'entry_fee_coins', label: 'Kirish (coin)', numeric: true, hideBelow: 'md' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const typeOptions = [
    { value: 'weekly', label: 'Haftalik' },
    { value: 'monthly', label: 'Oylik' },
    { value: 'seasonal', label: 'Mavsumiy' },
    { value: 'special', label: 'Maxsus' },
];

const STATUS = {
    draft: { label: 'Qoralama', variant: 'neutral' },
    registration: { label: "Ro'yxat ochiq", variant: 'success' },
    in_progress: { label: 'Davom etmoqda', variant: 'info' },
    finished: { label: 'Yakunlangan', variant: 'neutral' },
    cancelled: { label: 'Bekor qilingan', variant: 'danger' },
};
const statusOptions = Object.entries(STATUS).map(([value, meta]) => ({ value, label: meta.label }));
const statusMeta = (status) => STATUS[status] || { label: status, variant: 'neutral' };
const typeLabel = (type) => typeOptions.find((o) => o.value === type)?.label || type;

const openCount = computed(() => (props.tournaments || []).filter((t) => t.status === 'registration').length);

// "2026-11-01T09:00" -> "01.11.2026 09:00"
const formatLocal = (value) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}:\d{2})/.exec(value || '');
    return m ? `${m[3]}.${m[2]}.${m[1]} ${m[4]}` : '—';
};

const FIELDS = [
    'name', 'description', 'direction_id', 'type', 'status', 'entry_fee_coins', 'prize_pool_coins',
    'min_participants', 'max_participants', 'min_elo', 'max_elo', 'registration_starts_at',
    'registration_ends_at', 'starts_at', 'ends_at', 'questions_per_match', 'time_per_question',
];

const emptyData = () => ({
    name: '',
    description: '',
    direction_id: props.directions?.[0]?.value ?? '',
    type: 'weekly',
    status: 'draft',
    entry_fee_coins: 0,
    prize_pool_coins: 0,
    min_participants: 2,
    max_participants: 32,
    min_elo: null,
    max_elo: null,
    registration_starts_at: '',
    registration_ends_at: '',
    starts_at: '',
    ends_at: '',
    questions_per_match: 5,
    time_per_question: 20,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
    tournamentId: null,
    originalStatus: null,
});

const deleteModal = reactive({ show: false, loading: false, tournament: null });

const openCreateModal = () => {
    Object.assign(formModal, { show: true, isEdit: false, tournamentId: null, originalStatus: null, errors: {}, data: emptyData() });
};

const openEditModal = (tournament) => {
    const data = {};
    FIELDS.forEach((key) => { data[key] = tournament[key] ?? (key.endsWith('_at') ? '' : null); });
    Object.assign(formModal, {
        show: true, isEdit: true, tournamentId: tournament.id, originalStatus: tournament.status, errors: {}, data,
    });
};

const closeFormModal = () => {
    formModal.show = false;
    formModal.errors = {};
};

const submitForm = () => {
    formModal.loading = true;
    formModal.errors = {};

    // empty optional fields are sent as null
    const payload = Object.fromEntries(Object.entries(formModal.data).map(([k, v]) => [k, v === '' ? null : v]));
    const options = {
        preserveScroll: true,
        onSuccess: () => closeFormModal(),
        onError: (errors) => (formModal.errors = errors),
        onFinish: () => (formModal.loading = false),
    };

    if (formModal.isEdit) {
        router.put(route('admin.tournaments.update', formModal.tournamentId), payload, options);
    } else {
        router.post(route('admin.tournaments.store'), payload, options);
    }
};

const confirmDelete = (tournament) => {
    deleteModal.tournament = tournament;
    deleteModal.show = true;
};

const deleteTournament = () => {
    deleteModal.loading = true;
    router.delete(route('admin.tournaments.destroy', deleteModal.tournament.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.tournament = null;
        },
        onFinish: () => (deleteModal.loading = false),
    });
};
</script>
