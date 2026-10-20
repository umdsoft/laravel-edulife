<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    BookOpenIcon, CheckCircleIcon, ClipboardDocumentCheckIcon, ClockIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckSolid, PlayCircleIcon, DocumentTextIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Table from '@/Components/UI/Table.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { formatDate, formatDateTime, timeAgo } from '@/Components/Teacher/format';

const props = defineProps({
    enrollment: Object,
    student: Object,
    course: Object,
    modules: { type: Array, default: () => [] },
    attempts: { type: Array, default: () => [] },
    summary: Object,
});

const STATUS = {
    active: { label: 'O\'qimoqda', variant: 'info' },
    completed: { label: 'Tugatgan', variant: 'success' },
    expired: { label: 'Muddati tugagan', variant: 'warning' },
    refunded: { label: 'Qaytarilgan', variant: 'neutral' },
};
const status = computed(() => STATUS[props.enrollment.status] || { label: props.enrollment.status, variant: 'neutral' });
const progress = computed(() => Math.round(Number(props.enrollment.progress) || 0));

const watchTime = computed(() => {
    const minutes = Math.round((props.enrollment.total_watch_time || 0) / 60);
    if (minutes < 60) return { value: minutes, unit: 'daq' };
    return { value: Math.round((minutes / 60) * 10) / 10, unit: 'soat' };
});

const moduleDone = (m) => m.lessons.filter((l) => l.is_completed).length;

const TEST_TYPES = { lesson: 'Dars testi', module: 'Modul testi', final: 'Yakuniy test' };
const ATTEMPT_STATUS = {
    in_progress: { label: 'Jarayonda', variant: 'info' },
    submitted: { label: 'Topshirilgan', variant: 'neutral' },
    expired: { label: 'Vaqti tugagan', variant: 'warning' },
    cancelled: { label: 'Bekor qilingan', variant: 'neutral' },
};

const attemptColumns = [
    { key: 'test_title', label: 'Test' },
    { key: 'attempt_number', label: 'Urinish', numeric: true, hideBelow: 'sm' },
    { key: 'score', label: 'Ball', numeric: true },
    { key: 'result', label: 'Natija' },
    { key: 'submitted_at', label: 'Sana', hideBelow: 'md' },
];
</script>

