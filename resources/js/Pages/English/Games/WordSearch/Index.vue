<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { StarIcon, LockClosedIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    levels: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    config: { type: Object, default: () => ({}) },
    achievements: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    gameModes: { type: Array, default: () => [] },
    totalStars: { type: Number, default: 0 },
    wordFinderRanks: { type: Array, default: () => [] }
})

const showTutorial = ref(false)

const currentRank = computed(() => {
    if (!props.wordFinderRanks.length) return null
    let current = props.wordFinderRanks[0]
    for (const rank of props.wordFinderRanks) {
        if ((props.stats.total_xp || 0) >= rank.xp_required) current = rank
    }
    return current
})

const maxStars = computed(() => props.levels?.length * 3 || 30)

const tips = [
    { icon: '🔍', text: "So'zlar gorizontal, vertikal va diagonal yo'nalishda bo'lishi mumkin!" },
    { icon: '⭐', text: "3 ta yulduz olish uchun barcha so'zlarni toping" },
    { icon: '🎯', text: "Qidiruv paytida to'g'ri yo'nalishni tanlang" },
]

const getLevelGradient = (index) => {
    const colors = [
        'from-purple-500 to-indigo-600',
        'from-blue-500 to-cyan-600',
        'from-green-500 to-emerald-600',
        'from-yellow-500 to-orange-600',
        'from-red-500 to-pink-600',
        'from-pink-500 to-purple-600',
        'from-indigo-500 to-blue-600',
        'from-teal-500 to-green-600',
    ]
    return colors[index % colors.length]
}
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Header -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-lime-600 via-green-600 to-emerald-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🔍</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">📖</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">🎯</div>
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
                                <div class="absolute inset-0 bg-lime-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-lime-400 via-green-400 to-emerald-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-green-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🔍
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Word <span class="text-transparent bg-clip-text bg-gradient-to-r from-lime-300 to-emerald-300">Search</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    So'zlarni <span class="text-lime-300 font-bold">to'rdan</span> toping!
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

                    <!-- Stats Grid -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🧩
                                </div>
                                <div>
                                    <p class="text-2xl font-black text-white">{{ stats.puzzles_completed || 0 }}</p>
                                    <p class="text-white/60 text-xs">Boshqotirmalar</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    📝
                                </div>
                                <div>
                                    <p class="text-2xl font-black text-white">{{ stats.words_found || 0 }}</p>
                                    <p class="text-white/60 text-xs">So'zlar topildi</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🔥
                                </div>
                                <div>
                                    <p class="text-2xl font-black text-white">{{ stats.best_streak || 0 }}</p>
                                    <p class="text-white/60 text-xs">Eng yaxshi streak</p>
                                </div>
                            </div>
                        </div>
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🏆
                                </div>
                                <div>
                                    <p class="text-2xl font-black text-white">{{ currentRank?.name || 'Yangi' }}</p>
                                    <p class="text-white/60 text-xs">Daraja</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Section -->
            <div v-if="categories && categories.length > 0" class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📚</span> Kategoriyalar
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="bg-gradient-to-br rounded-xl p-4 text-center transition-all hover:scale-105 cursor-pointer"
                            :class="category.color"
                        >
                            <div class="text-2xl mb-2">{{ category.icon }}</div>
                            <div class="text-white font-semibold text-sm">{{ category.name }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> Darajalar
                    <span class="text-sm font-normal text-gray-500">({{ levels?.length || 0 }} ta)</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                    <div
                        v-for="(level, index) in levels"
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
                            :href="route('student.english.games.word-search.play', { level: level.level_number })"
                            class="block p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center relative bg-gradient-to-br"
                            :class="getLevelGradient(index)"
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

                            <!-- Grid Size & Word Count -->
                            <div class="flex items-center gap-2 mb-2">
                                <span class="bg-white/20 px-2 py-0.5 rounded text-white/90 text-xs font-medium">
                                    {{ level.grid_size }}
                                </span>
                                <span class="text-white/70 text-xs">📝 {{ level.word_count }} so'z</span>
                            </div>

                            <!-- Stars -->
                            <div class="flex gap-0.5 mb-1">
                                <StarIcon
                                    v-for="i in 3"
                                    :key="i"
                                    class="w-5 h-5"
                                    :class="[
                                        i <= level.stars ? 'text-yellow-400' : 'text-white/30'
                                    ]"
                                />
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

                            <!-- Level Name (faded) -->
                            <div class="text-gray-500 dark:text-gray-400 text-sm text-center mb-2">
                                {{ level.name }}
                            </div>

                            <!-- Empty Stars -->
                            <div class="flex gap-0.5 mt-2">
                                <StarIcon v-for="i in 3" :key="i" class="w-5 h-5 text-gray-300 dark:text-gray-600" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ranks Section -->
            <div v-if="wordFinderRanks && wordFinderRanks.length > 0" class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏅</span> Darajalar
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex overflow-x-auto gap-4 pb-2">
                        <div
                            v-for="rank in wordFinderRanks"
                            :key="rank.id"
                            class="flex-shrink-0 text-center p-4 rounded-xl min-w-[120px] transition-all hover:scale-105"
                            :class="currentRank?.id === rank.id
                                ? 'bg-gradient-to-br from-green-500 to-emerald-600 ring-2 ring-green-400 shadow-lg'
                                : (stats.total_xp || 0) >= rank.xp_required
                                    ? 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-300 dark:border-green-700'
                                    : 'bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600'"
                        >
                            <div class="text-2xl mb-1">{{ rank.icon }}</div>
                            <div class="font-medium text-sm" :class="currentRank?.id === rank.id ? 'text-white' : 'text-gray-900 dark:text-white'">
                                {{ rank.name }}
                            </div>
                            <div class="text-xs" :class="currentRank?.id === rank.id ? 'text-white/80' : 'text-gray-600 dark:text-gray-400'">
                                {{ rank.xp_required }} XP
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-lime-50 to-green-50 dark:from-lime-900/20 dark:to-green-900/20 rounded-2xl p-5 border border-lime-200 dark:border-lime-800">
                <h3 class="font-bold text-lime-900 dark:text-lime-300 mb-3 flex items-center gap-2">
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
                    <div class="bg-gradient-to-r from-lime-600 to-green-600 p-5 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🔍</span>
                            <div>
                                <h2 class="text-xl font-black text-white">Word Search</h2>
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
                                Berilgan to'rdan <strong>barcha so'zlarni</strong> toping! So'zlar gorizontal, vertikal yoki diagonal yo'nalishda bo'lishi mumkin.
                            </p>
                        </div>

                        <!-- How to Play Steps -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>📋</span> O'ynash Qadamlari
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-lime-50 dark:bg-lime-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-lime-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Qidirilayotgan so'zlar ro'yxatini ko'ring</p>
                                </div>
                                <div class="flex items-center gap-3 bg-lime-50 dark:bg-lime-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-lime-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">To'rdan so'zni toping</p>
                                </div>
                                <div class="flex items-center gap-3 bg-lime-50 dark:bg-lime-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-lime-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Harflarni tanlab so'zni ajrating</p>
                                </div>
                                <div class="flex items-center gap-3 bg-lime-50 dark:bg-lime-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-lime-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">4</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Barcha so'zlarni topguncha davom eting</p>
                                </div>
                            </div>
                        </div>

                        <!-- Directions Info -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                            <h3 class="font-bold text-blue-800 dark:text-blue-400 mb-2 flex items-center gap-2 text-sm">
                                <span>🧭</span> So'z Yo'nalishlari
                            </h3>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-lg">→</span>
                                    <span class="text-gray-700 dark:text-gray-300">Gorizontal</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-lg">↓</span>
                                    <span class="text-gray-700 dark:text-gray-300">Vertikal</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-lg">↘</span>
                                    <span class="text-gray-700 dark:text-gray-300">Diagonal</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-lg">↙</span>
                                    <span class="text-gray-700 dark:text-gray-300">Teskari</span>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                            <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                <span>⭐</span> Yulduz Tizimi
                            </h3>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                    <span class="font-bold text-gray-600 dark:text-gray-400">Ba'zi so'zlar</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">2 yulduz:</span>
                                    <span class="font-bold text-gray-600 dark:text-gray-400">Ko'p so'zlar</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                    <span class="font-bold text-green-600">Barcha so'zlar</span>
                                </div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button
                            @click="showTutorial = false"
                            class="w-full py-3 bg-gradient-to-r from-lime-600 to-green-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
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
