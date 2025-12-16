<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-amber-600 via-yellow-500 to-orange-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>
                </div>

                <!-- Glow Effects -->
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float" style="animation-delay: 2s;"></div>

                <!-- Floating Emojis -->
                <div class="absolute top-10 left-10 text-4xl animate-float opacity-40">📅</div>
                <div class="absolute top-20 right-20 text-3xl animate-float opacity-40" style="animation-delay: 1s;">🎯</div>
                <div class="absolute bottom-10 left-1/4 text-3xl animate-float opacity-40" style="animation-delay: 2s;">🏆</div>
                <div class="absolute bottom-20 right-1/3 text-4xl animate-float opacity-40" style="animation-delay: 3s;">⭐</div>

                <!-- Content -->
                <div class="relative z-10">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <Link
                                :href="route('student.english.games.index')"
                                class="p-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-2xl transition-all duration-300 hover:scale-110 group"
                            >
                                <ArrowLeftIcon class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" />
                            </Link>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="text-4xl drop-shadow-lg">📅</span>
                                    <h1 class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-yellow-100 to-white drop-shadow-lg">
                                        Daily Challenge Quiz
                                    </h1>
                                </div>
                                <p class="text-white/90 text-sm md:text-base font-medium ml-14 drop-shadow">Kundalik viktorina - har kuni yangi savolar!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards with Glassmorphism -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Current Streak -->
                        <div class="bg-white/20 backdrop-blur-md border border-white/30 rounded-2xl p-4 hover:bg-white/25 transition-all duration-300 hover:scale-105 group">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-3xl group-hover:scale-110 transition-transform">🔥</span>
                                <div class="text-3xl font-black text-white drop-shadow">{{ stats.current_streak || 0 }}</div>
                            </div>
                            <div class="text-white/90 font-semibold text-sm">Joriy Seriya</div>
                            <div class="text-white/70 text-xs">kunlik seriya</div>
                        </div>

                        <!-- Total Stars -->
                        <div class="bg-white/20 backdrop-blur-md border border-white/30 rounded-2xl p-4 hover:bg-white/25 transition-all duration-300 hover:scale-105 group">
                            <div class="flex items-center gap-3 mb-2">
                                <StarIcon class="w-8 h-8 text-yellow-200 drop-shadow group-hover:scale-110 transition-transform" />
                                <div class="text-3xl font-black text-white drop-shadow">{{ totalStars }}</div>
                            </div>
                            <div class="text-white/90 font-semibold text-sm">Jami Yulduz</div>
                            <div class="text-white/70 text-xs">toplangan</div>
                        </div>

                        <!-- Accuracy -->
                        <div class="bg-white/20 backdrop-blur-md border border-white/30 rounded-2xl p-4 hover:bg-white/25 transition-all duration-300 hover:scale-105 group">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-3xl group-hover:scale-110 transition-transform">🎯</span>
                                <div class="text-3xl font-black text-white drop-shadow">{{ stats.accuracy || 0 }}%</div>
                            </div>
                            <div class="text-white/90 font-semibold text-sm">Aniqlik</div>
                            <div class="text-white/70 text-xs">umumiy</div>
                        </div>

                        <!-- Current Rank -->
                        <div class="bg-white/20 backdrop-blur-md border border-white/30 rounded-2xl p-4 hover:bg-white/25 transition-all duration-300 hover:scale-105 group">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-3xl group-hover:scale-110 transition-transform">{{ currentRank?.icon || '🌟' }}</span>
                                <div class="text-2xl font-black text-white drop-shadow truncate">{{ currentRank?.name || 'Yangi' }}</div>
                            </div>
                            <div class="text-white/90 font-semibold text-sm">Daraja</div>
                            <div class="text-white/70 text-xs">joriy</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4">
                <!-- Daily Challenge Card -->
                <div class="mb-8">
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-3xl p-6 md:p-8 shadow-xl overflow-hidden transition-all duration-300"
                        :class="{ 'opacity-75': isDailyCompleted }"
                    >
                        <!-- Decorative Elements -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-yellow-200 to-orange-200 dark:from-yellow-900 dark:to-orange-900 rounded-full blur-3xl opacity-20 -mr-32 -mt-32"></div>

                        <div class="relative z-10">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="text-5xl">📅</span>
                                        <div>
                                            <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mb-1">
                                                Bugungi Viktorina
                                            </h2>
                                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                                {{ new Date().toLocaleDateString('uz-UZ', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                                            </p>
                                        </div>
                                        <span v-if="isDailyCompleted" class="ml-auto md:ml-0 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-3 py-1.5 rounded-full text-sm font-bold flex items-center gap-1">
                                            <span>✓</span>
                                            <span>Bajarildi</span>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl px-4 py-2 w-fit">
                                        <span class="text-2xl">🎁</span>
                                        <span class="text-yellow-700 dark:text-yellow-400 font-bold">1.5x bonus ball!</span>
                                    </div>
                                </div>

                                <div class="flex-shrink-0">
                                    <Link
                                        v-if="!isDailyCompleted"
                                        :href="route('student.english.games.daily-challenge.daily')"
                                        class="group relative inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-yellow-500 via-orange-500 to-orange-600 text-white font-black rounded-2xl hover:from-yellow-600 hover:via-orange-600 hover:to-orange-700 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                                    >
                                        <span class="text-lg">Boshlash</span>
                                        <span class="text-xl group-hover:translate-x-1 transition-transform">→</span>
                                    </Link>
                                    <div v-else class="text-center bg-green-50 dark:bg-green-900/20 rounded-2xl px-8 py-4">
                                        <div class="text-5xl mb-2">🏆</div>
                                        <div class="text-green-600 dark:text-green-400 font-black text-lg">Yakunlandi!</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Stats Grid -->
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">Statistika</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                            <div class="text-4xl font-black text-gray-900 dark:text-white mb-1">{{ stats.challenges_completed || 0 }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">Viktorinalar</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                            <div class="text-4xl font-black text-green-500 dark:text-green-400 mb-1">{{ stats.accuracy || 0 }}%</div>
                            <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">Aniqlik</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                            <div class="text-4xl font-black text-orange-500 dark:text-orange-400 mb-1">{{ stats.current_streak || 0 }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">Joriy seriya</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                            <div class="text-4xl font-black text-yellow-500 dark:text-yellow-400 mb-1">{{ stats.perfect_scores || 0 }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">Mukammal</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                            <div class="text-3xl font-black text-pink-500 dark:text-pink-400 mb-1 truncate">{{ currentRank?.name || 'Yangi' }}</div>
                            <div class="text-gray-600 dark:text-gray-400 font-semibold text-sm">Daraja</div>
                        </div>
                    </div>
                </div>

                <!-- Streak Rewards -->
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-2xl">🔥</span>
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white">Seriya Mukofotlari</h2>
                    </div>
                    <div class="flex overflow-x-auto gap-4 pb-4 -mx-4 px-4 scrollbar-hide">
                        <div
                            v-for="reward in streakRewards"
                            :key="reward.days"
                            class="flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl p-5 min-w-[160px] text-center shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105"
                            :class="{ 'ring-4 ring-orange-400 bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20': (stats.current_streak || 0) >= reward.days }"
                        >
                            <div class="text-4xl mb-3">{{ reward.icon }}</div>
                            <div class="text-gray-900 dark:text-white font-black text-lg mb-1">{{ reward.days }} kun</div>
                            <div class="text-gray-600 dark:text-gray-400 text-sm mb-2">{{ reward.name_uz }}</div>
                            <div class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs font-bold px-3 py-1 rounded-full">
                                +{{ reward.xp_bonus }} XP
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">Kategoriyalar</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="group bg-gradient-to-br rounded-2xl p-5 text-center shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer"
                            :class="category.color"
                        >
                            <div class="text-4xl mb-3 group-hover:scale-110 transition-transform">{{ category.icon }}</div>
                            <div class="text-white font-black text-sm mb-1">{{ category.name }}</div>
                            <div class="text-white/90 text-xs font-medium">{{ category.name_uz }}</div>
                        </div>
                    </div>
                </div>

                <!-- Levels Grid -->
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">Darajalar</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div
                            v-for="(level, index) in levels"
                            :key="level.id"
                            class="relative"
                        >
                            <Link
                                v-if="level.unlocked"
                                :href="route('student.english.games.daily-challenge.play', { level: level.level_number })"
                                class="block relative bg-gradient-to-br rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 overflow-hidden group"
                                :class="[
                                    index % 6 === 0 ? 'from-purple-500 to-pink-500' :
                                    index % 6 === 1 ? 'from-blue-500 to-cyan-500' :
                                    index % 6 === 2 ? 'from-green-500 to-emerald-500' :
                                    index % 6 === 3 ? 'from-orange-500 to-red-500' :
                                    index % 6 === 4 ? 'from-yellow-500 to-orange-500' :
                                    'from-pink-500 to-rose-500'
                                ]"
                            >
                                <!-- Shine Effect -->
                                <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>

                                <div class="relative">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-3xl font-black text-white drop-shadow">{{ level.level_number }}</span>
                                        <div v-if="level.completed" class="flex gap-0.5">
                                            <StarIcon
                                                v-for="i in 3"
                                                :key="i"
                                                class="w-5 h-5 drop-shadow"
                                                :class="i <= level.stars ? 'text-yellow-300' : 'text-white/30'"
                                            />
                                        </div>
                                    </div>
                                    <div class="text-white font-bold text-sm mb-2 drop-shadow">{{ level.name }}</div>
                                    <div class="flex items-center gap-2 text-xs text-white/90 font-medium mb-2">
                                        <span>{{ level.questions_count }} savol</span>
                                        <span>•</span>
                                        <span class="capitalize">{{ level.difficulty }}</span>
                                    </div>
                                    <div v-if="level.best_accuracy" class="bg-white/20 backdrop-blur-sm text-white text-xs font-bold px-2 py-1 rounded-lg w-fit">
                                        Eng yaxshi: {{ level.best_accuracy }}%
                                    </div>
                                </div>
                            </Link>
                            <div
                                v-else
                                class="block relative bg-gray-200 dark:bg-gray-800 rounded-2xl p-5 opacity-60 cursor-not-allowed"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-3xl font-black text-gray-400 dark:text-gray-600">{{ level.level_number }}</span>
                                    <LockClosedIcon class="w-6 h-6 text-gray-400 dark:text-gray-600" />
                                </div>
                                <div class="text-gray-500 dark:text-gray-500 font-bold text-sm mb-2">{{ level.name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-600 font-medium">
                                    {{ level.unlock_requirement }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Achievements Section -->
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">Yutuqlar</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div
                            v-for="achievement in achievements.slice(0, 8)"
                            :key="achievement.id"
                            class="bg-white dark:bg-gray-800 rounded-2xl p-5 text-center shadow-lg hover:shadow-xl transition-all duration-300"
                            :class="achievement.unlocked ? 'hover:scale-105' : 'opacity-60'"
                        >
                            <div class="text-5xl mb-3">{{ achievement.icon }}</div>
                            <div class="text-gray-900 dark:text-white font-black text-sm mb-2">{{ achievement.name }}</div>
                            <div class="text-gray-600 dark:text-gray-400 text-xs mb-3">{{ achievement.description_uz || achievement.description }}</div>
                            <div v-if="achievement.unlocked">
                                <span class="inline-block bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-bold px-3 py-1.5 rounded-full">
                                    Ochildi!
                                </span>
                            </div>
                            <div v-else class="relative pt-2">
                                <div class="overflow-hidden h-2 text-xs flex rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-blue-500 to-purple-500"
                                        :style="{ width: (achievement.progress || 0) + '%' }"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ achievement.progress || 0 }}%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quiz Master Ranks -->
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-4">Quiz Master Darajalari</h2>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                        <div class="flex overflow-x-auto gap-4 pb-4 -mx-2 px-2 scrollbar-hide">
                            <div
                                v-for="rank in quizMasterRanks"
                                :key="rank.id"
                                class="flex-shrink-0 text-center p-5 rounded-2xl min-w-[140px] transition-all duration-300 hover:scale-105"
                                :class="[
                                    currentRank?.id === rank.id
                                        ? 'bg-gradient-to-br from-purple-500 to-pink-500 ring-4 ring-purple-400 shadow-lg'
                                        : (stats.total_xp || 0) >= rank.xp_required
                                            ? 'bg-green-100 dark:bg-green-900/30'
                                            : 'bg-gray-100 dark:bg-gray-700'
                                ]"
                            >
                                <div class="text-4xl mb-2">{{ rank.icon }}</div>
                                <div
                                    class="font-black text-sm mb-1"
                                    :class="currentRank?.id === rank.id ? 'text-white' : 'text-gray-900 dark:text-white'"
                                >
                                    {{ rank.name }}
                                </div>
                                <div
                                    class="text-xs font-bold"
                                    :class="currentRank?.id === rank.id ? 'text-white/90' : 'text-gray-600 dark:text-gray-400'"
                                >
                                    {{ rank.xp_required }} XP
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Section -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6">
                    <div class="flex items-start gap-4">
                        <span class="text-4xl">💡</span>
                        <div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Maslahatlar</h3>
                            <ul class="space-y-2 text-gray-700 dark:text-gray-300 text-sm">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-500 mt-0.5">•</span>
                                    <span>Har kuni viktorinani yechib, seriyangizni davom ettiring va bonus XP yutib oling!</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-500 mt-0.5">•</span>
                                    <span>3 ta yulduz olish uchun barcha savollarga to'g'ri javob bering.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-500 mt-0.5">•</span>
                                    <span>Yuqori darajaga chiqish uchun ko'proq XP to'plang va yutuqlarni oching!</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ArrowLeftIcon, StarIcon, LockClosedIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    levels: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    config: { type: Object, default: () => ({}) },
    achievements: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    challengeTypes: { type: Array, default: () => [] },
    totalStars: { type: Number, default: 0 },
    quizMasterRanks: { type: Array, default: () => [] },
    streakRewards: { type: Array, default: () => [] },
    isDailyCompleted: { type: Boolean, default: false }
});

const currentRank = computed(() => {
    if (!props.quizMasterRanks.length) return null;
    let current = props.quizMasterRanks[0];
    for (const rank of props.quizMasterRanks) {
        if ((props.stats.total_xp || 0) >= rank.xp_required) {
            current = rank;
        }
    }
    return current;
});
</script>

<style scoped>
@keyframes gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 8s ease infinite;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
