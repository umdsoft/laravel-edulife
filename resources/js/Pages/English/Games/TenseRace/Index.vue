<script setup>
import { ref, computed } from 'vue';
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
    BoltIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    levels: Array,
    stats: Object,
    config: Object,
    achievements: Object,
    tenseCategories: Array,
    gameModes: Array,
    totalStars: Number,
    tenseMasterRanks: Array,
});

const selectedLevel = ref(null);
const selectedMode = ref('mixed');
const showTutorial = ref(false);

const selectLevel = (level) => {
    selectedLevel.value = level;
};

const getTenseName = (tenseId) => {
    const tense = props.tenseCategories?.find(t => t.id === tenseId);
    return tense?.name || tenseId;
};

const getSelectedMode = computed(() => {
    return props.gameModes?.find(m => m.id === selectedMode.value);
});

const getTenseGroupColor = (group) => {
    const colors = {
        'present': 'bg-green-500/30 text-green-300',
        'past': 'bg-blue-500/30 text-blue-300',
        'future': 'bg-purple-500/30 text-purple-300',
    };
    return colors[group] || 'bg-gray-500/30 text-gray-300';
};

const getTenseAccuracy = (tenseId) => {
    const tenseStats = props.stats?.tense_stats?.[tenseId];
    if (!tenseStats || tenseStats.total === 0) return 0;
    return Math.round((tenseStats.correct / tenseStats.total) * 100);
};

const getTensePracticed = (tenseId) => {
    return props.stats?.tense_stats?.[tenseId]?.total || 0;
};

const getQuestionTypeAccuracy = (typeId) => {
    const typeStats = props.stats?.question_type_stats?.[typeId];
    if (!typeStats || typeStats.total === 0) return 0;
    return Math.round((typeStats.correct / typeStats.total) * 100);
};

const practizedTensesCount = computed(() => {
    if (!props.stats?.tense_stats) return 0;
    return Object.keys(props.stats.tense_stats).filter(key =>
        props.stats.tense_stats[key].total > 0
    ).length;
});

const getLevelGradient = (index) => {
    const gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
        'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
        'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)',
    ];
    return gradients[index % gradients.length];
};

