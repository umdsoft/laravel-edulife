<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    olympiad: Object,
    leaderboard: Object,
    userRank: Object,
    statistics: Object,
});

const currentPage = ref(1);

const getRankEmoji = (rank) => {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return '';
};

const getRankClass = (rank) => {
    if (rank === 1) return 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white';
    if (rank === 2) return 'bg-gradient-to-r from-gray-300 to-gray-400 text-white';
    if (rank === 3) return 'bg-gradient-to-r from-orange-400 to-orange-500 text-white';
    return 'bg-gray-100 dark:bg-gray-700';
};
</script>

<template>
    <StudentLayout>
        <Head :title="`Reyting - ${olympiad.title}`" />
        
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link 
                        :href="route('student.olympiads.results', olympiad.slug)"
                        class="text-purple-600 hover:underline text-sm mb-2 inline-block">
                        ← Natijalarga qaytish
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        🏆 {{ olympiad.title }} - Reyting
                    </h1>
                </div>

                <!-- Statistics -->
                <div class="grid sm:grid-cols-3 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ statistics.total_participants }}</div>
                        <div class="text-sm text-gray-500">Ishtirokchilar</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ statistics.average_score }}%</div>
                        <div class="text-sm text-gray-500">O'rtacha ball</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ statistics.highest_score }}%</div>
                        <div class="text-sm text-gray-500">Eng yuqori ball</div>
                    </div>
                </div>

                <!-- Your Position (sticky) -->
                <div v-if="userRank.rank" class="sticky top-20 z-10 mb-6">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center font-bold text-xl">
                                    #{{ userRank.rank }}
                                </div>
                                <div>
                                    <div class="font-bold">Sizning o'rningiz</div>
                                    <div class="text-sm text-white/80">Top {{ userRank.percentile }}%</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold">{{ userRank.score }}</div>
                                <div class="text-sm text-white/80">{{ userRank.score_percent }}%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <!-- Top 3 Podium -->
                    <div v-if="currentPage === 1" class="p-6 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 grid sm:grid-cols-3 gap-4">
                        <template v-for="(entry, index) in leaderboard.data.slice(0, 3)" :key="entry.rank">
                            <div 
                                :class="[
                                    'text-center p-4 rounded-xl',
                                    getRankClass(entry.rank)
                                ]"
                                :style="{ order: index === 0 ? 1 : index === 1 ? 0 : 2 }">
                                <div class="text-4xl mb-2">{{ getRankEmoji(entry.rank) }}</div>
                                <div class="font-bold" :class="entry.rank <= 3 ? 'text-white' : 'text-gray-900 dark:text-white'">
                                    {{ entry.user_name }}
                                </div>
                                <div :class="entry.rank <= 3 ? 'text-white/80' : 'text-gray-500'" class="text-sm">
                                    {{ entry.score }} ball
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Full List -->
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="entry in leaderboard.data" 
                            :key="entry.rank"
                            :class="[
                                'flex items-center gap-4 p-4 transition-colors',
                                entry.is_current_user 
                                    ? 'bg-purple-50 dark:bg-purple-900/20' 
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                            ]">
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                                :class="entry.rank <= 3 ? getRankClass(entry.rank) : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">
                                {{ entry.rank <= 3 ? getRankEmoji(entry.rank) : entry.rank }}
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ entry.user_name }}
                                    <span v-if="entry.is_current_user" class="ml-2 text-xs text-purple-600">(Siz)</span>
                                </div>
                                <div v-if="entry.region" class="text-xs text-gray-500">{{ entry.region }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900 dark:text-white">{{ entry.score }}</div>
                                <div class="text-xs text-gray-500">{{ entry.score_percent }}%</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="leaderboard.last_page > 1" class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-center gap-2">
                            <button 
                                v-for="page in leaderboard.last_page" 
                                :key="page"
                                @click="currentPage = page; $inertia.visit(route('student.olympiads.results.leaderboard', olympiad.slug), { data: { page } })"
                                :class="[
                                    'w-10 h-10 rounded-lg font-medium transition-colors',
                                    page === leaderboard.current_page 
                                        ? 'bg-purple-600 text-white' 
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                                ]">
                                {{ page }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
