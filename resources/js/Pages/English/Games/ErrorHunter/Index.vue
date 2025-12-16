<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    gameModes: Object,
    errorTypes: Object,
    powerups: Object,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedMode = ref('spot_error')

const totalStars = computed(() => {
    return props.levels?.reduce((sum, level) => sum + (level.best_stars || 0), 0) || 0
})
const maxStars = computed(() => (props.levels?.length || 10) * 3)
const completedLevels = computed(() => props.levels?.filter(l => l.completed).length || 0)
const totalLevels = computed(() => props.levels?.length || 10)

const progressPercentage = computed(() => {
    return maxStars.value > 0 ? Math.round((totalStars.value / maxStars.value) * 100) : 0
})

const earnedAchievements = computed(() => {
    if (!props.achievements || !props.userAchievements) return []
    return props.achievements.filter(a => props.userAchievements.includes(a.id))
})

const gameModesList = computed(() => {
    return Object.values(props.gameModes || {})
})

const powerupsList = computed(() => {
    return Object.values(props.powerups || {})
})

const errorTypesList = computed(() => {
    return Object.values(props.errorTypes || {})
})

// Unique vibrant colors for each level - cycling through beautiful gradients
const levelGradients = {
    1: 'from-amber-400 via-amber-500 to-yellow-600',
    2: 'from-orange-400 via-orange-500 to-amber-600',
    3: 'from-rose-400 via-rose-500 to-orange-600',
    4: 'from-red-400 via-red-500 to-rose-600',
    5: 'from-pink-400 via-pink-500 to-rose-600',
    6: 'from-fuchsia-400 via-fuchsia-500 to-pink-600',
    7: 'from-purple-400 via-purple-500 to-fuchsia-600',
    8: 'from-violet-400 via-violet-500 to-purple-600',
    9: 'from-indigo-400 via-indigo-500 to-violet-600',
    10: 'from-blue-400 via-blue-500 to-indigo-600',
}

// CEFR level badge colors
const cefrColors = {
    'A1': 'bg-emerald-500',
    'A2': 'bg-cyan-500',
    'B1': 'bg-blue-500',
    'B2': 'bg-violet-500',
    'C1': 'bg-purple-500',
    'C2': 'bg-rose-500',
}

function getLevelIcon(level) {
    return level.icon || '🔍'
}

function getGradientClass(level) {
    return levelGradients[level.number] || 'from-amber-500 to-orange-600'
}

function getCefrBadgeClass(cefrLevel) {
    return cefrColors[cefrLevel] || 'bg-gray-500'
}

