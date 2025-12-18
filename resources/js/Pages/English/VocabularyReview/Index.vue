<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import {
    BookOpenIcon,
    AcademicCapIcon,
    FireIcon,
    SparklesIcon,
    ChartBarIcon,
    ClockIcon,
    CheckCircleIcon,
    PlayIcon,
    Cog6ToothIcon,
    TrophyIcon,
    StarIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolid } from '@heroicons/vue/24/solid';

const props = defineProps({
    config: Object,
    stats: Object,
    categoryProgress: Array,
    achievements: Object,
    reviewSummary: Object,
    reviewModes: Array,
    dailyGoals: Array,
    tips: Array,
});

const selectedMode = ref('mixed');
const selectedCategory = ref(null);
const wordCount = ref(20);
const showGoalSettings = ref(false);

const startReview = () => {
    const params = {
        mode: selectedMode.value,
        count: wordCount.value,
    };
    if (selectedCategory.value) {
        params.category = selectedCategory.value;
    }
    router.get('/student/english/vocabulary/review/session', params);
};

const setDailyGoal = (goalId) => {
    // API call to update goal
    showGoalSettings.value = false;
};

const getRankProgress = computed(() => {
    return props.stats?.vocabulary_rank?.progress || 0;
});

const getAccuracyColor = (accuracy) => {
    if (accuracy >= 90) return 'text-green-500';
    if (accuracy >= 70) return 'text-yellow-500';
    return 'text-red-500';
};

const getCategoryGradient = (index) => {
    const gradients = [
        'linear-gradient(135deg, #10B981, #059669)',
        'linear-gradient(135deg, #3B82F6, #2563EB)',
        'linear-gradient(135deg, #F59E0B, #D97706)',
        'linear-gradient(135deg, #EF4444, #DC2626)',
        'linear-gradient(135deg, #8B5CF6, #7C3AED)',
        'linear-gradient(135deg, #EC4899, #DB2777)',
        'linear-gradient(135deg, #06B6D4, #0891B2)',
        'linear-gradient(135deg, #84CC16, #65A30D)',
        'linear-gradient(135deg, #F97316, #EA580C)',
        'linear-gradient(135deg, #6366F1, #4F46E5)',
    ];
    return gradients[index % gradients.length];
};
</script>

