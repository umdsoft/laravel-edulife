<template>
    <AdminLayout>

        <Head title="Kurslar" />

        <PageHeader title="Kurslar" description="Barcha kurslarni ko'rish, tasdiqlash va boshqarish" />

        <div class="space-y-4">
            <FilterBar :active="hasFilters" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Kurs nomi" class="sm:w-72"
                    @update:model-value="search" />
                <Select v-model="filters.direction_id" :options="directionOptions" aria-label="Yo'nalish"
                    class="sm:w-52" @change="filter" />
                <Select v-model="filters.status" :options="statusOptions" aria-label="Holat" class="sm:w-48"
                    @change="filter" />
                <template v-if="courses.total !== undefined" #meta>Jami {{ formatNumber(courses.total) }} ta</template>
            </FilterBar>

            <Table :columns="columns" :rows="courses.data" :loading="loading" clickable caption="Kurslar ro'yxati"
                @row-click="openCourse">
                <template #cell-title="{ row }">
                    <div class="flex min-w-[16rem] items-center gap-3">
                        <img v-if="row.thumbnail && !brokenThumbs[row.id]" :src="mediaUrl(row.thumbnail)" alt=""
                            loading="lazy" class="h-11 w-16 shrink-0 rounded-md bg-surface-2 object-cover"
                            @error="brokenThumbs[row.id] = true" />
                        <span v-else
                            class="inline-flex h-11 w-16 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-subtle">
                            <BookOpenIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <Link :href="route('admin.courses.show', row.id)"
                                class="focus-ring block max-w-xs truncate rounded-sm font-medium text-fg hover:text-brand">
                                {{ row.title }}
                            </Link>
                            <p class="truncate text-13 text-fg-subtle">{{ row.direction_name }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-teacher_name="{ value }">
                    <span class="whitespace-nowrap text-fg-muted">{{ value }}</span>
                </template>
                <template #cell-price="{ value }">
                    <span class="whitespace-nowrap">{{ Number(value) > 0 ? formatMoney(value) : 'Bepul' }}</span>
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="courseStatusMeta(value).variant" dot>{{ courseStatusMeta(value).label }}</Badge>
                </template>
                <template #cell-created_at="{ value }">
                    <span class="whitespace-nowrap tabular-nums text-fg-muted">{{ formatDate(value) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <RowActions :label="`${row.title}: amallar`">
                        <DropdownItem :href="route('admin.courses.show', row.id)" :icon="EyeIcon">
                            {{ row.status === 'pending' ? 'Ko\'rib chiqish' : 'Ko\'rish' }}
                        </DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="BookOpenIcon" title="Kurs topilmadi" size="sm"
                        :description="hasFilters ? 'Qidiruv yoki filtrlarni o\'zgartirib ko\'ring' : 'Hali kurslar yaratilmagan'">
                        <Button v-if="hasFilters" variant="secondary" size="sm" @click="resetFilters">
                            Filtrlarni tozalash
                        </Button>
                    </EmptyState>
                </template>
                <template v-if="courses.links?.length > 3" #footer>
                    <Pagination :data="courses" class="!border-t-0" />
                </template>
            </Table>
        </div>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Kursni o'chirish"
            :message="`«${deleteModal.course?.title}» kursi o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deleteCourse" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpenIcon, EyeIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, DropdownItem, EmptyState, PageHeader, Pagination, Select, Table,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { courseStatusMeta, formatDate, formatMoney, formatNumber, mediaUrl } from '@/Components/Admin/format';

const props = defineProps({
    courses: Object,
    directions: Array,
    filters: Object,
});

const loading = ref(false);
const brokenThumbs = reactive({});
const deleteModal = reactive({
    show: false,
    loading: false,
    course: null,
});

const filters = reactive({
    search: props.filters?.search || '',
    direction_id: props.filters?.direction_id || '',
    status: props.filters?.status || '',
});

const directionOptions = computed(() => [
    { value: '', label: 'Barcha yo\'nalishlar' },
    ...(props.directions || []).map((d) => ({ value: d.id, label: d.name_uz })),
]);

const statusOptions = [
    { value: '', label: 'Barcha holatlar' },
    { value: 'draft', label: 'Qoralama' },
    { value: 'pending', label: 'Kutilmoqda' },
    { value: 'published', label: 'Nashr qilingan' },
    { value: 'rejected', label: 'Rad etilgan' },
];

const columns = [
    { key: 'title', label: 'Kurs' },
    { key: 'teacher_name', label: 'O\'qituvchi', hideBelow: 'md' },
    { key: 'price', label: 'Narx', numeric: true },
    { key: 'status', label: 'Holat' },
    { key: 'created_at', label: 'Yaratilgan', hideBelow: 'lg' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const hasFilters = computed(() => !!(filters.search || filters.direction_id || filters.status));

const visit = (options = {}) => {
    router.get(route('admin.courses.index'), filters, {
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
    filters.direction_id = '';
    filters.status = '';
    visit();
};

const openCourse = (course) => router.visit(route('admin.courses.show', course.id));

const confirmDelete = (course) => {
    deleteModal.course = course;
    deleteModal.show = true;
};

const deleteCourse = () => {
    deleteModal.loading = true;

    router.delete(route('admin.courses.destroy', deleteModal.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.course = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