function getTimeLimit(level) {
    // Get time limit based on selected mode
    if (typeof level.time_limit === 'object') {
        return level.time_limit[selectedMode.value] || level.time_limit.spot_error || 120
    }
    return level.time_limit || 120
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/error-hunter/play/${level.number}`)
}

function getModeIcon(mode) {
    const icons = {
        'spot_error': '🎯',
        'fix_error': '🔧',
        'rewrite': '✍️',
    }
    return icons[mode.id] || mode.icon || '🎮'
}

function getPowerupIcon(powerup) {
    return powerup.icon || '🔮'
}

function isAchievementUnlocked(achievementId) {
    return (props.userAchievements || []).includes(achievementId)
}
</script>

<template>
    <Head title="Xato Ovchisi - Error Hunter" />

    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-red-600 via-rose-600 to-pink-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-pink-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🔍</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">❌</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">✏️</div>
                    <div class="absolute bottom-8 right-1/4 text-4xl opacity-15 animate-float-delayed">⭐</div>
                </div>

                <div class="relative z-10">
                    <!-- Back Button -->
                    <Link
                        href="/student/english/games"
                        class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm mb-5 transition-all group bg-white/10 backdrop-blur-sm rounded-full px-3 py-1.5 border border-white/10 hover:border-white/30"
                    >
                        <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="font-medium">O'yinlarga qaytish</span>
                    </Link>

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Title Section -->
                        <div class="flex items-center gap-4">
                            <!-- Animated Icon -->
                            <div class="relative">
                                <div class="absolute inset-0 bg-red-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-red-400 via-rose-400 to-pink-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-rose-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🔍
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">Xato</span> Ovchisi
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    {{ config?.description_uz || "Gaplardagi grammatika, imlo va tinish belgisi xatolarini toping va tuzating" }}
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

                            <!-- Help & Achievement Buttons -->
                            <div class="flex gap-2">
                                <button
                                    @click="showAchievements = true"
                                    class="bg-white/15 backdrop-blur-xl rounded-2xl px-4 py-4 border border-white/20 hover:bg-white/25 hover:border-white/40 transition-all shadow-xl group"
                                >
                                    <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30 group-hover:scale-110 transition-transform">
                                        <span class="text-2xl">🏆</span>
                                    </div>
                                </button>

                                <button
                                    @click="showTutorial = true"
                                    class="bg-white/15 backdrop-blur-xl rounded-2xl px-4 py-4 border border-white/20 hover:bg-white/25 hover:border-white/40 transition-all shadow-xl group"
                                >
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                                        <span class="text-2xl">❓</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🎯
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ userStats?.total_errors_found || 0 }}</p>
                                    <p class="text-white/60 text-xs">Xatolar topildi</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🔥
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ userStats?.best_streak || 0 }}</p>
                                    <p class="text-white/60 text-xs">Eng yaxshi streak</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    ✅
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ userStats?.accuracy_rate || 0 }}%</p>
                                    <p class="text-white/60 text-xs">Aniqlik</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game Modes Section -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> O'yin Rejimlari
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="mode in gameModesList" :key="mode.id"
                         @click="selectedMode = mode.id"
                         :class="[
                             'relative p-5 rounded-2xl cursor-pointer transition-all duration-300 border-2 shadow-lg',
                             selectedMode === mode.id
                                 ? 'bg-gradient-to-br from-rose-500 to-pink-600 border-rose-400 shadow-rose-200 dark:shadow-rose-900/50 scale-[1.02]'
                                 : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-rose-400 hover:shadow-xl'
                         ]">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-md',
                                selectedMode === mode.id
                                    ? 'bg-white/20'
                                    : 'bg-gradient-to-br from-rose-100 to-pink-100 dark:from-rose-900/50 dark:to-pink-900/50'
                            ]">
                                {{ getModeIcon(mode) }}
                            </div>
                            <div>
                                <h3 :class="[
                                    'font-bold text-lg',
                                    selectedMode === mode.id ? 'text-white drop-shadow-md' : 'text-gray-800 dark:text-white'
                                ]">{{ mode.name_uz }}</h3>
                                <p :class="[
                                    'text-sm',
                                    selectedMode === mode.id ? 'text-white/90' : 'text-gray-500 dark:text-gray-400'
                                ]">{{ mode.description_uz }}</p>
                            </div>
                        </div>
                        <div v-if="selectedMode === mode.id" class="absolute top-3 right-3">
                            <span class="bg-white/30 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full font-semibold shadow-md">✓ Tanlangan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Types & Powerups Section -->
            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <!-- Error Types -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-5">
                    <h3 class="flex items-center gap-2 font-bold text-gray-800 dark:text-white mb-3">
                        <span class="text-xl">📝</span> Xato Turlari
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div v-for="errorType in errorTypesList" :key="errorType.id"
                             class="group flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                            <span class="text-lg group-hover:scale-110 transition-transform">{{ errorType.icon }}</span>
                            <div class="text-sm font-medium text-gray-800 dark:text-white leading-tight">{{ errorType.name_uz }}</div>
                        </div>
                    </div>
                </div>

                <!-- Powerups -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-5">
                    <h3 class="flex items-center gap-2 font-bold text-gray-800 dark:text-white mb-3">
                        <span class="text-xl">🔮</span> Maxsus Kuchlar
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div v-for="powerup in powerupsList" :key="powerup.id"
                             class="group flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                            <span class="text-xl group-hover:scale-110 transition-transform">{{ getPowerupIcon(powerup) }}</span>
                            <div>
                                <div class="text-sm font-medium text-gray-800 dark:text-white leading-tight">{{ powerup.name_uz }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ powerup.cost_coins }} tanga</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>📊</span> Bosqichlar
                    <span class="text-sm font-normal text-gray-500">({{ levels?.length || 10 }} ta)</span>
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                    <div v-for="level in levels" :key="level.number"
                         @click="playLevel(level)"
                         :class="[
                             'relative rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer group shadow-xl',
                             level.unlocked
                                 ? 'hover:scale-105 hover:shadow-2xl hover:-translate-y-1'
                                 : 'cursor-not-allowed'
                         ]">
                        <!-- Level Card Background with unique gradient -->
                        <div :class="['absolute inset-0 bg-gradient-to-br', getGradientClass(level)]"></div>

                        <!-- Shine effect on hover -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                        <!-- Decorative pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-black rounded-full translate-y-1/2 -translate-x-1/2"></div>
                        </div>

                        <!-- Lock Overlay -->
                        <div v-if="!level.unlocked" class="absolute inset-0 bg-gray-900/70 backdrop-blur-[2px] z-20 flex items-center justify-center">
                            <div class="text-center px-4">
                                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2 shadow-xl backdrop-blur-sm">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-white text-sm font-bold drop-shadow-lg">
                                    {{ level.unlock_requirement?.stars || 1 }} ⭐ kerak
                                </p>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-10 p-4 h-full flex flex-col min-h-[220px]">
                            <!-- Header with Icon and CEFR Badge -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-12 h-12 bg-white/25 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-2xl filter drop-shadow-md">{{ getLevelIcon(level) }}</span>
                                </div>
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-white text-xs font-bold shadow-lg',
                                    getCefrBadgeClass(level.cefr_level)
                                ]">
                                    {{ level.cefr_level }}
                                </span>
                            </div>

                            <!-- Level Number & Name -->
                            <div class="mb-2">
                                <h3 class="text-xl font-black text-white drop-shadow-lg">
                                    {{ level.number }}-daraja
                                </h3>
                                <p class="text-white/90 text-sm font-medium drop-shadow-md line-clamp-1">
                                    {{ level.name_uz }}
                                </p>
                            </div>

                            <!-- Spacer -->
                            <div class="flex-1"></div>

                            <!-- Stars -->
                            <div class="flex items-center gap-0.5 mb-3">
                                <template v-for="i in 3" :key="i">
                                    <svg v-if="i <= (level.best_stars || 0)"
                                         class="w-6 h-6 text-yellow-300 drop-shadow-lg"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <svg v-else
                                         class="w-6 h-6 text-white/40"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </template>
                            </div>

                            <!-- Level Info Pills -->
                            <div class="flex items-center gap-2">
                                <span class="flex items-center gap-1.5 bg-white/25 backdrop-blur-sm px-3 py-1.5 rounded-lg text-white text-sm font-semibold shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ level.sentences_count }}
                                </span>
                                <span class="flex items-center gap-1.5 bg-white/25 backdrop-blur-sm px-3 py-1.5 rounded-lg text-white text-sm font-semibold shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ getTimeLimit(level) }}s
                                </span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed" class="absolute top-3 left-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-lg border-2 border-white">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎯</span> Qanday O'ynaladi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl border border-rose-200/50 dark:border-rose-800/50">
                        <div class="w-10 h-10 bg-rose-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">1</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Rejimni tanlang</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Xatoni toping, tuzating yoki qayta yozing</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl border border-orange-200/50 dark:border-orange-800/50">
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">2</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Gapni o'qing</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Diqqat bilan xatoni aniqlang</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl border border-red-200/50 dark:border-red-800/50">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">3</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Xatoni tuzating</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">To'g'ri javobni tanlang yoki yozing</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-br from-pink-50 to-fuchsia-50 dark:from-pink-900/20 dark:to-fuchsia-900/20 rounded-xl border border-pink-200/50 dark:border-pink-800/50">
                        <div class="w-10 h-10 bg-pink-500 rounded-lg flex items-center justify-center text-white font-bold shadow-md">4</div>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Yutuqlar yig'ing</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Ball, XP va tangalar oling</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-2xl p-5 border border-rose-200 dark:border-rose-800">
                <h3 class="font-bold text-rose-900 dark:text-rose-300 mb-3 flex items-center gap-2">
                    <span>💡</span> Foydali Maslahatlar
                </h3>
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">⚡</span>
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">Tezlik muhim</p>
                            <p class="text-gray-700 dark:text-gray-300 text-xs">Tez javob berish uchun vaqt bonusi olasiz!</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">🔥</span>
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">Streak saqlang</p>
                            <p class="text-gray-700 dark:text-gray-300 text-xs">Ketma-ket to'g'ri javoblar multiplikatorni oshiradi!</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">✍️</span>
                        <div>
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1">Qayta Yozing</p>
                            <p class="text-gray-700 dark:text-gray-300 text-xs">Bu rejim eng ko'p XP va ball beradi!</p>
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
                        <div class="bg-gradient-to-r from-rose-600 to-pink-600 p-5 rounded-t-2xl">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">🔍</span>
                                <div>
                                    <h2 class="text-xl font-black text-white">Xato Ovchisi</h2>
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
                                    Gaplardagi grammatika, imlo va tinish belgisi xatolarini toping va tuzating!
                                </p>
                            </div>

                            <!-- Game Modes -->
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                    <span>🎮</span> O'yin Rejimlari
                                </h3>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-3 bg-rose-50 dark:bg-rose-900/20 rounded-xl p-3">
                                        <div class="w-10 h-10 bg-rose-500 rounded-xl flex items-center justify-center text-xl shrink-0 shadow-md">🎯</div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 dark:text-white text-sm">Xatoni Toping</h4>
                                            <p class="text-gray-600 dark:text-gray-400 text-xs">Gapdagi xato so'zni topib, ustiga bosing</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl p-3">
                                        <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center text-xl shrink-0 shadow-md">🔧</div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 dark:text-white text-sm">Xatoni Tuzating</h4>
                                            <p class="text-gray-600 dark:text-gray-400 text-xs">4 ta variantdan to'g'risini tanlang</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 rounded-xl p-3">
                                        <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-xl shrink-0 shadow-md">✍️</div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 dark:text-white text-sm">Qayta Yozing</h4>
                                            <p class="text-gray-600 dark:text-gray-400 text-xs">To'g'rilangan gapni yozing. Eng ko'p XP!</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Powerups -->
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                                <h3 class="font-bold text-purple-800 dark:text-purple-400 mb-2 flex items-center gap-2 text-sm">
                                    <span>🔮</span> Maxsus Kuchlar
                                </h3>
                                <p class="text-purple-700 dark:text-purple-300 text-sm">
                                    Qiyin holatlarda yordam olish uchun: Xatoni yoritish, Xato turi ko'rsatmasi, O'tkazib yuborish va Qo'shimcha vaqt.
                                </p>
                            </div>

                            <!-- Start Button -->
                            <button
                                @click="showTutorial = false"
                                class="w-full py-3 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                            >
                                Tushundim! 🚀
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Achievements Modal -->
            <Teleport to="body">
                <div
                    v-if="showAchievements"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                    @click.self="showAchievements = false"
                >
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden animate-scale-in">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-600 p-5">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold text-white flex items-center gap-2 drop-shadow-md">
                                    <span class="text-2xl">🏆</span> Yutuqlar
                                </h2>
                                <button @click="showAchievements = false" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-white/90 text-sm mt-1">{{ earnedAchievements.length }}/{{ achievements?.length || 0 }} yutuq ochilgan</p>
                        </div>

                        <!-- Achievements List -->
                        <div class="p-4 max-h-[60vh] overflow-y-auto space-y-3">
                            <div v-for="achievement in achievements" :key="achievement.id"
                                 :class="[
                                     'flex items-center gap-4 p-4 rounded-xl transition-all',
                                     isAchievementUnlocked(achievement.id)
                                         ? 'bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/30 dark:to-amber-900/30 border-2 border-yellow-400'
                                         : 'bg-gray-50 dark:bg-gray-700/50 border-2 border-transparent opacity-70'
                                 ]">
                                <!-- Icon -->
                                <div :class="[
                                    'w-14 h-14 rounded-xl flex items-center justify-center text-2xl shrink-0',
                                    isAchievementUnlocked(achievement.id)
                                        ? 'bg-gradient-to-br from-yellow-400 to-amber-500 shadow-lg'
                                        : 'bg-gray-200 dark:bg-gray-600'
                                ]">
                                    {{ achievement.icon }}
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-800 dark:text-white">{{ achievement.name_uz }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{{ achievement.description_uz }}</div>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">+{{ achievement.xp_reward }} XP</span>
                                        <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">+{{ achievement.coin_reward }} 🪙</span>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div v-if="isAchievementUnlocked(achievement.id)" class="shrink-0">
                                    <span class="text-2xl">✅</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="showAchievements = false"
                                    class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity shadow-lg">
                                Yopish
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
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

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
