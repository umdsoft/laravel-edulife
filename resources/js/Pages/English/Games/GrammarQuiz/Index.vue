<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import {
    ArrowLeftIcon,
    StarIcon,
    LockClosedIcon,
    SparklesIcon,
    BookOpenIcon,
    ChartBarIcon,
    TagIcon,
    PuzzlePieceIcon
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    levels: Array,
    stats: Object,
    config: Object,
    achievements: Array,
    categories: Array,
    quizTypes: Object,
    totalStars: Number,
});

const selectedLevel = ref(null);
const showTutorial = ref(false);

const selectLevel = (level) => {
    selectedLevel.value = level;
};

const getCategoryName = (categoryId) => {
    const category = props.categories?.find(c => c.id === categoryId);
    return category?.name || categoryId;
};

const getLevelGradient = (index) => {
    const colors = [
        'from-blue-500 to-blue-700',
        'from-purple-500 to-purple-700',
        'from-pink-500 to-pink-700',
        'from-red-500 to-red-700',
        'from-orange-500 to-orange-700',
        'from-yellow-500 to-yellow-700',
        'from-green-500 to-green-700',
        'from-teal-500 to-teal-700',
        'from-cyan-500 to-cyan-700',
        'from-indigo-500 to-indigo-700',
    ];
    return colors[index % colors.length];
};

const maxStars = computed(() => props.levels?.length * 3 || 0);

const gameModes = [
    { icon: '📝', name: "Multiple Choice", desc: "To'g'ri javobni tanlang" },
    { icon: '✏️', name: "Fill in Blanks", desc: "Bo'sh joylarni to'ldiring" },
    { icon: '🔀', name: "Sentence Order", desc: "Gaplarni to'g'ri tartibga qo'ying" },
    { icon: '🎯', name: "Error Detection", desc: "Xatolarni toping" },
];

const tips = [
    { icon: '⚡', text: "Har bir savol uchun vaqt limiti mavjud!" },
    { icon: '⭐', text: "3 ta yulduz olish uchun 95%+ to'g'ri javob kerak" },
    { icon: '🎯', text: "Har bir level uchun mukofot faqat 1 marta beriladi" },
];
</script>

