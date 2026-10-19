<script setup>
import { Head } from '@inertiajs/vue3';
import { MegaphoneIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, EmptyState, Pagination } from '@/Components/UI';
import LearnFrame from '@/Components/Student/LearnFrame.vue';
import AnnouncementCard from '@/Components/Student/AnnouncementCard.vue';

defineProps({
    course: Object,
    announcements: Object,
});
</script>

<template>

    <Head :title="`${course.title} - E'lonlar`" />

    <StudentLayout title="E'lonlar">
        <LearnFrame :course="course">
            <template #header>
                <PageHeader title="E'lonlar va yangiliklar" :description="course.title"
                    :back="route('student.learn.course', course.slug)" back-label="Kursga qaytish" />
            </template>

            <div v-if="announcements.data.length > 0" class="max-w-3xl space-y-4">
                <AnnouncementCard v-for="announcement in announcements.data" :key="announcement.id"
                    :announcement="announcement" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="MegaphoneIcon" title="E'lonlar yo'q"
                description="Hozircha kurs bo'yicha e'lonlar mavjud emas. Yangi e'lonlar shu yerda paydo bo'ladi." />

            <Pagination :data="announcements" />
        </LearnFrame>
    </StudentLayout>
</template>
