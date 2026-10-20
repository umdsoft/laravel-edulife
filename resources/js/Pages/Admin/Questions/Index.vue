<template>
    <AdminLayout>

        <Head title="Savollar banki" />

        <PageHeader title="Savollar banki" description="Testlardagi barcha savollar" />

        <div class="space-y-4">
            <FilterBar :active="!!filters.search" @reset="resetFilters">
                <SearchInput v-model="filters.search" placeholder="Savol matni" class="sm:w-80"
                    @update:model-value="search" />
                <template v-if="questions.total !== undefined" #meta>Jami {{ formatNumber(questions.total) }} ta</template>
            </FilterBar>

            <Table :columns="columns" :rows="questions.data" :loading="loading" clickable caption="Savollar ro'yxati"
                @row-click="openModal">
                <template #cell-question="{ row }">
                    <p class="line-clamp-2 min-w-[16rem] max-w-xl text-fg">{{ mediaPlaceholderText(row.question_text) }}</p>
                </template>
                <template #cell-test="{ row }">
                    <span class="line-clamp-1 text-fg-muted">{{ row.test?.title || '—' }}</span>
                </template>
                <template #cell-course="{ row }">
                    <span class="line-clamp-1 text-fg-muted">{{ row.course?.title || '—' }}</span>
                </template>
                <template #cell-type="{ value }">
                    <Badge variant="neutral">{{ typeLabel(value) }}</Badge>
                </template>
                <template #cell-points="{ value }">{{ formatNumber(value) }}</template>
                <template #cell-actions="{ row }">
                    <RowActions label="Savol amallari">
                        <DropdownItem :icon="EyeIcon" @click="openModal(row)">Ko'rish</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="confirmDelete(row)">O'chirish</DropdownItem>
                    </RowActions>
                </template>
                <template #empty>
                    <EmptyState :icon="QuestionMarkCircleIcon" title="Savollar topilmadi" size="sm"
                        :description="filters.search ? 'Boshqa so\'z bilan qidirib ko\'ring' : 'Savollar testlar ichida yaratiladi'">
                        <Button v-if="filters.search" variant="secondary" size="sm" @click="resetFilters">
                            Qidiruvni tozalash
                        </Button>
                    </EmptyState>
                </template>
                <template v-if="questions.links?.length > 3" #footer>
                    <Pagination :data="questions" class="!border-t-0" />
                </template>
            </Table>
        </div>

        <!-- Details -->
        <Modal :show="showModal" title="Savol tafsilotlari" size="xl" @close="closeModal">
            <div v-if="selectedQuestion" class="space-y-5 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="neutral">{{ typeLabel(selectedQuestion.type) }}</Badge>
                    <Badge variant="brand">{{ formatNumber(selectedQuestion.points) }} ball</Badge>
                    <span v-if="selectedQuestion.test?.title" class="text-13 text-fg-subtle">
                        {{ selectedQuestion.test.title }}
                    </span>
                </div>

                <div>
                    <p class="text-13 font-medium text-fg-subtle">Savol</p>
                    <MathText :text="selectedQuestion.question_text" tag="div" class="mt-1 block text-base text-fg" />
                </div>

                <div v-if="choices.length">
                    <p class="mb-2 text-13 font-medium text-fg-subtle">Javob variantlari</p>
                    <ul class="space-y-2">
                        <li v-for="(choice, i) in choices" :key="choice.id ?? i" :class="[
                            'flex items-start justify-between gap-3 rounded-md border px-3 py-2.5 text-sm',
                            choice.is_correct ? 'border-success-100 bg-success-50' : 'border-border bg-surface',
                        ]">
                            <MathText :text="choice.option_text ?? choice.answer_text ?? choice.text" class="min-w-0 text-fg" />
                            <Badge v-if="choice.is_correct" variant="success" size="sm" :icon="CheckIcon">To'g'ri</Badge>
                        </li>
                    </ul>
                </div>

                <div v-if="selectedQuestion.explanation">
                    <p class="text-13 font-medium text-fg-subtle">Izoh</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-fg-muted">{{ selectedQuestion.explanation }}</p>
                </div>
            </div>

            <template #footer>
                <Button variant="secondary" @click="closeModal">Yopish</Button>
            </template>
        </Modal>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Savolni o'chirish"
            message="Savol testdan butunlay o'chiriladi. Bu amalni bekor qilib bo'lmaydi." @confirm="deleteQuestion"
            @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckIcon, EyeIcon, QuestionMarkCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, DropdownItem, EmptyState, Modal, PageHeader, Pagination, Table,
} from '@/Components/UI';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import FilterBar from '@/Components/Admin/FilterBar.vue';
import RowActions from '@/Components/Admin/RowActions.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatNumber } from '@/Components/Admin/format';
import MathText from '@/Components/MathText.vue';
import { mediaPlaceholderText } from '@/Utils/richText';

const props = defineProps({
    questions: Object,
    filters: Object,
});

const loading = ref(false);
const showModal = ref(false);
const selectedQuestion = ref(null);
const deleteModal = reactive({ show: false, loading: false, question: null });

const filters = reactive({
    search: props.filters?.search || '',
});

const columns = [
    { key: 'question', label: 'Savol' },
    { key: 'test', label: 'Test', hideBelow: 'md' },
    { key: 'course', label: 'Kurs', hideBelow: 'lg' },
    { key: 'type', label: 'Tur', hideBelow: 'sm' },
    { key: 'points', label: 'Ball', numeric: true },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const TYPES = {
    single_choice: 'Bitta javob',
    multiple_choice: 'Bir nechta javob',
    true_false: 'To\'g\'ri / noto\'g\'ri',
    fill_blank: 'Bo\'sh joy',
    matching: 'Moslashtirish',
    ordering: 'Tartiblash',
};
const typeLabel = (type) => TYPES[type] || type || '—';

const choices = computed(() => {
    const q = selectedQuestion.value;
    if (!q) return [];
    return q.options?.length ? q.options : (q.answers || []);
});

const visit = (options = {}) => {
    router.get('/admin/questions', filters, {
        preserveState: true,
        onStart: () => { loading.value = true; },
        onFinish: () => { loading.value = false; },
        ...options,
    });
};

const search = () => visit({ preserveScroll: true });

const resetFilters = () => {
    filters.search = '';
    visit();
};

const openModal = (question) => {
    selectedQuestion.value = question;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedQuestion.value = null;
};

const confirmDelete = (question) => {
    deleteModal.question = question;
    deleteModal.show = true;
};

const deleteQuestion = () => {
    deleteModal.loading = true;
    router.delete(`/admin/questions/${deleteModal.question.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.show = false;
            deleteModal.question = null;
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
