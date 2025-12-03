<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    lesson: Object,
    module: Object,
    course: Object,
});

const deleteLesson = () => {
    if (confirm('Darsni o\'chirishni tasdiqlaysizmi?')) {
        useForm({}).delete(route('teacher.lessons.destroy', [props.course.id, props.module.id, props.lesson.id]));
    }
};

const getIcon = (type) => {
    switch (type) {
        case 'video': return 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z';
        case 'text': return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
        case 'quiz': return 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2';
        default: return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};
</script>

<template>
    <div
        class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md hover:shadow-sm transition-shadow">
        <div class="flex items-center gap-3">
            <div class="lesson-handle cursor-move text-gray-300 hover:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
            </div>
            <div class="text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(lesson.type)" />
                </svg>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-900">{{ lesson.title }}</div>
                <div class="text-xs text-gray-500 flex gap-2">
                    <span>{{ lesson.type === 'video' ? 'Video' : (lesson.type === 'text' ? 'Matn' : 'Test') }}</span>
                    <span v-if="lesson.is_free" class="text-green-600">• Bepul</span>
                    <span v-if="lesson.is_preview" class="text-blue-600">• Preview</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Link v-if="lesson.type === 'quiz'"
                :href="route('teacher.courses.tests.create', { course: course.id, lesson_id: lesson.id, type: 'lesson_test' })"
                class="text-xs text-indigo-600 hover:text-indigo-800 mr-2">
            + Test
            </Link>
            <Link :href="route('teacher.courses.modules.lessons.edit', [course.id, module.id, lesson.id])"
                class="p-1 text-gray-400 hover:text-blue-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            </Link>
            <button @click="deleteLesson" class="p-1 text-gray-400 hover:text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</template>
