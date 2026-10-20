<script setup>
import { Head, router } from '@inertiajs/vue3';
import { CheckBadgeIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { mediaPlaceholderText } from '@/Utils/richText';
import { Avatar, Badge, Button, PageHeader, Pagination, Table } from '@/Components/UI';

const props = defineProps({
    olympiad: Object,
    section: Object,
    queue: Object, // paginator of answers waiting for a manual grade
    graded_count: { type: Number, default: 0 },
});

const gradeHref = (row) => route('admin.olympiads.grading.grade', { olympiad: props.olympiad.id, answer: row.id });
const openRow = (row) => router.visit(gradeHref(row));
const minutes = (s) => `${Math.floor((s || 0) / 60)}:${String((s || 0) % 60).padStart(2, '0')}`;

const columns = [
    { key: 'user_name', label: 'Ishtirokchi' },
    { key: 'question_text', label: 'Savol va javob' },
    { key: 'max_points', label: 'Maks. ball', numeric: true, hideBelow: 'sm' },
    { key: 'time_spent', label: 'Vaqt', numeric: true, hideBelow: 'md' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];
</script>

<template>
    <AdminLayout>
        <Head :title="`${section.title} — baholash navbati`" />

        <PageHeader :title="section.title" description="Qo'lda baholanishi kerak bo'lgan javoblar (eng eskisi birinchi)"
            :breadcrumbs="[
                { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
                { label: olympiad.title, href: route('admin.olympiads.show', olympiad.id) },
                { label: 'Baholash', href: route('admin.olympiads.grading.index', olympiad.id) },
                { label: section.title },
            ]">
            <template #meta>
                <Badge variant="warning">Kutmoqda: {{ queue.total ?? (queue.data || []).length }}</Badge>
                <Badge variant="success">Baholangan: {{ graded_count }}</Badge>
            </template>
            <template v-if="(queue.data || []).length" #actions>
                <Button :icon-left="PencilSquareIcon" :href="gradeHref(queue.data[0])">Baholashni boshlash</Button>
            </template>
        </PageHeader>

        <Table :columns="columns" :rows="queue.data || []" clickable caption="Baholash navbati" @row-click="openRow"
            empty-title="Navbat bo'sh" empty-description="Bu bo'limdagi barcha javoblar baholangan.">
            <template #empty>
                <div class="flex flex-col items-center gap-2 py-10 text-center">
                    <CheckBadgeIcon class="h-8 w-8 text-success" aria-hidden="true" />
                    <p class="font-medium text-fg">Navbat bo'sh</p>
                    <p class="text-13 text-fg-muted">Bu bo'limdagi barcha javoblar baholangan.</p>
                </div>
            </template>
            <template #cell-user_name="{ row }">
                <div class="flex min-w-0 items-center gap-3">
                    <Avatar :name="row.user_name" size="sm" />
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg">{{ row.user_name || '—' }}</p>
                        <p class="text-13 tabular-nums text-fg-subtle">{{ row.created_at }}</p>
                    </div>
                </div>
            </template>
            <template #cell-question_text="{ row }">
                <p class="line-clamp-1 text-fg">{{ mediaPlaceholderText(row.question_text) }}</p>
                <p class="line-clamp-2 text-13 text-fg-muted">{{ row.answer_preview || 'Javob bo\'sh' }}</p>
            </template>
            <template #cell-time_spent="{ value }">{{ minutes(value) }}</template>
            <template #cell-actions="{ row }">
                <Button size="sm" variant="secondary" :href="gradeHref(row)">Baholash</Button>
            </template>
            <template #footer><Pagination :data="queue" /></template>
        </Table>
    </AdminLayout>
</template>
