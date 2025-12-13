<template>
    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Mode Selection Screen -->
            <div v-if="!gameStarted" class="min-h-screen flex items-center justify-center p-4">
                <div class="max-w-2xl w-full">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white text-4xl mb-4">
                            🔍
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name_uz }}</h1>
                        <p class="text-gray-500 dark:text-gray-400">{{ level?.description_uz }}</p>
                        <div class="flex items-center justify-center gap-2 mt-2">
                            <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-sm font-medium">
                                {{ level?.cefr_level }}
                            </span>
                            <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full text-sm font-medium">
                                {{ level?.sentences_count }} gap
                            </span>
                        </div>
                    </div>

                    <h2 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-4 text-center">O'yin rejimini tanlang</h2>

                    <div class="grid gap-4">
                        <button v-for="mode in Object.values(gameModes || {})" :key="mode.id"
                                @click="selectMode(mode.id)"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02] shadow-lg hover:shadow-2xl border-2 border-gray-100 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 overflow-hidden text-left">
                            <div class="flex items-center gap-5">
                                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-3xl text-white shadow-lg group-hover:scale-110 transition-transform">
                                    {{ mode.icon }}
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-1">{{ mode.name_uz }}</h3>
                                    <p class="text-gray-500 dark:text-gray-400">{{ mode.description_uz }}</p>
                                </div>
                                <div class="text-amber-500 group-hover:translate-x-1 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div class="mt-6 text-center">
                        <a href="/student/english/games/error-hunter" class="text-gray-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                            ← Orqaga qaytish
                        </a>
                    </div>
                </div>
            </div>

            <!-- Game Screen -->
            <div v-else-if="!gameCompleted" class="min-h-screen">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div class="max-w-4xl mx-auto flex items-center justify-between">
                        <button @click="confirmExit" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <div class="flex items-center gap-6">
                            <!-- Score -->
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🎯</span>
                                <span class="font-bold text-gray-800 dark:text-white">{{ score }}</span>
                            </div>

                            <!-- Streak -->
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🔥</span>
                                <span :class="['font-bold', streak > 0 ? 'text-orange-500' : 'text-gray-400']">{{ streak }}</span>
                            </div>

                            <!-- Timer -->
                            <div :class="['flex items-center gap-2', timeRemaining <= 30 ? 'text-red-500 animate-pulse' : '']">
                                <span class="text-xl">⏱️</span>
                                <span class="font-bold text-gray-800 dark:text-white">{{ formatTime(timeRemaining) }}</span>
                            </div>
                        </div>

                        <!-- Multiplier -->
                        <div v-if="streakMultiplier > 1" class="px-3 py-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-full text-sm font-bold">
                            x{{ streakMultiplier.toFixed(1) }}
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="max-w-4xl mx-auto mt-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ completedCount }}/{{ totalCount }}</span>
                            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-300"
                                     :style="{ width: `${(completedCount / totalCount) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Game Area -->
                <div class="max-w-4xl mx-auto px-4 py-8">
                    <!-- Error Type Badge -->
                    <div v-if="showHint && currentSentence" class="mb-4 flex justify-center">
                        <div class="px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full flex items-center gap-2">
                            <span>{{ getErrorTypeInfo(currentSentence.error_type)?.icon }}</span>
                            <span class="font-medium">{{ getErrorTypeInfo(currentSentence.error_type)?.name_uz }}</span>
                        </div>
                    </div>

                    <!-- Spot Error Mode -->
                    <div v-if="selectedMode === 'spot_error'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg">
                        <p class="text-center text-gray-500 dark:text-gray-400 mb-6">Xatoli so'zni bosing</p>
                        <div class="text-center leading-loose">
                            <span v-for="(word, index) in currentSentence?.words" :key="index"
                                  @click="!isCheckingAnswer && selectWord(index)"
                                  :class="[
                                      'inline-block mx-1 px-2 py-1 rounded-lg text-xl cursor-pointer transition-all',
                                      selectedWordIndex === index
                                          ? 'bg-amber-200 dark:bg-amber-700 text-amber-800 dark:text-amber-100 ring-2 ring-amber-400'
                                          : highlightedIndex === index
                                              ? 'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-100 animate-pulse'
                                              : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200'
                                  ]">
                                {{ word.word }}
                            </span>
                        </div>

                        <div class="mt-8 flex justify-center">
                            <button @click="submitSpotAnswer"
                                    :disabled="selectedWordIndex === null || isCheckingAnswer"
                                    :class="[
                                        'px-8 py-3 rounded-xl font-bold transition-all',
                                        selectedWordIndex !== null && !isCheckingAnswer
                                            ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:shadow-lg'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                    ]">
                                Tekshirish
                            </button>
                        </div>
                    </div>

                    <!-- Fix Error Mode -->
                    <div v-else-if="selectedMode === 'fix_error'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg">
                        <p class="text-center text-gray-500 dark:text-gray-400 mb-6">To'g'ri variantni tanlang</p>

                        <!-- Sentence with highlighted error -->
                        <div class="text-center leading-loose mb-8">
                            <span v-for="(word, index) in currentSentence?.words" :key="index"
                                  :class="[
                                      'inline-block mx-1 px-2 py-1 rounded-lg text-xl',
                                      word.is_error
                                          ? 'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-100 font-bold underline decoration-wavy decoration-red-500'
                                          : 'text-gray-800 dark:text-gray-200'
                                  ]">
                                {{ word.word }}
                            </span>
                        </div>

                        <!-- Options -->
                        <div class="grid grid-cols-2 gap-4 max-w-xl mx-auto">
                            <button v-for="(option, index) in currentSentence?.options" :key="index"
                                    @click="!isCheckingAnswer && selectFixOption(option)"
                                    :disabled="isCheckingAnswer"
                                    :class="[
                                        'p-4 rounded-xl font-bold text-lg transition-all border-2',
                                        selectedOption === option
                                            ? 'bg-amber-100 dark:bg-amber-900/30 border-amber-400 text-amber-700 dark:text-amber-300'
                                            : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-800 dark:text-gray-200 hover:border-amber-300 dark:hover:border-amber-600'
                                    ]">
                                {{ option }}
                            </button>
                        </div>

                        <div class="mt-8 flex justify-center">
                            <button @click="submitFixAnswer"
                                    :disabled="!selectedOption || isCheckingAnswer"
                                    :class="[
                                        'px-8 py-3 rounded-xl font-bold transition-all',
                                        selectedOption && !isCheckingAnswer
                                            ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:shadow-lg'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                    ]">
                                Tekshirish
                            </button>
                        </div>
                    </div>

                    <!-- Rewrite Mode -->
                    <div v-else-if="selectedMode === 'rewrite'" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg">
                        <p class="text-center text-gray-500 dark:text-gray-400 mb-6">Gapni to'g'ri shaklda yozing</p>

                        <!-- Sentence to fix -->
                        <div class="text-center mb-8">
                            <p class="text-xl text-gray-800 dark:text-gray-200 bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-800">
                                {{ currentSentence?.text }}
                            </p>
                        </div>

                        <!-- Input field -->
                        <div class="max-w-xl mx-auto">
                            <textarea v-model="userRewriteText"
                                      :disabled="isCheckingAnswer"
                                      rows="3"
                                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 focus:border-amber-400 dark:focus:border-amber-500 focus:ring-0 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-lg resize-none"
                                      placeholder="To'g'rilangan gapni yozing...">
                            </textarea>
                        </div>

                        <div class="mt-8 flex justify-center">
                            <button @click="submitRewriteAnswer"
                                    :disabled="!userRewriteText.trim() || isCheckingAnswer"
                                    :class="[
                                        'px-8 py-3 rounded-xl font-bold transition-all',
                                        userRewriteText.trim() && !isCheckingAnswer
                                            ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:shadow-lg'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                    ]">
                                Tekshirish
                            </button>
                        </div>
                    </div>

                    <!-- Powerups Bar -->
                    <div class="mt-6 flex justify-center gap-4">
                        <button v-for="powerup in Object.values(powerups || {})" :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="isPowerupUsed(powerup.id) || isCheckingAnswer"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-xl transition-all',
                                    isPowerupUsed(powerup.id) || isCheckingAnswer
                                        ? 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed opacity-50'
                                        : 'bg-white dark:bg-gray-800 hover:bg-amber-50 dark:hover:bg-amber-900/20 border border-gray-200 dark:border-gray-700 hover:border-amber-400 text-gray-700 dark:text-gray-300'
                                ]">
                            <span class="text-xl">{{ powerup.icon }}</span>
                            <span class="text-sm font-medium">{{ powerup.name_uz }}</span>
                        </button>
                    </div>

                    <!-- Feedback Message -->
                    <div v-if="feedbackMessage" class="mt-6">
                        <div :class="[
                            'text-center p-4 rounded-xl',
                            feedbackCorrect
                                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                        ]">
                            <p class="font-bold text-lg mb-2">{{ feedbackCorrect ? '✅ To\'g\'ri!' : '❌ Noto\'g\'ri!' }}</p>
                            <p v-if="feedbackExplanation">{{ feedbackExplanation }}</p>
                            <p v-if="feedbackCorrectText" class="mt-2 font-medium">
                                To'g'ri javob: <span class="text-green-600 dark:text-green-400">{{ feedbackCorrectText }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Screen -->
            <div v-else class="min-h-screen flex items-center justify-center p-4">
                <div class="max-w-lg w-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white p-6 text-center">
                        <div class="text-6xl mb-4">
                            {{ results?.stars === 3 ? '🏆' : results?.stars === 2 ? '⭐' : results?.stars === 1 ? '👍' : '😔' }}
                        </div>
                        <h2 class="text-2xl font-bold mb-2">
                            {{ results?.stars === 3 ? 'Mukammal!' : results?.stars === 2 ? 'Yaxshi!' : results?.stars === 1 ? 'Yomon emas!' : 'Qayta urinib ko\'ring' }}
                        </h2>
                        <div class="flex justify-center gap-2">
                            <span v-for="i in 3" :key="i" class="text-3xl">
                                {{ i <= (results?.stars || 0) ? '⭐' : '☆' }}
                            </span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Ball</span>
                            <span class="font-bold text-gray-800 dark:text-white">{{ results?.score || 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">XP</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400">+{{ results?.xp_earned || 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Tangalar</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400">+{{ results?.coins_earned || 0 }} 🪙</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Aniqlik</span>
                            <span class="font-bold text-gray-800 dark:text-white">{{ results?.accuracy || 0 }}%</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Eng yaxshi streak</span>
                            <span class="font-bold text-orange-600 dark:text-orange-400">{{ results?.best_streak || 0 }} 🔥</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600 dark:text-gray-400">Vaqt</span>
                            <span class="font-bold text-gray-800 dark:text-white">{{ results?.total_time || 0 }}s</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-6 pt-0 flex gap-4">
                        <a href="/student/english/games/error-hunter"
                           class="flex-1 py-3 px-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold text-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                            Ortga
                        </a>
                        <button @click="playAgain"
                                class="flex-1 py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-bold hover:shadow-lg transition-all">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Object,
    errorTypes: Object,
    powerups: Object,
});

// Game state
const gameStarted = ref(false);
const gameCompleted = ref(false);
const selectedMode = ref(null);
const sessionId = ref(null);

// Current sentence
const currentSentence = ref(null);
const totalCount = ref(0);
const completedCount = ref(0);

// Answers
const selectedWordIndex = ref(null);
const selectedOption = ref(null);
const userRewriteText = ref('');
const isCheckingAnswer = ref(false);

// Powerups
const usedPowerups = ref([]);
const highlightedIndex = ref(null);
const showHint = ref(false);

// Scoring
const score = ref(0);
const streak = ref(0);
const streakMultiplier = ref(1.0);

// Timer
const timeRemaining = ref(120);
const timerInterval = ref(null);
const startTime = ref(null);

// Results
const results = ref(null);

// Feedback
const feedbackMessage = ref('');
const feedbackCorrect = ref(false);
const feedbackExplanation = ref('');
const feedbackCorrectText = ref('');

const getErrorTypeInfo = (errorType) => {
    return props.errorTypes?.[errorType] || null;
};

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const selectMode = async (mode) => {
    selectedMode.value = mode;

    try {
        const response = await axios.post(`/student/english/games/error-hunter/start/${props.level.number}`, {
            game_mode: mode
        });

        if (response.data.success) {
            sessionId.value = response.data.session_id;
            currentSentence.value = response.data.current_sentence;
            totalCount.value = response.data.total_sentences;
            timeRemaining.value = response.data.time_limit;
            completedCount.value = 0;
            score.value = 0;
            streak.value = 0;
            streakMultiplier.value = 1.0;
            usedPowerups.value = [];
            gameStarted.value = true;

            startTimer();
        }
    } catch (error) {
        console.error('Failed to start session:', error);
    }
};

const startTimer = () => {
    startTime.value = Date.now();
    timerInterval.value = setInterval(() => {
        timeRemaining.value--;
        if (timeRemaining.value <= 0) {
            endGame();
        }
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
    }
};

const selectWord = (index) => {
    selectedWordIndex.value = index;
};

const selectFixOption = (option) => {
    selectedOption.value = option;
};

const getTimeSpent = () => {
    return startTime.value ? Math.floor((Date.now() - startTime.value) / 1000) : 0;
};

const submitSpotAnswer = async () => {
    if (selectedWordIndex.value === null || isCheckingAnswer.value) return;

    isCheckingAnswer.value = true;
    const timeSpent = getTimeSpent();

    try {
        const response = await axios.post('/student/english/games/error-hunter/check', {
            session_id: sessionId.value,
            sentence_id: currentSentence.value.id,
            answer: selectedWordIndex.value,
            time_spent: timeSpent,
            mode: selectedMode.value
        });

        handleAnswerResponse(response.data);
    } catch (error) {
        console.error('Failed to check answer:', error);
    }
};

const submitFixAnswer = async () => {
    if (!selectedOption.value || isCheckingAnswer.value) return;

    isCheckingAnswer.value = true;
    const timeSpent = getTimeSpent();

    try {
        const response = await axios.post('/student/english/games/error-hunter/check', {
            session_id: sessionId.value,
            sentence_id: currentSentence.value.id,
            answer: selectedOption.value,
            time_spent: timeSpent,
            mode: selectedMode.value
        });

        handleAnswerResponse(response.data);
    } catch (error) {
        console.error('Failed to check answer:', error);
    }
};

const submitRewriteAnswer = async () => {
    if (!userRewriteText.value.trim() || isCheckingAnswer.value) return;

    isCheckingAnswer.value = true;
    const timeSpent = getTimeSpent();

    try {
        const response = await axios.post('/student/english/games/error-hunter/check', {
            session_id: sessionId.value,
            sentence_id: currentSentence.value.id,
            answer: userRewriteText.value,
            time_spent: timeSpent,
            mode: selectedMode.value
        });

        handleAnswerResponse(response.data);
    } catch (error) {
        console.error('Failed to check answer:', error);
    }
};

const handleAnswerResponse = (data) => {
    if (data.correct) {
        feedbackCorrect.value = true;
        feedbackMessage.value = 'To\'g\'ri!';
        feedbackExplanation.value = data.explanation_uz || data.explanation;
        feedbackCorrectText.value = data.correct_text;

        score.value = data.total_score;
        streak.value = data.streak;
        streakMultiplier.value = data.streak_multiplier;
        completedCount.value = data.progress.completed;
    } else {
        feedbackCorrect.value = false;
        feedbackMessage.value = 'Noto\'g\'ri!';
        feedbackExplanation.value = '';
        feedbackCorrectText.value = '';

        streak.value = 0;
        streakMultiplier.value = 1.0;

        // Highlight error for spot mode
        if (selectedMode.value === 'spot_error' && data.error_index !== undefined) {
            highlightedIndex.value = data.error_index;
        }
    }

    // Show feedback then move to next
    setTimeout(() => {
        if (data.is_complete) {
            endGame();
        } else if (data.next_sentence) {
            moveToNextSentence(data.next_sentence);
        } else if (data.correct) {
            moveToNextSentence(null);
        }

        feedbackMessage.value = '';
        feedbackExplanation.value = '';
        feedbackCorrectText.value = '';
        isCheckingAnswer.value = false;
    }, data.correct ? 1500 : 2500);
};

const moveToNextSentence = (nextSentence) => {
    if (nextSentence) {
        currentSentence.value = nextSentence;
    }

    // Reset selections
    selectedWordIndex.value = null;
    selectedOption.value = null;
    userRewriteText.value = '';
    highlightedIndex.value = null;
    showHint.value = false;
    startTime.value = Date.now();
};

const usePowerup = async (powerupType) => {
    if (isPowerupUsed(powerupType) || isCheckingAnswer.value) return;

    try {
        const response = await axios.post('/student/english/games/error-hunter/powerup', {
            session_id: sessionId.value,
            powerup_type: powerupType
        });

        if (response.data.success) {
            usedPowerups.value.push(powerupType);

            const powerup = response.data.powerup;

            if (powerup.type === 'highlight') {
                highlightedIndex.value = powerup.error_index;
            } else if (powerup.type === 'hint') {
                showHint.value = true;
            } else if (powerup.type === 'skip') {
                if (powerup.is_complete) {
                    endGame();
                } else if (powerup.next_sentence) {
                    completedCount.value++;
                    moveToNextSentence(powerup.next_sentence);
                }
            } else if (powerup.type === 'extra_time') {
                timeRemaining.value = powerup.new_time_limit;
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error);
    }
};

const isPowerupUsed = (powerupType) => {
    return usedPowerups.value.includes(powerupType);
};

const endGame = async () => {
    stopTimer();

    try {
        const response = await axios.post('/student/english/games/error-hunter/complete', {
            session_id: sessionId.value
        });

        if (response.data.success) {
            results.value = response.data.results;
            gameCompleted.value = true;
        }
    } catch (error) {
        console.error('Failed to complete session:', error);
    }
};

const confirmExit = () => {
    if (confirm('Haqiqatan ham chiqmoqchimisiz? Progress saqlanmaydi.')) {
        stopTimer();
        router.visit('/student/english/games/error-hunter');
    }
};

const playAgain = () => {
    gameStarted.value = false;
    gameCompleted.value = false;
    selectedMode.value = null;
    sessionId.value = null;
    currentSentence.value = null;
    results.value = null;
    score.value = 0;
    streak.value = 0;
    completedCount.value = 0;
    usedPowerups.value = [];
};

onUnmounted(() => {
    stopTimer();
});
</script>
