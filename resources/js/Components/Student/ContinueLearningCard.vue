<script setup>
import { Link } from '@inertiajs/vue3';
import ProgressRing from './ProgressRing.vue';

defineProps({
    enrollment: Object,
});
</script>

<template>
    <div
        class="bg-white rounded-2xl p-4 border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow group">
        <!-- Thumbnail -->
        <div class="relative w-20 h-20 rounded-xl overflow-hidden shrink-0">
            <img :src="enrollment.course.thumbnail_url || '/images/course-placeholder.jpg'"
                class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors"></div>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <h4 class="font-bold text-gray-900 truncate mb-1 group-hover:text-purple-600 transition-colors">
                <Link :href="route('student.courses.show', enrollment.course.slug)">
                {{ enrollment.course.title }}
                </Link>
            </h4>
            <p class="text-xs text-gray-500 mb-2">
                {{ enrollment.last_lesson?.title || 'Boshlanmagan' }}
            </p>

            <!-- Progress Bar (Linear) -->
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-purple-600 h-1.5 rounded-full transition-all duration-500"
                    :style="{ width: `${enrollment.progress}%` }"></div>
            </div>
        </div>

        <!-- Play Button -->
        <Link :href="route('student.courses.show', enrollment.course.slug)"
            class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 hover:bg-purple-600 hover:text-white transition-all shrink-0">
        <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        </Link>
    </div>
</template>
