<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps({
    levels: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    config: { type: Object, default: () => ({}) },
    achievements: { type: Array, default: () => [] },
    gameModes: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    totalStars: { type: Number, default: 0 },
    typingMasterRanks: { type: Array, default: () => [] }
})

const showTutorial = ref(false)
const selectedMode = ref('classic')
const selectedCategory = ref('all')

const filteredLevels = computed(() => {
    if (selectedCategory.value === 'all') return props.levels
    return props.levels.filter(level => level.category === selectedCategory.value)
})

const maxStars = computed(() => props.levels?.length * 3 || 30)

const currentRank = computed(() => {
    if (!props.typingMasterRanks.length) return null
    let current = props.typingMasterRanks[0]
    for (const rank of props.typingMasterRanks) {
        if ((props.stats.total_xp || 0) >= rank.xp_required) {
            current = rank
        }
    }
    return current
})

const getLevelGradient = (index) => {
    const gradients = [
        'from-rose-500 to-pink-600',
        'from-orange-500 to-red-600',
        'from-amber-500 to-orange-600',
        'from-yellow-500 to-amber-600',
        'from-lime-500 to-green-600',
        'from-emerald-500 to-teal-600',
        'from-cyan-500 to-blue-600',
        'from-blue-500 to-indigo-600',
        'from-violet-500 to-purple-600',
        'from-purple-500 to-pink-600',
    ]
    return gradients[index % gradients.length]
}

const defaultModes = [
    { id: 'classic', icon: '⌨️', name: 'Klassik', description: "Matnni ko'chirib yozing" },
    { id: 'words', icon: '🔤', name: "So'zlar", description: "So'zlarni tez yozing" },
    { id: 'sentences', icon: '📝', name: 'Gaplar', description: 'Gaplarni yozing' },
    { id: 'code', icon: '💻', name: 'Kod', description: 'Dasturlash kodlarini yozing' },
    { id: 'quotes', icon: '💬', name: 'Iqtiboslar', description: 'Mashhur iqtiboslarni yozing' },
]

const modes = computed(() => props.gameModes.length ? props.gameModes : defaultModes)

const tips = [
    { icon: '⚡', text: "Tez va to'g'ri yozsangiz, WPM yuqori bo'ladi!" },
    { icon: '🎯', text: "3 ta yulduz olish uchun 95%+ aniqlik kerak" },
    { icon: '🏆', text: "Har bir darajada eng yaxshi natijani yengib chiqing" },
]
</script>

