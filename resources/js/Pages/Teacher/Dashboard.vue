<script setup>
import { Head } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import StatsCard from '@/Components/Teacher/StatsCard.vue';
import CourseCard from '@/Components/Teacher/CourseCard.vue';
import EarningsChart from '@/Components/Teacher/EarningsChart.vue';

defineProps({
    stats: Object,
    monthlyEarnings: Number,
    recentEnrollments: Array,
    recentReviews: Array,
    courses: Array,
    earningsChart: Array,
    teacherProfile: Object,
});

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount);
};
</script>

<template>

    <Head title="O'qituvchi paneli" />

    <TeacherLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </template>

        <div class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard title="Jami kurslar" :value="stats.total_courses" icon="📚"
                    :description="`+${stats.published_courses} ta faol`" />
                <StatsCard title="O'quvchilar" :value="stats.total_students" icon="👥" trend="+12%" :trendUp="true" />
                <StatsCard title="Jami daromad" :value="`${formatMoney(stats.total_earnings)}`" icon="💰" trend="+450K"
                    :trendUp="true" description="Bu oy" />
                <StatsCard title="Reyting" :value="stats.avg_rating" icon="⭐"
                    :description="`${stats.total_reviews} ta sharh`" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Earnings Chart -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl lg:col-span-2">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Daromadlar statistikasi</h3>
                        <select
                            class="px-3 py-1.5 text-sm border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                            <option>So'nggi 6 oy</option>
                        </select>
                    </div>
                    <EarningsChart :data="earningsChart" />
                </div>

                <!-- Balance Card -->
                <div class="p-6 bg-emerald-600 text-white rounded-2xl flex flex-col justify-between">
                    <div>
                        <p class="text-emerald-100 font-medium mb-1">Joriy balans</p>
                        <h3 class="text-3xl font-bold mb-4">{{ formatMoney(stats.pending_earnings) }} UZS</h3>
                        <p class="text-sm text-emerald-100 bg-emerald-500/50 inline-block px-3 py-1 rounded-lg">
                            Yechib olish uchun mavjud
                        </p>
                    </div>
                    <button
                        class="w-full py-3 bg-white text-emerald-600 font-bold rounded-xl hover:bg-emerald-50 transition-colors mt-6">
                        Yechib olish
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Recent Enrollments -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">So'nggi o'quvchilar</h3>
                        <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Hammasi</a>
                    </div>
                    <div class="space-y-4">
                        <div v-for="enrollment in recentEnrollments" :key="enrollment.id"
                            class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-medium">
                                {{ enrollment.user.first_name[0] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate">
                                    {{ enrollment.user.first_name }} {{ enrollment.user.last_name }}
                                </p>
                                <p class="text-xs text-gray-500 truncate">{{ enrollment.course.title }}</p>
                            </div>
                            <span class="text-xs text-gray-400">
                                {{ new Date(enrollment.created_at).toLocaleDateString() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Reviews -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">So'nggi sharhlar</h3>
                        <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Hammasi</a>
                    </div>
                    <div class="space-y-4">
                        <div v-for="review in recentReviews" :key="review.id" class="flex gap-4">
                            <div
                                class="w-10 h-10 rounded-full bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-500 font-medium">
                                {{ review.user.first_name[0] }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-bold text-gray-900">
                                        {{ review.user.first_name }} {{ review.user.last_name }}
                                    </span>
                                    <div class="flex text-yellow-400 text-xs">
                                        <span v-for="i in 5" :key="i">{{ i <= review.rating ? '★' : '☆' }}</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ review.content }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ review.course.title }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Courses -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Kurslarim</h3>
                    <a href="/teacher/courses"
                        class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Barcha
                        kurslar</a>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <CourseCard v-for="course in courses" :key="course.id" :course="course" />

                    <!-- Add New Card -->
                    <a href="/teacher/courses/create"
                        class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-200 rounded-2xl hover:border-emerald-500 hover:bg-emerald-50 transition-all group h-full min-h-[280px]">
                        <div
                            class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900 group-hover:text-emerald-700">Yangi kurs yaratish</span>
                    </a>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
