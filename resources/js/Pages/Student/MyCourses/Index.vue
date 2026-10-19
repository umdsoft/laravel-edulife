<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { BookOpenIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Tabs, EmptyState, Pagination, Button } from '@/Components/UI';
import ContinueLearningCard from '@/Components/Student/ContinueLearningCard.vue';

const props = defineProps({
    enrollments: Object,
    stats: Object,
});

const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', href: route('student.my-courses.index'), count: props.stats?.total, active: true },
    { key: 'progress', label: 'Jarayonda', href: route('student.my-courses.in-progress'), count: props.stats?.in_progress },
    { key: 'completed', label: 'Tugatilgan', href: route('student.my-courses.completed'), count: props.stats?.completed },
]);
</script>

<template>

    <Head title="Mening kurslarim" />

    <StudentLayout title="Kurslarim">
        <PageHeader title="Mening kurslarim" description="Yozilgan barcha kurslaringiz va ulardagi progress.">
            <template #actions>
                <Button variant="secondary" :href="route('student.courses.index')">Yangi kurs topish</Button>
            </template>
            <template #tabs>
                <Tabs :tabs="tabs" label="Kurslar holati" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div v-if="enrollments.data.length > 0" class="grid gap-3 md:grid-cols-2">
                <ContinueLearningCard v-for="enrollment in enrollments.data" :key="enrollment.id" :enrollment="enrollment" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="BookOpenIcon" title="Sizda hozircha kurslar yo'q"
                description="Yangi bilimlarni o'rganishni boshlash uchun kurslarga yoziling.">
                <Button :href="route('student.courses.index')">Kurslarni ko'rish</Button>
            </EmptyState>

            <Pagination :data="enrollments" />
        </div>
    </StudentLayout>
</template>
