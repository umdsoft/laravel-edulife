<template>
    <Head :title="`True/False - ${level?.name || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Mode Selection Screen -->
            <div v-if="gameState === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-4xl w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 rounded-3xl mb-6 shadow-2xl shadow-emerald-500/30">
                            <span class="text-5xl filter drop-shadow-lg">✓✗</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">O'yin rejimini tanlang</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">Level {{ level?.level_number }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.statements_count }} savol</span>
                        </div>
                    </div>

                    <!-- Mode Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <button v-for="mode in gameModes" :key="mode.id"
                                @click="selectedMode = mode.id"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl transition-all duration-300 hover:scale-[1.03] shadow-lg hover:shadow-2xl border-2 overflow-hidden"
                                :class="selectedMode === mode.id
                                    ? 'border-emerald-400 dark:border-emerald-500 ring-4 ring-emerald-200 dark:ring-emerald-800'
                                    : 'border-gray-100 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-500'">

                            <div class="relative z-10 p-6">
                                <!-- Icon -->
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-4xl mb-4 mx-auto bg-gradient-to-br shadow-xl group-hover:scale-110 transition-transform duration-300"
                                     :class="selectedMode === mode.id
                                        ? 'from-emerald-500 to-teal-600'
                                        : 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600'">
                                    <span :class="selectedMode === mode.id ? '' : 'grayscale'">{{ mode.icon }}</span>
                                </div>

                                <!-- Title -->
                                <h3 class="text-base font-bold text-gray-800 dark:text-white mb-1 transition-colors">{{ mode.name }}</h3>

                                <!-- Bonus multiplier -->
                                <div v-if="mode.bonus_multiplier > 1" class="text-sm font-semibold"
                                     :class="selectedMode === mode.id ? 'text-yellow-500' : 'text-yellow-600 dark:text-yellow-500'">
                                    {{ mode.bonus_multiplier }}x
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Start Button -->
                    <div class="mt-10 text-center">
                        <button @click="startGame" class="inline-flex items-center gap-2 px-12 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xl font-bold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            <span>Boshlash</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-6 text-center">
                        <Link :href="route('student.english.games.true-false.index')" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else-if="gameState === 'playing'" class="max-w-4xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <Link :href="route('student.english.games.true-false.index')" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </Link>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-lg">
                            <span class="text-2xl">✓✗</span>
                            <span class="font-bold text-white">{{ level?.name }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">Level {{ level?.level_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-100 dark:border-emerald-800/50">
                                <span class="text-emerald-500">🏆</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ score }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ correctCount }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">To'g'ri</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800/50">
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ wrongCount }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Noto'g'ri</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div v-if="livesRemaining !== null" class="bg-gradient-to-br from-pink-50 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/20 rounded-xl p-4 border border-pink-100 dark:border-pink-800/50">
                            <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ livesRemaining }}<span class="text-2xl ml-1">❤️</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Hayot</div>
                        </div>
                        <div v-else :class="[
                            'rounded-xl p-4 border',
                            timeLimit && timeLeft < 10
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 border-cyan-100 dark:border-cyan-800/50'
                        ]">
                            <div :class="timeLimit && timeLeft < 10 ? 'text-red-600 dark:text-red-400' : 'text-cyan-600 dark:text-cyan-400'" class="text-3xl font-bold">
                                {{ timeLimit ? `${timeLeft}s` : '∞' }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentIndex + 1 }}/{{ totalStatements }} savol</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: ((currentIndex / totalStatements) * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Statement Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700 mb-5">
                    <div class="text-center mb-8">
                        <p class="text-gray-900 dark:text-white text-2xl md:text-3xl font-semibold leading-relaxed mb-4">
                            {{ currentStatement?.statement }}
                        </p>
                        <p v-if="currentStatement?.statement_uz" class="text-gray-600 dark:text-gray-400 text-base">
                            {{ currentStatement.statement_uz }}
                        </p>
                    </div>

                    <!-- Answer Buttons -->
                    <div class="flex justify-center gap-6 max-w-xl mx-auto">
                        <button
                            @click="submitAnswer(true)"
                            :disabled="isAnswering"
                            class="flex-1 py-8 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-2xl hover:opacity-90 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl border-2 border-green-400"
                        >
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-2xl font-bold">TRUE</span>
                        </button>
                        <button
                            @click="submitAnswer(false)"
                            :disabled="isAnswering"
                            class="flex-1 py-8 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-2xl hover:opacity-90 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl border-2 border-red-400"
                        >
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-2xl font-bold">FALSE</span>
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="availablePowerups.length > 0" class="flex justify-center gap-3 mb-5">
                    <button
                        v-for="powerup in availablePowerups"
                        :key="powerup.id"
                        class="px-6 py-3 bg-white dark:bg-gray-800 rounded-xl flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg border border-gray-200 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-500 hover:scale-105"
                        :disabled="isPowerupUsed(powerup.id)"
                        @click="usePowerup(powerup.id)"
                    >
                        <span class="text-2xl">{{ powerup.icon }}</span>
                        <span class="text-gray-700 dark:text-gray-300 font-semibold">{{ powerup.name }}</span>
                    </button>
                </div>

                <!-- Hint Display -->
                <div v-if="currentHint" class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-2xl p-6 text-center shadow-xl border-2 border-yellow-200 dark:border-yellow-800/50">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <span class="text-3xl">💡</span>
                        <span class="text-yellow-700 dark:text-yellow-400 font-bold text-lg">Maslahat</span>
                    </div>
                    <p class="text-yellow-800 dark:text-yellow-300 font-medium text-lg">{{ currentHint }}</p>
                </div>
            </div>

            <!-- Result State -->
            <div v-else-if="gameState === 'result'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-10 max-w-2xl w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <div
                        class="w-32 h-32 mx-auto mb-6 rounded-full flex items-center justify-center text-6xl shadow-2xl"
                        :class="lastResult?.is_correct
                            ? 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30'
                            : 'bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30'"
                    >
                        {{ lastResult?.is_correct ? '✓' : '✗' }}
                    </div>
                    <h2
                        class="text-4xl font-bold mb-4"
                        :class="lastResult?.is_correct ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                    >
                        {{ lastResult?.is_correct ? 'To\'g\'ri!' : 'Noto\'g\'ri' }}
                    </h2>

                    <div class="mb-8">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-6 mb-4">
                            <p class="text-gray-700 dark:text-gray-300 text-lg mb-3 font-medium">
                                To'g'ri javob:
                            </p>
                            <p class="font-bold text-2xl" :class="lastResult?.correct_answer ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                {{ lastResult?.correct_answer ? 'TRUE' : 'FALSE' }}
                            </p>
                        </div>
                        <div v-if="lastResult?.explanation" class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 border border-blue-100 dark:border-blue-800/50">
                            <div class="flex items-center justify-center gap-2 mb-3">
                                <span class="text-2xl">ℹ️</span>
                                <span class="text-blue-700 dark:text-blue-400 font-bold">Tushuntirish</span>
                            </div>
                            <p class="text-gray-800 dark:text-gray-200 text-base leading-relaxed">
                                {{ lastResult.explanation }}
                            </p>
                            <p v-if="lastResult?.explanation_uz" class="text-gray-600 dark:text-gray-400 text-sm mt-2">
                                {{ lastResult.explanation_uz }}
                            </p>
                        </div>
                    </div>

                    <!-- Points Earned -->
                    <div v-if="lastResult?.points_earned > 0" class="mb-8">
                        <div class="inline-flex items-center gap-3 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 px-8 py-4 rounded-2xl border-2 border-amber-200 dark:border-amber-800/50">
                            <span class="text-3xl">🏆</span>
                            <span class="text-amber-600 dark:text-amber-400 text-2xl font-bold">+{{ lastResult.points_earned }} ball</span>
                        </div>
                    </div>

                    <button
                        @click="nextStatement"
                        class="px-12 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xl font-bold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105"
                    >
                        {{ lastResult?.round_complete || lastResult?.game_over ? 'Natijalarni ko\'rish' : 'Keyingi →' }}
                    </button>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameState === 'complete'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ finalResult?.is_perfect ? '🏆' : finalResult?.stars === 3 ? '🎉' : finalResult?.stars === 2 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= (finalResult?.stars || 0) ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ finalResult?.game_over ? 'O\'yin tugadi!' : finalResult?.is_perfect ? 'Mukammal!' : 'Tabriklaymiz!' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ finalResult?.accuracy || 0 }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-5 border border-emerald-100 dark:border-emerald-800/50">
                            <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ finalResult?.total_score || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ finalResult?.xp_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ finalResult?.coins_earned || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ finalResult?.best_streak || 0 }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Score Breakdown -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-5 mb-8">
                        <div class="flex justify-center gap-6 text-base">
                            <div class="flex items-center gap-2">
                                <span class="text-green-600 dark:text-green-400 font-bold text-xl">{{ finalResult?.correct_count || 0 }}</span>
                                <span class="text-gray-700 dark:text-gray-300">to'g'ri</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-red-600 dark:text-red-400 font-bold text-xl">{{ finalResult?.wrong_count || 0 }}</span>
                                <span class="text-gray-700 dark:text-gray-300">noto'g'ri</span>
                            </div>
                        </div>
                    </div>

                    <!-- New Achievements -->
                    <div v-if="finalResult?.new_achievements?.length" class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Yangi yutuqlar!</h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <div
                                v-for="achievement in finalResult.new_achievements"
                                :key="achievement.id"
                                class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl px-4 py-3 flex items-center gap-2 shadow-lg border-2 border-yellow-200 dark:border-yellow-800/50"
                            >
                                <span class="text-2xl">{{ achievement.icon }}</span>
                                <span class="text-yellow-700 dark:text-yellow-400 font-semibold">{{ achievement.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Qayta o'ynash
                        </button>
                        <Link :href="route('student.english.games.true-false.index')" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105 inline-flex items-center justify-center">
                            Darajalar
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import axios from 'axios';

const props = defineProps({
    level: {
        type: Object,
        required: true
    },
    config: {
        type: Object,
        default: () => ({})
    },
    gameModes: {
        type: Array,
        default: () => []
    },
    powerups: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    }
});

// Game state
const gameState = ref('idle');
const sessionId = ref(null);
const score = ref(0);
const streak = ref(0);
const correctCount = ref(0);
const wrongCount = ref(0);
const currentIndex = ref(0);
const totalStatements = ref(0);

// Mode selection
const selectedMode = ref('classic');

// Current statement
const currentStatement = ref(null);
const isAnswering = ref(false);
const lastResult = ref(null);
const finalResult = ref(null);

// Powerups
const usedPowerups = ref([]);
const currentHint = ref(null);

// Timer
const timeLimit = ref(null);
const timeLeft = ref(0);
let timer = null;
const answerStartTime = ref(0);

// Survival mode lives
const livesRemaining = ref(null);

const availablePowerups = computed(() => {
    return props.powerups.filter(p => {
        if (p.id === 'extra_time' && !timeLimit.value) return false;
        if (p.id === 'extra_life' && selectedMode.value !== 'survival') return false;
        return true;
    });
});

function isPowerupUsed(powerupId) {
    const maxUses = props.powerups.find(p => p.id === powerupId)?.uses_per_game || 1;
    const usedCount = usedPowerups.value.filter(id => id === powerupId).length;
    return usedCount >= maxUses;
}

// Start game
async function startGame() {
    try {
        const response = await axios.post(
            route('student.english.games.true-false.start', { level: props.level.level_number }),
            { mode: selectedMode.value }
        );

        if (response.data.success) {
            const data = response.data.data;
            sessionId.value = data.session_id;
            totalStatements.value = data.total_statements;
            currentStatement.value = data.current_statement;
            currentIndex.value = data.current_index;

            // Set mode-specific settings
            const modeConfig = data.mode_config || {};
            timeLimit.value = modeConfig.time_per_statement || props.level.time_limit || null;

            if (selectedMode.value === 'survival') {
                livesRemaining.value = modeConfig.max_mistakes || 3;
            }

            score.value = 0;
            streak.value = 0;
            correctCount.value = 0;
            wrongCount.value = 0;
            usedPowerups.value = [];
            currentHint.value = null;

            gameState.value = 'playing';
            startTimer();
        }
    } catch (error) {
        console.error('Failed to start game:', error);
    }
}

// Timer functions
function startTimer() {
    if (!timeLimit.value) {
        answerStartTime.value = Date.now();
        return;
    }

    timeLeft.value = timeLimit.value;
    answerStartTime.value = Date.now();

    timer = setInterval(() => {
        timeLeft.value--;
        if (timeLeft.value <= 0) {
            clearInterval(timer);
            submitAnswer(null); // Time's up - wrong answer
        }
    }, 1000);
}

function stopTimer() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

// Submit answer
async function submitAnswer(answer) {
    if (isAnswering.value) return;

    stopTimer();
    isAnswering.value = true;
    currentHint.value = null;

    const answerTime = (Date.now() - answerStartTime.value) / 1000;

    // If answer is null (timeout), treat as wrong
    const userAnswer = answer !== null ? answer : !currentStatement.value?.answer;

    try {
        const response = await axios.post(
            route('student.english.games.true-false.check'),
            {
                session_id: sessionId.value,
                statement_id: currentStatement.value.id,
                answer: userAnswer,
                time: answerTime
            }
        );

        if (response.data.success) {
            lastResult.value = response.data.data;
            score.value = response.data.data.total_score;
            streak.value = response.data.data.streak;
            correctCount.value = response.data.data.correct_count;
            wrongCount.value = response.data.data.wrong_count;

            // Update lives for survival mode
            if (selectedMode.value === 'survival' && !lastResult.value.is_correct) {
                livesRemaining.value = Math.max(0, livesRemaining.value - 1);
            }

            gameState.value = 'result';
        }
    } catch (error) {
        console.error('Failed to check answer:', error);
    } finally {
        isAnswering.value = false;
    }
}

// Next statement
async function nextStatement() {
    if (lastResult.value?.round_complete || lastResult.value?.game_over) {
        completeGame();
        return;
    }

    currentStatement.value = lastResult.value.next_statement;
    currentIndex.value = lastResult.value.current_index;
    lastResult.value = null;
    currentHint.value = null;

    gameState.value = 'playing';
    startTimer();
}

// Complete game
async function completeGame() {
    try {
        const response = await axios.post(
            route('student.english.games.true-false.complete'),
            { session_id: sessionId.value }
        );

        if (response.data.success) {
            finalResult.value = response.data.data;
            finalResult.value.game_over = lastResult.value?.game_over || false;
            gameState.value = 'complete';
        }
    } catch (error) {
        console.error('Failed to complete game:', error);
    }
}

// Use powerup
async function usePowerup(powerupId) {
    try {
        const response = await axios.post(
            route('student.english.games.true-false.powerup'),
            {
                session_id: sessionId.value,
                powerup_id: powerupId
            }
        );

        if (response.data.success) {
            usedPowerups.value.push(powerupId);
            const result = response.data.data;

            if (result.hint) {
                currentHint.value = result.hint;
            }
            if (result.extra_time) {
                timeLeft.value += result.extra_time;
            }
            if (result.next_statement) {
                currentStatement.value = result.next_statement;
                currentIndex.value = result.current_index;
                currentHint.value = null;
                stopTimer();
                startTimer();
            }
            if (result.round_complete) {
                completeGame();
            }
            if (result.extra_lives !== undefined) {
                livesRemaining.value = result.extra_lives + (props.gameModes.find(m => m.id === 'survival')?.max_mistakes || 3);
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error);
    }
}

// Restart game
function restartGame() {
    gameState.value = 'idle';
    sessionId.value = null;
    score.value = 0;
    streak.value = 0;
    correctCount.value = 0;
    wrongCount.value = 0;
    currentIndex.value = 0;
    currentStatement.value = null;
    lastResult.value = null;
    finalResult.value = null;
    usedPowerups.value = [];
    currentHint.value = null;
    livesRemaining.value = null;
}

// Cleanup
onUnmounted(() => {
    stopTimer();
});
</script>
