<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseFilters from '@/Components/Student/CourseFilters.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    direction: Object,
    courses: Object,
    filters: Object,
});
</script>

<template>

    <Head :title="direction.name" />

    <StudentLayout>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters (Sidebar) -->
            <aside class="w-full lg:w-64 shrink-0">
                <CourseFilters :filters="filters" />
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <div class="mb-6 flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center shrink-0">
                        <img v-if="direction.icon_url" :src="direction.icon_url" :alt="direction.name"
                            class="w-10 h-10 object-contain">
                        <span v-else class="text-3xl">📚</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ direction.name }}</h1>
                        <p class="text-gray-500 mt-1">{{ direction.description || 'Bu yo\'nalishdagi barcha kurslar' }}
                        </p>
                    </div>
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
                    <p class="text-gray-500 mt-2">Bu yo'nalishda hozircha kurslar mavjud emas</p>
                </div>

                <!-- Pagination -->
                <Pagination :data="courses" />
            </div>
        </div>
    </StudentLayout>
</template>
