<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ArrowLeftIcon, StarIcon, LockClosedIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    levels: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    config: {
        type: Object,
        default: () => ({})
    },
    achievements: {
        type: Array,
        default: () => []
    },
    gameModes: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    },
    totalStars: {
        type: Number,
        default: 0
    },
    timeMasterRanks: {
        type: Array,
        default: () => []
    }
});

const selectedMode = ref('classic');
const selectedCategory = ref('all');
const showTutorial = ref(false);

const filteredLevels = computed(() => {
    if (selectedCategory.value === 'all') {
        return props.levels;
    }
    return props.levels.filter(level => level.category === selectedCategory.value || level.category === 'mixed');
});

const currentRank = computed(() => {
    if (!props.timeMasterRanks.length) return null;

    let current = props.timeMasterRanks[0];
    for (const rank of props.timeMasterRanks) {
        if ((props.stats.total_xp || 0) >= rank.xp_required) {
            current = rank;
        }
    }
    return current;
});

const maxStars = computed(() => props.levels?.length * 3 || 30);

const getLevelGradient = (index) => {
    const colors = [
        { from: '#EF4444', to: '#B91C1C' }, // red
        { from: '#F97316', to: '#C2410C' }, // orange
        { from: '#F59E0B', to: '#B45309' }, // amber
        { from: '#EAB308', to: '#A16207' }, // yellow
        { from: '#84CC16', to: '#4D7C0F' }, // lime
        { from: '#10B981', to: '#047857' }, // emerald
        { from: '#14B8A6', to: '#0F766E' }, // teal
        { from: '#06B6D4', to: '#0E7490' }, // cyan
        { from: '#3B82F6', to: '#1E40AF' }, // blue
        { from: '#6366F1', to: '#4338CA' }, // indigo
        { from: '#8B5CF6', to: '#6D28D9' }, // violet
        { from: '#A855F7', to: '#7E22CE' }, // purple
        { from: '#D946EF', to: '#A21CAF' }, // fuchsia
        { from: '#EC4899', to: '#BE185D' }, // pink
        { from: '#F43F5E', to: '#BE123C' }, // rose
    ];
    const colorIndex = index % colors.length;
    return `linear-gradient(135deg, ${colors[colorIndex].from}, ${colors[colorIndex].to})`;
};