<template>
    <StudentLayout>
        <Head title="So'zlarni Takrorlash | EDULIFE" />

        <div>
            <!-- Hero Section -->
            <div class="relative rounded-3xl overflow-hidden mb-8 bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>

                <div class="relative p-6 lg:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Left: Title & Description -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl shadow-lg">
                                    🔄
                                </div>
                                <div>
                                    <h1 class="text-2xl lg:text-3xl font-black text-white">{{ config?.name_uz || "So'zlarni Takrorlash" }}</h1>
                                    <p class="text-white/80 text-sm">{{ config?.description_uz }}</p>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="flex flex-wrap gap-2 mt-4">
                                <button @click="startReview"
                                    class="flex items-center gap-2 px-5 py-2.5 bg-white text-purple-700 rounded-xl font-bold hover:bg-purple-50 transition-colors shadow-lg">
                                    <PlayIcon class="w-5 h-5" />
                                    Takrorlashni boshlash
                                </button>
                                <button @click="showGoalSettings = true"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-white/20 text-white rounded-xl font-medium hover:bg-white/30 transition-colors backdrop-blur-sm">
                                    <Cog6ToothIcon class="w-5 h-5" />
                                    Maqsad
                                </button>
                            </div>
                        </div>

                        <!-- Right: Daily Goal Progress -->
                        <div class="bg-white/15 backdrop-blur-md rounded-2xl p-5 border border-white/20 min-w-[280px]">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-white/80 text-sm">Bugungi maqsad</span>
                                <span class="text-white font-bold">{{ reviewSummary?.today_reviewed || 0 }} / {{ reviewSummary?.daily_goal?.words || 20 }}</span>
                            </div>
                            <div class="h-3 bg-white/20 rounded-full overflow-hidden mb-3">
                                <div class="h-full bg-gradient-to-r from-green-400 to-emerald-400 rounded-full transition-all duration-500"
                                     :style="{ width: `${reviewSummary?.goal_progress || 0}%` }"></div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-white/80">
                                    <span>{{ reviewSummary?.daily_goal?.icon }}</span>
                                    <span>{{ reviewSummary?.daily_goal?.name_uz || 'Oddiy' }}</span>
                                </div>
                                <div class="text-white font-medium">
                                    {{ reviewSummary?.goal_progress || 0 }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Overview Cards -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-5 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-green-400 to-emerald-600">
                                    📚
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats?.words_learned || 0 }}</p>
                                    <p class="text-white/60 text-xs">O'rganilgan</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-yellow-400 to-amber-600">
                                    ⭐
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats?.words_mastered || 0 }}</p>
                                    <p class="text-white/60 text-xs">O'zlashtirilgan</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-orange-400 to-red-600">
                                    🔥
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats?.daily_streak || 0 }}</p>
                                    <p class="text-white/60 text-xs">Kun seriyasi</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-blue-400 to-blue-600">
                                    🎯
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats?.accuracy || 0 }}%</p>
                                    <p class="text-white/60 text-xs">Aniqlik</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-purple-400 to-purple-600">
                                    🏆
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ achievements?.total_earned || 0 }}</p>
                                    <p class="text-white/60 text-xs">Yutuqlar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vocabulary Rank Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="text-5xl" :style="{ filter: `drop-shadow(0 0 10px ${stats?.vocabulary_rank?.current?.color || '#9CA3AF'})` }">
                            {{ stats?.vocabulary_rank?.current?.icon || '📚' }}
                        </div>
                        <div>
                            <h3 class="text-gray-900 dark:text-white font-bold text-xl">
                                {{ stats?.vocabulary_rank?.current?.name || 'Beginner' }}
                            </h3>
                            <p class="text-purple-600 dark:text-purple-400 text-sm">
                                {{ stats?.vocabulary_rank?.current?.name_uz || "Boshlang'ich" }}
                            </p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                                {{ stats?.words_learned || 0 }} so'z o'rganildi
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Jami XP</div>
                        <div class="text-gray-900 dark:text-white text-2xl font-bold">{{ stats?.total_xp || 0 }}</div>
                    </div>
                </div>

                <!-- Progress to next rank -->
                <div v-if="stats?.vocabulary_rank?.next" class="mt-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Keyingi daraja: {{ stats.vocabulary_rank.next.name_uz }}</span>
                        <span class="text-gray-900 dark:text-white">{{ stats.vocabulary_rank.words_to_next }} so'z kerak</span>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-violet-400 to-purple-500 rounded-full transition-all duration-500"
                             :style="{ width: `${getRankProgress}%` }"></div>
                    </div>
                </div>
                <div v-else class="mt-4 text-center text-purple-600 dark:text-purple-400">
                    🏆 Maksimal darajaga erishdingiz! Siz Vocabulary Master!
                </div>
            </div>

            <!-- Review Modes & Quick Start -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Review Modes -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center gap-2">
                        <BookOpenIcon class="w-6 h-6" />
                        Takrorlash rejimini tanlang
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        <button v-for="mode in reviewModes" :key="mode.id"
                            @click="selectedMode = mode.id"
                            class="p-4 rounded-xl border-2 transition-all text-center"
                            :class="selectedMode === mode.id
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30'
                                : 'border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500'">
                            <span class="text-3xl block mb-2">{{ mode.icon }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white block">{{ mode.name_uz }}</span>
                            <span class="text-xs text-purple-600 dark:text-purple-400">{{ mode.xp_multiplier }}x XP</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Start -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                    <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center gap-2">
                        <PlayIcon class="w-6 h-6" />
                        Tezkor boshlash
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="text-gray-600 dark:text-gray-400 text-sm mb-2 block">So'zlar soni</label>
                            <div class="flex gap-2">
                                <button v-for="count in [10, 20, 30, 50]" :key="count"
                                    @click="wordCount = count"
                                    class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors"
                                    :class="wordCount === count
                                        ? 'bg-purple-500 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                    {{ count }}
                                </button>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <span>Takrorlash uchun tayyor:</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">{{ reviewSummary?.words_to_review || 0 }} so'z</span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-4">
                                <span>Yangi so'zlar:</span>
                                <span class="font-bold text-green-600 dark:text-green-400">{{ reviewSummary?.new_words_available || 0 }} so'z</span>
                            </div>
                        </div>

                        <button @click="startReview"
                            class="w-full py-3 bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-xl font-bold hover:from-violet-600 hover:to-purple-700 transition-all shadow-lg flex items-center justify-center gap-2">
                            <PlayIcon class="w-5 h-5" />
                            Boshlash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Categories Progress -->
            <div class="mb-6">
                <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center gap-2">
                    <ChartBarIcon class="w-6 h-6" />
                    Kategoriyalar bo'yicha progress
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div v-for="(category, index) in categoryProgress" :key="category.id"
                        class="relative rounded-xl overflow-hidden cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-xl"
                        :style="{ background: getCategoryGradient(index) }"
                        @click="selectedCategory = selectedCategory === category.id ? null : category.id">

                        <!-- Selected indicator -->
                        <div v-if="selectedCategory === category.id"
                             class="absolute top-2 right-2 w-6 h-6 bg-white rounded-full flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-purple-600" />
                        </div>

                        <div class="p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-3xl">{{ category.icon }}</span>
                                <div>
                                    <h3 class="text-white font-bold">{{ category.name_uz }}</h3>
                                    <p class="text-white/70 text-xs">{{ category.total_words }} so'z</p>
                                </div>
                            </div>

                            <!-- Progress bar -->
                            <div class="h-2 bg-white/30 rounded-full overflow-hidden mb-2">
                                <div class="h-full bg-white rounded-full transition-all duration-500"
                                     :style="{ width: `${category.progress}%` }"></div>
                            </div>

                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/80">{{ category.learned }}/{{ category.total_words }}</span>
                                <span class="text-white font-bold">{{ category.progress }}%</span>
                            </div>

                            <!-- Mastered indicator -->
                            <div v-if="category.mastered > 0" class="mt-2 flex items-center gap-1 text-yellow-300 text-xs">
                                <StarSolid class="w-4 h-4" />
                                <span>{{ category.mastered }} o'zlashtirilgan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievements Preview -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-gray-900 dark:text-white text-xl font-bold flex items-center gap-2">
                        <TrophyIcon class="w-6 h-6" />
                        Yutuqlar
                    </h2>
                    <span class="text-purple-600 dark:text-purple-400 text-sm font-medium">
                        {{ achievements?.total_earned || 0 }} / {{ achievements?.total || 0 }}
                    </span>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-6 lg:grid-cols-8 gap-3">
                    <div v-for="achievement in achievements?.earned?.slice(0, 8)" :key="achievement.id"
                        class="text-center p-3 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                        <span class="text-2xl block mb-1">{{ achievement.icon }}</span>
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium line-clamp-1">{{ achievement.name_uz }}</span>
                    </div>

                    <div v-for="achievement in achievements?.locked?.slice(0, Math.max(0, 8 - (achievements?.earned?.length || 0)))"
                        :key="achievement.id"
                        class="text-center p-3 bg-gray-100 dark:bg-gray-700 rounded-xl opacity-50">
                        <span class="text-2xl block mb-1 grayscale">{{ achievement.icon }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium line-clamp-1">???</span>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-2xl p-5 border border-violet-200 dark:border-violet-800">
                <h3 class="font-bold text-violet-900 dark:text-violet-300 mb-3 flex items-center gap-2">
                    <span>💡</span> Foydali Maslahatlar
                </h3>
                <div class="grid md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div v-for="tip in tips" :key="tip.text"
                        class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">{{ tip.icon }}</span>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">{{ tip.text }}</p>
                    </div>
                </div>
            </div>

            <!-- Goal Settings Modal -->
            <Teleport to="body">
                <div v-if="showGoalSettings"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="showGoalSettings = false">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-scale-in">
                        <div class="text-center mb-6">
                            <div class="text-4xl mb-3">🎯</div>
                            <h3 class="text-gray-900 dark:text-white text-xl font-bold">Kunlik maqsadni tanlang</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Qanchalik jadal o'rganmoqchisiz?</p>
                        </div>

                        <div class="space-y-3 mb-6">
                            <button v-for="goal in dailyGoals" :key="goal.id"
                                @click="setDailyGoal(goal.id)"
                                class="w-full p-4 rounded-xl border-2 text-left transition-all flex items-center gap-4 hover:border-purple-400 dark:hover:border-purple-500"
                                :class="reviewSummary?.daily_goal?.id === goal.id
                                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30'
                                    : 'border-gray-200 dark:border-gray-600'">
                                <span class="text-3xl">{{ goal.icon }}</span>
                                <div class="flex-1">
                                    <div class="text-gray-900 dark:text-white font-bold">{{ goal.name_uz }}</div>
                                    <div class="text-gray-600 dark:text-gray-400 text-sm">{{ goal.words }} so'z / {{ goal.time }} daqiqa</div>
                                </div>
                                <CheckCircleIcon v-if="reviewSummary?.daily_goal?.id === goal.id"
                                    class="w-6 h-6 text-purple-500" />
                            </button>
                        </div>

                        <button @click="showGoalSettings = false"
                            class="w-full py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Yopish
                        </button>
                    </div>
                </div>
            </Teleport>
        </div>
    </StudentLayout>
</template>

<style scoped>
.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
