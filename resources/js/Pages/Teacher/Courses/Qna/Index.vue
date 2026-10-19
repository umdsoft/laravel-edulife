<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon, QuestionMarkCircleIcon, BookmarkIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Card from '@/Components/UI/Card.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import CourseNav from '@/Components/Teacher/CourseNav.vue';
import QnaItem from '@/Components/Teacher/QnaItem.vue';

const props = defineProps({
    course: Object,
    questions: Object,
    stats: Object,
    filters: Object,
});

const filterStatus = ref(props.filters.status || '');
const loading = ref(false);

watch(filterStatus, (val) => {
    router.get(
        route('teacher.courses.qna.index', props.course.id),
        { status: val },
        {
            preserveState: true,
            replace: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        }
    );
});

// Tabs need a non-empty key for "all"
const tabKey = computed({
    get: () => filterStatus.value || 'all',
    set: (v) => (filterStatus.value = v === 'all' ? '' : v),
});
const tabs = computed(() => [
    { key: 'all', label: 'Barchasi' },
    { key: 'unanswered', label: 'Javobsiz', count: props.stats?.unanswered || undefined },
    { key: 'answered', label: 'Javob berilgan' },
    { key: 'pinned', label: 'Qadalgan' },
]);
</script>

<template>
    <TeacherLayout>
        <Head :title="`${course.title} — Savol-javob`" />

        <PageHeader title="Savol-javob" description="O'quvchilarning dars bo'yicha savollari. Avval javobsizlariga e'tibor bering."
            :breadcrumbs="[{ label: 'Kurslarim', href: route('teacher.courses.index') }, { label: course.title, href: route('teacher.courses.edit', course.id) }, { label: 'Savol-javob' }]">
            <template #tabs>
                <CourseNav :course="course" active="qna" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard label="Jami savollar" :value="stats.total" :icon="ChatBubbleLeftRightIcon" tone="brand" />
                <StatCard label="Javobsiz" :value="stats.unanswered" :icon="QuestionMarkCircleIcon" :tone="stats.unanswered ? 'danger' : 'success'" />
                <StatCard label="Qadalgan" :value="stats.pinned" :icon="BookmarkIcon" tone="info" />
            </div>

            <div class="max-w-3xl space-y-4">
                <Tabs v-model="tabKey" :tabs="tabs" variant="pills" label="Savollar filtri" />

                <div :class="['space-y-4 transition-opacity duration-150', loading ? 'opacity-60' : '']" :aria-busy="loading || undefined">
                    <QnaItem v-for="question in questions.data" :key="question.id" :question="question" :course="course" />

                    <Card v-if="questions.data.length === 0">
                        <EmptyState :icon="ChatBubbleLeftRightIcon" :title="filterStatus ? 'Bu filtr bo\'yicha savol yo\'q' : 'Savollar yo\'q'"
                            :description="filterStatus ? 'Boshqa filtrni tanlab ko\'ring.' : 'O\'quvchilar savol berganda shu yerda ko\'rinadi.'" />
                    </Card>

                    <div v-if="questions.links && questions.links.length > 3" class="overflow-hidden rounded-lg border border-border">
                        <Pagination :data="questions" />
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
