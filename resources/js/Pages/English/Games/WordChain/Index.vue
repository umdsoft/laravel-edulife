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
    totalStars: { type: Number, default: 0 },
    chainMasterRanks: { type: Array, default: () => [] },
});

const selectedCategory = ref(null);
const showTutorial = ref(false);

const filteredLevels = computed(() => {
    if (!selectedCategory.value) return props.levels;
    return props.levels.filter(level => level.categories.includes(selectedCategory.value));
});

const maxStars = computed(() => props.levels?.length * 3 || 30);

const goToLevel = (levelNumber) => {
    router.visit(route('student.english.games.word-chain.play', { level: levelNumber }));
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
        'linear-gradient(135deg, #10b981, #059669)', // emerald
        'linear-gradient(135deg, #22c55e, #16a34a)', // green
        'linear-gradient(135deg, #84cc16, #65a30d)', // lime
        'linear-gradient(135deg, #14b8a6, #0d9488)', // teal
        'linear-gradient(135deg, #06b6d4, #0891b2)', // cyan
    ];
    return gradients[index % gradients.length];
};

const tips = [
    { icon: '🔗', text: "Eng uzun zanjirni yaratish uchun ko'proq so'zlar yodlang!" },
    { icon: '⭐', text: "3 ta yulduz olish uchun maqsadga yeting va kamroq xato qiling" },
    { icon: '🎯', text: "Har bir kategoriya uchun alohida so'zlar mavjud" },
];
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-green-600 to-lime-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-lime-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🔗</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">⛓️</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">📝</div>
                    <div class="absolute bottom-8 right-1/4 text-4xl opacity-15 animate-float-delayed">⭐</div>
                </div>

                <div class="relative z-10">
                    <!-- Back Button -->
                    <Link
                        :href="route('student.english.games.index')"
                        class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm mb-5 transition-all group bg-white/10 backdrop-blur-sm rounded-full px-3 py-1.5 border border-white/10 hover:border-white/30"
                    >
                        <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="font-medium">Barcha o'yinlar</span>
                    </Link>

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Title Section -->
                        <div class="flex items-center gap-4">
                            <!-- Animated Icon -->
                            <div class="relative">
                                <div class="absolute inset-0 bg-green-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-emerald-400 via-green-400 to-lime-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-green-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🔗
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Word <span class="text-transparent bg-clip-text bg-gradient-to-r from-lime-300 to-emerald-300">Chain</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    So'zlar <span class="text-lime-300 font-bold">zanjiri</span> yarating!
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="flex gap-3 flex-wrap">
                            <!-- Total Chains Card -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                        <span class="text-2xl">🔗</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ stats.total_chains }}</div>
                                        <div class="text-white/60 text-xs font-medium">Jami zanjirlar</div>
                                    </div>
                                </div>
                            </div>

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

                    <!-- Quick Stats Row -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/15 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center text-xl shadow-lg">
                                    📊
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-white">{{ stats.longest_chain }}</div>
                                    <div class="text-white/60 text-xs">Eng uzun</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/15 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-teal-600 rounded-xl flex items-center justify-center text-xl shadow-lg">
                                    💎
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-white">{{ stats.total_xp }}</div>
                                    <div class="text-white/60 text-xs">Jami XP</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/15 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-xl shadow-lg">
                                    📚
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-white">{{ stats.unique_words_count }}</div>
                                    <div class="text-white/60 text-xs">Noyob so'z</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/15 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-600 rounded-xl flex items-center justify-center text-xl shadow-lg">
                                    🔥
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-white">{{ stats.daily_streak }}</div>
                                    <div class="text-white/60 text-xs">Kun seriya</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Filter -->
                    <div v-if="categories.length > 0" class="mt-6">
                        <h3 class="text-white font-bold mb-3 flex items-center gap-2">
                            <span>🏷️</span> Kategoriyalar
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="category in categories" :key="category.id"
                                    @click="selectedCategory = selectedCategory === category.id ? null : category.id"
                                    :class="[
                                        'px-4 py-2 rounded-xl font-medium transition-all backdrop-blur-md border',
                                        selectedCategory === category.id
                                            ? 'bg-white/25 text-white border-white/40 shadow-lg'
                                            : 'bg-white/10 text-white/80 border-white/20 hover:bg-white/20 hover:border-white/30'
                                    ]">
                                <span class="mr-2">{{ category.icon }}</span>
                                {{ category.name_uz }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rank Progress Section -->
            <div v-if="stats.rank?.next" class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-8 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">{{ stats.rank.current.icon }}</span>
                        <span class="text-gray-900 dark:text-white font-bold">{{ stats.rank.current.name_uz }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">Keyingi:</span>
                        <span class="text-2xl">{{ stats.rank.next.icon }}</span>
                        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ stats.rank.next.name_uz }}</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-gradient-to-r from-emerald-500 to-green-500 h-3 rounded-full transition-all shadow-lg" :style="{ width: `${stats.rank.progress}%` }"></div>
                </div>
                <div class="text-center text-gray-600 dark:text-gray-400 text-sm mt-2 font-medium">{{ Math.round(stats.rank.progress) }}% bajarildi</div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> O'yin Levellari
                    <span class="text-sm font-normal text-gray-500">({{ filteredLevels?.length || 0 }} ta)</span>
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
                            class="p-4 md:p-5 min-h-[200px] md:min-h-[220px] flex flex-col items-center justify-center relative"
                            :style="{ background: getLevelGradient(index) }"
                        >
                            <!-- Completed Badge -->
                            <div
                                v-if="level.completed"
                                class="absolute top-2 right-2 bg-green-500 text-white w-6 h-6 rounded-full flex items-center justify-center shadow-lg"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
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
                                <span
                                    v-for="i in 3"
                                    :key="i"
                                    :class="[
                                        'text-xl md:text-2xl',
                                        i <= level.best_stars ? 'text-yellow-400' : 'text-white/30'
                                    ]"
                                >⭐</span>
                            </div>

                            <!-- Level Info -->
                            <div class="flex flex-col gap-1 text-xs text-white/80 text-center mb-2">
                                <span>Maqsad: {{ level.target_chain_length }} so'z</span>
                                <span v-if="level.time_limit">⏱ {{ formatTime(level.time_limit) }}</span>
                                <span>Boshi: <strong class="text-white">{{ level.starting_letter }}</strong></span>
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

                            <!-- Best Chain -->
                            <div v-if="level.best_chain_length > 0" class="mt-2 text-xs text-white/70">
                                🏆 Eng yaxshi: {{ level.best_chain_length }}
                            </div>
                        </div>

                        <!-- Locked Level Card -->
                        <div
                            v-else
                            class="p-4 md:p-5 min-h-[200px] md:min-h-[220px] flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 border-2 border-gray-200 dark:border-gray-600 border-dashed"
                        >
                            <!-- Lock Icon -->
                            <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center mb-3">
                                <span class="text-2xl opacity-60">🔒</span>
                            </div>

                            <!-- Level Number (faded) -->
                            <div class="text-4xl font-black text-gray-400 dark:text-gray-500 mb-2">
                                {{ level.level_number }}
                            </div>

                            <!-- Unlock Message -->
                            <div class="bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded-full">
                                <span class="text-gray-600 dark:text-gray-300 text-sm font-medium">
                                    Oldingi levelni tugating
                                </span>
                            </div>

                            <!-- Empty Stars -->
                            <div class="flex gap-0.5 mt-2">
                                <span v-for="i in 3" :key="i" class="text-xl text-gray-300 dark:text-gray-600">⭐</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-8 shadow-lg border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📊</span> Batafsil Statistika
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                        <div class="text-3xl mb-1">🔗</div>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.total_chains }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Jami zanjirlar</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-cyan-50 to-teal-50 dark:from-cyan-900/20 dark:to-teal-900/20 rounded-xl border border-cyan-200 dark:border-cyan-800">
                        <div class="text-3xl mb-1">📝</div>
                        <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ stats.total_words_used }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">So'zlar ishlatilgan</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                        <div class="text-3xl mb-1">💎</div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.unique_words_count }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Noyob so'zlar</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                        <div class="text-3xl mb-1">🔥</div>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.daily_streak }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-sm">Kunlik seriya</div>
                    </div>
                </div>
            </div>

            <!-- Achievements Section -->
            <div v-if="achievements.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-8 shadow-lg border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏆</span> Yutuqlar
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div v-for="achievement in achievements.slice(0, 6)" :key="achievement.id"
                         :class="[
                             'p-4 rounded-xl text-center transition-all border',
                             achievement.achieved
                                ? 'bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 border-emerald-200 dark:border-emerald-800 shadow-md'
                                : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 opacity-50'
                         ]">
                        <div class="text-3xl mb-2">{{ achievement.icon }}</div>
                        <div class="text-gray-900 dark:text-white text-sm font-medium">{{ achievement.name_uz }}</div>
                        <div v-if="achievement.achieved" class="text-emerald-600 dark:text-emerald-400 text-xs mt-1 font-bold">+{{ achievement.xp_reward }} XP</div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-2xl p-5 border border-emerald-200 dark:border-emerald-800">
                <h3 class="font-bold text-emerald-900 dark:text-emerald-300 mb-3 flex items-center gap-2">
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
                    <div class="bg-gradient-to-r from-emerald-600 to-green-600 p-5 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🔗</span>
                            <div>
                                <h2 class="text-xl font-black text-white">Word Chain</h2>
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
                                Oxirgi so'zning <strong>oxirgi harfi</strong> bilan boshlanadigan yangi so'z yozib, eng uzun zanjirni yarating!
                            </p>
                        </div>

                        <!-- How to Play -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>📋</span> O'ynash Qadamlari
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-emerald-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Birinchi so'zni yozing (masalan: <strong>cat</strong>)</p>
                                </div>
                                <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-emerald-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Oxirgi harf bilan yangi so'z yozing (<strong>t</strong>able)</p>
                                </div>
                                <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-emerald-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Maqsad uzunligiga yetguningizcha davom eting!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Example Chain -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                            <h3 class="font-bold text-blue-800 dark:text-blue-400 mb-2 flex items-center gap-2 text-sm">
                                <span>🔗</span> Misol Zanjir
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-800 dark:text-gray-200">cat</span>
                                <span class="text-blue-500">→</span>
                                <span class="px-3 py-1 bg-white dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-800 dark:text-gray-200">table</span>
                                <span class="text-blue-500">→</span>
                                <span class="px-3 py-1 bg-white dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-800 dark:text-gray-200">elephant</span>
                                <span class="text-blue-500">→</span>
                                <span class="px-3 py-1 bg-white dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-800 dark:text-gray-200">tree</span>
                            </div>
                        </div>

                        <!-- Scoring -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                            <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                <span>⭐</span> Yulduzlar Tizimi
                            </h3>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                    <span class="font-bold text-gray-600 dark:text-gray-400">Maqsadga yeting</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">2 yulduz:</span>
                                    <span class="font-bold text-gray-600 dark:text-gray-400">+50% uzunroq</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                    <span class="font-bold text-gray-600 dark:text-gray-400">+100% uzunroq</span>
                                </div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button
                            @click="showTutorial = false"
                            class="w-full py-3 bg-gradient-to-r from-emerald-600 to-green-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
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
