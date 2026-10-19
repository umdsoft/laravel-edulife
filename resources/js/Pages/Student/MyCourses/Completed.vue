<script setup>
import { Head } from '@inertiajs/vue3';
import { AcademicCapIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Tabs, EmptyState, Pagination, Button } from '@/Components/UI';
import CourseCardHorizontal from '@/Components/Student/CourseCardHorizontal.vue';
import { routePath } from '@/Utils/nav';

defineProps({
    enrollments: Object,
});

const certificatesHref = routePath('student.certificates.index');

const tabs = [
    { key: 'all', label: 'Barchasi', href: route('student.my-courses.index') },
    { key: 'progress', label: 'Jarayonda', href: route('student.my-courses.in-progress') },
    { key: 'completed', label: 'Tugatilgan', href: route('student.my-courses.completed'), active: true },
];
</script>

<template>

    <Head title="Tugatilgan kurslar" />

    <StudentLayout title="Kurslarim">
        <PageHeader title="Mening kurslarim" description="Muvaffaqiyatli yakunlagan kurslaringiz.">
            <template #actions>
                <Button v-if="certificatesHref" variant="secondary" :href="certificatesHref">
                    Sertifikatlarim
                </Button>
            </template>
            <template #tabs>
                <Tabs :tabs="tabs" label="Kurslar holati" />
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div v-if="enrollments.data.length > 0" class="space-y-3">
                <CourseCardHorizontal v-for="enrollment in enrollments.data" :key="enrollment.id" :course="enrollment.course" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="AcademicCapIcon" title="Tugatilgan kurslar yo'q"
                description="Siz hali birorta kursni tugatmagansiz.">
                <Button :href="route('student.my-courses.in-progress')">O'qishni davom ettirish</Button>
            </EmptyState>

            <Pagination :data="enrollments" />
        </div>
    </StudentLayout>
</template>
