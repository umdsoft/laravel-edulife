<template>
    <AdminLayout>

        <Head title="Foydalanuvchilar" />

        <PageHeader title="Foydalanuvchilar" description="Platformadagi barcha foydalanuvchilarni boshqarish">
            <template #actions>
                <Button :icon-left="PlusIcon" :href="route('admin.users.create')">Yangi foydalanuvchi</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <FilterBar :active="hasFilters" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Ism, telefon yoki email" class="sm:w-72"
                    @update:model-value="search" />
                <Select v-model="filters.role" :options="roleOptions" aria-label="Rol" class="sm:w-44"
                    @change="filter" />
                <Select v-model="filters.status" :options="statusOptions" aria-label="Holat" class="sm:w-44"
                    @change="filter" />
                <template v-if="users.total !== undefined" #meta>Jami {{ formatNumber(users.total) }} ta</template>
            </FilterBar>

            <Table :columns="columns" :rows="users.data" :loading="loading" clickable
                caption="Foydalanuvchilar ro'yxati" @row-click="openUser">
                <template #cell-name="{ row }">
                    <div class="flex min-w-[14rem] items-center gap-3">
                        <Avatar :name="row.full_name" size="md" />
                        <div class="min-w-0">
                            <Link :href="route('admin.users.show', row.id)"
                                class="focus-ring block truncate rounded-sm font-medium text-fg hover:text-brand">
                                {{ row.full_name }}
                            </Link>
                            <p class="truncate text-13 text-fg-subtle">{{ row.email || 'Email kiritilmagan' }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-phone="{ value }">
                    <span class="whitespace-nowrap tabular-nums text-fg-muted">{{ value || '—' }}</span>
                </template>
                <template #cell-role="{ value }">
                    <Badge :variant="roleMeta(value).variant">{{ roleMeta(value).label }}</Badge>
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="userStatusMeta(value).variant" dot>{{ userStatusMeta(value).label }}</Badge>
                </template>
                <template #cell-coin_balance="{ value }">{{ value === null ? '—' : formatNumber(value) }}</template>
                <template #cell-created_at="{ value }">
                    <span class="whitespace-nowrap tabular-nums text-fg-muted">{{ formatDate(value) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.full_name}: amallar`">
                        <DropdownItem :href="route('admin.users.show', row.id)" :icon="EyeIcon">Ko'rish</DropdownItem>
                        <DropdownItem :href="route('admin.users.edit', row.id)" :icon="PencilSquareIcon">
                            Tahrirlash
                        </DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="UsersIcon" title="Foydalanuvchi topilmadi" size="sm"
                        :description="hasFilters ? 'Qidiruv yoki filtrlarni o\'zgartirib ko\'ring' : 'Hali foydalanuvchilar yo\'q'">
                        <Button v-if="hasFilters" variant="secondary" size="sm" @click="resetFilters">
                            Filtrlarni tozalash
                        </Button>
                    </EmptyState>
                </template>
                <template v-if="users.links?.length > 3" #footer>
                    <Pagination :data="users" class="!border-t-0" />
                </template>
            </Table>
        </div>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Foydalanuvchini o'chirish"
            :message="`${deleteModal.user?.full_name} foydalanuvchisi o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deleteUser" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { EyeIcon, PencilSquareIcon, PlusIcon, TrashIcon, UsersIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Avatar, Badge, Button, DropdownItem, EmptyState, PageHeader, Pagination, Select, Table,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatDate, formatNumber, roleMeta, userStatusMeta } from '@/Components/Admin/format';

const props = defineProps({
    users: Object,
    filters: Object,
});

const loading = ref(false);
const deleteModal = reactive({
    show: false,
    loading: false,
    user: null,
});

const filters = reactive({
    search: props.filters?.search || '',
    role: props.filters?.role || '',
    status: props.filters?.status || '',
});

const roleOptions = [
    { value: '', label: 'Barcha rollar' },
    { value: 'student', label: 'O\'quvchi' },
    { value: 'teacher', label: 'O\'qituvchi' },
    { value: 'admin', label: 'Admin' },
    { value: 'super_admin', label: 'Super admin' },
];

const statusOptions = [
    { value: '', label: 'Barcha holatlar' },
    { value: 'active', label: 'Faol' },
    { value: 'inactive', label: 'Nofaol' },
    { value: 'blocked', label: 'Bloklangan' },
];

const columns = [
    { key: 'name', label: 'Foydalanuvchi' },
    { key: 'phone', label: 'Telefon', hideBelow: 'md' },
    { key: 'role', label: 'Rol' },
    { key: 'status', label: 'Holat' },
    { key: 'coin_balance', label: 'COIN', numeric: true, hideBelow: 'sm' },
    { key: 'created_at', label: 'Ro\'yxatdan o\'tgan', hideBelow: 'lg' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const hasFilters = computed(() => !!(filters.search || filters.role || filters.status));

const visit = (options = {}) => {
    router.get(route('admin.users.index'), filters, {
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
    filters.role = '';
    filters.status = '';
    visit();
};

const openUser = (user) => router.visit(route('admin.users.show', user.id));

const confirmDelete = (user) => {
    deleteModal.user = user;
    deleteModal.show = true;
};

const deleteUser = () => {
    deleteModal.loading = true;

    router.delete(route('admin.users.destroy', deleteModal.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.user = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
