<template>
    <Head :title="`Article Master - ${level?.name || 'Game'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Idle State -->
            <div v-if="gameState === 'idle'" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-2xl w-full px-4">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 rounded-3xl mb-6 shadow-2xl shadow-amber-500/30">
                            <span class="text-5xl filter drop-shadow-lg">{{ config.icon || '📝' }}</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level.name_uz }}</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl border border-gray-100 dark:border-gray-700">
                        <p class="text-gray-600 dark:text-gray-300 mb-6 text-center">{{ level.description }}</p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4 mb-8">
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ level.question_count }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Savol</div>
                            </div>
                            <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                                <div class="text-3xl font-bold text-violet-600 dark:text-violet-400 capitalize">{{ getDifficultyLabel(level.difficulty) }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Daraja</div>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ level.time_limit ? formatTime(level.time_limit) : '∞' }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Vaqt</div>
                            </div>
                        </div>

                        <button @click="startGame" class="w-full py-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-bold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            O'yinni boshlash
                        </button>
                    </div>

                    <div class="mt-10 text-center">
                        <Link :href="route('student.english.games.article-master.index')" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameState === 'complete'" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="mb-4">
                        <span class="text-7xl">{{ summary.stars === 3 ? '🏆' : summary.stars === 2 ? '🎉' : summary.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= summary.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ summary.stars === 3 ? 'Mukammal!' : summary.stars === 2 ? 'Ajoyib!' : summary.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ summary.correct_answers }}/{{ summary.total_questions }} to'g'ri javob</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ summary.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ summary.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ summary.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-2xl p-5 border border-green-100 dark:border-green-800/50">
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ summary.correct_answers }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">To'g'ri</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link :href="route('student.english.games.article-master.index')" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </Link>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else class="max-w-6xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <Link :href="route('student.english.games.article-master.index')" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </Link>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl shadow-lg">
                            <span class="text-2xl">{{ config.icon || '📝' }}</span>
                            <span class="font-bold text-white">{{ level.name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-100 dark:border-purple-800/50">
                                <span class="text-purple-600 dark:text-purple-400 font-bold">{{ score }} ball</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ currentIndex + 1 }}/{{ totalQuestions }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savol</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div v-if="timeLimit" :class="[
                            'rounded-xl p-4 border',
                            timeRemaining < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="timeRemaining < 30 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ formatTime(timeRemaining) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                        <div v-else class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">∞</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ currentIndex }}/{{ totalQuestions }}</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: `${((currentIndex) / totalQuestions) * 100}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Question Card -->
                <div v-if="currentQuestion" class="bg-white dark:bg-gray-800 rounded-2xl p-6 md:p-8 shadow-xl border border-gray-100 dark:border-gray-700 mb-5">
                    <!-- Question Type Badge -->
                    <div class="flex items-center justify-between mb-6">
                        <span class="px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-sm font-medium">
                            {{ getQuestionTypeLabel(currentQuestion.type) }}
                        </span>
                        <span v-if="showFeedback" :class="lastAnswerCorrect ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="font-bold text-lg">
                            {{ lastAnswerCorrect ? "To'g'ri! +" + lastPointsEarned : "Noto'g'ri" }}
                        </span>
                    </div>

                    <!-- Sentence -->
                    <div class="text-2xl md:text-3xl text-gray-900 dark:text-white mb-4 text-center leading-relaxed font-bold">
                        {{ currentQuestion.sentence }}
                    </div>

                    <!-- Translation -->
                    <div class="text-gray-600 dark:text-gray-300 text-center mb-8 text-lg">
                        {{ currentQuestion.translation }}
                    </div>

                    <!-- Options -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <button v-for="option in currentQuestion.options" :key="option"
                                @click="selectAnswer(option)"
                                :disabled="showFeedback || removedOptions.includes(option)"
                                :class="[
                                    'p-5 rounded-xl font-bold text-lg transition-all shadow-lg border-2',
                                    removedOptions.includes(option)
                                        ? 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed line-through border-gray-300 dark:border-gray-600'
                                        : selectedAnswer === option
                                            ? showFeedback
                                                ? lastAnswerCorrect
                                                    ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400'
                                                    : 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 border-red-400 animate-shake'
                                                : 'bg-gradient-to-r from-amber-500 to-orange-500 text-white border-transparent shadow-amber-500/30'
                                            : showFeedback && option === correctAnswer
                                                ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400'
                                                : 'bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white hover:bg-amber-50 dark:hover:bg-amber-900/20 border-gray-200 dark:border-gray-600 hover:border-amber-400 hover:scale-[1.02]'
                                ]">
                            {{ option === '-' ? 'artikl yo\'q' : option }}
                        </button>
                    </div>

                    <!-- Rule Explanation (shown after answer) -->
                    <div v-if="showFeedback && currentRule" class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-100 dark:border-blue-800/50 rounded-xl p-5 mb-6">
                        <div class="text-blue-700 dark:text-blue-400 font-bold mb-2">Qoida:</div>
                        <div class="text-gray-900 dark:text-white font-medium mb-1">{{ currentRule }}</div>
                        <div v-if="currentRuleUz" class="text-gray-700 dark:text-gray-300">{{ currentRuleUz }}</div>
                    </div>

                    <!-- Next Button -->
                    <div v-if="showFeedback" class="text-center">
                        <button @click="nextQuestion" class="px-10 py-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            {{ currentIndex + 1 >= totalQuestions ? 'Yakunlash' : 'Keyingi savol →' }}
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex flex-wrap justify-center gap-3">
                        <button v-for="powerup in powerups" :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="getPowerupUsesRemaining(powerup.id) <= 0 || showFeedback"
                                class="px-5 py-3 bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700 dark:to-gray-600 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-white transition-all hover:from-slate-100 hover:to-slate-200 dark:hover:from-gray-600 dark:hover:to-gray-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-md hover:shadow-lg hover:scale-105">
                            <span class="text-xl">{{ powerup.icon }}</span>
                            <span class="text-sm font-bold">{{ powerup.name_uz }}</span>
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-bold px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 rounded-full">({{ getPowerupUsesRemaining(powerup.id) }})</span>
                        </button>
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
    level: { type: Object, required: true },
    config: { type: Object, default: () => ({}) },
    powerups: { type: Array, default: () => [] },
});

const gameState = ref('idle');
const sessionId = ref(null);
const questions = ref([]);
const currentIndex = ref(0);
const score = ref(0);
const streak = ref(0);
const totalQuestions = ref(0);
const timeLimit = ref(null);
const timeRemaining = ref(null);
const timerInterval = ref(null);
const summary = ref(null);
const powerupsUsed = ref({});

const selectedAnswer = ref(null);
const showFeedback = ref(false);
const lastAnswerCorrect = ref(false);
const lastPointsEarned = ref(0);
const correctAnswer = ref(null);
const currentRule = ref(null);
const currentRuleUz = ref(null);
const removedOptions = ref([]);
const questionStartTime = ref(null);

const currentQuestion = computed(() => questions.value[currentIndex.value]);

const startGame = async () => {
    try {
        const response = await axios.post(route('student.english.games.article-master.start-session', { level: props.level.level_number }));
        if (response.data.success) {
            const data = response.data.data;
            sessionId.value = data.session_id;
            questions.value = data.questions;
            totalQuestions.value = data.question_count;
            timeLimit.value = data.time_limit;
            timeRemaining.value = data.time_limit;
            gameState.value = 'playing';
            questionStartTime.value = Date.now();

            if (timeLimit.value) startTimer();
        }
    } catch (error) {
        console.error('Failed to start game:', error);
    }
};

const startTimer = () => {
    timerInterval.value = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;
        } else {
            clearInterval(timerInterval.value);
            endGame();
        }
    }, 1000);
};

const selectAnswer = async (answer) => {
    if (showFeedback.value) return;

    selectedAnswer.value = answer;
    const timeSpent = (Date.now() - questionStartTime.value) / 1000;

    try {
        const response = await axios.post(route('student.english.games.article-master.submit-answer'), {
            session_id: sessionId.value,
            question_index: currentIndex.value,
            answer: answer,
            time_spent: timeSpent,
        });

        if (response.data.success) {
            const data = response.data.data;
            lastAnswerCorrect.value = data.correct;
            lastPointsEarned.value = data.points_earned;
            correctAnswer.value = data.correct_answer;
            currentRule.value = data.rule;
            currentRuleUz.value = data.rule_uz;
            score.value = data.score;
            streak.value = data.streak;
            showFeedback.value = true;

            if (data.is_complete) {
                summary.value = data.summary;
            }
        }
    } catch (error) {
        console.error('Failed to submit answer:', error);
    }
};

const nextQuestion = () => {
    if (summary.value) {
        endGame();
        return;
    }

    currentIndex.value++;
    selectedAnswer.value = null;
    showFeedback.value = false;
    lastAnswerCorrect.value = false;
    correctAnswer.value = null;
    currentRule.value = null;
    currentRuleUz.value = null;
    removedOptions.value = [];
    questionStartTime.value = Date.now();
};

const usePowerup = async (powerupId) => {
    try {
        const response = await axios.post(route('student.english.games.article-master.use-powerup'), {
            session_id: sessionId.value,
            powerup_id: powerupId,
        });

        if (response.data.success) {
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1;
            const data = response.data.data;

            if (powerupId === 'hint' && data.hint) {
                alert(`Maslahat: ${data.hint}`);
            } else if (powerupId === 'fifty_fifty' && data.removed_options) {
                removedOptions.value = data.removed_options;
            } else if (powerupId === 'skip' && data.skipped) {
                currentIndex.value = data.current_index;
                selectedAnswer.value = null;
                showFeedback.value = false;
                removedOptions.value = [];
                questionStartTime.value = Date.now();

                if (data.is_complete) {
                    summary.value = data.summary;
                    endGame();
                }
            } else if (powerupId === 'extra_time' && data.time_bonus) {
                timeRemaining.value = data.time_remaining;
            } else if (powerupId === 'show_rule' && data.rule) {
                alert(`Qoida: ${data.rule}\n${data.rule_uz || ''}`);
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error);
    }
};

const getPowerupUsesRemaining = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId);
    if (!powerup) return 0;
    return (powerup.uses_per_game || 1) - (powerupsUsed.value[powerupId] || 0);
};

const endGame = async () => {
    if (timerInterval.value) clearInterval(timerInterval.value);

    if (!summary.value) {
        try {
            const response = await axios.post(route('student.english.games.article-master.complete-session'), {
                session_id: sessionId.value,
            });
            if (response.data.success) {
                summary.value = response.data.data;
            }
        } catch (error) {
            console.error('Failed to complete session:', error);
        }
    }

    gameState.value = 'complete';
};

const restartGame = () => {
    gameState.value = 'idle';
    sessionId.value = null;
    questions.value = [];
    currentIndex.value = 0;
    score.value = 0;
    streak.value = 0;
    timeRemaining.value = null;
    summary.value = null;
    powerupsUsed.value = {};
    selectedAnswer.value = null;
    showFeedback.value = false;
    removedOptions.value = [];
};

const formatTime = (seconds) => {
    if (seconds === null) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const getQuestionTypeLabel = (type) => {
    const labels = {
        fill_blank: "Bo'sh joyni to'ldiring",
        choose_correct: "To'g'ri javobni tanlang",
        error_correction: "Xatoni tuzating",
        true_false: "To'g'ri yoki noto'g'ri",
    };
    return labels[type] || type;
};

const getDifficultyLabel = (difficulty) => {
    const labels = {
        beginner: "Boshlang'ich",
        elementary: 'Oddiy',
        intermediate: "O'rta",
        advanced: 'Yuqori',
    };
    return labels[difficulty] || difficulty;
};

onUnmounted(() => {
    if (timerInterval.value) clearInterval(timerInterval.value);
});
</script>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.animate-shake {
    animation: shake 0.3s ease-in-out;
}
</style>
