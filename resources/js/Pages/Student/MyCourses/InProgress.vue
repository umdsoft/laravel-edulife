<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import ContinueLearningCard from '@/Components/Student/ContinueLearningCard.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    enrollments: Object,
});
</script>

<template>

    <Head title="Jarayondagi kurslar" />

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
                    class="px-4 py-2 border-b-2 border-purple-600 text-purple-600 font-medium text-sm">
                Jarayonda
                </Link>
                <Link :href="route('student.my-courses.completed')"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                Tugatilgan
                </Link>
            </div>
        </div>

        <!-- Course List -->
        <div v-if="enrollments.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ContinueLearningCard v-for="enrollment in enrollments.data" :key="enrollment.id"
                :enrollment="enrollment" />
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">⏳</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Jarayondagi kurslar yo'q</h3>
            <p class="text-gray-500 mt-2 mb-6">Sizda hozircha o'qilayotgan kurslar mavjud emas</p>
            <Link :href="route('student.courses.index')"
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-purple-600 hover:bg-purple-700">
            Kurslarni ko'rish
            </Link>
        </div>

        <!-- Pagination -->
        <Pagination :data="enrollments" class="mt-8" />
    </StudentLayout>
</template>
