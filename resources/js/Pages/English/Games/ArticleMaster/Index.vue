<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ArrowLeftIcon, LockClosedIcon, StarIcon, CheckCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    levels: { type: Array, required: true },
    stats: { type: Object, required: true },
    config: { type: Object, required: true },
    achievements: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    articles: { type: Array, default: () => [] },
    totalStars: { type: Number, default: 0 },
    articleMasterRanks: { type: Array, default: () => [] },
});

const selectedCategory = ref(null);
const showTutorial = ref(false);

const filteredLevels = computed(() => {
    if (!selectedCategory.value) return props.levels;
    return props.levels.filter(level => level.categories.includes(selectedCategory.value));
});

const maxStars = computed(() => props.levels?.length * 3 || 30);

const goToLevel = (levelNumber) => {
    router.visit(route('student.english.games.article-master.play', { level: levelNumber }));
};

const getArticleAccuracy = (articleId) => {
    const articleStats = props.stats.article_stats?.[articleId];
    if (!articleStats || articleStats.total === 0) return 0;
    return Math.round((articleStats.correct / articleStats.total) * 100);
};

const formatTime = (seconds) => {
    if (!seconds) return '';
    const mins = Math.floor(seconds / 60);
    return `${mins} daq`;
};

const getDifficultyColor = (difficulty) => {
    const colors = {
        beginner: 'bg-green-500/30 text-green-300',
        elementary: 'bg-blue-500/30 text-blue-300',
        intermediate: 'bg-yellow-500/30 text-yellow-300',
        advanced: 'bg-red-500/30 text-red-300',
    };
    return colors[difficulty] || 'bg-gray-500/30 text-gray-300';
};

const getDifficultyLabel = (difficulty) => {
    const labels = {
        beginner: "Boshlang'ich",
        elementary: 'Oddiy',
        intermediate: "O'rta",
        advanced: 'Yuqori',
    };
    return labels[difficulty] || difficulty;
};

const getLevelGradient = (index) => {
    const gradients = [
        'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', // Amber
        'linear-gradient(135deg, #f97316 0%, #ea580c 100%)', // Orange
        'linear-gradient(135deg, #eab308 0%, #ca8a04 100%)', // Yellow
        'linear-gradient(135deg, #f59e0b 0%, #ea580c 100%)', // Amber-Orange mix
        'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)', // Light Amber
    ];
    return gradients[index % gradients.length];
};

