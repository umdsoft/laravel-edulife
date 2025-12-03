<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseTabs from '@/Components/Student/CourseTabs.vue';
import EnrollButton from '@/Components/Student/EnrollButton.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';
import { format } from 'date-fns';

const props = defineProps({
    course: Object,
    enrollment: Object,
    isEnrolled: Boolean,
    isInWishlist: Boolean,
    similarCourses: Array,
    ratingDistribution: Object,
});

const activeTab = ref('about');

const tabs = computed(() => [
    { id: 'about', name: 'Kurs haqida' },
    { id: 'curriculum', name: 'Dastur', count: props.course.lessons_count },
    { id: 'reviews', name: 'Sharhlar', count: props.course.reviews_count },
]);

const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price);
};

const formatDate = (date) => {
    return format(new Date(date), 'dd.MM.yyyy');
};

const toggleWishlist = () => {
    router.post(route('student.wishlist.toggle', props.course.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head :title="course.title" />

    <StudentLayout>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column (Main Content) -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Header -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <Link :href="route('student.courses.index')"
                            class="text-sm text-gray-500 hover:text-purple-600">Kurslar</Link>
                        <span class="text-gray-300">/</span>
                        <Link :href="route('student.courses.category', course.direction.id)"
                            class="text-sm text-gray-500 hover:text-purple-600">{{ course.direction.name }}</Link>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ course.title }}</h1>
                    <p class="text-lg text-gray-600 mb-6">{{ course.description }}</p>

                    <div class="flex flex-wrap items-center gap-6 text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <span class="text-yellow-500 text-lg">⭐</span>
                            <span class="font-bold text-gray-900">{{ Number(course.reviews_avg_rating || 0).toFixed(1)
                                }}</span>
                            <span>({{ course.reviews_count }} sharhlar)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>👥</span>
                            <span>{{ course.enrollments_count }} o'quvchi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>📅</span>
                            <span>Oxirgi yangilanish: {{ formatDate(course.updated_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <CourseTabs :tabs="tabs" v-model:activeTab="activeTab" />

                <!-- Tab Content -->
                <div class="min-h-[400px]">
                    <!-- About Tab -->
                    <div v-if="activeTab === 'about'" class="space-y-8">
                        <div class="prose max-w-none" v-html="course.description"></div>

                        <!-- What you'll learn -->
                        <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Nimalarni o'rganasiz</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Placeholder content as it's not in DB yet -->
                                <div class="flex items-start gap-3">
                                    <span class="text-green-500 mt-1">✓</span>
                                    <span>Zamonaviy dasturlash asoslari</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="text-green-500 mt-1">✓</span>
                                    <span>Real loyihalar ustida ishlash</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="text-green-500 mt-1">✓</span>
                                    <span>Portfolio yaratish</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="text-green-500 mt-1">✓</span>
                                    <span>Jamoada ishlash ko'nikmalari</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Curriculum Tab -->
                    <div v-else-if="activeTab === 'curriculum'" class="space-y-4">
                        <div v-for="module in course.modules" :key="module.id"
                            class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between font-medium">
                                <span>{{ module.title }}</span>
                                <span class="text-sm text-gray-500">{{ module.lessons.length }} ta dars</span>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <div v-for="lesson in module.lessons" :key="lesson.id"
                                    class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-400">
                                            <svg v-if="lesson.type === 'video'" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l4 4a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </span>
                                        <span class="text-gray-700">{{ lesson.title }}</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-500">{{ lesson.duration_minutes }} daqiqa</span>
                                        <Link v-if="lesson.is_preview || isEnrolled"
                                            :href="route('student.courses.preview', [course.slug, lesson.id])"
                                            class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                                        {{ isEnrolled ? 'Ko\'rish' : 'Preview' }}
                                        </Link>
                                        <span v-else class="text-gray-400">🔒</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div v-else-if="activeTab === 'reviews'" class="space-y-8">
                        <!-- Rating Summary -->
                        <div class="flex items-center gap-8 bg-gray-50 p-6 rounded-2xl">
                            <div class="text-center">
                                <div class="text-5xl font-bold text-gray-900 mb-1">{{ Number(course.reviews_avg_rating
                                    || 0).toFixed(1) }}</div>
                                <div class="flex text-yellow-400 text-xl justify-center mb-1">⭐⭐⭐⭐⭐</div>
                                <div class="text-sm text-gray-500">Kurs reytingi</div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <div v-for="(count, star) in ratingDistribution" :key="star"
                                    class="flex items-center gap-4">
                                    <div class="w-12 text-sm text-gray-600 font-medium">{{ star }} yulduz</div>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-400 rounded-full"
                                            :style="{ width: `${(count / course.reviews_count) * 100}%` }"></div>
                                    </div>
                                    <div class="w-12 text-sm text-gray-400 text-right">{{ Math.round((count /
                                        (course.reviews_count || 1)) * 100) }}%</div>
                                </div>
                            </div>
                        </div>

                        <!-- Reviews List -->
                        <div class="space-y-6">
                            <div v-for="review in course.reviews" :key="review.id"
                                class="border-b border-gray-100 pb-6 last:border-0">
                                <div class="flex items-start gap-4">
                                    <img :src="review.user.avatar_url || `https://ui-avatars.com/api/?name=${review.user.first_name}+${review.user.last_name}`"
                                        class="w-10 h-10 rounded-full object-cover">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <h4 class="font-bold text-gray-900">{{ review.user.full_name }}</h4>
                                            <span class="text-sm text-gray-500">{{ formatDate(review.created_at)
                                                }}</span>
                                        </div>
                                        <div class="flex text-yellow-400 text-sm mb-2">
                                            <span v-for="i in 5" :key="i">{{ i <= review.rating ? '⭐' : '☆' }}</span>
                                        </div>
                                        <p class="text-gray-600">{{ review.comment }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column (Sidebar) -->
            <div class="space-y-6">
                <!-- Course Card (Sticky) -->
                <div class="sticky top-24 bg-white rounded-2xl border border-gray-100 shadow-lg overflow-hidden">
                    <!-- Video Preview / Thumbnail -->
                    <div class="relative aspect-video bg-gray-900 group cursor-pointer">
                        <img :src="course.thumbnail_url || '/images/course-placeholder.jpg'"
                            class="w-full h-full object-cover opacity-90 group-hover:opacity-75 transition-opacity">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div
                                class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-gray-900">
                                {{ course.is_free ? 'Bepul' : `${formatPrice(course.price)} UZS` }}
                            </span>
                            <span v-if="!course.is_free" class="text-sm text-gray-500 line-through">
                                {{ formatPrice(course.price * 1.5) }} UZS
                            </span>
                        </div>

                        <EnrollButton :course="course" :is-enrolled="isEnrolled" />

                        <button @click="toggleWishlist"
                            class="w-full py-3 rounded-xl border border-gray-200 font-medium text-gray-700 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                            <span :class="isInWishlist ? 'text-red-500' : 'text-gray-400'">
                                {{ isInWishlist ? '❤️' : '🤍' }}
                            </span>
                            {{ isInWishlist ? 'Istaklar ro\'yxatidan olish' : 'Istaklar ro\'yxatiga qo\'shish' }}
                        </button>

                        <div class="space-y-4 pt-6 border-t border-gray-100">
                            <h4 class="font-bold text-gray-900">Bu kursda:</h4>
                            <ul class="space-y-3 text-sm text-gray-600">
                                <li class="flex items-center gap-3">
                                    <span class="w-5 text-center">🎥</span>
                                    <span>{{ course.total_duration || 0 }} soat video darslar</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <span class="w-5 text-center">📝</span>
                                    <span>{{ course.lessons_count || 0 }} ta dars</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <span class="w-5 text-center">📱</span>
                                    <span>Mobil qurilmalarda ko'rish</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <span class="w-5 text-center">🏆</span>
                                    <span>Bitiruv sertifikati</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Courses -->
        <section v-if="similarCourses.length > 0" class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">O'xshash kurslar</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <CourseCard v-for="course in similarCourses" :key="course.id" :course="course" />
            </div>
        </section>
    </StudentLayout>
</template>
