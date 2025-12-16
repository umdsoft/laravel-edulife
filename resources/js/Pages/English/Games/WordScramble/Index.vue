<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: Array,
    config: Object,
    userStats: Object,
    categories: Array,
    achievements: Array,
    userAchievements: Array,
})

const showTutorial = ref(false)
const showAchievements = ref(false)
const selectedLevel = ref(null)

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

const hints = computed(() => {
    return Object.values(props.config?.hint_system?.hints || {})
})

function getLevelIcon(level) {
    return level.icon || '🌱'
}

function getGradientClass(level) {
    return level.color || 'from-purple-500 to-pink-600'
}

function playLevel(level) {
    if (!level.unlocked) return
    router.visit(`/student/english/games/word-scramble/play/${level.number}`)
}

function getHintIcon(hint) {
    const icons = {
        'definition': '📖',
        'translation': '🌐',
        'first_letter': '🔤',
        'reveal_letter': '✨',
        'image': '🖼️',
    }
    return icons[hint.id] || '💡'
}

function getAchievementIcon(achievement) {
    return achievement.icon || '🏆'
}

// Level gradient colors array (cycling through colors)
const levelColors = [
    'from-blue-500 to-indigo-600',
    'from-purple-500 to-pink-600',
    'from-pink-500 to-rose-600',
    'from-orange-500 to-red-600',
    'from-yellow-500 to-orange-600',
    'from-green-500 to-teal-600',
    'from-teal-500 to-cyan-600',
    'from-cyan-500 to-blue-600',
    'from-indigo-500 to-purple-600',
    'from-violet-500 to-fuchsia-600',
]

function getLevelColor(index) {
    return levelColors[index % levelColors.length]
}
</script>

