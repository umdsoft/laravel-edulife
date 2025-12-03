<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    course: Object,
    currentLesson: Object,
    progress: Object, // Map of lesson_id -> progress object
    moduleProgress: Object, // Map of module_id -> progress object
});

const isLessonCompleted = (lessonId, progressMap) => {
    return progressMap[lessonId]?.is_completed;
};

const isLessonInProgress = (lessonId, progressMap) => {
    const p = progressMap[lessonId];
    return p && !p.is_completed && p.watch_percentage > 0;
};

const formatDuration = (seconds) => {
    if (!seconds) return '--';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
};
</script>

<template>
    <div class="bg-white border-l border-gray-200 h-full overflow-y-auto w-80 flex-shrink-0 hidden lg:block">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900">Dastur</h3>
        </div>

        <div class="divide-y divide-gray-100">
            <div v-for="module in course.modules" :key="module.id" class="py-2">
                <div class="px-4 py-2">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-medium text-gray-900 text-sm">{{ module.title }}</h4>
                        <span class="text-xs text-gray-500" v-if="moduleProgress[module.id]">
                            {{ moduleProgress[module.id].completed }}/{{ moduleProgress[module.id].total }}
                        </span>
                    </div>
                    <!-- Module Progress Bar -->
                    <div class="h-1 bg-gray-100 rounded-full overflow-hidden" v-if="moduleProgress[module.id]">
                        <div class="h-full bg-green-500 transition-all duration-300"
                            :style="{ width: `${moduleProgress[module.id].percentage}%` }"></div>
                    </div>
                </div>

                <div class="mt-1">
                    <Link v-for="lesson in module.lessons" :key="lesson.id"
                        :href="route('student.learn.lesson', { course: course.slug, lesson: lesson.id })"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors relative"
                        :class="{ 'bg-purple-50 hover:bg-purple-50': currentLesson && currentLesson.id === lesson.id }">

                    <!-- Active Indicator -->
                    <div v-if="currentLesson && currentLesson.id === lesson.id"
                        class="absolute left-0 top-0 bottom-0 w-1 bg-purple-600"></div>

                    <!-- Status Icon -->
                    <div class="flex-shrink-0">
                        <div v-if="isLessonCompleted(lesson.id, progress)"
                            class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div v-else-if="isLessonInProgress(lesson.id, progress)"
                            class="w-5 h-5 rounded-full border-2 border-blue-500 flex items-center justify-center">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                        <div v-else class="w-5 h-5 rounded-full border-2 border-gray-300"></div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"
                            :class="currentLesson && currentLesson.id === lesson.id ? 'text-purple-700' : 'text-gray-700'">
                            {{ lesson.title }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ formatDuration(lesson.duration) }}
                            </span>
                            <span v-if="lesson.type === 'text'"
                                class="text-xs bg-gray-100 text-gray-600 px-1.5 rounded">Matn</span>
                        </div>
                    </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
