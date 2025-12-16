<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import axios from 'axios';

const props = defineProps({
    level: Object,
    config: Object,
    gameModes: Array,
    powerups: Array,
    audioCategories: Array,
    audioSettings: Object,
});

// Game state
const isLoading = ref(false);
const gameStarted = ref(false);
const gameOver = ref(false);
const sessionId = ref(null);
const currentItem = ref(null);
const isAnswering = ref(false);
const selectedAnswer = ref(null);
const eliminatedOptions = ref([]);
const showFeedback = ref(false);
const lastAnswerCorrect = ref(false);
const lastPointsEarned = ref(0);
const lastTranscript = ref('');
const showExitModal = ref(false);
const results = ref({});
const isPlaying = ref(false);
const showTranscript = ref(false);
const audioSpeed = ref(1.0);

// Timers
const gameTimer = ref(null);
const answerStartTime = ref(null);
const answerTimer = ref(null);
const answerProgress = ref(100);

// Speech synthesis
const synth = window.speechSynthesis;

const gameState = ref({
    score: 0,
    streak: 0,
    bestStreak: 0,
    timeRemaining: 60,
    questionsAnswered: 0,
    totalQuestions: 0,
    availablePowerups: {},
    replaysRemaining: 3,
    maxReplays: 3,
});

// Computed
const progressPercentage = computed(() => {
    if (gameState.value.totalQuestions === 0) return 0;
    return (gameState.value.questionsAnswered / gameState.value.totalQuestions) * 100;
});

// Methods
const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return mins > 0 ? `${mins}:${secs.toString().padStart(2, '0')}` : `${secs}s`;
};

const getCategoryName = (categoryId) => {
    const category = props.audioCategories?.find(c => c.id === categoryId);
    return category?.name_uz || category?.name || categoryId;
};

const getQuestionTypeName = (type) => {
    const names = {
        'spelling': 'Imlo',
        'meaning': 'Ma\'no',
        'fill_blank': 'Bo\'sh joy',
        'comprehension': 'Tushunish',
        'sequence': 'Tartib',
    };
    return names[type] || type;
};

const playAudio = () => {
    if (!currentItem.value?.audio_text || isPlaying.value) return;

    isPlaying.value = true;
    const utterance = new SpeechSynthesisUtterance(currentItem.value.audio_text);
    utterance.lang = 'en-US';
    utterance.rate = audioSpeed.value;

    utterance.onend = () => {
        isPlaying.value = false;
    };

    utterance.onerror = () => {
        isPlaying.value = false;
    };

    synth.speak(utterance);
};

const replayAudio = async () => {
    if (gameState.value.replaysRemaining <= 0) return;

    try {
        await axios.post('/student/english/games/listen-choose/replay', {
            session_id: sessionId.value
        });

        gameState.value.replaysRemaining--;
        playAudio();
    } catch (error) {
        console.error('Failed to use replay:', error);
    }
};

const startGame = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post(`/student/english/games/listen-choose/start/${props.level.level_number}`, {
            mode: 'word'
        });

        if (response.data.success) {
            const data = response.data.data;
            sessionId.value = data.session_id;
            currentItem.value = data.first_item;
            gameState.value.timeRemaining = data.time_limit;
            gameState.value.totalQuestions = data.total_questions;
            gameState.value.maxReplays = data.max_replays;
            gameState.value.replaysRemaining = data.max_replays;
            gameState.value.availablePowerups = {};

            // Initialize powerups
            props.powerups?.forEach(p => {
                gameState.value.availablePowerups[p.id] = {
                    ...p,
                    available: true,
                    used: false
                };
            });

            gameStarted.value = true;
            startTimers();

            // Auto-play first audio after delay
            setTimeout(() => playAudio(), props.audioSettings?.auto_play_delay || 500);
        }
    } catch (error) {
        console.error('Failed to start game:', error);
        alert('O\'yinni boshlashda xatolik yuz berdi');
    } finally {
        isLoading.value = false;
    }
};

const startTimers = () => {
    // Main game timer
    gameTimer.value = setInterval(() => {
        if (gameState.value.timeRemaining > 0) {
            gameState.value.timeRemaining -= 0.1;
            if (gameState.value.timeRemaining <= 0) {
                endGame();
            }
        }
    }, 100);

    // Answer timer for progress bar
    answerStartTime.value = Date.now();
    answerTimer.value = setInterval(() => {
        const elapsed = (Date.now() - answerStartTime.value) / 1000;
        answerProgress.value = Math.max(0, 100 - (elapsed / 15) * 100);
    }, 50);
};

const resetAnswerTimer = () => {
    answerStartTime.value = Date.now();
    answerProgress.value = 100;
    gameState.value.replaysRemaining = gameState.value.maxReplays;
    showTranscript.value = false;
    audioSpeed.value = 1.0;
};

