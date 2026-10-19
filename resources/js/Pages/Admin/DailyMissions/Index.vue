<template>
    <AdminLayout>

        <Head title="Kunlik vazifalar" />

        <PageHeader title="Kunlik vazifalar" description="Har kuni yangilanadigan vazifalar va ularning mukofotlari">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi vazifa</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <div v-if="missions?.length" class="flex flex-wrap items-center gap-x-4 gap-y-1 text-13 text-fg-subtle">
                <span>Jami <span class="font-medium text-fg tabular-nums">{{ missions.length }}</span> ta</span>
                <span>Faol <span class="font-medium text-fg tabular-nums">{{ activeCount }}</span> ta</span>
            </div>

            <Table :columns="columns" :rows="missions || []" caption="Kunlik vazifalar ro'yxati">
                <template #cell-title="{ row }">
                    <div class="flex min-w-[16rem] items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-xl"
                            aria-hidden="true">{{ row.icon }}</span>
                        <div class="min-w-0">
                            <p class="font-medium text-fg">{{ row.title }}</p>
                            <p v-if="row.description" class="line-clamp-1 max-w-md text-13 text-fg-subtle">{{ row.description }}</p>
                            <code class="font-mono text-xs text-fg-subtle">{{ row.code }}</code>
                        </div>
                    </div>
                </template>
                <template #cell-type="{ value }">
                    <span class="whitespace-nowrap text-fg-muted">{{ getTypeLabel(value) }}</span>
                </template>
                <template #cell-target_count="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-xp_reward="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-coin_reward="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-difficulty="{ value }">
                    <Badge :variant="getDifficultyBadgeVariant(value)">{{ getDifficultyLabel(value) }}</Badge>
                </template>
                <template #cell-is_active="{ row }">
                    <Switch :id="`mission-active-${row.id}`" :model-value="!!row.is_active" size="sm"
                        @update:model-value="toggleStatus(row)" />
                    <label :for="`mission-active-${row.id}`" class="sr-only">{{ row.title }}: faol</label>
                </template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.title}: amallar`">
                        <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="CheckBadgeIcon" title="Vazifalar yo'q" size="sm"
                        description="O'quvchilarni har kuni faol ushlab turish uchun birinchi vazifani yarating">
                        <Button size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi vazifa</Button>
                    </EmptyState>
                </template>
            </Table>
        </div>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Vazifani tahrirlash' : 'Yangi vazifa'" size="2xl"
            :closeable="!formModal.loading" @close="closeFormModal">
            <form id="mission-form" class="space-y-5" @submit.prevent="submitForm">
                <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                    <Input v-model="formModal.data.title" label="Sarlavha" :error="formModal.errors.title" required
                        autofocus />
                    <Input v-model="formModal.data.icon" label="Belgi (emoji)" :error="formModal.errors.icon" required
                        maxlength="10" />
                </div>

                <Input v-model="formModal.data.code" label="Kod" :error="formModal.errors.code" required
                    hint="Tizim ichidagi noyob kalit, masalan: daily_3_lessons" class="font-mono" />

                <Textarea v-model="formModal.data.description" label="Tavsif" :rows="2"
                    :error="formModal.errors.description" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <Select v-model="formModal.data.type" label="Tur" :options="typeOptions" :error="formModal.errors.type"
                        required />
                    <Input v-model.number="formModal.data.target_count" type="number" min="1" inputmode="numeric"
                        label="Maqsad" :hint="formModal.data.type === 'watch_time' ? 'Soniyalarda' : 'Necha marta'"
                        :error="formModal.errors.target_count" required />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <Input v-model.number="formModal.data.xp_reward" type="number" min="0" inputmode="numeric"
                        label="XP mukofoti" :error="formModal.errors.xp_reward" required />
                    <Input v-model.number="formModal.data.coin_reward" type="number" min="0" inputmode="numeric"
                        label="COIN mukofoti" :error="formModal.errors.coin_reward" required />
                    <Select v-model="formModal.data.difficulty" label="Qiyinlik" :options="difficultyOptions"
                        :error="formModal.errors.difficulty" required />
                </div>

                <div class="rounded-md border border-border p-4">
                    <Checkbox v-model="formModal.data.is_active" label="Faol"
                        description="Vazifa o'quvchilarga har kuni beriladi" />
                </div>
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                <Button type="submit" form="mission-form" :loading="formModal.loading">
                    {{ formModal.isEdit ? 'Yangilash' : 'Saqlash' }}
                </Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Vazifani o'chirish"
            :message="`«${deleteModal.mission?.title}» vazifasi o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deleteMission" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckBadgeIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, Checkbox, DropdownItem, EmptyState, Input, Modal, PageHeader, Select, Switch, Table, Textarea,
} from '@/Components/UI';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    missions: Array,
});

const columns = [
    { key: 'title', label: 'Vazifa' },
    { key: 'type', label: 'Tur', hideBelow: 'md' },
    { key: 'target_count', label: 'Maqsad', numeric: true },
    { key: 'xp_reward', label: 'XP', numeric: true, hideBelow: 'sm' },
    { key: 'coin_reward', label: 'COIN', numeric: true, hideBelow: 'sm' },
    { key: 'difficulty', label: 'Qiyinlik', hideBelow: 'sm' },
    { key: 'is_active', label: 'Faol' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const typeOptions = [
    { value: 'lessons', label: 'Darslar' },
    { value: 'tests', label: 'Testlar' },
    { value: 'battles', label: 'Battlelar' },
    { value: 'battle_wins', label: 'Battle g\'alabalari' },
    { value: 'watch_time', label: 'Ko\'rish vaqti (soniya)' },
    { value: 'login', label: 'Kirish' },
];

const difficultyOptions = [
    { value: 'easy', label: 'Oson' },
    { value: 'medium', label: 'O\'rta' },
    { value: 'hard', label: 'Qiyin' },
];

const activeCount = computed(() => (props.missions || []).filter((m) => m.is_active).length);

const emptyData = () => ({
    code: '',
    title: '',
    description: '',
    icon: '',
    type: 'lessons',
    target_count: 1,
    xp_reward: 0,
    coin_reward: 0,
    difficulty: 'easy',
    is_active: true,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
    missionId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    mission: null,
});

const toggleStatus = (mission) => {
    router.patch(`/admin/daily-missions/${mission.id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = emptyData();
    formModal.errors = {};
};

const openEditModal = (mission) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.missionId = mission.id;
    formModal.data = { ...mission };
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
        ? `/admin/daily-missions/${formModal.missionId}`
        : '/admin/daily-missions';

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

const confirmDelete = (mission) => {
    deleteModal.mission = mission;
    deleteModal.show = true;
};

const deleteMission = () => {
    deleteModal.loading = true;

    router.delete(`/admin/daily-missions/${deleteModal.mission.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.mission = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

const getTypeLabel = (type) => {
    const labels = {
        lessons: 'Darslar',
        tests: 'Testlar',
        battles: 'Battlelar',
        battle_wins: 'Battle g\'alabalari',
        watch_time: 'Ko\'rish vaqti',
        login: 'Kirish',
        streak: 'Streak',
    };
    return labels[type] || type;
};

const getDifficultyLabel = (difficulty) => {
    const labels = {
        easy: 'Oson',
        medium: 'O\'rta',
        hard: 'Qiyin',
    };
    return labels[difficulty] || difficulty;
};

const getDifficultyBadgeVariant = (difficulty) => {
    const variants = {
        easy: 'success',
        medium: 'warning',
        hard: 'danger',
    };
    return variants[difficulty] || 'neutral';
};
</script>
