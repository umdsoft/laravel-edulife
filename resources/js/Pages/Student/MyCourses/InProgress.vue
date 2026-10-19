<script setup>
import { Head } from '@inertiajs/vue3';
import { ClockIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Tabs, EmptyState, Pagination, Button } from '@/Components/UI';
import ContinueLearningCard from '@/Components/Student/ContinueLearningCard.vue';

defineProps({
    enrollments: Object,
});

const tabs = [
    { key: 'all', label: 'Barchasi', href: route('student.my-courses.index') },
    { key: 'progress', label: 'Jarayonda', href: route('student.my-courses.in-progress'), active: true },
    { key: 'completed', label: 'Tugatilgan', href: route('student.my-courses.completed') },
];
</script>

<template>

    <Head title="Jarayondagi kurslar" />

    <StudentLayout title="Kurslarim">
        <PageHeader title="Mening kurslarim" description="Hozir o'qiyotgan kurslaringiz.">
            <template #tabs>
                <Tabs :tabs="tabs" label="Kurslar holati" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div v-if="enrollments.data.length > 0" class="grid gap-3 md:grid-cols-2">
                <ContinueLearningCard v-for="enrollment in enrollments.data" :key="enrollment.id" :enrollment="enrollment" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="ClockIcon" title="Jarayondagi kurslar yo'q"
                description="Sizda hozircha o'qilayotgan kurslar mavjud emas.">
                <Button :href="route('student.courses.index')">Kurslarni ko'rish</Button>
            </EmptyState>

            <Pagination :data="enrollments" />
        </div>
    </StudentLayout>
</template>