const selectAnswer = async (index) => {
    if (isAnswering.value || eliminatedOptions.value.includes(index)) return;

    isAnswering.value = true;
    selectedAnswer.value = index;
    const answerTime = (Date.now() - answerStartTime.value) / 1000;

    try {
        const response = await axios.post('/student/english/games/listen-choose/check', {
            session_id: sessionId.value,
            item_id: currentItem.value.id,
            answer: index,
            time: answerTime
        });

        if (response.data.success) {
            const data = response.data.data;

            lastAnswerCorrect.value = data.is_correct;
            lastPointsEarned.value = data.points_earned;
            lastTranscript.value = data.transcript;

            // Update game state
            gameState.value.score = data.total_score;
            gameState.value.streak = data.streak;
            gameState.value.bestStreak = data.best_streak;
            gameState.value.questionsAnswered = data.questions_answered;

            // Show feedback
            showFeedback.value = true;
            setTimeout(() => {
                showFeedback.value = false;
            }, lastAnswerCorrect.value ? 800 : 1500);

            if (data.game_over) {
                setTimeout(() => endGame(), 500);
            } else {
                // Next question
                setTimeout(() => {
                    currentItem.value = data.next_item;
                    selectedAnswer.value = null;
                    eliminatedOptions.value = [];
                    isAnswering.value = false;
                    resetAnswerTimer();
                    // Auto-play next audio
                    setTimeout(() => playAudio(), 300);
                }, lastAnswerCorrect.value ? 300 : 1000);
            }
        }
    } catch (error) {
        console.error('Failed to check answer:', error);
        isAnswering.value = false;
    }
};

