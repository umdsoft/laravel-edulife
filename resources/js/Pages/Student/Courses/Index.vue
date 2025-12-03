<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseFilters from '@/Components/Student/CourseFilters.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    courses: Object,
    directions: Array,
    filters: Object,
});
</script>

<template>

    <Head title="Kurslar" />

    <StudentLayout>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters (Sidebar) -->
            <aside class="w-full lg:w-64 shrink-0">
                <CourseFilters :filters="filters" :directions="directions" />
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Barcha kurslar</h1>
                    <p class="text-gray-500 mt-1">O'zingizga mos kursni toping va o'rganishni boshlang</p>
                </div>

                <!-- Course Grid -->
                <div v-if="courses.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    <CourseCard v-for="course in courses.data" :key="course.id" :course="course" />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🔍</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Kurslar topilmadi</h3>
                    <p class="text-gray-500 mt-2">Filtrlarni o'zgartirib ko'ring yoki boshqa so'z bilan izlang</p>
                </div>

                <!-- Pagination -->
                <Pagination :data="courses" />
            </div>
        </div>
    </StudentLayout>
</template>