const tips = [
    { icon: '⚡', text: "Tez javob bersangiz, bonus ball olasiz!" },
    { icon: '⭐', text: "3 ta yulduz olish uchun 95%+ aniqlik kerak" },
    { icon: '🎯', text: "Har bir level uchun mukofot faqat 1 marta beriladi" },
];
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Header -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-red-600 via-orange-600 to-amber-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-red-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">⏱️</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">🔥</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">⚡</div>
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
                                <div class="absolute inset-0 bg-red-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-red-400 via-orange-500 to-amber-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-orange-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    ⏱️
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Beat the <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">Clock</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    Vaqtni <span class="text-yellow-300 font-bold">yengib</span> chiqing!
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

                    <!-- Game Modes Cards -->
                    <div v-if="gameModes.length > 0" class="mt-8 grid grid-cols-2 lg:grid-cols-5 gap-3">
                        <div
                            v-for="(mode, index) in gameModes"
                            :key="mode.id"
                            class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 transition-all cursor-pointer"
                            :class="[
                                selectedMode === mode.id
                                    ? 'bg-white/25 border-white/40 ring-2 ring-yellow-400/50'
                                    : 'hover:bg-white/20 hover:border-white/30',
                                index === 0 ? 'hover:shadow-lg hover:shadow-red-500/20' : '',
                                index === 1 ? 'hover:shadow-lg hover:shadow-orange-500/20' : '',
                                index === 2 ? 'hover:shadow-lg hover:shadow-amber-500/20' : '',
                                index === 3 ? 'hover:shadow-lg hover:shadow-yellow-500/20' : '',
                                index === 4 ? 'hover:shadow-lg hover:shadow-lime-500/20' : '',
                            ]"
                            @click="selectedMode = mode.id"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110"
                                    :class="[
                                        index === 0 ? 'bg-gradient-to-br from-red-400 to-red-600' : '',
                                        index === 1 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : '',
                                        index === 2 ? 'bg-gradient-to-br from-amber-400 to-amber-600' : '',
                                        index === 3 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : '',
                                        index === 4 ? 'bg-gradient-to-br from-lime-400 to-lime-600' : '',
                                    ]"
                                >
                                    {{ mode.icon }}
                                </div>
                                <div class="text-center">
                                    <p class="text-white font-bold text-sm">{{ mode.name }}</p>
                                    <p class="text-white/60 text-xs hidden md:block">{{ mode.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="mb-8 grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 text-center hover:shadow-xl transition-shadow">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.games_played || 0 }}</div>
                    <div class="text-gray-600 dark:text-gray-400 text-sm mt-1">O'yinlar</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 text-center hover:shadow-xl transition-shadow">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats.correct_answers || 0 }}</div>
                    <div class="text-gray-600 dark:text-gray-400 text-sm mt-1">To'g'ri javoblar</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 text-center hover:shadow-xl transition-shadow">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.best_accuracy || 0 }}%</div>
                    <div class="text-gray-600 dark:text-gray-400 text-sm mt-1">Eng yaxshi aniqlik</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 text-center hover:shadow-xl transition-shadow">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.best_streak || 0 }}</div>
                    <div class="text-gray-600 dark:text-gray-400 text-sm mt-1">Eng yaxshi streak</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 text-center hover:shadow-xl transition-shadow">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ currentRank?.name || 'Yangi' }}</div>
                    <div class="text-gray-600 dark:text-gray-400 text-sm mt-1">Daraja</div>
                </div>
            </div>

            <!-- Categories -->
            <div v-if="categories.length > 0" class="mb-8 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏷️</span> Kategoriyalar
                </h2>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        class="px-4 py-2 rounded-xl font-medium transition-all"
                        :class="[
                            selectedCategory === category.id
                                ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600'
                        ]"
                        @click="selectedCategory = category.id"
                    >
                        {{ category.icon }} {{ category.name }}
                    </button>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> Darajalar
                    <span class="text-sm font-normal text-gray-500">({{ filteredLevels?.length || 0 }} ta)</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                    <div
                        v-for="(level, index) in filteredLevels"
                        :key="level.id"
                        class="relative rounded-2xl overflow-hidden transition-all duration-300"
                        :class="[
                            level.unlocked
                                ? 'cursor-pointer hover:scale-105 hover:shadow-2xl'
                                : 'cursor-not-allowed'
                        ]"
                    >
                        <!-- Unlocked Level Card -->
                        <Link
                            v-if="level.unlocked"
                            :href="route('student.english.games.beat-the-clock.play', { level: level.level_number })"
                            class="block p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center relative"
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

                            <!-- Questions + Time -->
                            <div class="flex items-center gap-2 mb-2">
                                <span class="bg-white/20 px-2 py-0.5 rounded text-white/90 text-xs font-medium">
                                    {{ level.questions_count }} savol
                                </span>
                                <span class="text-white/70 text-xs">⏱ {{ level.time_per_question || Math.round(level.total_time / level.questions_count) }}s</span>
                            </div>

                            <!-- Stars -->
                            <div class="flex gap-0.5 mb-1">
                                <StarIcon
                                    v-for="i in 3"
                                    :key="i"
                                    class="w-5 h-5"
                                    :class="i <= level.stars ? 'text-yellow-400' : 'text-white/30'"
                                />
                            </div>

                            <!-- Best Accuracy -->
                            <div v-if="level.best_accuracy" class="text-white/80 text-xs font-medium bg-white/10 px-2 py-1 rounded">
                                🏆 {{ level.best_accuracy }}%
                            </div>
                        </Link>

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

                            <!-- Unlock Requirement -->
                            <div class="bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded-full">
                                <span class="text-gray-600 dark:text-gray-300 text-xs font-medium">
                                    {{ level.unlock_requirement }}
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

            <!-- Achievements Section -->
            <div v-if="achievements.length > 0" class="mb-8 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏆</span> Yutuqlar
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="achievement in achievements.slice(0, 8)"
                        :key="achievement.id"
                        class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center transition-all"
                        :class="{
                            'opacity-50': !achievement.unlocked,
                            'hover:shadow-lg': achievement.unlocked
                        }"
                    >
                        <div class="text-3xl mb-2">{{ achievement.icon }}</div>
                        <div class="text-gray-900 dark:text-white font-medium text-sm">{{ achievement.name }}</div>
                        <div class="text-gray-600 dark:text-gray-400 text-xs mt-1">{{ achievement.description }}</div>
                        <div v-if="achievement.unlocked" class="mt-2">
                            <span class="text-xs bg-green-500/20 text-green-600 dark:text-green-400 px-2 py-1 rounded-full font-medium">
                                Ochildi!
                            </span>
                        </div>
                        <div v-else class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ achievement.progress || 0 }}%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Master Ranks -->
            <div v-if="timeMasterRanks.length > 0" class="mb-8 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>👑</span> Time Master Darajalari
                </h2>
                <div class="flex overflow-x-auto gap-4 pb-2">
                    <div
                        v-for="rank in timeMasterRanks"
                        :key="rank.id"
                        class="flex-shrink-0 text-center p-4 rounded-xl min-w-[120px] transition-all"
                        :class="[
                            currentRank?.id === rank.id
                                ? 'bg-gradient-to-br from-orange-500 to-red-500 text-white ring-2 ring-orange-400 shadow-lg'
                                : stats.total_xp >= rank.xp_required
                                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                        ]"
                    >
                        <div class="text-3xl mb-2">{{ rank.icon }}</div>
                        <div class="font-bold text-sm">{{ rank.name }}</div>
                        <div class="text-xs opacity-80 mt-1">{{ rank.xp_required }} XP</div>
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
                    <div class="bg-gradient-to-r from-red-600 to-orange-600 p-5 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">⏱️</span>
                            <div>
                                <h2 class="text-xl font-black text-white">Beat the Clock</h2>
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
                                <strong>Vaqt tugamasidan</strong> oldin barcha savollarga javob bering! Har bir savol uchun ma'lum vaqt beriladi.
                            </p>
                        </div>

                        <!-- Game Modes -->
                        <div v-if="gameModes.length > 0">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>🎮</span> O'yin Rejimlari
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="mode in gameModes.slice(0, 4)"
                                    :key="mode.id"
                                    class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5"
                                >
                                    <span class="text-xl">{{ mode.icon }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-xs">{{ mode.name }}</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-[10px]">{{ mode.description }}</p>
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
                                <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Savolni o'qing va javobni tanlang</p>
                                </div>
                                <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Vaqt tugamasidan javob bering</p>
                                </div>
                                <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Ballar yig'ib yulduzlar oling</p>
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
                                    <span class="text-gray-700 dark:text-gray-300">To'g'ri javob:</span>
                                    <span class="font-bold text-green-600">+10 ball</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">Tez javob:</span>
                                    <span class="font-bold text-blue-600">+5 ball</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                    <span class="text-gray-600 dark:text-gray-400">70%+</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                    <span class="text-gray-600 dark:text-gray-400">95%+</span>
                                </div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button
                            @click="showTutorial = false"
                            class="w-full py-3 bg-gradient-to-r from-red-600 to-orange-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
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