const maxStars = computed(() => props.levels?.length * 3 || 30);
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">🏎️</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">⏰</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">📊</div>
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
                                <div class="absolute inset-0 bg-indigo-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-indigo-400 via-purple-500 to-violet-600 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-purple-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    🏎️
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Tense <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">Race</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    Fe'l zamonlarini o'rganish va <span class="text-yellow-300 font-bold">tezkorlik</span> musobaqasi
                                </p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="flex gap-3 flex-wrap">
                            <!-- Stars Card -->
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
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-purple-400 to-purple-600">
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
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-indigo-400 to-indigo-600">
                                    ⏱️
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ practizedTensesCount }}</p>
                                    <p class="text-white/60 text-xs">Zamonlar</p>
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
                    </div>
                </div>
            </div>

            <!-- Tense Master Rank Card -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <div class="text-5xl" :style="{ filter: `drop-shadow(0 0 10px ${stats.tense_master_rank?.current?.color || '#9CA3AF'})` }">
                                {{ stats.tense_master_rank?.current?.icon || '📚' }}
                            </div>
                            <div>
                                <h3 class="text-gray-900 dark:text-white font-bold text-xl">
                                    {{ stats.tense_master_rank?.current?.name || 'Beginner' }}
                                </h3>
                                <p class="text-indigo-600 dark:text-indigo-400 text-sm">
                                    {{ stats.tense_master_rank?.current?.name_uz || 'Boshlang\'ich' }}
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                                    {{ stats.total_xp || 0 }} XP
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-gray-500 dark:text-gray-400 text-sm">O'rganilgan zamonlar</div>
                            <div class="text-gray-900 dark:text-white text-2xl font-bold">{{ practizedTensesCount }} / 12</div>
                        </div>
                    </div>

                    <!-- Progress to next rank -->
                    <div v-if="stats.tense_master_rank?.next" class="mt-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Keyingi daraja: {{ stats.tense_master_rank.next.name_uz }}</span>
                            <span class="text-gray-900 dark:text-white">{{ stats.tense_master_rank.xp_to_next }} XP kerak</span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-400 to-purple-400 rounded-full transition-all duration-500"
                                 :style="{ width: `${stats.tense_master_rank.progress || 0}%` }"></div>
                        </div>
                    </div>
                    <div v-else class="mt-4 text-center text-indigo-600 dark:text-indigo-400">
                        🏆 Maksimal darajaga erishdingiz! Siz Tense Master!
                    </div>
                </div>
            </div>

            <!-- Tense Categories Overview -->
            <div class="mb-6">
                <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center">
                    <ClockIcon class="w-6 h-6 mr-2" />
                    Fe'l zamonlari
                </h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="tense in tenseCategories" :key="tense.id"
                         class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-400 hover:shadow-lg transition-all">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-xl">{{ tense.icon }}</span>
                            <span class="text-xs px-2 py-0.5 rounded"
                                  :class="getTenseGroupColor(tense.group)">
                                {{ tense.group }}
                            </span>
                        </div>
                        <h3 class="text-gray-900 dark:text-white font-bold text-sm">{{ tense.name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">{{ tense.name_uz }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-indigo-600 dark:text-indigo-400 text-xs">
                                {{ getTenseAccuracy(tense.id) }}% aniqlik
                            </span>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">
                                {{ getTensePracticed(tense.id) }} savol
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-gray-900 dark:text-white text-xl font-bold mb-4 flex items-center">
                    <FireIcon class="w-6 h-6 mr-2" />
                    Bosqichlar
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
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
                                <span class="text-white/90">{{ level.time_limit }}s</span>
                                <span class="text-white/90">+{{ level.xp_reward }} XP</span>
                            </div>

                            <!-- Tenses in level -->
                            <div class="mt-3 flex flex-wrap gap-1">
                                <span v-for="tense in level.tenses?.slice(0, 3)" :key="tense"
                                      class="bg-white/20 text-white text-xs px-2 py-0.5 rounded">
                                    {{ getTenseName(tense) }}
                                </span>
                                <span v-if="level.tenses?.length > 3"
                                      class="bg-white/20 text-white text-xs px-2 py-0.5 rounded">
                                    +{{ level.tenses.length - 3 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
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
                        <p class="text-gray-700 dark:text-gray-300 text-sm">Har bir tense turida mashq qilishga harakat qiling</p>
                    </div>
                </div>
            </div>

            <!-- Level Selection Modal -->
            <Teleport to="body">
                <div v-if="selectedLevel"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="selectedLevel = null">
                    <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-violet-600 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-indigo-400/30">
                        <div class="text-center mb-6">
                            <div class="text-4xl mb-3">🏎️</div>
                            <h2 class="text-white text-2xl font-bold">{{ selectedLevel.name }}</h2>
                            <p class="text-indigo-200">{{ selectedLevel.name_uz }}</p>
                            <span class="inline-block mt-2 bg-white/20 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm">
                                {{ selectedLevel.cefr_level }} - {{ selectedLevel.difficulty }}
                            </span>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-200">Vaqt limiti</span>
                                <span class="text-white">{{ selectedLevel.time_limit }} soniya</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-200">Savollar soni</span>
                                <span class="text-white">{{ selectedLevel.questions_count }} ta</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-200">XP mukofot</span>
                                <span class="text-white">+{{ selectedLevel.xp_reward }} XP</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-indigo-200">Tanga mukofot</span>
                                <span class="text-yellow-300">🪙 {{ selectedLevel.coin_reward }}</span>
                            </div>
                        </div>

                        <!-- Tenses in level -->
                        <div class="mb-6">
                            <p class="text-indigo-200 text-sm mb-2">Fe'l zamonlari:</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="tense in selectedLevel.tenses" :key="tense"
                                      class="bg-white/10 text-indigo-100 px-2 py-1 rounded text-xs backdrop-blur-sm">
                                    {{ getTenseName(tense) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button @click="selectedLevel = null"
                                    class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors backdrop-blur-sm">
                                Bekor qilish
                            </button>
                            <Link :href="route('student.english.games.tense-race.play', selectedLevel.level_number)"
                                  class="flex-1 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl transition-colors text-center font-semibold shadow-lg">
                                Boshlash 🏎️
                            </Link>
                        </div>
                    </div>
                </div>
            </Teleport>

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
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-5 rounded-t-2xl">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">🏎️</span>
                                <div>
                                    <h2 class="text-xl font-black text-white">Tense Race</h2>
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
                                    <strong>Vaqt tugamasidan</strong> oldin fe'l zamonlarini to'g'ri aniqlang! Tezlik va aniqlik - muvaffaqiyat kaliti.
                                </p>
                            </div>

                            <!-- Start Button -->
                            <button
                                @click="showTutorial = false"
                                class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                            >
                                Tushundim! 🚀
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
</style>
