<script setup>
import { computed } from 'vue';
import { mediaPlaceholderText } from '@/Utils/richText';
import { Head, Link } from '@inertiajs/vue3';
import {
    ChartBarIcon, CheckBadgeIcon, ClockIcon, UsersIcon, QueueListIcon, PencilSquareIcon, QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Table from '@/Components/UI/Table.vue';
import Badge from '@/Components/UI/Badge.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Button from '@/Components/UI/Button.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import CourseNav from '@/Components/Teacher/CourseNav.vue';
import MathText from '@/Components/MathText.vue';
import { formatDateTime, formatTimestamp } from '@/Components/Teacher/format';

const props = defineProps({
    course: Object,
    test: Object,
    stats: Object,
    attempts: { type: Array, default: () => [] },
    questions: { type: Array, default: () => [] },
});

const TYPES = { lesson: 'Dars testi', module: 'Modul testi', final: 'Yakuniy test' };
const typeLabel = computed(() => TYPES[props.test.type] || props.test.type);
const avgTime = computed(() => (props.stats.avg_time ? formatTimestamp(props.stats.avg_time) : '—'));

const plain = (html) => mediaPlaceholderText(String(html || '')).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
const rateTone = (rate) => (rate === null ? 'neutral' : rate >= 70 ? 'success' : rate >= 40 ? 'warning' : 'danger');

const columns = [
    { key: 'student', label: 'O\'quvchi' },
    { key: 'score', label: 'Ball', numeric: true },
    { key: 'result', label: 'Natija' },
    { key: 'time_spent', label: 'Vaqt', numeric: true, hideBelow: 'md' },
    { key: 'submitted_at', label: 'Sana', hideBelow: 'sm' },
];
</script>

<template>
    <Head :title="`${test.title} — Natijalar`" />

    <TeacherLayout>
        <PageHeader :title="test.title" :description="test.description"
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Testlar', href: route('teacher.courses.tests.index', course.id) },
                { label: test.title },
            ]">
            <template #meta>
                <Badge variant="neutral">{{ typeLabel }}</Badge>
                <Badge :variant="test.is_active ? 'success' : 'neutral'" dot>{{ test.is_active ? 'Faol' : 'Nofaol' }}</Badge>
                <Badge variant="neutral">O'tish bali {{ Math.round(test.passing_score) }}%</Badge>
            </template>
            <template #actions>
                <Button variant="secondary" :icon-left="PencilSquareIcon" :href="route('teacher.courses.tests.edit', [course.id, test.id])">Sozlamalar</Button>
                <Button :icon-left="QueueListIcon" :href="route('teacher.tests.questions.index', test.id)">Savollar ({{ test.questions_count }})</Button>
            </template>
            <template #tabs>
                <CourseNav :course="course" active="tests" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Urinishlar" :value="stats.total_attempts" :hint="`${stats.students} ta o'quvchi`" :icon="UsersIcon" tone="brand" />
                <StatCard label="O'rtacha ball" :value="stats.avg_score" unit="%" :icon="ChartBarIcon" tone="info" />
                <StatCard label="O'tganlar" :value="stats.pass_rate" unit="%" :icon="CheckBadgeIcon" tone="success" />
                <StatCard label="O'rtacha vaqt" :value="avgTime" :icon="ClockIcon" tone="warning" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card title="So'nggi urinishlar" description="Oxirgi 50 ta topshirilgan urinish" padding="none" class="min-w-0 lg:col-span-2">
                    <Table :columns="columns" :rows="attempts" :bordered="false" caption="Test urinishlari"
                        empty-title="Hali hech kim topshirmagan" empty-description="O'quvchilar testni topshirganda natijalar shu yerda ko'rinadi.">
                        <template #cell-student="{ row }">
                            <Link :href="route('teacher.students.show', row.enrollment_id)" class="focus-ring flex min-w-0 items-center gap-3 rounded-sm">
                                <Avatar :src="row.avatar_url" :name="row.student" size="sm" />
                                <span class="truncate font-medium text-fg hover:text-brand">{{ row.student || '—' }}</span>
                            </Link>
                        </template>
                        <template #cell-score="{ row }">{{ Math.round(row.score) }}%</template>
                        <template #cell-result="{ row }">
                            <Badge :variant="row.is_passed ? 'success' : 'danger'" dot>{{ row.is_passed ? 'O\'tdi' : 'O\'tmadi' }}</Badge>
                        </template>
                        <template #cell-time_spent="{ value }">{{ formatTimestamp(value) }}</template>
                        <template #cell-submitted_at="{ value }"><span class="text-13 text-fg-muted">{{ formatDateTime(value) }}</span></template>
                    </Table>
                </Card>

                <Card title="Savollar bo'yicha" description="To'g'ri javoblar ulushi (javobsiz qoldirilgani — xato), eng qiyinlari tepada" class="min-w-0">
                    <EmptyState v-if="!questions.length" size="sm" :icon="QuestionMarkCircleIcon" title="Savollar yo'q">
                        <Button size="sm" variant="secondary" :href="route('teacher.tests.questions.create', test.id)">Savol qo'shish</Button>
                    </EmptyState>
                    <ul v-else class="space-y-4">
                        <li v-for="q in questions" :key="q.id">
                            <MathText :text="plain(q.question_text) || 'Savol'" tag="p" class="line-clamp-2 text-sm text-fg" />
                            <div v-if="q.correct_rate !== null" class="mt-1.5 flex items-center gap-2">
                                <ProgressBar :value="q.correct_rate" size="xs" :tone="rateTone(q.correct_rate)" class="flex-1" />
                                <span class="w-10 text-right text-13 tabular-nums text-fg-muted">{{ q.correct_rate }}%</span>
                            </div>
                            <p v-if="q.correct_rate !== null && q.skipped_count" class="mt-0.5 text-xs tabular-nums text-fg-subtle">{{ q.skipped_count }} marta javobsiz qoldirilgan</p>
                            <p v-if="q.correct_rate === null" class="mt-1 text-13 text-fg-subtle">Hali javob berilmagan</p>
                        </li>
                    </ul>
                </Card>
            </div>
        </div>
    </TeacherLayout>
</template>