<template>
    <Head :title="student.full_name" />

    <TeacherLayout>
        <PageHeader :title="student.full_name"
            :breadcrumbs="[{ label: 'O\'quvchilarim', href: route('teacher.students.index') }, { label: student.full_name }]">
            <template #meta>
                <Badge :variant="status.variant" dot>{{ status.label }}</Badge>
                <Badge variant="neutral">{{ course.title }}</Badge>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Progress" :value="progress" unit="%" :icon="BookOpenIcon" tone="brand">
                    <ProgressBar :value="progress" size="xs" class="mt-3" :tone="enrollment.status === 'completed' ? 'success' : 'brand'" />
                </StatCard>
                <StatCard label="Tugatilgan darslar" :value="`${summary.completed_lessons} / ${summary.total_lessons}`" :icon="CheckCircleIcon" tone="success" />
                <StatCard label="O'tilgan testlar" :value="summary.tests_passed" :hint="`${summary.attempts} ta urinish`" :icon="ClipboardDocumentCheckIcon" tone="info" />
                <StatCard label="Ko'rish vaqti" :value="watchTime.value" :unit="watchTime.unit" :icon="ClockIcon" tone="warning" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="min-w-0 space-y-6 lg:col-span-2">
                    <Card title="Darslar bo'yicha o'zlashtirish" :description="course.title">
                        <EmptyState v-if="!modules.length" size="sm" :icon="BookOpenIcon" title="Kursda hali darslar yo'q" />
                        <div v-else class="space-y-5">
                            <section v-for="m in modules" :key="m.id">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-fg">{{ m.title }}</h3>
                                    <span class="text-13 tabular-nums text-fg-subtle">{{ moduleDone(m) }} / {{ m.lessons.length }}</span>
                                </div>
                                <ul class="divide-y divide-border rounded-md border border-border">
                                    <li v-for="l in m.lessons" :key="l.id" class="flex items-center gap-3 px-3 py-2.5">
                                        <CheckSolid v-if="l.is_completed" class="h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                                        <PlayCircleIcon v-else-if="l.type === 'video'" class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                        <DocumentTextIcon v-else class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                        <span class="min-w-0 flex-1 truncate text-sm" :class="l.is_completed ? 'text-fg' : 'text-fg-muted'">{{ l.title }}</span>
                                        <span v-if="l.is_completed" class="shrink-0 text-13 tabular-nums text-fg-subtle">{{ formatDate(l.completed_at) }}</span>
                                        <span v-else-if="l.started && l.type === 'video'" class="shrink-0 text-13 tabular-nums text-fg-subtle">{{ l.watch_percentage }}%</span>
                                        <span v-else-if="l.started" class="shrink-0 text-13 text-fg-subtle">Boshlangan</span>
                                        <span v-else class="sr-only">Boshlanmagan</span>
                                    </li>
                                    <li v-if="!m.lessons.length" class="px-3 py-2.5 text-13 text-fg-subtle">Darslar yo'q</li>
                                </ul>
                            </section>
                        </div>
                    </Card>

                    <Card title="Test urinishlari" padding="none">
                        <Table :columns="attemptColumns" :rows="attempts" :bordered="false" caption="Test urinishlari"
                            empty-title="Hali test topshirilmagan" empty-description="O'quvchi test topshirganda natijalar shu yerda ko'rinadi.">
                            <template #cell-test_title="{ row }">
                                <p class="font-medium text-fg">{{ row.test_title || '—' }}</p>
                                <p class="text-13 text-fg-subtle">{{ TEST_TYPES[row.test_type] || '' }}</p>
                            </template>
                            <template #cell-score="{ row }">
                                <span v-if="row.status === 'submitted'">{{ Math.round(row.score) }}%</span>
                                <span v-else class="text-fg-subtle">—</span>
                            </template>
                            <template #cell-result="{ row }">
                                <Badge v-if="row.status !== 'submitted'" :variant="(ATTEMPT_STATUS[row.status] || {}).variant || 'neutral'">
                                    {{ (ATTEMPT_STATUS[row.status] || {}).label || row.status }}
                                </Badge>
                                <Badge v-else :variant="row.is_passed ? 'success' : 'danger'" dot>{{ row.is_passed ? 'O\'tdi' : 'O\'tmadi' }}</Badge>
                            </template>
                            <template #cell-submitted_at="{ row }">
                                <span class="text-13 text-fg-muted">{{ formatDateTime(row.submitted_at || row.started_at) }}</span>
                            </template>
                        </Table>
                    </Card>
                </div>

                <aside class="space-y-6">
                    <Card>
                        <div class="flex items-center gap-4">
                            <Avatar :src="student.avatar_url" :name="student.full_name" size="lg" />
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-fg">{{ student.full_name }}</p>
                                <p class="text-13 text-fg-subtle">O'quvchi</p>
                            </div>
                        </div>
                    </Card>
                    <Card title="Ma'lumot">
                        <dl class="divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Yozilgan sana</dt>
                                <dd class="font-medium tabular-nums">{{ formatDate(enrollment.created_at) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Oxirgi faollik</dt>
                                <dd class="font-medium">{{ enrollment.last_activity_at ? timeAgo(enrollment.last_activity_at) : 'Hali kirmagan' }}</dd>
                            </div>
                            <div v-if="enrollment.completed_at" class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Tugatgan sana</dt>
                                <dd class="font-medium tabular-nums">{{ formatDate(enrollment.completed_at) }}</dd>
                            </div>
                        </dl>
                    </Card>
                </aside>
            </div>
        </div>
    </TeacherLayout>
</template>