<template>
    <Head title="Typing Race - Tez Yozish" />

    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Header -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-rose-600 via-orange-600 to-amber-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">⌨️</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">🏎️</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">⚡</div>
                    <div class="absolute bottom-8 right-1/4 text-4xl opacity-15 animate-float-delayed">🎯</div>
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
                                <div class="absolute inset-0 bg-orange-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-orange-400 via-red-500 to-rose-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-red-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🏎️
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Typing <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">Race</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    <span class="text-yellow-300 font-bold">Tez</span> va <span class="text-green-400 font-bold">aniq</span> yozing!
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
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

                            <!-- Best WPM Card -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                                        <span class="text-2xl">⚡</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ stats.best_wpm || 0 }}</div>
                                        <div class="text-white/60 text-xs font-medium">WPM rekord</div>
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
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/10">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🎮</span>
                                <div>
                                    <div class="text-xl font-bold text-white">{{ stats.games_played || 0 }}</div>
                                    <div class="text-white/60 text-xs">O'yinlar</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/10">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📊</span>
                                <div>
                                    <div class="text-xl font-bold text-white">{{ stats.average_wpm || 0 }}</div>
                                    <div class="text-white/60 text-xs">O'rtacha WPM</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/10">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🎯</span>
                                <div>
                                    <div class="text-xl font-bold text-white">{{ stats.best_accuracy || 0 }}%</div>
                                    <div class="text-white/60 text-xs">Aniqlik</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/10">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ currentRank?.icon || '🏅' }}</span>
                                <div>
                                    <div class="text-xl font-bold text-white">{{ currentRank?.name || 'Yangi' }}</div>
                                    <div class="text-white/60 text-xs">Daraja</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Modes Cards -->
                    <div class="mt-6 grid grid-cols-2 lg:grid-cols-5 gap-3">
                        <div
                            v-for="(mode, index) in modes"
                            :key="mode.id"
                            @click="selectedMode = mode.id"
                            class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02] cursor-pointer"
                            :class="[
                                selectedMode === mode.id ? 'ring-2 ring-yellow-400 bg-white/20' : '',
                                index === 0 ? 'hover:shadow-lg hover:shadow-rose-500/20' : '',
                                index === 1 ? 'hover:shadow-lg hover:shadow-orange-500/20' : '',
                                index === 2 ? 'hover:shadow-lg hover:shadow-amber-500/20' : '',
                                index === 3 ? 'hover:shadow-lg hover:shadow-yellow-500/20' : '',
                                index === 4 ? 'hover:shadow-lg hover:shadow-lime-500/20' : '',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110"
                                    :class="[
                                        index === 0 ? 'bg-gradient-to-br from-rose-400 to-rose-600' : '',
                                        index === 1 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : '',
                                        index === 2 ? 'bg-gradient-to-br from-amber-400 to-amber-600' : '',
                                        index === 3 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : '',
                                        index === 4 ? 'bg-gradient-to-br from-lime-400 to-lime-600' : '',
                                    ]"
                                >
                                    {{ mode.icon }}
                                </div>
                                <div>
                                    <p class="text-white font-bold text-sm">{{ mode.name }}</p>
                                    <p class="text-white/60 text-xs hidden md:block">{{ mode.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Filter -->
            <div class="mb-6" v-if="categories.length">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span>📂</span> Kategoriyalar
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="selectedCategory = 'all'"
                        class="px-4 py-2 rounded-xl font-medium transition-all"
                        :class="selectedCategory === 'all'
                            ? 'bg-gradient-to-r from-rose-500 to-orange-500 text-white shadow-lg'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    >
                        Barchasi
                    </button>
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectedCategory = category.id"
                        class="px-4 py-2 rounded-xl font-medium transition-all"
                        :class="selectedCategory === category.id
                            ? 'bg-gradient-to-r from-rose-500 to-orange-500 text-white shadow-lg'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    >
                        {{ category.icon }} {{ category.name }}
                    </button>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏁</span> Poygalar
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
                            :href="route('student.english.games.typing-race.play', { level: level.level_number })"
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

                            <!-- Target WPM + Accuracy -->
                            <div class="flex items-center gap-2 mb-2">
                                <span class="bg-white/20 px-2 py-0.5 rounded text-white/90 text-xs font-medium">
                                    {{ level.target_wpm }} WPM
                                </span>
                                <span class="text-white/70 text-xs">{{ level.target_accuracy }}%</span>
                            </div>

                            <!-- Stars -->
                            <div class="flex gap-0.5 mb-1">
                                <span
                                    v-for="i in 3"
                                    :key="i"
                                    :class="[
                                        'text-xl md:text-2xl',
                                        i <= (level.stars || 0) ? 'text-yellow-400' : 'text-white/30'
                                    ]"
                                >⭐</span>
                            </div>

                            <!-- Best WPM -->
                            <div v-if="level.best_wpm > 0" class="text-white/60 text-xs">
                                🏆 {{ level.best_wpm }} WPM
                            </div>
                        </Link>

                        <!-- Locked Level Card -->
                        <div
                            v-else
                            class="p-4 md:p-5 min-h-[180px] md:min-h-[200px] flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 border-2 border-gray-200 dark:border-gray-600 border-dashed"
                        >
                            <!-- Lock Icon -->
                            <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center mb-3">
                                <span class="text-2xl opacity-60">🔒</span>
                            </div>

                            <!-- Level Number (faded) -->
                            <div class="text-4xl font-black text-gray-400 dark:text-gray-500 mb-2">
                                {{ level.level_number }}
                            </div>

                            <!-- Unlock Requirement -->
                            <div class="bg-gray-200 dark:bg-gray-600 px-3 py-1 rounded-full">
                                <span class="text-gray-600 dark:text-gray-300 text-sm font-medium">
                                    {{ level.unlock_requirement || 'Oldingi darajani yuting' }}
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
            <div class="mb-8" v-if="achievements.length">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏆</span> Yutuqlar
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <div
                        v-for="achievement in achievements.slice(0, 6)"
                        :key="achievement.id"
                        class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700 transition-all hover:shadow-md"
                        :class="{ 'opacity-50': !achievement.unlocked }"
                    >
                        <div class="text-3xl mb-2">{{ achievement.icon }}</div>
                        <div class="text-gray-900 dark:text-white font-medium text-sm">{{ achievement.name }}</div>
                        <div class="text-gray-500 dark:text-gray-400 text-xs mt-1">{{ achievement.description }}</div>
                        <div v-if="achievement.unlocked" class="mt-2">
                            <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-1 rounded-full">
                                Ochildi!
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Typing Master Ranks -->
            <div class="mb-8" v-if="typingMasterRanks.length">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎖️</span> Yozish Ustasi Darajalari
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex overflow-x-auto gap-3 pb-2">
                        <div
                            v-for="rank in typingMasterRanks"
                            :key="rank.id"
                            class="flex-shrink-0 text-center p-3 rounded-xl min-w-[120px] transition-all"
                            :class="[
                                currentRank?.id === rank.id
                                    ? 'bg-gradient-to-br from-rose-500 to-orange-500 text-white shadow-lg'
                                    : (stats.total_xp || 0) >= rank.xp_required
                                        ? 'bg-green-100 dark:bg-green-900/30'
                                        : 'bg-gray-100 dark:bg-gray-700'
                            ]"
                        >
                            <div class="text-2xl mb-1">{{ rank.icon }}</div>
                            <div :class="currentRank?.id === rank.id ? 'text-white' : 'text-gray-900 dark:text-white'" class="font-medium text-sm">{{ rank.name }}</div>
                            <div :class="currentRank?.id === rank.id ? 'text-white/80' : 'text-gray-500 dark:text-gray-400'" class="text-xs">{{ rank.xp_required }} XP</div>
                            <div :class="currentRank?.id === rank.id ? 'text-white/80' : 'text-orange-500'" class="text-xs">{{ rank.wpm_required }}+ WPM</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-rose-50 to-orange-50 dark:from-rose-900/20 dark:to-orange-900/20 rounded-2xl p-5 border border-rose-200 dark:border-rose-800">
                <h3 class="font-bold text-rose-900 dark:text-rose-300 mb-3 flex items-center gap-2">
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
                    <!-- Close Button -->
                    <button
                        @click="showTutorial = false"
                        class="absolute -top-3 -right-3 w-10 h-10 bg-gray-800 dark:bg-gray-600 hover:bg-gray-700 rounded-full flex items-center justify-center text-white shadow-lg z-10 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-rose-600 to-orange-600 p-5 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🏎️</span>
                            <div>
                                <h2 class="text-xl font-black text-white">Typing Race</h2>
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
                                Matnni <strong>tez va aniq</strong> yozing! WPM (words per minute) va aniqlik darajasini oshiring.
                            </p>
                        </div>

                        <!-- How to Play Steps -->
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                <span>📋</span> O'ynash Qadamlari
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-rose-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Matnni diqqat bilan o'qing</p>
                                </div>
                                <div class="flex items-center gap-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-rose-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Matnni klaviaturada yozing</p>
                                </div>
                                <div class="flex items-center gap-3 bg-rose-50 dark:bg-rose-900/20 rounded-lg p-2.5">
                                    <div class="w-6 h-6 bg-rose-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                    <p class="text-gray-800 dark:text-gray-200 text-sm">Vaqt tugamasidan tugatishga harakat qiling</p>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                            <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                <span>⭐</span> Baholash Tizimi
                            </h3>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                    <span class="font-bold text-gray-600">Maqsad WPM</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">2 yulduz:</span>
                                    <span class="font-bold text-blue-600">85%+ aniqlik</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                    <span class="font-bold text-green-600">95%+ aniqlik</span>
                                </div>
                                <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                    <span class="text-gray-700 dark:text-gray-300">Bonus:</span>
                                    <span class="font-bold text-orange-600">Tez tugatish</span>
                                </div>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <button
                            @click="showTutorial = false"
                            class="w-full py-3 bg-gradient-to-r from-rose-600 to-orange-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                        >
                            Tushundim! 🏎️
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
