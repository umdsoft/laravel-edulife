<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import ReviewForm from '@/Components/Student/ReviewForm.vue';

defineProps({
    course: Object,
    enrollment: Object,
    existingReview: Object,
});
</script>

<template>

    <Head :title="`${course.title} - Sharh qoldirish`" />

    <StudentLayout>
        <div class="max-w-2xl mx-auto py-8">
            <Link :href="route('student.learn.course', course.slug)"
                class="text-sm text-purple-600 hover:underline mb-4 inline-block">
            &larr; Kursga qaytish
            </Link>

            <div class="flex items-center gap-4 mb-8">
                <img :src="course.thumbnail_url" class="w-20 h-20 rounded-xl object-cover border border-gray-200">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ course.title }}</h1>
                    <p class="text-gray-500">{{ course.teacher.full_name }}</p>
                </div>
            </div>

            <ReviewForm :course="course" :existing-review="existingReview" />
        </div>
    </StudentLayout>
</template>