<template>
    <Head title="So'z Jumboq - Word Scramble" />

    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-orange-600 via-amber-600 to-yellow-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-orange-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🔀</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">🔤</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">🎲</div>
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
                                <div class="absolute inset-0 bg-orange-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-orange-400 via-amber-400 to-yellow-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-orange-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🔀
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    {{ config?.game_name_uz || "So'z Jumboq" }}
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    {{ config?.description_uz || "Aralashgan harflarni to'g'ri tartibda joylashtiring" }}
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards & Action Buttons -->
                        <div class="flex gap-3 flex-wrap">
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

                            <!-- Achievements Button -->
                            <button
                                @click="showAchievements = true"
                                class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 hover:bg-white/25 hover:border-white/40 transition-all shadow-xl group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30 group-hover:scale-110 transition-transform">
                                        <span class="text-2xl">🏆</span>
                                    </div>
                                    <span class="text-white font-semibold text-sm hidden md:block">Yutuqlar</span>
                                </div>
                            </button>

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

                    <!-- Stats Info Cards -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    ✓
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ completedLevels }}/{{ totalLevels }}</p>
                                    <p class="text-white/60 text-xs">Bosqichlar</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🔤
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ userStats?.total_words_completed || 0 }}</p>
                                    <p class="text-white/60 text-xs">So'zlar</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    🔥
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ userStats?.best_streak || 0 }}</p>
                                    <p class="text-white/60 text-xs">Eng yaxshi</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-violet-600 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110">
                                    📊
                                </div>
                                <div>
                                    <p class="text-white font-bold text-lg">{{ progressPercentage }}%</p>
                                    <p class="text-white/60 text-xs">Taraqqiyot</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hints Section - White Card with Dark Mode -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">💡</span>
                            <span class="font-semibold text-gray-800 dark:text-white">Maslahatlar:</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div v-for="hint in hints" :key="hint.id"
                                 class="group relative flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">{{ getHintIcon(hint) }}</span>
                                <div class="hidden sm:block">
                                    <div class="font-medium text-gray-800 dark:text-white text-sm leading-tight">{{ hint.name_uz }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ hint.cost_coins > 0 ? hint.cost_coins + ' tanga' : 'Bepul' }}</div>
                                </div>
                                <!-- Tooltip for mobile -->
                                <div class="sm:hidden absolute -top-12 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                    {{ hint.name_uz }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> Bosqichlar
                    <span class="text-sm font-normal text-gray-500">({{ totalLevels }} ta)</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <div v-for="(level, index) in levels" :key="level.number"
                         @click="playLevel(level)"
                         :class="[
                             'relative rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer group min-h-[220px]',
                             level.unlocked
                                 ? 'hover:scale-105 hover:shadow-2xl'
                                 : 'cursor-not-allowed'
                         ]">
                        <!-- Level Card Background with cycling colors -->
                        <div :class="[
                            'absolute inset-0 bg-gradient-to-br',
                            getLevelColor(index)
                        ]"></div>

                        <!-- Lock Overlay -->
                        <div v-if="!level.unlocked" class="absolute inset-0 bg-black/50 backdrop-blur-[2px] z-20 flex items-center justify-center">
                            <div class="text-center">
                                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-white text-sm font-medium">{{ level.unlock_requirement?.stars || 1 }} yulduz kerak</p>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="relative z-10 p-5 h-full flex flex-col">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-3xl drop-shadow-lg">{{ getLevelIcon(level) }}</span>
                                <span class="bg-white/25 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-bold shadow-lg">
                                    {{ level.cefr_level }}
                                </span>
                            </div>

                            <!-- Title & Description -->
                            <h3 class="text-lg font-bold text-white drop-shadow-md mb-1">{{ level.name_uz }}</h3>
                            <p class="text-white/90 text-sm mb-3 drop-shadow-sm line-clamp-2">{{ level.description_uz }}</p>

                            <!-- Spacer -->
                            <div class="flex-1"></div>

                            <!-- Stars -->
                            <div class="flex items-center gap-1 mb-2">
                                <span v-for="i in 3" :key="i"
                                      :class="i <= (level.best_stars || 0) ? 'text-yellow-300 drop-shadow-lg' : 'text-white/40'"
                                      class="text-xl">⭐</span>
                            </div>

                            <!-- Level Info -->
                            <div class="flex items-center justify-between text-white/90 text-sm font-medium">
                                <span class="bg-white/20 px-2 py-1 rounded-lg">{{ level.words_count }} so'z</span>
                                <span class="bg-white/20 px-2 py-1 rounded-lg">{{ level.word_length_min }}-{{ level.word_length_max }} harf</span>
                            </div>

                            <!-- Completed Badge -->
                            <div v-if="level.completed" class="absolute top-3 right-3 z-10">
                                <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold shadow-lg">✓</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section - White Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg mb-8 border border-gray-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎮</span> Qanday O'ynaladi
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">1️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Harflarni ko'ring</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Aralashgan harflar ekranda ko'rsatiladi</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">2️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Sudrab joylashtiring</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Harflarni to'g'ri tartibda joylashtiring</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">3️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Maslahatlar</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Qiyinchilikda maslahatlardan foydalaning</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl">
                        <span class="text-2xl">4️⃣</span>
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">Ball yig'ing</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Tezroq javob bering va streak hosil qiling</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-6 border border-amber-200 dark:border-amber-800">
                <h2 class="text-xl font-bold text-amber-900 dark:text-amber-300 mb-4 flex items-center gap-2">
                    <span>💡</span> Foydali Maslahatlar
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-4">
                        <span class="text-2xl">⚡</span>
                        <div>
                            <div class="font-semibold text-amber-900 dark:text-amber-300 mb-1">Tezroq javob bering</div>
                            <p class="text-sm text-amber-800 dark:text-amber-400">Tez javob berish uchun qo'shimcha ball olasiz!</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-4">
                        <span class="text-2xl">🔥</span>
                        <div>
                            <div class="font-semibold text-amber-900 dark:text-amber-300 mb-1">Streak saqlang</div>
                            <p class="text-sm text-amber-800 dark:text-amber-400">Ketma-ket to'g'ri javoblar ball multiplikatorini oshiradi!</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-4">
                        <span class="text-2xl">📏</span>
                        <div>
                            <div class="font-semibold text-amber-900 dark:text-amber-300 mb-1">Uzun so'zlar</div>
                            <p class="text-sm text-amber-800 dark:text-amber-400">Uzun so'zlar uchun ko'proq ball beriladi!</p>
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
                        <div class="bg-gradient-to-r from-orange-600 to-amber-600 p-5 rounded-t-2xl">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">🔀</span>
                                <div>
                                    <h2 class="text-xl font-black text-white">{{ config?.game_name_uz || "So'z Jumboq" }}</h2>
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
                                    Aralashgan harflardan to'g'ri so'z tuzish! Maslahatlardan foydalanib, 3 yulduz to'plang.
                                </p>
                            </div>

                            <!-- How to Play Steps -->
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                    <span>📋</span> O'ynash Qadamlari
                                </h3>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Bosqichni tanlang</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Harflarni bosing yoki sudrab joylashtiring</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Maslahatlardan foydalaning (ta'rif, tarjima va h.k.)</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-orange-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">4</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Tekshirish tugmasini bosing</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Hints -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                                <h3 class="font-bold text-blue-800 dark:text-blue-400 mb-2 flex items-center gap-2 text-sm">
                                    <span>💡</span> Maslahatlar
                                </h3>
                                <div class="space-y-1.5 text-xs">
                                    <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span>📖</span>
                                        <span class="text-blue-700 dark:text-blue-300">Ta'rif - so'z ma'nosini ko'rish</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span>🌐</span>
                                        <span class="text-blue-700 dark:text-blue-300">Tarjima - o'zbek tilidagi ma'no</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span>🔤</span>
                                        <span class="text-blue-700 dark:text-blue-300">Birinchi harf - boshlang'ich harf</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span>✨</span>
                                        <span class="text-blue-700 dark:text-blue-300">Harf ochish - tasodifiy harf</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Scoring -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                                <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                    <span>⭐</span> Yulduz Tizimi
                                </h3>
                                <div class="grid grid-cols-1 gap-2 text-xs">
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-yellow-700 dark:text-yellow-300">1 yulduz:</span>
                                        <span class="font-bold text-yellow-600">Minimum so'zlarni yech</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-yellow-700 dark:text-yellow-300">2 yulduz:</span>
                                        <span class="font-bold text-yellow-600">70%+ aniqlik</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-yellow-700 dark:text-yellow-300">3 yulduz:</span>
                                        <span class="font-bold text-yellow-600">90%+ aniqlik</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Start Button -->
                            <button
                                @click="showTutorial = false"
                                class="w-full py-3 bg-gradient-to-r from-orange-600 to-amber-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                            >
                                Tushundim! 🚀
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Achievements Modal -->
            <Teleport to="body">
                <Transition name="modal">
                    <div v-if="showAchievements" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showAchievements = false">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden animate-scale-in">
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-orange-600 to-amber-600 p-5">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                        <span class="text-2xl">🏆</span> Yutuqlar
                                    </h2>
                                    <button @click="showAchievements = false" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-white/80 text-sm mt-1">{{ earnedAchievements.length }}/{{ achievements?.length || 0 }} yutuq ochilgan</p>
                            </div>

                            <!-- Achievements List -->
                            <div class="p-4 max-h-[60vh] overflow-y-auto space-y-3">
                                <div v-for="achievement in achievements" :key="achievement.id"
                                     :class="[
                                         'flex items-center gap-4 p-4 rounded-xl transition-all',
                                         userAchievements?.includes(achievement.id)
                                             ? 'bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/30 dark:to-amber-900/30 border-2 border-yellow-400'
                                             : 'bg-gray-50 dark:bg-gray-700/50 border-2 border-transparent opacity-70'
                                     ]">
                                    <!-- Icon -->
                                    <div :class="[
                                        'w-14 h-14 rounded-xl flex items-center justify-center text-2xl shrink-0',
                                        userAchievements?.includes(achievement.id)
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
                                            <span class="text-xs font-medium text-orange-600 dark:text-orange-400">+{{ achievement.xp_reward }} XP</span>
                                            <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">+{{ achievement.coin_reward }} 🪙</span>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div v-if="userAchievements?.includes(achievement.id)" class="shrink-0">
                                        <span class="text-2xl">✅</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                                <button @click="showAchievements = false"
                                        class="w-full py-3 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity">
                                    Yopish
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
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

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.9) translateY(20px);
}
</style>
