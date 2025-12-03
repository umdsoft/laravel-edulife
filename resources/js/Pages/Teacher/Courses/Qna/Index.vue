<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import QnaItem from '@/Components/Teacher/QnaItem.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    course: Object,
    questions: Object,
    stats: Object,
    filters: Object,
});

const filterStatus = ref(props.filters.status || '');

watch(filterStatus, (val) => {
    router.get(
        route('teacher.courses.qna.index', props.course.id),
        { status: val },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <TeacherLayout>

        <Head :title="`${course.title} - Savol-javoblar`" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <div class="flex items-center text-sm text-gray-500 mb-1">
                    <Link :route="route('teacher.courses.index')" class="hover:text-indigo-600">Kurslar</Link>
                    <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link :route="route('teacher.courses.edit', course.id)" class="hover:text-indigo-600">{{
                        course.title }}</Link>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Savol-javoblar</h1>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Jami savollar</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Javobsiz savollar</div>
                    <div class="mt-2 text-3xl font-bold text-red-600">{{ stats.unanswered }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Qadalgan savollar</div>
                    <div class="mt-2 text-3xl font-bold text-indigo-600">{{ stats.pinned }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <div class="flex items-center space-x-4">
                    <select v-model="filterStatus"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Barcha savollar</option>
                        <option value="unanswered">Javobsiz</option>
                        <option value="answered">Javob berilgan</option>
                        <option value="pinned">Qadalgan</option>
                    </select>
                </div>
            </div>

            <!-- Questions List -->
            <div class="space-y-6">
                <QnaItem v-for="question in questions.data" :key="question.id" :question="question" :course="course" />

                <div v-if="questions.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Savollar yo'q</h3>
                    <p class="mt-1 text-sm text-gray-500">Hozircha hech kim savol bermagan.</p>
                </div>

                <Pagination :data="questions" />
            </div>
        </div>
    </TeacherLayout>
</template>
