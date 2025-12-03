<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import BulkLessonForm from '@/Components/Teacher/BulkLessonForm.vue';

defineProps({
    course: Object,
    module: Object,
});
</script>

<template>
    <TeacherLayout>

        <Head :title="`${course.title} - Ommaviy dars qo'shish`" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <div class="flex items-center text-sm text-gray-500 mb-1">
                    <Link :route="route('teacher.courses.edit', [course.id, { tab: 'curriculum' }])"
                        class="hover:text-indigo-600">
                    {{ course.title }}
                    </Link>
                    <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>{{ module.title }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Ommaviy dars qo'shish</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Bir vaqtning o'zida bir nechta dars yaratish. Keyinchalik har bir darsga video va materiallarni
                    yuklashingiz mumkin.
                </p>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <BulkLessonForm :course="course" :module="module"
                    @cancel="$inertia.visit(route('teacher.courses.edit', [course.id, { tab: 'curriculum' }]))" />
            </div>
        </div>
    </TeacherLayout>
</template>
