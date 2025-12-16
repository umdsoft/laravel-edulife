<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-orange-600 via-red-600 to-rose-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🔥</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">⚡</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">🎯</div>
                    <div class="absolute bottom-8 right-1/4 text-4xl opacity-15 animate-float-delayed">⭐</div>
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
                                <div class="absolute inset-0 bg-orange-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-orange-400 via-red-500 to-rose-600 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-red-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🔥
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Rapid <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">Fire</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    Tezkor savollar bilan ingliz tilingizni <span class="text-yellow-300 font-bold">sinang</span>!
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="flex gap-3 flex-wrap">
                            <!-- Total Stars -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-xl flex items-center justify-center shadow-lg shadow-yellow-500/30">
                                        <span class="text-2xl">⭐</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ totalStars }}</div>
                                        <div class="text-white/60 text-xs font-medium">Yulduzlar</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Accuracy -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                                        <span class="text-2xl">🎯</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ stats.accuracy || 0 }}%</div>
                                        <div class="text-white/60 text-xs font-medium">Aniqlik</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Best Streak -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-rose-500 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/30">
                                        <span class="text-2xl">🔥</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ stats.best_streak || 0 }}</div>
                                        <div class="text-white/60 text-xs font-medium">Eng yaxshi</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Overview Cards -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-5 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-green-400 to-emerald-600">
                                    🎮
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats.games_played || 0 }}</p>
                                    <p class="text-white/60 text-xs">O'yinlar</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-blue-400 to-blue-600">
                                    ✅
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats.total_correct || 0 }}</p>
                                    <p class="text-white/60 text-xs">To'g'ri</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-orange-400 to-red-600">
                                    🔥
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats.best_streak || 0 }}</p>
                                    <p class="text-white/60 text-xs">Best Streak</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-yellow-400 to-amber-600">
                                    🪙
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats.total_coins || 0 }}</p>
                                    <p class="text-white/60 text-xs">Tangalar</p>
                                </div>
                            </div>
                        </div>

                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-purple-400 to-purple-600">
                                    ⚡
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats.best_qpm || 0 }}</p>
                                    <p class="text-white/60 text-xs">QPM</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Modes Cards -->
                    <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div
                            v-for="(mode, index) in gameModes"
                            :key="mode.id"
                            class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 transition-all hover:scale-[1.02] cursor-pointer"
                            :class="[
                                { 'border-orange-400 ring-2 ring-orange-400/50 bg-white/20': selectedMode === mode.id },
                                index === 0 ? 'hover:shadow-lg hover:shadow-orange-500/20 hover:border-white/30' : '',
                                index === 1 ? 'hover:shadow-lg hover:shadow-red-500/20 hover:border-white/30' : '',
                                index === 2 ? 'hover:shadow-lg hover:shadow-rose-500/20 hover:border-white/30' : '',
                                index === 3 ? 'hover:shadow-lg hover:shadow-amber-500/20 hover:border-white/30' : '',
                            ]"
                            @click="selectedMode = mode.id"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110"
                                    :class="[
                                        index === 0 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : '',
                                        index === 1 ? 'bg-gradient-to-br from-red-400 to-red-600' : '',
                                        index === 2 ? 'bg-gradient-to-br from-rose-400 to-rose-600' : '',
                                        index === 3 ? 'bg-gradient-to-br from-amber-400 to-amber-600' : '',
                                    ]"
                                >
                                    {{ mode.icon }}
                                </div>
                                <div>
                                    <p class="text-white font-bold text-sm">{{ mode.name }}</p>
                                    <p class="text-white/60 text-xs">{{ mode.name_uz }}</p>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="text-orange-300 text-xs font-medium">{{ mode.point_multiplier }}x ball</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Speed Rank Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="text-5xl" :style="{ filter: `drop-shadow(0 0 10px ${stats.speed_rank?.current?.color || '#9CA3AF'})` }">
                            {{ stats.speed_rank?.current?.icon || '🐢' }}
                        </div>
                        <div>
                            <h3 class="text-gray-900 dark:text-white font-bold text-xl">
                                {{ stats.speed_rank?.current?.name || 'Turtle' }}
                            </h3>
                            <p class="text-orange-600 dark:text-orange-400 text-sm">
                                {{ stats.speed_rank?.current?.name_uz || 'Toshbaqa' }}
                            </p>
                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                                {{ stats.best_qpm || 0 }} savol/daqiqa
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Jami XP</div>
                        <div class="text-gray-900 dark:text-white text-2xl font-bold">{{ stats.total_xp || 0 }}</div>
                    </div>
                </div>

                <!-- Progress to next rank -->
                <div v-if="stats.speed_rank?.next" class="mt-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Keyingi daraja: {{ stats.speed_rank.next.name_uz }}</span>
                        <span class="text-gray-900 dark:text-white">{{ stats.speed_rank.qpm_to_next }} QPM kerak</span>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-orange-400 to-red-400 rounded-full transition-all duration-500"
                             :style="{ width: `${getSpeedProgress}%` }"></div>
                    </div>
                </div>
                <div v-else class="mt-4 text-center text-orange-600 dark:text-orange-400">
                    ⚛️ Maksimal tezlik darajasiga erishdingiz!
                </div>
            </div>

            <!-- Word Categories -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <TagIcon class="w-6 h-6 mr-2 text-orange-600" />
                    So'z Kategoriyalari
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <div v-for="category in categories" :key="category.id"
                         class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:border-orange-400 dark:hover:border-orange-600 transition-all cursor-default">
                        <div class="text-center">
                            <span class="text-3xl mb-2 block">{{ category.icon }}</span>
                            <div class="text-gray-900 dark:text-white text-sm font-medium">{{ category.name }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs">{{ category.name_uz }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <FireIcon class="w-6 h-6 mr-2 text-orange-600" />
                    Bosqichlar
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div v-for="(level, index) in levels" :key="level.id"
                         class="relative rounded-xl overflow-hidden border transition-all duration-300"
                         :class="[
                             level.is_unlocked
                                 ? 'hover:scale-105 cursor-pointer shadow-lg'
                                 : 'border-gray-300 dark:border-gray-600 opacity-60'
                         ]"
                         :style="level.is_unlocked ? { background: getLevelGradient(index) } : {}"
                         @click="level.is_unlocked && selectLevel(level)">

                        <!-- Lock overlay -->
                        <div v-if="!level.is_unlocked"
                             class="absolute inset-0 bg-gray-100 dark:bg-gray-800 flex items-center justify-center z-10">
                            <div class="text-center p-4">
                                <LockClosedIcon class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Oldingi bosqichni tugatish kerak</p>
                            </div>
                        </div>

                        <!-- Level content -->
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="bg-white/30 backdrop-blur-sm text-white px-2 py-1 rounded text-xs font-medium">
                                    {{ level.cefr_level }}
                                </span>
                                <span v-if="level.is_premium" class="text-yellow-300">
                                    <SparklesIcon class="w-5 h-5" />
                                </span>
                            </div>

                            <h3 class="text-white font-bold text-lg mb-1">{{ level.name }}</h3>
                            <p class="text-white/90 text-sm mb-3">{{ level.name_uz }}</p>

                            <!-- Stars -->
                            <div class="flex items-center space-x-1 mb-3">
                                <StarIcon v-for="star in 3" :key="star"
                                         class="w-5 h-5"
                                         :class="star <= level.stars ? 'text-yellow-300 fill-yellow-300' : 'text-white/30'" />
                            </div>

                            <!-- Level info -->
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/80">⏱ {{ level.time_limit }}s</span>
                                <span class="text-white/80">+{{ level.xp_reward }} XP</span>
                            </div>

                            <!-- Categories -->
                            <div class="mt-3 flex flex-wrap gap-1">
                                <span v-for="cat in level.categories?.slice(0, 3)" :key="cat"
                                      class="bg-white/20 backdrop-blur-sm text-white px-2 py-0.5 rounded text-xs">
                                    {{ getCategoryName(cat) }}
                                </span>
                                <span v-if="level.categories?.length > 3"
                                      class="bg-white/20 backdrop-blur-sm text-white px-2 py-0.5 rounded text-xs">
                                    +{{ level.categories.length - 3 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="mt-8">
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-5 border border-amber-200 dark:border-amber-800">
                    <h3 class="font-bold text-amber-900 dark:text-amber-300 mb-3 flex items-center gap-2">
                        <span>💡</span> Foydali Maslahatlar
                    </h3>
                    <div class="grid md:grid-cols-3 gap-3">
                        <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                            <span class="text-xl">⚡</span>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">Tez javob bersangiz, bonus ball olasiz!</p>
                        </div>
                        <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                            <span class="text-xl">⭐</span>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">3 ta yulduz olish uchun 95%+ aniqlik kerak</p>
                        </div>
                        <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                            <span class="text-xl">🎯</span>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">Har bir level uchun mukofot faqat 1 marta beriladi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Level Selection Modal -->
            <Teleport to="body">
                <div v-if="selectedLevel"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="selectedLevel = null">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl border border-gray-200 dark:border-gray-700 animate-scale-in">
                        <!-- Header with gradient -->
                        <div class="bg-gradient-to-r from-orange-600 to-red-600 p-6 rounded-t-2xl">
                            <div class="text-center">
                                <div class="text-4xl mb-3">🔥</div>
                                <h2 class="text-white text-2xl font-bold">{{ selectedLevel.name }}</h2>
                                <p class="text-orange-100">{{ selectedLevel.name_uz }}</p>
                                <span class="inline-block mt-2 bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm">
                                    {{ selectedLevel.cefr_level }} - {{ selectedLevel.difficulty }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Selected Mode -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-2xl">{{ getSelectedMode?.icon }}</span>
                                        <div>
                                            <div class="text-gray-900 dark:text-white font-medium">{{ getSelectedMode?.name }}</div>
                                            <div class="text-gray-600 dark:text-gray-400 text-xs">{{ getSelectedMode?.name_uz }}</div>
                                        </div>
                                    </div>
                                    <span class="text-orange-600 dark:text-orange-400 text-sm">{{ getSelectedMode?.point_multiplier }}x</span>
                                </div>
                            </div>

                            <div class="space-y-4 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Vaqt limiti</span>
                                    <span class="text-gray-900 dark:text-white">{{ getSelectedMode?.time_limit || selectedLevel.time_limit }} soniya</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">XP mukofot</span>
                                    <span class="text-gray-900 dark:text-white">+{{ selectedLevel.xp_reward }} XP</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Tanga mukofot</span>
                                    <span class="text-yellow-500">🪙 {{ selectedLevel.coin_reward }}</span>
                                </div>
                            </div>

                            <!-- Categories in level -->
                            <div class="mb-6">
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Kategoriyalar:</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="cat in selectedLevel.categories" :key="cat"
                                          class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                        {{ getCategoryName(cat) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex space-x-3">
                                <button @click="selectedLevel = null"
                                        class="flex-1 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition-colors">
                                    Bekor qilish
                                </button>
                                <Link :href="route('student.english.games.rapid-fire.play', selectedLevel.level_number)"
                                      class="flex-1 py-3 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-xl transition-colors text-center font-semibold">
                                    Boshlash 🔥
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import {
    ArrowLeftIcon,
    StarIcon,
    LockClosedIcon,
    SparklesIcon,
    ChartBarIcon,
    TagIcon,
    TrophyIcon,
    FireIcon,
    BoltIcon
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    levels: Array,
    stats: Object,
    config: Object,
    achievements: Object,
    categories: Array,
    gameModes: Array,
    totalStars: Number,
});

const selectedLevel = ref(null);
const selectedMode = ref('classic');

const selectLevel = (level) => {
    selectedLevel.value = level;
};

const getCategoryName = (categoryId) => {
    const category = props.categories?.find(c => c.id === categoryId);
    return category?.name || categoryId;
};

const getSelectedMode = computed(() => {
    return props.gameModes?.find(m => m.id === selectedMode.value);
});

const getSpeedProgress = computed(() => {
    if (!props.stats?.speed_rank?.next) return 100;
    const current = props.stats.best_qpm || 0;
    const next = props.stats.speed_rank.next.min_qpm;
    const prev = props.stats.speed_rank.current?.min_qpm || 0;
    return Math.min(100, ((current - prev) / (next - prev)) * 100);
});

// Color gradients for levels (cycling through orange/red theme)
const levelColors = [
    { from: '#f97316', to: '#ea580c' }, // orange-500 to orange-600
    { from: '#ef4444', to: '#dc2626' }, // red-500 to red-600
    { from: '#f43f5e', to: '#e11d48' }, // rose-500 to rose-600
    { from: '#fb923c', to: '#f97316' }, // orange-400 to orange-500
    { from: '#fbbf24', to: '#f59e0b' }, // amber-400 to amber-500
    { from: '#ec4899', to: '#db2777' }, // pink-500 to pink-600
];

const getLevelGradient = (index) => {
    const colorIndex = index % levelColors.length;
    const colors = levelColors[colorIndex];
    return `linear-gradient(135deg, ${colors.from}, ${colors.to})`;
};
</script>

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
