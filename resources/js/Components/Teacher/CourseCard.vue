<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    course: Object,
});

const statusColors = {
    draft: 'bg-gray-100 text-gray-600',
    pending: 'bg-yellow-100 text-yellow-600',
    published: 'bg-green-100 text-green-600',
    rejected: 'bg-red-100 text-red-600',
};

const statusLabels = {
    draft: 'Draft',
    pending: 'Kutilmoqda',
    published: 'Faol',
    rejected: 'Rad etilgan',
};
</script>

<template>
    <div class="group bg-white border border-gray-100 rounded-2xl overflow-hidden hover:shadow-lg transition-shadow">
        <!-- Thumbnail -->
        <div class="relative aspect-video bg-gray-100">
            <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title"
                class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>

            <!-- Status Badge -->
            <div class="absolute top-3 right-3">
                <span :class="['px-2.5 py-1 text-xs font-medium rounded-lg shadow-sm', statusColors[course.status]]">
                    {{ statusLabels[course.status] }}
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4">
            <h3 class="font-bold text-gray-900 line-clamp-1 mb-2 group-hover:text-emerald-600 transition-colors">
                {{ course.title }}
            </h3>

            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <div class="flex items-center gap-1">
                    <span class="text-yellow-400">★</span>
                    <span class="font-medium text-gray-900">{{ course.reviews_avg_rating ?
                        Number(course.reviews_avg_rating).toFixed(1) : '0.0' }}</span>
                    <span class="text-xs">({{ course.reviews_count }})</span>
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>{{ course.enrollments_count }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link :href="`/teacher/courses/${course.id}/edit`"
                    class="flex-1 px-3 py-2 text-sm font-medium text-center text-gray-700 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                Tahrirlash
                </Link>
                <Link :href="`/teacher/courses/${course.id}`"
                    class="px-3 py-2 text-gray-400 hover:text-emerald-600 bg-gray-50 rounded-xl hover:bg-emerald-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                </Link>
            </div>
        </div>
    </div>
</template>
