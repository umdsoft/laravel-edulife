<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-600 via-teal-600 to-emerald-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-cyan-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🎧</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">🔊</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">👂</div>
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
                                <div class="absolute inset-0 bg-teal-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-cyan-400 via-teal-400 to-emerald-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-teal-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🎧
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Listen & <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 to-teal-300">Choose</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    Audioni <span class="text-emerald-300 font-bold">eshiting</span> va to'g'ri javobni <span class="text-cyan-300 font-bold">tanlang</span>!
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
                                        <div class="text-white/60 text-xs font-medium">Yulduzlar</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Accuracy Card -->
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

                            <!-- First Try Card -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                                        <span class="text-2xl">🏆</span>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-black text-white">{{ stats.first_try_rate || 0 }}%</div>
                                        <div class="text-white/60 text-xs font-medium">1-urinish</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Modes Cards -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div
                            v-for="(mode, index) in gameModes"
                            :key="mode.id"
                            class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02] cursor-default"
                            :class="[
                                index === 0 ? 'hover:shadow-lg hover:shadow-cyan-500/20' : '',
                                index === 1 ? 'hover:shadow-lg hover:shadow-teal-500/20' : '',
                                index === 2 ? 'hover:shadow-lg hover:shadow-emerald-500/20' : '',
                                index === 3 ? 'hover:shadow-lg hover:shadow-blue-500/20' : '',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110"
                                    :class="[
                                        index === 0 ? 'bg-gradient-to-br from-cyan-400 to-cyan-600' : '',
                                        index === 1 ? 'bg-gradient-to-br from-teal-400 to-teal-600' : '',
                                        index === 2 ? 'bg-gradient-to-br from-emerald-400 to-emerald-600' : '',
                                        index === 3 ? 'bg-gradient-to-br from-blue-400 to-blue-600' : '',
                                    ]"
                                >
                                    {{ mode.icon }}
                                </div>
                                <div>
                                    <p class="text-white font-bold text-sm">{{ mode.name_uz }}</p>
                                    <p class="text-white/60 text-xs">{{ mode.point_multiplier }}x ball</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Overview Cards -->
                    <div class="mt-8 grid grid-cols-2 lg:grid-cols-5 gap-3">
                        <div class="group bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 hover:bg-white/20 hover:border-white/30 transition-all hover:scale-[1.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-blue-400 to-blue-600">
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
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-green-400 to-emerald-600">
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
                                    🏆
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ achievements.total_earned || 0 }}</p>
                                    <p class="text-white/60 text-xs">Yutuqlar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Listening Rank Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="text-5xl" :style="{ filter: `drop-shadow(0 0 10px ${stats.listening_rank?.current?.color || '#9CA3AF'})` }">
                            {{ stats.listening_rank?.current?.icon || '👂' }}
                        </div>
                        <div>
                            <h3 class="text-gray-900 dark:text-white font-bold text-xl">
                                {{ stats.listening_rank?.current?.name || 'Beginner Listener' }}
                            </h3>
                            <p class="text-cyan-600 dark:text-cyan-400 text-sm">
                                {{ stats.listening_rank?.current?.name_uz || 'Boshlang\'ich tinglovchi' }}
                            </p>
                            <p class="text-cyan-500 dark:text-cyan-400 text-xs mt-1">
                                {{ stats.total_xp || 0 }} XP
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-cyan-600 dark:text-cyan-400 text-sm">Jami eshitishlar</div>
                        <div class="text-gray-900 dark:text-white text-2xl font-bold">{{ stats.total_questions || 0 }}</div>
                    </div>
                </div>

                <!-- Progress to next rank -->
                <div v-if="stats.listening_rank?.next" class="mt-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Keyingi daraja: {{ stats.listening_rank.next.name_uz }}</span>
                        <span class="text-gray-900 dark:text-white">{{ stats.listening_rank.xp_to_next }} XP kerak</span>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-cyan-400 to-teal-400 rounded-full transition-all duration-500"
                             :style="{ width: `${stats.listening_rank.progress || 0}%` }"></div>
                    </div>
                </div>
                <div v-else class="mt-4 text-center text-cyan-600 dark:text-cyan-400">
                    🏆 Maksimal darajaga erishdingiz! Siz Listening Master!
                </div>
            </div>

            <!-- Audio Categories -->
            <div class="mb-6">
                <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center">
                    <SpeakerWaveIcon class="w-6 h-6 mr-2" />
                    Audio kategoriyalari
                </h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="category in audioCategories" :key="category.id"
                         class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-cyan-400 dark:hover:border-cyan-500 transition-all shadow-md">
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="text-2xl">{{ category.icon }}</span>
                            <span class="text-xs px-2 py-0.5 rounded"
                                  :class="getDifficultyColor(category.difficulty)">
                                {{ category.difficulty }}
                            </span>
                        </div>
                        <h3 class="text-gray-900 dark:text-white font-bold">{{ category.name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">{{ category.name_uz }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-cyan-600 dark:text-cyan-400 text-xs">
                                {{ getCategoryAccuracy(category.id) }}% aniqlik
                            </span>
                            <span class="text-gray-600 dark:text-gray-400 text-xs">
                                {{ getCategoryPracticed(category.id) }} savol
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-6">
                <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center">
                    <FireIcon class="w-6 h-6 mr-2" />
                    Bosqichlar
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <div v-for="(level, index) in levels" :key="level.id"
                         class="relative rounded-xl overflow-hidden border transition-all duration-300"
                         :class="[
                             level.is_unlocked
                                 ? 'hover:scale-105 cursor-pointer shadow-lg hover:shadow-xl'
                                 : 'border-gray-300 dark:border-gray-600 opacity-60'
                         ]"
                         :style="level.is_unlocked ? { background: getLevelGradient(index) } : ''"
                         @click="level.is_unlocked && selectLevel(level)">

                        <!-- Lock overlay -->
                        <div v-if="!level.is_unlocked"
                             class="absolute inset-0 bg-gray-100 dark:bg-gray-800 flex items-center justify-center z-10">
                            <div class="text-center">
                                <LockClosedIcon class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Oldingi bosqichni tugatish kerak</p>
                            </div>
                        </div>

                        <!-- Level content -->
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="bg-white/30 text-white px-2 py-1 rounded text-xs font-medium">
                                    {{ level.cefr_level }}
                                </span>
                                <span v-if="level.is_premium" class="text-yellow-400">
                                    <SparklesIcon class="w-5 h-5" />
                                </span>
                            </div>

                            <h3 class="text-white font-bold text-lg mb-1">{{ level.name }}</h3>
                            <p class="text-white/90 text-sm mb-3">{{ level.name_uz }}</p>

                            <!-- Stars -->
                            <div class="flex items-center space-x-1 mb-3">
                                <StarIcon v-for="star in 3" :key="star"
                                         class="w-5 h-5"
                                         :class="star <= level.stars ? 'text-yellow-400 fill-yellow-400' : 'text-white/30'" />
                            </div>

                            <!-- Level info -->
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/90">{{ level.time_limit }}s</span>
                                <span class="text-white/90">+{{ level.xp_reward }} XP</span>
                            </div>

                            <!-- Categories in level -->
                            <div class="mt-3 flex flex-wrap gap-1">
                                <span v-for="cat in level.categories?.slice(0, 2)" :key="cat"
                                      class="bg-white/20 text-white px-2 py-0.5 rounded text-xs">
                                    {{ getCategoryName(cat) }}
                                </span>
                                <span v-if="level.categories?.length > 2"
                                      class="bg-white/20 text-white px-2 py-0.5 rounded text-xs">
                                    +{{ level.categories.length - 2 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-2xl p-5 border border-teal-200 dark:border-teal-800">
                <h3 class="font-bold text-teal-900 dark:text-teal-300 mb-3 flex items-center gap-2">
                    <span>💡</span> Foydali Maslahatlar
                </h3>
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">🎧</span>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">Audio diqqat bilan eshiting va zarur bo'lsa qayta eshiting</p>
                    </div>
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">⭐</span>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">3 ta yulduz olish uchun 90%+ aniqlik kerak</p>
                    </div>
                    <div class="flex items-start gap-3 bg-white/50 dark:bg-white/5 rounded-xl p-3">
                        <span class="text-xl">🎯</span>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">Birinchi urinishda to'g'ri javob bering bonus XP uchun</p>
                    </div>
                </div>
            </div>

            <!-- Level Selection Modal -->
            <Teleport to="body">
                <div v-if="selectedLevel"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="selectedLevel = null">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-scale-in">
                        <div class="text-center mb-6">
                            <div class="text-4xl mb-3">🎧</div>
                            <h2 class="text-gray-900 dark:text-white text-2xl font-bold">{{ selectedLevel.name }}</h2>
                            <p class="text-gray-600 dark:text-gray-400">{{ selectedLevel.name_uz }}</p>
                            <span class="inline-block mt-2 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 px-3 py-1 rounded-full text-sm">
                                {{ selectedLevel.cefr_level }} - {{ selectedLevel.difficulty }}
                            </span>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Vaqt limiti</span>
                                <span class="text-gray-900 dark:text-white">{{ selectedLevel.time_limit }} soniya</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Savollar soni</span>
                                <span class="text-gray-900 dark:text-white">{{ selectedLevel.questions_count }} ta</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Takrorlashlar</span>
                                <span class="text-gray-900 dark:text-white">{{ selectedLevel.max_replays }} marta</span>
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
                                      class="bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 px-2 py-1 rounded text-xs">
                                    {{ getCategoryName(cat) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button @click="selectedLevel = null"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition-colors">
                                Bekor qilish
                            </button>
                            <Link :href="route('student.english.games.listen-choose.play', selectedLevel.level_number)"
                                  class="flex-1 py-3 bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 text-white rounded-xl transition-colors text-center font-semibold">
                                Boshlash 🎧
                            </Link>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
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
    SpeakerWaveIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    levels: Array,
    stats: Object,
    config: Object,
    achievements: Object,
    audioCategories: Array,
    gameModes: Array,
    totalStars: Number,
    listeningRanks: Array,
});

const selectedLevel = ref(null);

const selectLevel = (level) => {
    selectedLevel.value = level;
};

const getCategoryName = (categoryId) => {
    const category = props.audioCategories?.find(c => c.id === categoryId);
    return category?.name || categoryId;
};

const getDifficultyColor = (difficulty) => {
    const colors = {
        'easy': 'bg-green-500/30 text-green-700 dark:text-green-300',
        'medium': 'bg-yellow-500/30 text-yellow-700 dark:text-yellow-300',
        'hard': 'bg-red-500/30 text-red-700 dark:text-red-300',
    };
    return colors[difficulty] || 'bg-gray-500/30 text-gray-700 dark:text-gray-300';
};

const getCategoryAccuracy = (categoryId) => {
    const categoryStats = props.stats?.category_stats?.[categoryId];
    if (!categoryStats || categoryStats.total === 0) return 0;
    return Math.round((categoryStats.correct / categoryStats.total) * 100);
};

const getCategoryPracticed = (categoryId) => {
    return props.stats?.category_stats?.[categoryId]?.total || 0;
};

const getLevelGradient = (index) => {
    const gradients = [
        'linear-gradient(135deg, #0891b2 0%, #14b8a6 100%)', // cyan to teal
        'linear-gradient(135deg, #14b8a6 0%, #10b981 100%)', // teal to emerald
        'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)', // sky to cyan
        'linear-gradient(135deg, #10b981 0%, #059669 100%)', // emerald to emerald-dark
        'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)', // blue to blue-dark
        'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)', // cyan to cyan-dark
        'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)', // teal to teal-dark
        'linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%)', // cyan-light to sky
    ];
    return gradients[index % gradients.length];
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
