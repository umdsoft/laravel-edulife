<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import CourseCard from '@/Components/Teacher/CourseCard.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    courses: Object,
});
</script>

<template>

    <Head title="Kurslarim" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Kurslarim</h2>
                <Link href="/teacher/courses/create"
                    class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 transition-colors">
                + Yangi kurs
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filters (Placeholder) -->
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl">
                    Hammasi
                </button>
                <button
                    class="px-4 py-2 bg-white text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 border border-gray-200">
                    Published
                </button>
                <button
                    class="px-4 py-2 bg-white text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 border border-gray-200">
                    Draft
                </button>
                <button
                    class="px-4 py-2 bg-white text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 border border-gray-200">
                    Pending
                </button>
            </div>

            <!-- Course Grid -->
            <div v-if="courses.data.length > 0"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <CourseCard v-for="course in courses.data" :key="course.id" :course="course" />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    📚
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Kurslar topilmadi</h3>
                <p class="text-gray-500 mb-6">Sizda hali kurslar mavjud emas yoki qidiruv bo'yicha topilmadi.</p>
                <Link href="/teacher/courses/create"
                    class="inline-block px-6 py-2 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700">
                Birinchi kursni yaratish
                </Link>
            </div>

            <!-- Pagination -->
            <Pagination :data="courses" />
        </div>
    </TeacherLayout>
</template>