<template>
    <StudentLayout>
        <div class="min-h-screen pb-8">
            <!-- Premium Hero Section -->
            <div class="relative rounded-3xl p-6 md:p-8 mb-8 overflow-hidden">
                <!-- Animated Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-500 animate-gradient"></div>

                <!-- Mesh Pattern Overlay -->
                <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;)"></div>

                <!-- Glow Effects -->
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-yellow-400/20 rounded-full blur-3xl animate-pulse-slow"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl animate-pulse-slow-delayed"></div>

                <!-- Floating Elements -->
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute top-8 left-12 text-5xl opacity-20 animate-float">📝</div>
                    <div class="absolute top-16 right-16 text-4xl opacity-20 animate-float-delayed">✏️</div>
                    <div class="absolute bottom-16 left-1/3 text-3xl opacity-20 animate-float">📚</div>
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
                                <div class="absolute inset-0 bg-emerald-400/30 rounded-2xl blur-xl animate-pulse"></div>
                                <div class="relative w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-emerald-400 via-teal-400 to-cyan-500 rounded-2xl flex items-center justify-center text-3xl md:text-4xl shadow-2xl shadow-emerald-500/40 transform hover:scale-105 hover:rotate-3 transition-all duration-300">
                                    📝
                                </div>
                            </div>
                            <div>
                                <h1 class="text-3xl md:text-5xl font-black text-white mb-1 tracking-tight">
                                    Grammar <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">Quiz</span>
                                </h1>
                                <p class="text-white/80 text-sm md:text-lg font-medium">
                                    Grammatika bilimingizni <span class="text-yellow-300 font-bold">sinab</span> ko'ring!
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

                            <!-- Grammarian Rank Card -->
                            <div class="bg-white/15 backdrop-blur-xl rounded-2xl px-5 py-4 border border-white/20 shadow-xl hover:bg-white/20 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                        <span class="text-2xl">{{ stats.grammarian_rank?.current?.icon || '📖' }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white">{{ stats.grammarian_rank?.current?.name || 'Novice' }}</div>
                                        <div class="text-white/60 text-xs font-medium">{{ stats.total_xp || 0 }} XP</div>
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
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-lg transition-transform group-hover:scale-110 bg-gradient-to-br from-green-400 to-emerald-600">
                                    📊
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ stats.total_questions || 0 }}</p>
                                    <p class="text-white/60 text-xs">Jami savollar</p>
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
                                    <p class="text-white/60 text-xs">To'g'ri javoblar</p>
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
                                    📚
                                </div>
                                <div>
                                    <p class="text-white font-black text-2xl">{{ levels?.filter(l => l.stars > 0).length || 0 }}</p>
                                    <p class="text-white/60 text-xs">Bosqichlar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grammarian Rank Card -->
            <div class="mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <div class="text-5xl" :style="{ filter: `drop-shadow(0 0 10px ${stats.grammarian_rank?.current?.color || '#9CA3AF'})` }">
                                {{ stats.grammarian_rank?.current?.icon || '📖' }}
                            </div>
                            <div>
                                <h3 class="text-gray-900 dark:text-white font-bold text-xl">
                                    {{ stats.grammarian_rank?.current?.name || 'Novice' }}
                                </h3>
                                <p class="text-emerald-600 dark:text-emerald-400 text-sm">
                                    {{ stats.grammarian_rank?.current?.name_uz || 'Yangi boshlovchi' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-gray-600 dark:text-gray-400 text-sm">Jami XP</div>
                            <div class="text-gray-900 dark:text-white text-2xl font-bold">{{ stats.total_xp || 0 }}</div>
                        </div>
                    </div>

                    <!-- Progress to next rank -->
                    <div v-if="stats.grammarian_rank?.next" class="mt-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Keyingi daraja: {{ stats.grammarian_rank.next.name_uz }}</span>
                            <span class="text-gray-900 dark:text-white">{{ stats.grammarian_rank.xp_to_next }} XP kerak</span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-400 to-teal-400 rounded-full transition-all duration-500"
                                 :style="{ width: `${stats.grammarian_rank.progress}%` }"></div>
                        </div>
                    </div>
                    <div v-else class="mt-4 text-center text-emerald-600 dark:text-emerald-400">
                        Maksimal darajaga erishdingiz!
                    </div>
                </div>
            </div>

            <!-- Levels Grid -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <BookOpenIcon class="w-6 h-6" />
                    Bosqichlar
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div v-for="(level, index) in levels" :key="level.id"
                         class="relative rounded-xl overflow-hidden transition-all duration-300"
                         :class="[
                             level.is_unlocked
                                 ? 'cursor-pointer hover:scale-105 hover:shadow-2xl'
                                 : 'cursor-not-allowed'
                         ]"
                         @click="level.is_unlocked && selectLevel(level)">

                        <!-- Unlocked Level Card -->
                        <div
                            v-if="level.is_unlocked"
                            class="p-4 min-h-[200px] flex flex-col justify-center relative bg-gradient-to-br"
                            :class="getLevelGradient(index)"
                        >
                            <!-- Premium Badge -->
                            <div v-if="level.is_premium" class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 rounded-full p-1">
                                <SparklesIcon class="w-5 h-5" />
                            </div>

                            <!-- Level Info -->
                            <div class="text-center">
                                <span class="bg-white/30 text-white px-2 py-1 rounded text-xs font-medium">
                                    {{ level.cefr_level }}
                                </span>

                                <h3 class="text-white font-bold text-lg mt-3 mb-1">{{ level.name }}</h3>
                                <p class="text-white/90 text-sm mb-3">{{ level.name_uz }}</p>

                                <!-- Stars -->
                                <div class="flex items-center justify-center space-x-1 mb-3">
                                    <StarIcon v-for="star in 3" :key="star"
                                             class="w-5 h-5"
                                             :class="star <= level.stars ? 'text-yellow-400 fill-yellow-400' : 'text-white/40'" />
                                </div>

                                <!-- Level info -->
                                <div class="flex items-center justify-center space-x-4 text-sm text-white/90">
                                    <span>{{ level.questions_count }} savol</span>
                                    <span>+{{ level.xp_reward }} XP</span>
                                </div>

                                <!-- Categories -->
                                <div class="mt-3 flex flex-wrap gap-1 justify-center">
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

                        <!-- Locked Level Card -->
                        <div
                            v-else
                            class="p-4 min-h-[200px] flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 border-2 border-gray-200 dark:border-gray-600 border-dashed"
                        >
                            <div class="text-center">
                                <LockClosedIcon class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Oldingi bosqichni</p>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">tugatish kerak</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories & Quiz Types Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Categories Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-gray-900 dark:text-white font-bold mb-4 flex items-center">
                        <TagIcon class="w-5 h-5 mr-2" />
                        Kategoriyalar
                    </h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div v-for="category in categories" :key="category.id"
                             class="flex items-center p-2 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <span class="text-2xl mr-3">{{ category.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-gray-900 dark:text-white text-sm font-medium truncate">{{ category.name }}</div>
                                <div class="text-gray-600 dark:text-gray-400 text-xs truncate">{{ category.name_uz }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quiz Types Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-gray-900 dark:text-white font-bold mb-4 flex items-center">
                        <PuzzlePieceIcon class="w-5 h-5 mr-2" />
                        Savol turlari
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(type, key) in quizTypes" :key="key"
                             class="flex items-center space-x-3">
                            <span class="text-2xl">{{ type.icon }}</span>
                            <div>
                                <div class="text-gray-900 dark:text-white text-sm">{{ type.name }}</div>
                                <div class="text-gray-600 dark:text-gray-400 text-xs">{{ type.point_multiplier }}x ball</div>
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

            <!-- Level Selection Modal -->
            <Teleport to="body">
                <div v-if="selectedLevel"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="selectedLevel = null">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 animate-scale-in">
                        <div class="text-center mb-6">
                            <div class="text-4xl mb-3">📝</div>
                            <h2 class="text-gray-900 dark:text-white text-2xl font-bold">{{ selectedLevel.name }}</h2>
                            <p class="text-gray-600 dark:text-gray-400">{{ selectedLevel.name_uz }}</p>
                            <span class="inline-block mt-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-3 py-1 rounded-full text-sm">
                                {{ selectedLevel.cefr_level }} - {{ selectedLevel.difficulty }}
                            </span>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Savollar soni</span>
                                <span class="text-gray-900 dark:text-white">{{ selectedLevel.questions_count }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Vaqt limiti</span>
                                <span class="text-gray-900 dark:text-white">{{ Math.floor(selectedLevel.time_limit / 60) }} daqiqa</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">XP mukofot</span>
                                <span class="text-gray-900 dark:text-white">+{{ selectedLevel.xp_reward }} XP</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Tanga mukofot</span>
                                <span class="text-yellow-600 dark:text-yellow-400">{{ selectedLevel.coin_reward }}</span>
                            </div>
                        </div>

                        <!-- Categories in level -->
                        <div class="mb-6">
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Kategoriyalar:</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="cat in selectedLevel.categories" :key="cat"
                                      class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                    {{ getCategoryName(cat) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button @click="selectedLevel = null"
                                    class="flex-1 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl transition-colors">
                                Bekor qilish
                            </button>
                            <Link :href="route('student.english.games.grammar-quiz.play', selectedLevel.level_number)"
                                  class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl transition-colors text-center font-semibold">
                                Boshlash
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
                        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-5 rounded-t-2xl">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">📝</span>
                                <div>
                                    <h2 class="text-xl font-black text-white">Grammar Quiz</h2>
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
                                    Grammatika savollariga to'g'ri javob bering! Qanchalik ko'p to'g'ri javob bersangiz, shunchalik ko'p XP va tanga olasiz.
                                </p>
                            </div>

                            <!-- Game Modes -->
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2 text-sm">
                                    <span>🎮</span> Savol Turlari
                                </h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <div
                                        v-for="mode in gameModes"
                                        :key="mode.name"
                                        class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5"
                                    >
                                        <span class="text-xl">{{ mode.icon }}</span>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white text-xs">{{ mode.name }}</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-[10px]">{{ mode.desc }}</p>
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
                                    <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-emerald-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">1</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Savolni diqqat bilan o'qing</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-emerald-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">2</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">To'g'ri javobni tanlang yoki yozing</p>
                                    </div>
                                    <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-2.5">
                                        <div class="w-6 h-6 bg-emerald-500 text-white text-xs font-bold rounded flex items-center justify-center flex-shrink-0">3</div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">Keyingi savolga o'ting</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Scoring -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                                <h3 class="font-bold text-yellow-800 dark:text-yellow-400 mb-2 flex items-center gap-2 text-sm">
                                    <span>⭐</span> Mukofotlar
                                </h3>
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">1 yulduz:</span>
                                        <span class="font-bold text-gray-600 dark:text-gray-400">60%+ to'g'ri</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">2 yulduz:</span>
                                        <span class="font-bold text-gray-600 dark:text-gray-400">80%+ to'g'ri</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white/50 dark:bg-black/20 rounded p-2">
                                        <span class="text-gray-700 dark:text-gray-300">3 yulduz:</span>
                                        <span class="font-bold text-green-600">95%+ to'g'ri</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Start Button -->
                            <button
                                @click="showTutorial = false"
                                class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm"
                            >
                                Tushundim!
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
