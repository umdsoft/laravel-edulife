<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import LessonSidebar from '@/Components/Student/LessonSidebar.vue';
import AnnouncementCard from '@/Components/Student/AnnouncementCard.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    course: Object,
    announcements: Object,
});
</script>

<template>

    <Head :title="`${course.title} - E'lonlar`" />

    <StudentLayout>
        <div class="flex h-[calc(100vh-64px)] -m-6">
            <!-- Main Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <Link :href="route('student.learn.course', course.slug)"
                            class="text-sm text-purple-600 hover:underline mb-1 inline-block">
                        &larr; Kursga qaytish
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">E'lonlar va Yangiliklar</h1>
                    </div>
                </div>

                <!-- Announcements List -->
                <div class="space-y-6">
                    <AnnouncementCard v-for="announcement in announcements.data" :key="announcement.id"
                        :announcement="announcement" />

                    <div v-if="announcements.data.length === 0"
                        class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">E'lonlar yo'q</h3>
                        <p class="text-gray-500 mt-2">Hozircha kurs bo'yicha e'lonlar mavjud emas.</p>
                    </div>

                    <Pagination :data="announcements" />
                </div>
            </div>

            <!-- Sidebar (Desktop) -->
            <LessonSidebar :course="course" :progress="{}" :module-progress="{}" />
        </div>
    </StudentLayout>
</template>
