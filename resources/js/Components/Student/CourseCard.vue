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
    <article
        class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col h-full">
        <!-- Thumbnail with lazy loading -->
        <div class="relative aspect-video overflow-hidden bg-gray-100">
            <img 
                :src="course.thumbnail_url || '/images/course-placeholder.jpg'" 
                :alt="`${course.title} kursi thumbnail`"
                class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
                width="400"
                height="225"
            >

            <!-- Badges -->
            <div class="absolute top-3 left-3 flex gap-2">
                <span v-if="course.is_bestseller"
                    class="px-2 py-1 text-xs font-bold text-white bg-yellow-500 rounded-lg shadow-sm">
                    Bestseller
                </span>
                <span v-if="course.is_new"
                    class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-lg shadow-sm">
                    Yangi
                </span>
            </div>

            <!-- Price Badge -->
            <div
                class="absolute bottom-3 right-3 px-2 py-1 bg-white/90 backdrop-blur-sm rounded-lg shadow-sm text-sm font-bold text-gray-900">
                {{ course.is_free ? 'Bepul' : `${formatPrice(course.price)} UZS` }}
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 flex flex-col flex-1">
            <!-- Category -->
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded-md">
                    {{ course.direction?.name }}
                </span>
                <div class="flex items-center gap-1 text-yellow-500" role="img" :aria-label="`${rating} yulduz`">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="text-xs font-bold text-gray-700">{{ rating }}</span>
                    <span class="text-xs text-gray-400">({{ course.reviews_count || 0 }})</span>
                </div>
            </div>

            <!-- Title -->
            <h3
                class="text-base font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-purple-600 transition-colors">
                <Link :href="route('student.courses.show', course.slug)">
                {{ course.title }}
                </Link>
            </h3>

            <!-- Teacher -->
            <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img 
                        :src="course.teacher?.avatar_url || `https://ui-avatars.com/api/?name=${course.teacher?.first_name}+${course.teacher?.last_name}`"
                        :alt="`${course.teacher?.full_name} avatar`"
                        class="w-6 h-6 rounded-full object-cover"
                        loading="lazy"
                        width="24"
                        height="24"
                    >
                    <span class="text-xs text-gray-500 truncate max-w-[100px]">
                        {{ course.teacher?.full_name }}
                    </span>
                </div>
                <div class="flex items-center gap-1 text-gray-400" title="O'quvchilar soni">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="text-xs">{{ course.enrollments_count || 0 }}</span>
                </div>
            </div>
        </div>
    </article>
</template>
