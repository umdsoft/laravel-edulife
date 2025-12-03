<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import LessonCard from './LessonCard.vue';

const props = defineProps({
    module: Object,
    course: Object,
});

const emit = defineEmits(['edit', 'delete', 'reorder-lessons']);

const isExpanded = ref(true);
const lessonsList = ref(props.module.lessons || []);

const onLessonsReorder = () => {
    emit('reorder-lessons', lessonsList.value);
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        <div class="p-4 bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="module-handle cursor-move text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                </div>
                <button @click="isExpanded = !isExpanded" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform"
                        :class="{ 'rotate-180': !isExpanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div>
                    <h3 class="font-medium text-gray-900">{{ module.title }}</h3>
                    <div class="text-sm text-gray-500 flex gap-3">
                        <span v-if="module.is_free" class="text-green-600">Bepul</span>
                        <span>{{ module.lessons?.length || 0 }} ta dars</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    :href="route('teacher.courses.tests.create', { course: course.id, module_id: module.id, type: 'module_test' })"
                    class="text-sm text-indigo-600 hover:text-indigo-800">
                + Test
                </Link>
                <button @click="$emit('edit')" class="p-1 text-gray-400 hover:text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button @click="$emit('delete')" class="p-1 text-gray-400 hover:text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <Link :href="route('teacher.courses.modules.lessons.create', module.id)"
                    class="ml-2 btn-sm btn-primary">
                + Dars
                </Link>
            </div>
        </div>

        <div v-show="isExpanded" class="p-4 border-t border-gray-200 bg-gray-50/50">
            <div v-if="!module.lessons || module.lessons.length === 0" class="text-center text-sm text-gray-500 py-4">
                Darslar yo'q
            </div>

            <draggable v-model="lessonsList" item-key="id" handle=".lesson-handle" @end="onLessonsReorder"
                class="space-y-2" group="lessons">
                <template #item="{ element: lesson }">
                    <LessonCard :lesson="lesson" :module="module" :course="course" />
                </template>
            </draggable>
        </div>
    </div>
</template>
