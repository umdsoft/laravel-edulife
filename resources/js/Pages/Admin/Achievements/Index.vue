<template>
    <AdminLayout>

        <Head title="Yutuqlar" />

        <PageHeader title="Yutuqlar" description="O'quvchilar qo'lga kiritadigan yutuqlar va mukofotlar">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreateModal">Yangi yutuq</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <FilterBar :active="hasFilters" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Sarlavha yoki kod" class="sm:w-72"
                    @update:model-value="search" />
                <Select v-model="filters.category" :options="categoryFilterOptions" aria-label="Kategoriya"
                    class="sm:w-48" @change="filter" />
                <Select v-model="filters.rarity" :options="rarityFilterOptions" aria-label="Kamyoblik" class="sm:w-48"
                    @change="filter" />
                <template v-if="achievements.total !== undefined" #meta>
                    Jami {{ formatNumber(achievements.total) }} ta
                </template>
            </FilterBar>

            <Table :columns="columns" :rows="achievements.data" :loading="loading" caption="Yutuqlar ro'yxati">
                <template #cell-title="{ row }">
                    <div class="flex min-w-[16rem] items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-xl"
                            aria-hidden="true">{{ row.icon }}</span>
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 font-medium text-fg">
                                {{ row.title }}
                                <Badge v-if="row.is_hidden" variant="neutral" size="sm" :icon="EyeSlashIcon">Yashirin</Badge>
                            </p>
                            <p v-if="row.description" class="line-clamp-1 max-w-md text-13 text-fg-subtle">{{ row.description }}</p>
                            <code class="font-mono text-xs text-fg-subtle">{{ row.code }}</code>
                        </div>
                    </div>
                </template>
                <template #cell-category="{ value }">
                    <span class="text-fg-muted">{{ categoryLabel(value) }}</span>
                </template>
                <template #cell-rarity="{ value }">
                    <Badge :variant="rarityMeta(value).variant">{{ rarityMeta(value).label }}</Badge>
                </template>
                <template #cell-xp_reward="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-coin_reward="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-is_active="{ row }">
                    <Switch :id="`ach-active-${row.id}`" :model-value="!!row.is_active" size="sm"
                        @update:model-value="toggleStatus(row)" />
                    <label :for="`ach-active-${row.id}`" class="sr-only">{{ row.title }}: faol</label>
                </template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.title}: amallar`">
                        <DropdownItem :icon="PencilSquareIcon" @click="openEditModal(row)">Tahrirlash</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="StarIcon" title="Yutuq topilmadi" size="sm"
                        :description="hasFilters ? 'Qidiruv yoki filtrlarni o\'zgartirib ko\'ring' : 'Birinchi yutuqni yarating'">
                        <Button v-if="hasFilters" variant="secondary" size="sm" @click="resetFilters">
                            Filtrlarni tozalash
                        </Button>
                        <Button v-else size="sm" :icon-left="PlusIcon" @click="openCreateModal">Yangi yutuq</Button>
                    </EmptyState>
                </template>
                <template v-if="achievements.links?.length > 3" #footer>
                    <Pagination :data="achievements" class="!border-t-0" />
                </template>
            </Table>
        </div>

        <!-- Create / edit -->
        <Modal :show="formModal.show" :title="formModal.isEdit ? 'Yutuqni tahrirlash' : 'Yangi yutuq'" size="2xl"
            :closeable="!formModal.loading" @close="closeFormModal">
            <form id="achievement-form" class="space-y-5" @submit.prevent="submitForm">
                <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                    <Input v-model="formModal.data.title" label="Sarlavha" :error="formModal.errors.title" required
                        autofocus />
                    <Input v-model="formModal.data.icon" label="Belgi (emoji)" :error="formModal.errors.icon" required
                        maxlength="10" />
                </div>

                <Input v-model="formModal.data.code" label="Kod" :error="formModal.errors.code" required
                    hint="Tizim ichidagi noyob kalit, masalan: first_lesson" class="font-mono" />

                <Textarea v-model="formModal.data.description" label="Tavsif" :rows="2"
                    :error="formModal.errors.description" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <Select v-model="formModal.data.category" label="Kategoriya" :options="categoryOptions"
                        :error="formModal.errors.category" required />
                    <Select v-model="formModal.data.rarity" label="Kamyoblik" :options="rarityOptions"
                        :error="formModal.errors.rarity" required />
                    <Input v-model.number="formModal.data.xp_reward" type="number" min="0" inputmode="numeric"
                        label="XP mukofoti" :error="formModal.errors.xp_reward" required />
                    <Input v-model.number="formModal.data.coin_reward" type="number" min="0" inputmode="numeric"
                        label="COIN mukofoti" :error="formModal.errors.coin_reward" required />
                </div>

                <div class="grid gap-4 rounded-md border border-border p-4 sm:grid-cols-2">
                    <Checkbox v-model="formModal.data.is_active" label="Faol"
                        description="O'quvchilar bu yutuqni qo'lga kirita oladi" />
                    <Checkbox v-model="formModal.data.is_hidden" label="Yashirin"
                        description="Ochilmaguncha ro'yxatda ko'rinmaydi" />
                </div>
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="formModal.loading" @click="closeFormModal">Bekor qilish</Button>
                <Button type="submit" form="achievement-form" :loading="formModal.loading">
                    {{ formModal.isEdit ? 'Yangilash' : 'Saqlash' }}
                </Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Yutuqni o'chirish"
            :message="`«${deleteModal.achievement?.title}» yutug'i o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deleteAchievement" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { EyeSlashIcon, PencilSquareIcon, PlusIcon, StarIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, Checkbox, DropdownItem, EmptyState, Input, Modal, PageHeader, Pagination, Select, Switch,
    Table, Textarea,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    achievements: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    rarity: props.filters?.rarity || '',
});