const tips = [
    { icon: '📖', text: "Ingliz tilida 'a', 'an', 'the' va artikl ishlatmaslik to'g'ri qo'llaniladi!" },
    { icon: '⭐', text: "3 ta yulduz olish uchun 95%+ aniqlik kerak" },
    { icon: '🎯', text: "Har bir level uchun mukofot faqat 1 marta beriladi" },
];
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-amber-600 via-orange-600 to-yellow-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-orange-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">📖</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">📚</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float font-bold text-white">A</div>
                    <div class="absolute bottom-8 right-1/4 text-4xl opacity-15 animate-float-delayed">📝</div>
                </div>

                <div class="relative z-10">
                    <!-- Back Button -->
                    <Link
                        :href="route('student.english.games.index')"
                        class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm mb-5 transition-all group bg-white/10 backdrop-blur-sm rounded-full px-3 py-1.5 border border-white/10 hover:border-white/30"
                    >
                        <ArrowLeftIcon class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" />
                        <span class="font-medium">Barcha o'yinlar</span>
                    </Link>

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Title Section -->
                        <div class="flex items-center gap-4">
                            <!-- Animated Icon -->
                            <div class="relative">
                                <div class="absolute inset-0 bg-yellow-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-yellow-400 via-orange-400 to-amber-600 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-orange-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    📖
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Article <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-orange-200">Master</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    {{ config.description_uz }}
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="flex gap-3">
                            <!-- Stars Progress Card -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-yellow-500/30">
                                        <span class="text-2xl">⭐</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ totalStars }}</div>
                                        <div class="text-white/60 text-xs font-medium">/ {{ maxStars }} yulduz</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Button -->
                            <button
                                @click="showTutorial = true"
                                class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 hover:bg-white/25 hover:border-white/40 transition-all shadow-xl group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                                        <span class="text-2xl">❓</span>
                                    </div>
                                    <span class="text-white font-semibold text-sm hidden md:block">Qanday<br>o'ynash?</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="text-2xl font-bold text-white">{{ stats.total_correct }}</div>
                            <div class="text-white/70 text-sm">To'g'ri javoblar</div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="text-2xl font-bold text-green-400">{{ stats.overall_accuracy }}%</div>
                            <div class="text-white/70 text-sm">To'g'rilik</div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ stats.rank?.current?.icon }}</span>
                                <span class="text-white font-medium">{{ stats.rank?.current?.name_uz }}</span>
                            </div>
                            <div class="text-white/70 text-sm">Daraja</div>
                        </div>
                        <div v-if="stats.rank?.next" class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all">
                            <div class="w-full bg-white/20 rounded-full h-2 mb-1">
                                <div class="bg-gradient-to-r from-yellow-400 to-orange-400 h-2 rounded-full transition-all" :style="{ width: `${stats.rank.progress}%` }"></div>
                            </div>
                            <div class="text-white/70 text-sm">Keyingi: {{ stats.rank.next.name_uz }}</div>
                        </div>
                    </div>

                    <!-- Article Stats Cards -->
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div
                            v-for="article in articles"
                            :key="article.id"
                            class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all text-center"
                        >
                            <div class="text-3xl font-black text-yellow-300 mb-1">{{ article.name }}</div>
                            <div class="text-white text-lg font-bold">{{ getArticleAccuracy(article.id) }}%</div>
                            <div class="text-white/70 text-xs">{{ article.name_uz }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📂</span> Kategoriyalar
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectedCategory = selectedCategory === category.id ? null : category.id"
                        :class="[
                            'px-4 py-2 rounded-xl font-medium transition-all',
                            selectedCategory === category.id
                                ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
                        ]"
                    >
                        <span class="mr-2">{{ category.icon }}</span>
                        {{ category.name_uz }}
                    </button>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> O'yin Levellari
                    <span class="text-sm font-normal text-gray-500">({{ filteredLevels.length }} ta)</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                    <div
                        v-for="(level, index) in filteredLevels"
                        :key="level.id"
                        @click="level.unlocked && goToLevel(level.level_number)"
                        class="relative rounded-2xl overflow-hidden transition-all duration-300"
                        :class="[
                            level.unlocked
                                ? 'cursor-pointer hover:scale-105 hover:shadow-2xl'
                                : 'cursor-not-allowed'
                        ]"
                    >
                        <!-- Unlocked Level Card -->
                        <div
                            v-if="level.unlocked"
                            class="p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center relative"
                            :style="{ background: getLevelGradient(index) }"
                        >
                            <!-- Completed Badge -->
                            <div
                                v-if="level.completed"
                                class="absolute top-2 right-2 bg-green-500 text-white w-6 h-6 rounded-full flex items-center justify-center shadow-lg"
                            >
                                <CheckCircleIcon class="w-4 h-4" />
                            </div>

                            <!-- Level Number -->
                            <div class="text-5xl md:text-6xl font-black text-white/90 mb-1">
                                {{ level.level_number }}
                            </div>

                            <!-- Level Name -->
                            <div class="text-white font-bold text-center text-sm md:text-base mb-1">
                                {{ level.name }}
                            </div>
                            <div class="text-white/80 text-xs mb-2">{{ level.name_uz }}</div>

                            <!-- Stars -->
                            <div class="flex gap-0.5 mb-2">
                                <StarIcon
                                    v-for="i in 3"
                                    :key="i"
                                    class="w-5 h-5"
                                    :class="i <= level.best_stars ? 'text-yellow-300' : 'text-white/30'"
                                />
                            </div>

                            <!-- Level Info -->
                            <div class="flex items-center gap-2 text-xs text-white/70 mb-2">
                                <span>{{ level.question_count }} savol</span>
                                <span v-if="level.time_limit">⏱ {{ formatTime(level.time_limit) }}</span>
                            </div>

                            <!-- Difficulty Badge -->
                            <div>
                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    getDifficultyColor(level.difficulty)
                                ]">
                                    {{ getDifficultyLabel(level.difficulty) }}
                                </span>
                            </div>
                        </div>

                        <!-- Locked Level Card -->
                        <div
                            v-else
                            class="p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 border-2 border-gray-200 dark:border-gray-600 border-dashed"
                        >
                            <!-- Lock Icon -->
                            <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center mb-3">
                                <LockClosedIcon class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                            </div>

                            <!-- Level Number (faded) -->
                            <div class="text-4xl font-black text-gray-400 dark:text-gray-500 mb-2">
                                {{ level.level_number }}
                            </div>

                            <!-- Unlock Message -->
                            <div class="bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded-full">
                                <span class="text-gray-600 dark:text-gray-300 text-sm font-medium">
                                    Qulflangan
                                </span>
                            </div>

                            <!-- Empty Stars -->
                            <div class="flex gap-0.5 mt-2">
                                <StarIcon v-for="i in 3" :key="i" class="w-5 h-5 text-gray-300 dark:text-gray-600" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievements Section -->
            <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏆</span> Yutuqlar
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div
                        v-for="achievement in achievements.slice(0, 6)"
                        :key="achievement.id"
                        :class="[
                            'p-4 rounded-xl text-center transition-all border-2',
                            achievement.achieved
                                ? 'bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-yellow-400 dark:border-yellow-600'
                                : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 opacity-60'
                        ]"
                    >
                        <div class="text-3xl mb-2">{{ achievement.icon }}</div>
                        <div class="text-gray-900 dark:text-white text-sm font-medium">{{ achievement.name_uz }}</div>
                        <div v-if="achievement.achieved" class="text-yellow-600 dark:text-yellow-400 text-xs mt-1 font-bold">+{{ achievement.xp_reward }} XP</div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-5 border border-amber-200 dark:border-amber-800">
                <h3 class="font-bold text-amber-900 dark:text-amber-300 mb-3 flex items-center gap-2">
                    <span>💡</span> Foydali Maslahatlar
                </h3>
                <div class="grid md:grid-cols-3 gap-3">
                    <div
                        v-for="tip in tips"
                        :key="tip.text"
                        class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3"
                    >
                        <span class="text-xl">{{ tip.icon }}</span>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">{{ tip.text }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tutorial Modal -->
        <Teleport to="body">
            <div
                v-if="showTutorial"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center p-4 overflow-y-auto"
                @click.self="showTutorial = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg my-4 relative animate-scale-in">
                    <!-- Sticky Close Button -->
                    <button
                        @click="showTutorial = false"
                        class="absolute -top-3 -right-3 w-10 h-10 bg-gray-800 dark:bg-gray-600 hover:bg-gray-700 rounded-full flex items-center justify-center text-white shadow-lg z-10 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-amber-600 to-orange-600 p-5 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">📖</span>
                            <div>
                                <h2 class="text-xl font-black text-white">Article Master</h2>
                                <p class="text-white/80 text-sm">Qanday o'ynash kerak?</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 space-y-4">
                        <!-- Game Goal -->
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                            <h3 class="font-bold text-green-800 dark:text-green-400 mb-1 flex items-center gap-2 text-sm">
                                <span>🎯</span> O'yin Maqsadi
                            </h3>
                            <p class="text-green-700 dark:text-green-300 text-sm">
                                Artiklarni (<strong>a</strong>, <strong>an</strong>, <strong>the</strong>) to'g'ri joylashtiring yoki artikl kerak emasligini toping!
                            </p>
                        </div>

                        <!-- Articles Explanation -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>📚</span> Artikl Turlari
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-start gap-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-2.5">
                                    <span class="text-xl font-bold text-blue-600">A</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-xs">Noaniq artikl (undosh)</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-[10px]">Masalan: a book, a car</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2.5">
                                    <span class="text-xl font-bold text-purple-600">AN</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-xs">Noaniq artikl (unli)</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-[10px]">Masalan: an apple, an hour</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-2.5">
                                    <span class="text-xl font-bold text-amber-600">THE</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-xs">Aniq artikl</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-[10px]">Masalan: the sun, the book</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                                    <span class="text-xl font-bold text-gray-600 dark:text-gray-400">Ø</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-xs">Artikl kerak emas</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-[10px]">Masalan: water, love</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- How to Play Steps -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>📋</span> O'ynash Qadamlari
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-amber-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Gapni o'qing va bo'sh joyni toping</p>
                                </div>
                                <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-amber-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">To'g'ri artiklni tanlang</p>
                                </div>
                                <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-amber-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Javobni tasdiqlang va davom eting</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                            <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                <span>⭐</span> Ball Tizimi
                            </h3>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                    <span class="text-gray-600 dark:text-gray-400">Min so'z</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">2 yulduz:</span>
                                    <span class="text-gray-600 dark:text-gray-400">85%+</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2 col-span-2">
                                    <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                    <span class="font-bold text-yellow-600">95%+</span>
                                </div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button
                            @click="showTutorial = false"
                            class="w-full py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                        >
                            Tushundim! 🚀
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </StudentLayout>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-15px) rotate(-5deg); }
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.2; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.1); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 5s ease-in-out infinite;
    animation-delay: 1s;
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 8s ease infinite;
}

.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}

.animate-pulse-slow-delayed {
    animation: pulse-slow 5s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
