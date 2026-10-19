<script setup>
import { Head } from '@inertiajs/vue3';
import { RectangleStackIcon, AcademicCapIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, EmptyState, Pagination, Button } from '@/Components/UI';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseFilters from '@/Components/Student/CourseFilters.vue';

defineProps({
    direction: Object,
    courses: Object,
    filters: Object,
});
</script>

<template>

    <Head :title="direction.name" />

    <StudentLayout :title="direction.name">
        <PageHeader :breadcrumbs="[{ label: 'Kurslar', href: route('student.courses.index') }, { label: direction.name }]">
            <template #title>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                        <img v-if="direction.icon_url" :src="direction.icon_url" alt="" class="h-7 w-7 object-contain">
                        <RectangleStackIcon v-else class="h-6 w-6" aria-hidden="true" />
                    </span>
                    <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ direction.name }}</h1>
                </div>
            </template>
            <template #meta>
                <span class="text-sm text-fg-muted">{{ direction.description || "Bu yo'nalishdagi barcha kurslar" }}</span>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <aside class="w-full shrink-0 lg:w-64">
                <CourseFilters :filters="filters" />
            </aside>

            <div class="min-w-0 flex-1 space-y-6">
                <div v-if="courses.data.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <CourseCard v-for="course in courses.data" :key="course.id" :course="course" />
                </div>

                <EmptyState v-else bordered size="lg" :icon="AcademicCapIcon" title="Kurslar topilmadi"
                    description="Bu yo'nalishda hozircha kurslar mavjud emas.">
                    <Button variant="secondary" :href="route('student.courses.index')">Barcha kurslar</Button>
                </EmptyState>

                <Pagination :data="courses" />
            </div>
        </div>
    </StudentLayout>
</template>