const CATEGORIES = {
    learning: 'O\'qish',
    testing: 'Test',
    battle: 'Battle',
    tournament: 'Turnir',
    streak: 'Streak',
    social: 'Ijtimoiy',
    special: 'Maxsus',
};
const RARITIES = {
    common: { label: 'Oddiy', variant: 'neutral' },
    rare: { label: 'Kam uchraydigan', variant: 'info' },
    epic: { label: 'Epik', variant: 'brand' },
    legendary: { label: 'Afsonaviy', variant: 'warning' },
};

const categoryOptions = Object.entries(CATEGORIES).map(([value, label]) => ({ value, label }));
const rarityOptions = Object.entries(RARITIES).map(([value, m]) => ({ value, label: m.label }));
const categoryFilterOptions = [{ value: '', label: 'Barcha kategoriyalar' }, ...categoryOptions];
const rarityFilterOptions = [{ value: '', label: 'Barcha kamyobliklar' }, ...rarityOptions];

const categoryLabel = (c) => CATEGORIES[c] || c;
const rarityMeta = (r) => RARITIES[r] || { label: r || '—', variant: 'neutral' };

const columns = [
    { key: 'title', label: 'Yutuq' },
    { key: 'category', label: 'Kategoriya', hideBelow: 'md' },
    { key: 'rarity', label: 'Kamyoblik' },
    { key: 'xp_reward', label: 'XP', numeric: true, hideBelow: 'sm' },
    { key: 'coin_reward', label: 'COIN', numeric: true, hideBelow: 'sm' },
    { key: 'is_active', label: 'Faol' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const hasFilters = computed(() => !!(filters.search || filters.category || filters.rarity));

const emptyData = () => ({
    code: '',
    title: '',
    description: '',
    icon: '',
    category: 'learning',
    rarity: 'common',
    xp_reward: 0,
    coin_reward: 0,
    is_hidden: false,
    is_active: true,
});

const formModal = reactive({
    show: false,
    isEdit: false,
    loading: false,
    errors: {},
    data: emptyData(),
    achievementId: null,
});

const deleteModal = reactive({
    show: false,
    loading: false,
    achievement: null,
});

const visit = (options = {}) => {
    router.get('/admin/achievements', filters, {
        preserveState: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
        ...options,
    });
};

const search = () => visit({ preserveScroll: true });
const filter = () => visit();

const resetFilters = () => {
    filters.search = '';
    filters.category = '';
    filters.rarity = '';
    visit();
};

const toggleStatus = (achievement) => {
    router.patch(`/admin/achievements/${achievement.id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    formModal.show = true;
    formModal.isEdit = false;
    formModal.data = emptyData();
    formModal.errors = {};
};

const openEditModal = (achievement) => {
    formModal.show = true;
    formModal.isEdit = true;
    formModal.achievementId = achievement.id;
    formModal.data = {
        code: achievement.code,
        title: achievement.title,
        description: achievement.description,
        icon: achievement.icon,
        category: achievement.category,
        rarity: achievement.rarity,
        xp_reward: achievement.xp_reward,
        coin_reward: achievement.coin_reward,
        is_hidden: !!achievement.is_hidden,
        is_active: !!achievement.is_active,
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
        ? `/admin/achievements/${formModal.achievementId}`
        : '/admin/achievements';

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

const confirmDelete = (achievement) => {
    deleteModal.achievement = achievement;
    deleteModal.show = true;
};

const deleteAchievement = () => {
    deleteModal.loading = true;

    router.delete(`/admin/achievements/${deleteModal.achievement.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.achievement = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
