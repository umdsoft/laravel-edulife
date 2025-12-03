<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    course: Object,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price);
};

const rating = computed(() => {
    return Number(props.course.reviews_avg_rating || 0).toFixed(1);
});
</script>

<template>
    <div
        class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col sm:flex-row h-full">
        <!-- Thumbnail -->
        <div class="relative w-full sm:w-64 aspect-video sm:aspect-auto overflow-hidden bg-gray-100 shrink-0">
            <img :src="course.thumbnail_url || '/images/course-placeholder.jpg'" :alt="course.title"
                class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">

            <!-- Badges -->
            <div class="absolute top-3 left-3 flex gap-2">
                <span v-if="course.is_bestseller"
                    class="px-2 py-1 text-xs font-bold text-white bg-yellow-500 rounded-lg shadow-sm">
                    Bestseller
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 flex flex-col flex-1">
            <div class="flex justify-between items-start">
                <div>
                    <!-- Category -->
                    <span
                        class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded-md mb-2 inline-block">
                        {{ course.direction?.name }}
                    </span>

                    <!-- Title -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">
                        <Link :href="route('student.courses.show', course.slug)">
                        {{ course.title }}
                        </Link>
                    </h3>

                    <!-- Description -->
                    <p class="text-sm text-gray-500 line-clamp-2 mb-4">
                        {{ course.description }}
                    </p>
                </div>

                <!-- Price -->
                <div class="text-right shrink-0 ml-4">
                    <span class="block text-lg font-bold text-gray-900">
                        {{ course.is_free ? 'Bepul' : `${formatPrice(course.price)} UZS` }}
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-auto flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Teacher -->
                    <div class="flex items-center gap-2">
                        <img :src="course.teacher?.avatar_url || `https://ui-avatars.com/api/?name=${course.teacher?.first_name}+${course.teacher?.last_name}`"
                            class="w-6 h-6 rounded-full object-cover">
                        <span class="text-sm text-gray-600">
                            {{ course.teacher?.full_name }}
                        </span>
                    </div>

                    <!-- Rating -->
                    <div class="flex items-center gap-1 text-yellow-500">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="text-sm font-bold text-gray-700">{{ rating }}</span>
                        <span class="text-sm text-gray-400">({{ course.reviews_count || 0 }})</span>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{ course.enrollments_count || 0 }} o'quvchi
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ course.total_duration || '0' }} soat
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
