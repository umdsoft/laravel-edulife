<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { UsersIcon, BoltIcon, AcademicCapIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Table from '@/Components/UI/Table.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Input from '@/Components/UI/Input.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { formatDate, fullName, timeAgo } from '@/Components/Teacher/format';

const props = defineProps({
    enrollments: Object,
    stats: Object,
});

const search = ref('');
const rows = computed(() => {
    const q = search.value.trim().toLowerCase();
    const list = props.enrollments?.data || [];
    if (!q) return list;
    return list.filter((e) => [fullName(e.user), e.user?.email, e.user?.phone, e.course?.title]
        .some((v) => String(v || '').toLowerCase().includes(q)));
});

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
                <Input v-if="(enrollments.data || []).length" v-model="search" type="search" placeholder="Ism, email yoki kurs bo'yicha qidirish"
                    :leading-icon="MagnifyingGlassIcon" aria-label="O'quvchi qidirish" class="sm:max-w-sm" />

                <Table :columns="columns" :rows="rows" caption="O'quvchilar ro'yxati"
                    :empty-title="search ? 'Hech kim topilmadi' : 'Hali o\'quvchilar yo\'q'"
                    :empty-description="search ? 'Qidiruv so\'zini o\'zgartirib ko\'ring.' : 'O\'quvchilar kursingizga yozilganda shu yerda ko\'rinadi.'">
                    <template #cell-student="{ row }">
                        <div class="flex min-w-0 items-center gap-3">
                            <Avatar :src="row.user?.avatar_url" :name="fullName(row.user)" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-fg">{{ fullName(row.user) }}</p>
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
