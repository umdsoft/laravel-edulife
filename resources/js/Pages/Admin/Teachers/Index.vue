<template>
    <AdminLayout>

        <Head title="O'qituvchilar" />

        <PageHeader title="O'qituvchilar" description="O'qituvchilarni ko'rish va tasdiqlash" />

        <div class="space-y-4">
            <FilterBar :active="hasFilters" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Ism yoki telefon" class="sm:w-72"
                    @update:model-value="search" />
                <Select v-model="filters.is_verified" :options="verifiedOptions" aria-label="Tasdiqlash holati"
                    class="sm:w-48" @change="filter" />
                <template v-if="teachers.total !== undefined" #meta>Jami {{ formatNumber(teachers.total) }} ta</template>
            </FilterBar>

            <Table :columns="columns" :rows="teachers.data" :loading="loading" clickable
                caption="O'qituvchilar ro'yxati" @row-click="openTeacher">
                <template #cell-name="{ row }">
                    <div class="flex min-w-[14rem] items-center gap-3">
                        <Avatar :name="row.full_name" size="md" />
                        <div class="min-w-0">
                            <Link :href="route('admin.teachers.show', row.id)"
                                class="focus-ring block truncate rounded-sm font-medium text-fg hover:text-brand">
                                {{ row.full_name }}
                            </Link>
                            <p class="truncate text-13 text-fg-subtle tabular-nums">{{ row.phone || row.email || '—' }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-total_courses="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-total_students="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-avg_rating="{ value }">
                    <span class="inline-flex items-center justify-end gap-1">
                        <StarIcon class="h-4 w-4 text-warning" aria-hidden="true" />
                        {{ Number(value || 0).toFixed(1) }}
                    </span>
                </template>
                <template #cell-is_verified="{ value }">
                    <Badge v-if="value" variant="success" :icon="CheckBadgeIcon">Tasdiqlangan</Badge>
                    <Badge v-else variant="warning" dot>Tasdiqlanmagan</Badge>
                </template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.full_name}: amallar`">
                        <DropdownItem :href="route('admin.teachers.show', row.id)" :icon="EyeIcon">Ko'rish</DropdownItem>
                        <DropdownItem v-if="!row.is_verified" :icon="CheckBadgeIcon" @click="setVerified(row, true)">
                            Tasdiqlash
                        </DropdownItem>
                        <DropdownItem v-else :icon="XCircleIcon" @click="setVerified(row, false)">
                            Tasdiqni bekor qilish
                        </DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="AcademicCapIcon" title="O'qituvchi topilmadi" size="sm"
                        :description="hasFilters ? 'Qidiruv yoki filtrlarni o\'zgartirib ko\'ring' : 'Hali o\'qituvchilar yo\'q'">
                        <Button v-if="hasFilters" variant="secondary" size="sm" @click="resetFilters">
                            Filtrlarni tozalash
                        </Button>
                    </EmptyState>
                </template>
                <template v-if="teachers.links?.length > 3" #footer>
                    <Pagination :data="teachers" class="!border-t-0" />
                </template>
            </Table>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AcademicCapIcon, CheckBadgeIcon, EyeIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Avatar, Badge, Button, DropdownItem, EmptyState, PageHeader, Pagination, Select, Table,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import { formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    teachers: Object,
    filters: Object,
});

const loading = ref(false);

const filters = reactive({
    search: props.filters?.search || '',
    is_verified: props.filters?.is_verified || '',
});

const verifiedOptions = [
    { value: '', label: 'Barchasi' },
    { value: 'verified', label: 'Tasdiqlangan' },
    { value: 'unverified', label: 'Tasdiqlanmagan' },
];

const columns = [
    { key: 'name', label: 'O\'qituvchi' },
    { key: 'total_courses', label: 'Kurslar', numeric: true },
    { key: 'total_students', label: 'O\'quvchilar', numeric: true, hideBelow: 'sm' },
    { key: 'avg_rating', label: 'Reyting', numeric: true, hideBelow: 'md' },
    { key: 'is_verified', label: 'Holat' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const hasFilters = computed(() => !!(filters.search || filters.is_verified));

const visit = (options = {}) => {
    router.get(route('admin.teachers.index'), filters, {
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
    filters.is_verified = '';
    visit();
};

const openTeacher = (teacher) => router.visit(route('admin.teachers.show', teacher.id));

const setVerified = (teacher, verified) => {
    router.patch(route(verified ? 'admin.teachers.verify' : 'admin.teachers.unverify', teacher.id), {}, {
        preserveScroll: true,
    });
};
</script>
