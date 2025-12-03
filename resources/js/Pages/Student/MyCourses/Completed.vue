<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseCardHorizontal from '@/Components/Student/CourseCardHorizontal.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    enrollments: Object,
});
</script>

<template>

    <Head title="Tugatilgan kurslar" />

    <StudentLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Mening kurslarim</h1>

            <!-- Tabs -->
            <div class="flex items-center gap-4 mt-6 border-b border-gray-200">
                <Link :href="route('student.my-courses.index')"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                Barchasi
                </Link>
                <Link :href="route('student.my-courses.in-progress')"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                Jarayonda
                </Link>
                <Link :href="route('student.my-courses.completed')"
                    class="px-4 py-2 border-b-2 border-purple-600 text-purple-600 font-medium text-sm">
                Tugatilgan
                </Link>
            </div>
        </div>

        <!-- Course List -->
        <div v-if="enrollments.data.length > 0" class="space-y-4">
            <div v-for="enrollment in enrollments.data" :key="enrollment.id" class="h-40">
                <CourseCardHorizontal :course="enrollment.course" />
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">🎓</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Tugatilgan kurslar yo'q</h3>
            <p class="text-gray-500 mt-2 mb-6">Siz hali birorta kursni tugatmagansiz</p>
            <Link :href="route('student.my-courses.in-progress')"
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-purple-600 hover:bg-purple-700">
            O'qishni davom ettirish
            </Link>
        </div>

        <!-- Pagination -->
        <Pagination :data="enrollments" class="mt-8" />
    </StudentLayout>
</template>
