<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import StatsCard from '@/Components/Student/StatsCard.vue';
import ContinueLearningCard from '@/Components/Student/ContinueLearningCard.vue';
import RecommendationSlider from '@/Components/Student/RecommendationSlider.vue';
import CategoryCard from '@/Components/Student/CategoryCard.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';

defineProps({
    profile: Object,
    stats: Object,
    continueLearning: Array,
    recommendations: Array,
    directions: Array,
    popularCourses: Array,
    newCourses: Array,
});
</script>

<template>

    <Head title="Dashboard" />

    <StudentLayout>
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatsCard title="O'qilayotgan kurslar" :value="stats.courses_in_progress" icon="📚" color="purple" />
            <StatsCard title="Tugatilgan kurslar" :value="stats.courses_completed" icon="🎓" color="green" />
            <StatsCard title="Umumiy vaqt" :value="stats.total_watch_time" icon="⏱️" color="blue" />
            <StatsCard title="Yig'ilgan tangalar" :value="stats.coins" icon="🪙" color="yellow" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column (Main Content) -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Continue Learning -->
                <section v-if="continueLearning.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">O'qishni davom ettirish</h2>
                        <Link :href="route('student.my-courses.in-progress')"
                            class="text-sm font-medium text-purple-600 hover:text-purple-700">
                        Barchasi
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <ContinueLearningCard v-for="enrollment in continueLearning" :key="enrollment.id"
                            :enrollment="enrollment" />
                    </div>
                </section>

                <!-- Recommendations -->
                <section v-if="recommendations.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Siz uchun tavsiyalar</h2>
                    </div>
                    <RecommendationSlider :courses="recommendations" />
                </section>

                <!-- Popular Courses -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Mashhur kurslar</h2>
                        <Link :href="route('student.courses.index', { sort: 'popular' })"
                            class="text-sm font-medium text-purple-600 hover:text-purple-700">
                        Barchasi
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <CourseCard v-for="course in popularCourses.slice(0, 4)" :key="course.id" :course="course" />
                    </div>
                </section>
            </div>

            <!-- Right Column (Sidebar) -->
            <div class="space-y-8">
                <!-- Daily Mission (Placeholder) -->
                <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg">Kunlik vazifa</h3>
                        <span class="bg-white/20 px-2 py-1 rounded-lg text-xs font-bold">12s qoldi</span>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">
                                🎯
                            </div>
                            <div>
                                <p class="font-medium">3 ta darsni tugatish</p>
                                <p class="text-xs text-purple-200">Mukofot: +50 XP</p>
                            </div>
                        </div>
                        <div class="w-full bg-black/20 rounded-full h-2">
                            <div class="bg-white h-2 rounded-full w-1/3"></div>
                        </div>
                        <button
                            class="w-full py-2 bg-white text-purple-600 rounded-xl font-bold text-sm hover:bg-purple-50 transition-colors">
                            Vazifani bajarish
                        </button>
                    </div>
                </div>

                <!-- Categories -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Yo'nalishlar</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <CategoryCard v-for="direction in directions.slice(0, 4)" :key="direction.id"
                            :direction="direction" />
                    </div>
                    <Link :href="route('student.courses.index')"
                        class="block mt-4 text-center text-sm font-medium text-purple-600 hover:text-purple-700">
                    Barcha yo'nalishlar
                    </Link>
                </section>

                <!-- New Courses -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Yangi kurslar</h2>
                    <div class="space-y-4">
                        <Link v-for="course in newCourses.slice(0, 3)" :key="course.id"
                            :href="route('student.courses.show', course.slug)" class="flex gap-3 group">
                        <img :src="course.thumbnail_url || '/images/course-placeholder.jpg'"
                            class="w-20 h-14 rounded-lg object-cover group-hover:opacity-90 transition-opacity">
                        <div>
                            <h4
                                class="font-bold text-gray-900 text-sm line-clamp-2 group-hover:text-purple-600 transition-colors">
                                {{ course.title }}
                            </h4>
                            <p class="text-xs text-gray-500 mt-1">{{ course.teacher?.full_name }}</p>
                        </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </StudentLayout>
</template>
