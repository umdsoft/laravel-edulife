<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
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
    englishProgress: Object,
    labProgress: Object,
    dailyMissions: Object,
});

const navigateTo = (path) => {
    router.visit(path);
};
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
                <!-- English Learning Card (in main column) -->
                <!-- Learning Modules Grid -->
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-4">O'quv modullari</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- English Learning Card -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-xl transform transition-all duration-300 hover:scale-[1.01] hover:shadow-2xl group cursor-pointer"
                            @click="navigateTo('/student/english')">
                            <!-- Decorative elements -->
                            <div
                                class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2">
                            </div>
                            <div
                                class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2">
                            </div>
    
                            <div class="relative z-10 flex flex-col h-full justify-between">
                                <div>
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-3xl shadow-lg">
                                                🇬🇧
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-bold">Ingliz tili</h3>
                                                <p class="text-blue-100 text-sm">CEFR standartida</p>
                                            </div>
                                        </div>
                                        <div v-if="englishProgress?.started"
                                            class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-medium">
                                            {{ englishProgress.level }}
                                        </div>
                                    </div>
    
                                    <template v-if="englishProgress?.started">
                                        <div class="grid grid-cols-2 gap-3 mb-4">
                                            <div class="bg-white/10 rounded-lg p-2 text-center">
                                                <div class="text-lg font-bold">{{ englishProgress.total_xp }}</div>
                                                <div class="text-[10px] text-blue-100">XP</div>
                                            </div>
                                            <div class="bg-white/10 rounded-lg p-2 text-center">
                                                <div class="text-lg font-bold">🔥 {{ englishProgress.streak }}</div>
                                                <div class="text-[10px] text-blue-100">Streak</div>
                                            </div>
                                        </div>
    
                                        <div class="mb-4">
                                            <div class="flex justify-between text-sm mb-1">
                                                <span class="text-blue-100">{{ englishProgress.level_name }}</span>
                                                <span class="font-bold">{{ englishProgress.progress_percent }}%</span>
                                            </div>
                                            <div class="w-full bg-white/20 rounded-full h-2 overflow-hidden">
                                                <div class="bg-white h-2 rounded-full transition-all duration-500 ease-out"
                                                    :style="{ width: `${englishProgress.progress_percent}%` }"></div>
                                            </div>
                                        </div>
                                    </template>
    
                                    <template v-else>
                                        <p class="text-blue-100 mb-4 text-sm">
                                            Interaktiv darslar va o'yinlar orqali ingliz tilini o'rganing.
                                        </p>
                                    </template>
                                </div>
    
                                <button
                                    class="w-full py-3 bg-white text-blue-600 rounded-xl font-bold hover:bg-blue-50 transition-colors shadow-lg mt-auto">
                                    {{ englishProgress?.started ? 'Davom ettirish →' : 'Bepul boshlash 🚀' }}
                                </button>
                            </div>
                        </div>

                        <!-- Physics Lab Card -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 rounded-2xl p-6 text-white shadow-xl transform transition-all duration-300 hover:scale-[1.01] hover:shadow-2xl group cursor-pointer"
                            @click="navigateTo('/student/lab')">
                            <div
                                class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2">
                            </div>
    
                            <div class="relative z-10 flex flex-col h-full justify-between">
                                <div>
                                    <div class="flex items-center gap-3 mb-4">
                                        <div
                                            class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-3xl shadow-lg">
                                            🔬
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold">Virtual Lab</h3>
                                            <p class="text-emerald-100 text-sm">Fizika tajribalari</p>
                                        </div>
                                    </div>
    
                                    <template v-if="labProgress?.started">
                                        <div class="flex items-center justify-between text-sm mb-2">
                                            <span class="text-emerald-100">Jarayon</span>
                                            <span class="font-bold">{{ labProgress.completed_simulations }}/{{ labProgress.total_simulations }}</span>
                                        </div>
                                        <div class="w-full bg-white/20 rounded-full h-2 mb-4">
                                            <div class="bg-white h-2 rounded-full transition-all"
                                                :style="{ width: `${labProgress.progress_percent}%` }"></div>
                                        </div>
                                    </template>
    
                                    <template v-else>
                                        <p class="text-emerald-100 text-sm mb-4">
                                            Virtual laboratoriyada fizik qonuniyatlarni kashf eting.
                                        </p>
                                    </template>
                                </div>
                                
                                <button
                                    class="w-full py-3 bg-white text-emerald-600 rounded-xl font-bold hover:bg-emerald-50 transition-colors shadow-lg mt-auto">
                                    {{ labProgress?.started ? 'Tajribalarni davom ettirish' : 'Laboratoriyaga kirish 🧪' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

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
            <div class="space-y-6">
                <!-- Daily Missions (Real Data) -->
                <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg">Kunlik vazifalar</h3>
                        <span class="bg-white/20 px-2 py-1 rounded-lg text-xs font-bold">
                            {{ dailyMissions?.hours_remaining || 0 }}s qoldi
                        </span>
                    </div>

                    <div class="space-y-3" v-if="dailyMissions?.missions?.length > 0">
                        <div v-for="mission in dailyMissions.missions" :key="mission.id"
                            class="bg-white/10 rounded-xl p-3">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center text-lg">
                                    {{ mission.icon }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-sm">{{ mission.title }}</p>
                                    <div class="flex items-center gap-2 text-xs text-purple-200">
                                        <span>+{{ mission.xp_reward }} XP</span>
                                        <span v-if="mission.coin_reward">+{{ mission.coin_reward }} 🪙</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold">{{ mission.current }}/{{ mission.target }}</div>
                                    <div v-if="mission.is_completed && !mission.is_claimed"
                                        class="text-[10px] text-green-300">✓
                                        Tayyor</div>
                                </div>
                            </div>
                            <div class="w-full bg-black/20 rounded-full h-1.5">
                                <div class="bg-white h-1.5 rounded-full transition-all"
                                    :class="mission.is_completed ? 'bg-green-400' : 'bg-white'"
                                    :style="{ width: `${mission.progress_percent}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-4 text-purple-200 text-sm">
                        Bugun uchun vazifalar yo'q
                    </div>

                    <div class="mt-4 text-center text-xs text-purple-200">
                        {{ dailyMissions?.completed_count || 0 }}/{{ dailyMissions?.total_count || 0 }} bajarildi
                    </div>
                </div>

                <!-- Categories -->
                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-3">Yo'nalishlar</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <CategoryCard v-for="direction in directions.slice(0, 4)" :key="direction.id"
                            :direction="direction" />
                    </div>
                    <Link :href="route('student.courses.index')"
                        class="block mt-3 text-center text-sm font-medium text-purple-600 hover:text-purple-700">
                    Barcha yo'nalishlar
                    </Link>
                </section>

                <!-- New Courses -->
                <section>
                    <h2 class="text-lg font-bold text-gray-900 mb-3">Yangi kurslar</h2>
                    <div class="space-y-3">
                        <Link v-for="course in newCourses.slice(0, 3)" :key="course.id"
                            :href="route('student.courses.show', course.slug)" class="flex gap-3 group">
                        <img :src="course.thumbnail_url || '/images/course-placeholder.jpg'"
                            class="w-16 h-12 rounded-lg object-cover group-hover:opacity-90 transition-opacity">
                        <div>
                            <h4
                                class="font-bold text-gray-900 text-sm line-clamp-2 group-hover:text-purple-600 transition-colors">
                                {{ course.title }}
                            </h4>
                            <p class="text-xs text-gray-500">{{ course.teacher?.full_name }}</p>
                        </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </StudentLayout>
</template>
