<template>
    <AdminLayout>

        <Head title="Sharhlar" />

        <PageHeader title="Sharhlar" description="O'quvchilarning kurslarga qoldirgan sharhlarini moderatsiya qilish" />

        <div class="space-y-4">
            <FilterBar :active="hasFilters" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Matn, foydalanuvchi yoki kurs" class="sm:w-80"
                    @update:model-value="search" />
                <Select v-model="filters.status" :options="statusOptions" aria-label="Holat" class="sm:w-48"
                    @change="filter" />
                <template v-if="reviews.total !== undefined" #meta>Jami {{ formatNumber(reviews.total) }} ta</template>
            </FilterBar>

            <Table :columns="columns" :rows="reviews.data" :loading="loading" clickable caption="Sharhlar ro'yxati"
                @row-click="openModal">
                <template #cell-user="{ row }">
                    <div class="flex min-w-[12rem] items-center gap-3">
                        <Avatar :src="row.user?.avatar" :name="userName(row)" size="sm" />
                        <span class="truncate font-medium text-fg">{{ userName(row) }}</span>
                    </div>
                </template>
                <template #cell-course="{ row }">
                    <span class="line-clamp-1 min-w-[10rem] text-fg-muted">{{ row.course?.title || '—' }}</span>
                </template>
                <template #cell-rating="{ value }">
                    <span class="inline-flex items-center gap-0.5" role="img" :aria-label="`${value} / 5`">
                        <StarIcon v-for="n in 5" :key="n" class="h-4 w-4"
                            :class="n <= Math.round(Number(value) || 0) ? 'text-warning' : 'text-surface-3'"
                            aria-hidden="true" />
                    </span>
                </template>
                <template #cell-review_text="{ value }">
                    <span class="line-clamp-1 max-w-xs text-fg-muted">{{ value || '—' }}</span>
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="statusMeta(value).variant" dot>{{ statusMeta(value).label }}</Badge>
                </template>
                <template #cell-created_at="{ value }">
                    <span class="whitespace-nowrap tabular-nums text-fg-muted">{{ formatDate(value) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <RowActions label="Sharh amallari">
                        <DropdownItem :icon="EyeIcon" @click="openModal(row)">Ko'rish</DropdownItem>
                        <template v-if="row.status === 'pending'">
                            <DropdownItem :icon="CheckIcon" @click="approve(row)">Tasdiqlash</DropdownItem>
                            <DropdownItem :icon="XMarkIcon" @click="reject(row)">Rad etish</DropdownItem>
                        </template>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="ChatBubbleLeftRightIcon" title="Sharhlar topilmadi" size="sm"
                        :description="hasFilters ? 'Qidiruv yoki filtrlarni o\'zgartirib ko\'ring' : 'Hali sharhlar qoldirilmagan'">
                        <Button v-if="hasFilters" variant="secondary" size="sm" @click="resetFilters">
                            Filtrlarni tozalash
                        </Button>
                    </EmptyState>
                </template>
                <template v-if="reviews.links?.length > 3" #footer>
                    <Pagination :data="reviews" class="!border-t-0" />
                </template>
            </Table>
        </div>

        <!-- Details -->
        <Modal :show="showModal" title="Sharh tafsilotlari" size="lg" :closeable="!processing" @close="closeModal">
            <div v-if="selectedReview" class="space-y-5 pb-2">
                <div class="flex items-center gap-3">
                    <Avatar :src="selectedReview.user?.avatar" :name="userName(selectedReview)" size="md" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-fg">{{ userName(selectedReview) }}</p>
                        <p class="truncate text-13 text-fg-subtle">{{ selectedReview.course?.title }}</p>
                    </div>
                    <Badge :variant="statusMeta(selectedReview.status).variant" dot>
                        {{ statusMeta(selectedReview.status).label }}
                    </Badge>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-0.5" aria-hidden="true">
                        <StarIcon v-for="n in 5" :key="n" class="h-5 w-5"
                            :class="n <= Math.round(Number(selectedReview.rating) || 0) ? 'text-warning' : 'text-surface-3'" />
                    </span>
                    <span class="text-sm font-medium text-fg tabular-nums">{{ selectedReview.rating }} / 5</span>
                    <span class="text-13 text-fg-subtle tabular-nums">· {{ formatDate(selectedReview.created_at) }}</span>
                </div>

                <blockquote class="whitespace-pre-line rounded-md bg-surface-2 p-4 text-sm leading-6 text-fg">
                    {{ selectedReview.review_text || 'Matn yo\'q' }}
                </blockquote>
            </div>

            <template #footer>
                <Button variant="secondary" :disabled="processing" @click="closeModal">Yopish</Button>
                <template v-if="selectedReview?.status === 'pending'">
                    <Button variant="danger" :icon-left="XMarkIcon" :disabled="processing" @click="reject(selectedReview)">
                        Rad etish
                    </Button>
                    <Button :icon-left="CheckIcon" :loading="processing" @click="approve(selectedReview)">
                        Tasdiqlash
                    </Button>
                </template>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Sharhni o'chirish"
            message="Sharh butunlay o'chiriladi. Bu amalni bekor qilib bo'lmaydi." @confirm="deleteReview"
            @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ChatBubbleLeftRightIcon, CheckIcon, EyeIcon, TrashIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Avatar, Badge, Button, DropdownItem, EmptyState, Modal, PageHeader, Pagination, Select, Table,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatDate, formatNumber } from '@/Components/Admin/format';

const props = defineProps({
    reviews: Object,
    filters: Object,
});

const loading = ref(false);
const processing = ref(false);
const showModal = ref(false);
const selectedReview = ref(null);
const deleteModal = reactive({ show: false, loading: false, review: null });

const filters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const STATUS = {
    pending: { label: 'Kutilmoqda', variant: 'warning' },
    approved: { label: 'Tasdiqlangan', variant: 'success' },
    rejected: { label: 'Rad etilgan', variant: 'danger' },
};
const statusMeta = (s) => STATUS[s] || { label: s || '—', variant: 'neutral' };

const statusOptions = [
    { value: '', label: 'Barcha holatlar' },
    { value: 'pending', label: 'Kutilmoqda' },
    { value: 'approved', label: 'Tasdiqlangan' },
    { value: 'rejected', label: 'Rad etilgan' },
];

const columns = [
    { key: 'user', label: 'Foydalanuvchi' },
    { key: 'course', label: 'Kurs', hideBelow: 'md' },
    { key: 'rating', label: 'Baho' },
    { key: 'review_text', label: 'Sharh', hideBelow: 'lg' },
    { key: 'status', label: 'Holat' },
    { key: 'created_at', label: 'Sana', hideBelow: 'sm' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const hasFilters = computed(() => !!(filters.search || filters.status));

const userName = (review) => {
    const u = review?.user;
    if (!u) return 'Noma\'lum';
    return u.full_name || [u.first_name, u.last_name].filter(Boolean).join(' ') || 'Noma\'lum';
};

const visit = (options = {}) => {
    router.get('/admin/reviews', filters, {
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
    filters.status = '';
    visit();
};

const openModal = (review) => {
    selectedReview.value = review;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedReview.value = null;
};

const moderate = (review, action) => {
    processing.value = true;
    router.patch(`/admin/reviews/${review.id}/${action}`, {}, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => {
            processing.value = false;
        },
    });
};

const approve = (review) => moderate(review, 'approve');
const reject = (review) => moderate(review, 'reject');

const confirmDelete = (review) => {
    deleteModal.review = review;
    deleteModal.show = true;
};

const deleteReview = () => {
    deleteModal.loading = true;
    router.delete(`/admin/reviews/${deleteModal.review.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.review = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
