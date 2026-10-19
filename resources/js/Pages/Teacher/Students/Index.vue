<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { UsersIcon, BoltIcon, AcademicCapIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Table from '@/Components/UI/Table.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { formatDate, fullName, timeAgo } from '@/Components/Teacher/format';
import { useServerFilters } from '@/Composables/useServerFilters';

const props = defineProps({
    enrollments: Object,
    stats: Object,
    filters: { type: Object, default: () => ({}) },
    courses: { type: Array, default: () => [] },
});

const { filters, reset } = useServerFilters(
    { search: props.filters.search || '', course: props.filters.course || '', status: props.filters.status || '' },
    route('teacher.students.index'),
    { only: ['enrollments', 'filters'] },
);
const hasFilters = computed(() => !!(filters.search || filters.course || filters.status));
const rows = computed(() => props.enrollments?.data || []);
const courseOptions = computed(() => props.courses.map((c) => ({ value: c.id, label: c.title })));
const statusOptions = [
    { value: 'active', label: 'O'qimoqda' },
    { value: 'completed', label: 'Tugatgan' },
    { value: 'expired', label: 'Muddati tugagan' },
    { value: 'refunded', label: 'Qaytarilgan' },
];
const openStudent = (row) => router.visit(route('teacher.students.show', row.id));

const columns = [
    { key: 'student', label: 'O\'quvchi' },
    { key: 'course', label: 'Kurs', hideBelow: 'md' },
    { key: 'progress', label: 'Progress', width: '160px' },
    { key: 'last_activity_at', label: 'Oxirgi faollik', hideBelow: 'lg' },
    { key: 'status', label: 'Holat', hideBelow: 'sm' },
];

const STATUS = {
    active: { label: 'O\'qimoqda', variant: 'info' },
    completed: { label: 'Tugatgan', variant: 'success' },
    expired: { label: 'Muddati tugagan', variant: 'warning' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
    dropped: { label: 'Tashlab ketgan', variant: 'neutral' },
};
const statusOf = (s) => STATUS[s] || { label: s || '—', variant: 'neutral' };
const pct = (v) => Math.round(Number(v) || 0);
</script>

<template>
    <Head title="O'quvchilarim" />

    <TeacherLayout>
        <PageHeader title="O'quvchilarim" description="Kurslaringizga yozilgan o'quvchilar va ularning o'zlashtirishi." />

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard label="Jami o'quvchilar" :value="stats.total_students" :icon="UsersIcon" tone="brand" />
                <StatCard label="Bu hafta faol" :value="stats.active_this_week" :icon="BoltIcon" tone="success" />
                <StatCard label="Kursni tugatganlar" :value="stats.completed" :icon="AcademicCapIcon" tone="info" />
            </div>

            <div class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <Input v-model="filters.search" type="search" placeholder="Ism yoki familiya bo'yicha qidirish"
                        :leading-icon="MagnifyingGlassIcon" aria-label="O'quvchi qidirish" class="sm:w-72" />
                    <Select v-model="filters.course" :options="courseOptions" placeholder="Barcha kurslar" aria-label="Kurs" class="sm:w-60" />
                    <Select v-model="filters.status" :options="statusOptions" placeholder="Barcha holatlar" aria-label="Holat" class="sm:w-48" />
                    <Button v-if="hasFilters" variant="ghost" @click="reset">Tozalash</Button>
                </div>

                <Table :columns="columns" :rows="rows" caption="O'quvchilar ro'yxati" clickable @row-click="openStudent"
                    :empty-title="hasFilters ? 'Hech kim topilmadi' : 'Hali o'quvchilar yo'q'"
                    :empty-description="hasFilters ? 'Filtrlarni o'zgartirib ko'ring.' : 'O'quvchilar kursingizga yozilganda shu yerda ko'rinadi.'">
                    <template #cell-student="{ row }">
                        <div class="flex min-w-0 items-center gap-3">
                            <Avatar :src="row.user?.avatar_url" :name="fullName(row.user)" size="sm" />
                            <div class="min-w-0">
                                <Link :href="route('teacher.students.show', row.id)" class="focus-ring block truncate rounded-sm font-medium text-fg hover:text-brand">
                                    {{ fullName(row.user) }}
                                </Link>
                                <p class="truncate text-13 text-fg-subtle">
                                    <span class="md:hidden">{{ row.course?.title }}</span>
                                    <span class="hidden md:inline">{{ row.user?.email || row.user?.phone }}</span>
                                </p>
                            </div>
                        </div>
                    </template>
                    <template #cell-course="{ row }">
                        <span class="line-clamp-2 text-fg-muted">{{ row.course?.title }}</span>
                    </template>
                    <template #cell-progress="{ row }">
                        <div class="flex items-center gap-2">
                            <ProgressBar :value="pct(row.progress)" size="sm" :tone="row.status === 'completed' ? 'success' : 'brand'" class="flex-1" />
                            <span class="w-9 text-right text-13 tabular-nums text-fg-muted">{{ pct(row.progress) }}%</span>
                        </div>
                    </template>
                    <template #cell-last_activity_at="{ value }">
                        <span v-if="value" class="text-13 text-fg-muted" :title="formatDate(value)">{{ timeAgo(value) }}</span>
                        <span v-else class="text-13 text-fg-subtle">Hali kirmagan</span>
                    </template>
                    <template #cell-status="{ value }">
                        <Badge :variant="statusOf(value).variant" dot>{{ statusOf(value).label }}</Badge>
                    </template>
                    <template v-if="enrollments.links && enrollments.links.length > 3" #footer>
                        <Pagination :data="enrollments" />
                    </template>
                </Table>
            </div>
        </div>
    </TeacherLayout>
</template>