const usePowerup = async (powerupId) => {
    if (!gameState.value.availablePowerups[powerupId]?.available) return;

    try {
        const response = await axios.post('/student/english/games/listen-choose/powerup', {
            session_id: sessionId.value,
            powerup_id: powerupId
        });

        if (response.data.success) {
            const data = response.data.data;

            // Mark powerup as used
            gameState.value.availablePowerups[powerupId].available = false;
            gameState.value.availablePowerups[powerupId].used = true;

            switch (data.effect) {
                case 'extra_replay':
                    gameState.value.replaysRemaining = data.replays_remaining;
                    break;

                case 'slow_speed':
                    audioSpeed.value = data.speed;
                    playAudio();
                    break;

                case 'options_eliminated':
                    eliminatedOptions.value = data.eliminated_indices;
                    break;

                case 'transcript_shown':
                    showTranscript.value = true;
                    break;

                case 'time_added':
                    gameState.value.timeRemaining += data.value;
                    break;
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error);
    }
};

const endGame = async () => {
    clearInterval(gameTimer.value);
    clearInterval(answerTimer.value);
    synth.cancel(); // Stop any playing audio

    try {
        const response = await axios.post('/student/english/games/listen-choose/complete', {
            session_id: sessionId.value,
            final_time: props.level.time_limit - gameState.value.timeRemaining
        });

        if (response.data.success) {
            results.value = response.data.data;
            gameOver.value = true;
        }
    } catch (error) {
        console.error('Failed to complete game:', error);
        gameOver.value = true;
    }
};

const restartGame = () => {
    gameOver.value = false;
    gameStarted.value = false;
    gameState.value = {
        score: 0,
        streak: 0,
        bestStreak: 0,
        timeRemaining: 60,
        questionsAnswered: 0,
        totalQuestions: 0,
        availablePowerups: {},
        replaysRemaining: 3,
        maxReplays: 3,
    };
    currentItem.value = null;
    selectedAnswer.value = null;
    eliminatedOptions.value = [];
    results.value = {};
    showTranscript.value = false;
    audioSpeed.value = 1.0;
};

const confirmExit = () => {
    if (gameStarted.value && !gameOver.value) {
        showExitModal.value = true;
    } else {
        goBack();
    }
};

const goBack = () => {
    clearInterval(gameTimer.value);
    clearInterval(answerTimer.value);
    synth.cancel();
    router.visit('/student/english/games/listen-choose');
};

const getOptionClass = (index) => {
    if (eliminatedOptions.value.includes(index)) {
        return 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed opacity-50';
    }
    if (selectedAnswer.value === index) {
        if (isAnswering.value) {
            return lastAnswerCorrect.value
                ? 'bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400 scale-105'
                : 'bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-600 dark:text-red-400 border-red-400 scale-105';
        }
        return 'bg-gradient-to-r from-cyan-500 to-teal-500 text-white scale-105 shadow-2xl shadow-cyan-500/30 border-transparent';
    }
    return 'bg-white dark:bg-gray-700/50 text-gray-800 dark:text-white hover:bg-cyan-50 dark:hover:bg-cyan-900/20 border-gray-200 dark:border-gray-600 hover:border-cyan-400 hover:shadow-xl hover:scale-105';
};

onMounted(() => {
    // Prevent accidental page leave
    window.addEventListener('beforeunload', (e) => {
        if (gameStarted.value && !gameOver.value) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

onUnmounted(() => {
    clearInterval(gameTimer.value);
    clearInterval(answerTimer.value);
    synth.cancel();
});
</script>

<template>
    <Head :title="`Tinglang va Tanlang - ${level?.name_uz || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-cyan-100 to-teal-100 dark:from-cyan-900/30 dark:to-teal-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-cyan-200 dark:border-cyan-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-cyan-600 border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-gray-800 dark:text-white text-2xl font-bold mb-2">O'yin yuklanmoqda...</p>
                    <p class="text-gray-500 dark:text-gray-400">Iltimos kuting</p>
                </div>
            </div>

            <!-- Game Start Screen -->
            <div v-else-if="!gameStarted" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-lg w-full px-4">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-cyan-500 via-teal-500 to-cyan-600 rounded-3xl mb-6 shadow-2xl shadow-cyan-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🎧</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level.name_uz || level.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{ level.description_uz || level.description }}</p>

                        <!-- Level Info -->
                        <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                                    <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">{{ level.max_replays }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Takrorlash</div>
                                </div>
                                <div class="bg-teal-50 dark:bg-teal-900/20 rounded-xl p-4 border border-teal-100 dark:border-teal-800/50">
                                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ level.questions_count }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Savollar</div>
                                </div>
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/50">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ level.time_limit }}s</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Vaqt</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <button @click="startGame" class="w-full py-4 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-xl font-bold text-lg hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Boshlash
                        </button>
                        <button @click="goBack" class="w-full py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 transition-all shadow-md border border-gray-100 dark:border-gray-700">
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameOver && results" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ results.stars === 3 ? '🏆' : results.stars === 2 ? '🎉' : results.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= results.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ results.stars === 3 ? 'Mukammal!' : results.stars === 2 ? 'Ajoyib!' : results.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ results.accuracy }}% aniqlik</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl p-5 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ results.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-teal-50 dark:bg-teal-900/20 rounded-2xl p-5 border border-teal-100 dark:border-teal-800/50">
                            <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">+{{ results.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">+{{ results.coins_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Tanga</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ results.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
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
                        <button @click="confirmExit" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-teal-600 rounded-xl shadow-lg">
                            <span class="text-2xl">🎧</span>
                            <span class="font-bold text-white">{{ level?.name_uz || level?.name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 px-4 py-2 bg-cyan-50 dark:bg-cyan-900/30 rounded-xl border border-cyan-100 dark:border-cyan-800/50">
                                <span class="text-cyan-500">🔁</span>
                                <span class="text-cyan-600 dark:text-cyan-400 font-bold">{{ gameState.replaysRemaining }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ gameState.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ gameState.streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-100 dark:border-cyan-800/50">
                            <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ gameState.questionsAnswered }}/{{ gameState.totalQuestions }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Savol</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            gameState.timeRemaining < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="gameState.timeRemaining < 30 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ formatTime(gameState.timeRemaining) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Progress</span>
                            <span>{{ gameState.questionsAnswered }}/{{ gameState.totalQuestions }} savol</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-cyan-500 via-teal-500 to-blue-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progressPercentage + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Audio Player Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <!-- Question Type Badges -->
                        <div class="flex items-center justify-center gap-3 mb-6">
                            <span class="bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 px-4 py-2 rounded-full text-sm font-semibold border border-cyan-200 dark:border-cyan-800/50">
                                {{ getQuestionTypeName(currentItem?.type) }}
                            </span>
                            <span class="bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 px-4 py-2 rounded-full text-sm font-semibold border border-teal-200 dark:border-teal-800/50">
                                {{ getCategoryName(currentItem?.category) }}
                            </span>
                        </div>

                        <!-- Audio Control -->
                        <div class="flex items-center justify-center mb-6">
                            <button @click="playAudio"
                                    :disabled="isPlaying"
                                    class="w-24 h-24 rounded-full bg-gradient-to-r from-cyan-500 to-teal-500 flex items-center justify-center text-white shadow-2xl shadow-cyan-500/50 transition-all transform hover:scale-110 disabled:opacity-70"
                                    :class="{ 'animate-pulse': isPlaying }">
                                <svg v-if="!isPlaying" class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                                </svg>
                                <div v-else class="flex gap-1.5">
                                    <span class="w-1.5 h-8 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                    <span class="w-1.5 h-8 bg-white rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                    <span class="w-1.5 h-8 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                </div>
                            </button>
                        </div>

                        <!-- Replay Button -->
                        <button @click="replayAudio"
                                :disabled="gameState.replaysRemaining <= 0"
                                class="px-6 py-3 rounded-xl transition-all font-semibold shadow-md"
                                :class="gameState.replaysRemaining > 0
                                    ? 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed opacity-50'">
                            🔁 Qayta eshitish ({{ gameState.replaysRemaining }} qoldi)
                        </button>

                        <!-- Question Display -->
                        <div class="mt-6 mb-4">
                            <p v-if="currentItem?.question" class="text-gray-800 dark:text-white text-xl font-medium">
                                {{ currentItem.question }}
                            </p>
                            <p v-else-if="currentItem?.sentence" class="text-gray-800 dark:text-white text-xl font-medium">
                                {{ currentItem.sentence }}
                            </p>
                            <p v-else class="text-gray-500 dark:text-gray-400">
                                Audioni eshiting va to'g'ri javobni tanlang
                            </p>
                        </div>

                        <!-- Transcript -->
                        <div v-if="showTranscript" class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-800/50 rounded-xl">
                            <p class="text-gray-800 dark:text-white font-medium">{{ currentItem?.audio_text }}</p>
                        </div>

                        <!-- Answer Timer Progress -->
                        <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full mt-6 overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-green-500 to-cyan-500 rounded-full transition-all duration-100"
                                 :style="{ width: `${answerProgress}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Options Grid -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button v-for="(option, index) in currentItem?.options" :key="index"
                                @click="selectAnswer(index)"
                                :disabled="isAnswering || eliminatedOptions.includes(index)"
                                :class="getOptionClass(index)"
                                class="p-5 rounded-xl text-left transition-all duration-300 transform border-2 shadow-lg">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full bg-white/20 dark:bg-black/20 flex items-center justify-center font-bold">
                                    {{ ['A', 'B', 'C', 'D'][index] }}
                                </span>
                                <span class="font-semibold text-base">{{ option }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Powerups -->
                    <div v-if="Object.keys(gameState.availablePowerups).length > 0" class="flex flex-wrap justify-center gap-3 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button v-for="(powerup, id) in gameState.availablePowerups" :key="id"
                                @click="usePowerup(id)"
                                :disabled="!powerup.available || isAnswering"
                                class="flex flex-col items-center p-4 rounded-xl transition-all shadow-md hover:shadow-lg"
                                :class="powerup.available
                                    ? 'bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 hover:from-amber-100 hover:to-yellow-100 dark:hover:from-amber-900/30 dark:hover:to-yellow-900/30 border border-amber-200 dark:border-amber-800/50 cursor-pointer hover:scale-105'
                                    : 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed opacity-50'">
                            <span class="text-3xl mb-2">{{ powerup.icon }}</span>
                            <span class="text-xs font-semibold" :class="powerup.available ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400'">{{ powerup.name_uz || powerup.name }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feedback Toast -->
            <Transition name="slide-up">
                <div v-if="showFeedback"
                     class="fixed bottom-8 left-1/2 -translate-x-1/2 px-8 py-5 rounded-2xl text-white font-bold text-lg shadow-2xl z-50 border-2"
                     :class="lastAnswerCorrect
                         ? 'bg-gradient-to-r from-green-500 to-emerald-500 border-green-400'
                         : 'bg-gradient-to-r from-red-500 to-rose-500 border-red-400'">
                    <div class="flex items-center gap-4">
                        <span class="text-3xl">{{ lastAnswerCorrect ? '✓' : '✗' }}</span>
                        <div>
                            <span class="block">{{ lastAnswerCorrect ? `+${lastPointsEarned} ball` : 'Noto\'g\'ri!' }}</span>
                            <p v-if="!lastAnswerCorrect && lastTranscript" class="text-sm mt-1 font-normal opacity-90">
                                To'g'ri: {{ lastTranscript }}
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Exit Confirmation Modal -->
            <Transition name="fade">
                <div v-if="showExitModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 max-w-md w-full p-8">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-4xl">⚠️</span>
                            </div>
                            <h2 class="text-gray-800 dark:text-white text-2xl font-bold mb-2">O'yindan chiqish?</h2>
                            <p class="text-gray-500 dark:text-gray-400">O'yinni tugatmasdan chiqsangiz, progress saqlanmaydi.</p>
                        </div>
                        <div class="flex gap-3">
                            <button @click="showExitModal = false"
                                    class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-all font-semibold hover:scale-105">
                                Davom etish
                            </button>
                            <button @click="goBack"
                                    class="flex-1 py-3.5 bg-gradient-to-r from-red-500 to-rose-600 hover:opacity-90 text-white rounded-xl transition-all font-semibold shadow-lg hover:shadow-xl hover:scale-105">
                                Chiqish
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </StudentLayout>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translate(-50%, 20px);
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
