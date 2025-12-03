<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    prevLesson: Object,
    nextLesson: Object,
    course: Object,
});
</script>

<template>
    <div class="flex items-center justify-between border-t border-gray-200 pt-6 mt-6">
        <!-- Previous Lesson -->
        <div class="flex-1">
            <Link v-if="prevLesson"
                :href="route('student.learn.lesson', { course: course.slug, lesson: prevLesson.id })"
                class="group flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-left">
            <div
                class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Oldingi dars</p>
                <p class="font-medium text-gray-900 group-hover:text-purple-700 line-clamp-1">{{ prevLesson.title }}</p>
            </div>
            </Link>
        </div>

        <!-- Next Lesson -->
        <div class="flex-1 flex justify-end">
            <Link v-if="nextLesson"
                :href="route('student.learn.lesson', { course: course.slug, lesson: nextLesson.id })"
                class="group flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors text-right">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Keyingi dars</p>
                <p class="font-medium text-gray-900 group-hover:text-purple-700 line-clamp-1">{{ nextLesson.title }}</p>
            </div>
            <div
                class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            </Link>

            <!-- Finish Course Button if no next lesson -->
            <div v-else class="flex items-center">
                <Link :href="route('student.learn.course', course.slug)"
                    class="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-medium shadow-lg shadow-green-200">
                <span>Kursni yakunlash</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                </Link>
            </div>
        </div>
    </div>
</template>
