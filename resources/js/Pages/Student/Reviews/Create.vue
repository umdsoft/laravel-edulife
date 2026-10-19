<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader } from '@/Components/UI';
import ReviewForm from '@/Components/Student/ReviewForm.vue';
import CourseThumbnail from '@/Components/Student/CourseThumbnail.vue';

defineProps({
    course: Object,
    enrollment: Object,
    existingReview: Object,
});
</script>

<template>

    <Head :title="`${course.title} - Sharh qoldirish`" />

    <StudentLayout title="Sharh">
        <div class="mx-auto max-w-xl">
            <PageHeader :back="route('student.learn.course', course.slug)" back-label="Kursga qaytish">
                <template #title>
                    <div class="flex items-center gap-4">
                        <CourseThumbnail :src="course.thumbnail_url" alt="" icon-class="h-6 w-6" class="h-16 w-16 shrink-0 rounded-md border border-border" />
                        <div class="min-w-0">
                            <h1 class="text-xl font-semibold tracking-tight text-fg sm:text-2xl">{{ course.title }}</h1>
                            <p v-if="course.teacher?.full_name" class="mt-0.5 text-sm text-fg-muted">{{ course.teacher.full_name }}</p>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <ReviewForm :course="course" :existing-review="existingReview" />
        </div>
    </StudentLayout>
</template>
