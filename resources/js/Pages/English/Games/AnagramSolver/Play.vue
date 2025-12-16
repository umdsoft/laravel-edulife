<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import axios from 'axios';

const props = defineProps({
    level: { type: Object, required: true },
    config: { type: Object, required: true },
    powerups: { type: Array, default: () => [] },
});

// Game state
const isLoading = ref(false);
const hasError = ref(false);
const errorMessage = ref('');
const gameStarted = ref(false);
const gameCompleted = ref(false);
const sessionId = ref(null);
const words = ref([]);
const currentWordIndex = ref(0);
const score = ref(0);
const wordsSolved = ref(0);
const mistakes = ref(0);
const streak = ref(0);
const timeLimit = ref(props.level.time_limit);
const timeRemaining = ref(props.level.time_limit);
const summary = ref(null);
const powerupsUsed = ref({});
const totalWords = ref(0);

// Current word state
const scrambledLetters = ref([]);
const answerLetters = ref([]);
const selectedLetters = ref([]);
const revealedPositions = ref([]);
const wordStartTime = ref(null);

// UI state
const feedback = ref(null);
const showExitModal = ref(false);
const showRevealModal = ref(false);
const revealedWord = ref('');
const revealedTranslation = ref('');

let timerInterval = null;

const currentWord = computed(() => {
    return words.value[currentWordIndex.value] || null;
});

const isAnswerComplete = computed(() => {
    return answerLetters.value.every(letter => letter !== null);
});

const progress = computed(() => {
    return totalWords.value > 0 ? Math.round((wordsSolved.value / totalWords.value) * 100) : 0;
});

const timerPercentage = computed(() => {
    return timeLimit.value > 0 ? (timeRemaining.value / timeLimit.value) * 100 : 0;
});

const startGame = async () => {
    isLoading.value = true;
    hasError.value = false;

    try {
        const response = await axios.post(route('student.english.games.anagram-solver.start', { level: props.level.level_number }));

        if (response.data.success) {
            sessionId.value = response.data.data.session_id;
            words.value = response.data.data.words;
            totalWords.value = response.data.data.word_count;
            gameStarted.value = true;

            loadCurrentWord();

            if (timeLimit.value) {
                startTimer();
            }
        }
    } catch (error) {
        hasError.value = true;
        errorMessage.value = error.response?.data?.message || 'Xatolik yuz berdi';
    } finally {
        isLoading.value = false;
    }
};

const loadCurrentWord = () => {
    const word = currentWord.value;
    if (!word) return;

    scrambledLetters.value = word.scrambled.split('');
    answerLetters.value = new Array(word.length).fill(null);
    selectedLetters.value = [];
    revealedPositions.value = [];
    feedback.value = null;
    wordStartTime.value = Date.now();
};

const selectLetter = (index) => {
    if (selectedLetters.value.includes(index)) return;
    if (currentWord.value?.solved) return;

    const emptyIndex = answerLetters.value.findIndex(l => l === null);
    if (emptyIndex === -1) return;

    selectedLetters.value.push(index);
    answerLetters.value[emptyIndex] = scrambledLetters.value[index];
};

const removeLetter = (index) => {
    if (currentWord.value?.solved) return;
    const letter = answerLetters.value[index];
    if (!letter) return;

    // Find the original index in scrambled letters
    const originalIndex = selectedLetters.value.find(i => scrambledLetters.value[i] === letter);
    if (originalIndex !== undefined) {
        selectedLetters.value = selectedLetters.value.filter(i => i !== originalIndex);
    }

    answerLetters.value[index] = null;
};

const clearAnswer = () => {
    if (currentWord.value?.solved) return;
    answerLetters.value = new Array(currentWord.value.length).fill(null);
    selectedLetters.value = [];
};

const submitAnswer = async () => {
    if (!isAnswerComplete.value || currentWord.value?.solved) return;

    const answer = answerLetters.value.join('');
    const timeSpent = (Date.now() - wordStartTime.value) / 1000;

    try {
        const response = await axios.post(route('student.english.games.anagram-solver.answer'), {
            session_id: sessionId.value,
            word_index: currentWordIndex.value,
            answer: answer,
            time_spent: timeSpent,
        });

        if (response.data.success) {
            const data = response.data.data;

            if (data.correct) {
                feedback.value = {
                    correct: true,
                    message: "To'g'ri! 🎉",
                    points: data.points_earned,
                };
                words.value[currentWordIndex.value].solved = true;
                score.value = data.score;
                wordsSolved.value = data.words_solved;
                streak.value = data.streak;

                // Auto-advance after short delay
                setTimeout(() => {
                    if (!data.is_complete) {
                        goToNextUnsolved();
                    }
                }, 1000);
            } else {
                feedback.value = {
                    correct: false,
                    message: "Noto'g'ri!",
                    correctAnswer: data.correct_answer,
                };
                mistakes.value = data.mistakes;
                streak.value = 0;
            }

            if (data.is_complete) {
                gameCompleted.value = true;
                summary.value = data.summary;
                stopTimer();
            }
        }
    } catch (error) {
        console.error('Failed to submit answer:', error);
    }
};

const goToWord = (index) => {
    if (words.value[index]?.solved && currentWordIndex.value !== index) return;
    currentWordIndex.value = index;
    loadCurrentWord();
};

const goToNextUnsolved = () => {
    const nextIndex = words.value.findIndex((w, i) => i > currentWordIndex.value && !w.solved);
    if (nextIndex !== -1) {
        currentWordIndex.value = nextIndex;
        loadCurrentWord();
    } else {
        // Find any unsolved word
        const anyUnsolved = words.value.findIndex(w => !w.solved);
        if (anyUnsolved !== -1) {
            currentWordIndex.value = anyUnsolved;
            loadCurrentWord();
        }
    }
};

const usePowerup = async (powerupId) => {
    if (getPowerupUsesRemaining(powerupId) <= 0) return;
    if (currentWord.value?.solved) return;

    try {
        let response;

        if (powerupId === 'hint') {
            response = await axios.post(route('student.english.games.anagram-solver.hint'), {
                session_id: sessionId.value,
                word_index: currentWordIndex.value,
            });

            if (response.data.success) {
                const data = response.data.data;
                revealedPositions.value.push(data.position);
                answerLetters.value[data.position] = data.letter;
                powerupsUsed.value['hint'] = (powerupsUsed.value['hint'] || 0) + 1;
            }
        } else if (powerupId === 'shuffle') {
            response = await axios.post(route('student.english.games.anagram-solver.shuffle'), {
                session_id: sessionId.value,
                word_index: currentWordIndex.value,
            });

            if (response.data.success) {
                scrambledLetters.value = response.data.data.scrambled.split('');
                clearAnswer();
                powerupsUsed.value['shuffle'] = (powerupsUsed.value['shuffle'] || 0) + 1;
            }
        } else if (powerupId === 'skip') {
            response = await axios.post(route('student.english.games.anagram-solver.skip'), {
                session_id: sessionId.value,
                word_index: currentWordIndex.value,
            });

            if (response.data.success) {
                const data = response.data.data;
                words.value[currentWordIndex.value].solved = true;
                powerupsUsed.value['skip'] = (powerupsUsed.value['skip'] || 0) + 1;

                if (data.is_complete) {
                    gameCompleted.value = true;
                    summary.value = data.summary;
                    stopTimer();
                } else {
                    goToNextUnsolved();
                }
            }
        } else if (powerupId === 'extra_time') {
            response = await axios.post(route('student.english.games.anagram-solver.powerup'), {
                session_id: sessionId.value,
                powerup_id: 'extra_time',
            });

            if (response.data.success) {
                timeRemaining.value = response.data.data.time_remaining;
                powerupsUsed.value['extra_time'] = (powerupsUsed.value['extra_time'] || 0) + 1;
            }
        } else if (powerupId === 'reveal_word') {
            response = await axios.post(route('student.english.games.anagram-solver.reveal'), {
                session_id: sessionId.value,
                word_index: currentWordIndex.value,
            });

            if (response.data.success) {
                const data = response.data.data;
                revealedWord.value = data.word;
                revealedTranslation.value = data.translation;
                showRevealModal.value = true;
                powerupsUsed.value['reveal_word'] = (powerupsUsed.value['reveal_word'] || 0) + 1;
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error);
    }
};

const closeRevealModal = () => {
    showRevealModal.value = false;
    revealedWord.value = '';
    revealedTranslation.value = '';
};

const getPowerupUsesRemaining = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId);
    if (!powerup) return 0;
    const used = powerupsUsed.value[powerupId] || 0;
    return (powerup.uses_per_game || 1) - used;
};

const startTimer = () => {
    timerInterval = setInterval(() => {
        if (timeRemaining.value > 0) {
            timeRemaining.value--;

            // Sync time with server every 10 seconds
            if (timeRemaining.value % 10 === 0) {
                syncTime();
            }
        } else {
            completeGame();
        }
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

const syncTime = async () => {
    try {
        await axios.post(route('student.english.games.anagram-solver.time'), {
            session_id: sessionId.value,
            time_remaining: timeRemaining.value,
        });
    } catch (error) {
        console.error('Failed to sync time:', error);
    }
};

const completeGame = async () => {
    stopTimer();

    try {
        const response = await axios.post(route('student.english.games.anagram-solver.complete'), {
            session_id: sessionId.value,
        });

        if (response.data.success) {
            summary.value = response.data.data;
            gameCompleted.value = true;
        }
    } catch (error) {
        console.error('Failed to complete game:', error);
    }
};

const restartGame = () => {
    gameStarted.value = false;
    gameCompleted.value = false;
    sessionId.value = null;
    words.value = [];
    currentWordIndex.value = 0;
    score.value = 0;
    wordsSolved.value = 0;
    mistakes.value = 0;
    streak.value = 0;
    timeRemaining.value = props.level.time_limit;
    summary.value = null;
    powerupsUsed.value = {};
    scrambledLetters.value = [];
    answerLetters.value = [];
    selectedLetters.value = [];
    revealedPositions.value = [];
    feedback.value = null;
};

const confirmExit = () => {
    if (gameStarted.value && !gameCompleted.value) {
        showExitModal.value = true;
    } else {
        goBack();
    }
};

const goBack = () => {
    stopTimer();
    router.visit(route('student.english.games.anagram-solver.index'));
};

const formatTimeDisplay = (seconds) => {
    if (seconds === null) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

// Watch for word changes
watch(currentWordIndex, () => {
    if (gameStarted.value && !gameCompleted.value) {
        loadCurrentWord();
    }
});

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <Head :title="`Anagram Solver - ${level?.name || 'Game'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">

            <!-- Loading Screen -->
            <div v-if="isLoading" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-12 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 relative mx-auto mb-8">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-violet-200 dark:border-violet-800"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-violet-600 border-t-transparent animate-spin"></div>
                    </div>
                    <p class="text-gray-800 dark:text-white text-2xl font-bold mb-2">O'yin yuklanmoqda...</p>
                    <p class="text-gray-500 dark:text-gray-400">Iltimos kuting</p>
                </div>
            </div>

            <!-- Error Screen -->
            <div v-else-if="hasError" class="flex items-center justify-center min-h-[80vh]">
                <div class="text-center bg-white dark:bg-gray-800 rounded-3xl p-10 max-w-md shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-5xl">😞</span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-3">Xatolik yuz berdi</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8">{{ errorMessage }}</p>
                    <button @click="goBack" class="px-8 py-3.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Orqaga qaytish
                    </button>
                </div>
            </div>

            <!-- Pre-Game Screen -->
            <div v-else-if="!gameStarted" class="flex items-center justify-center min-h-[80vh]">
                <div class="max-w-lg w-full px-4">
                    <!-- Header Card -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-violet-500 via-purple-500 to-fuchsia-600 rounded-3xl mb-6 shadow-2xl shadow-violet-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🔤</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level?.name_uz }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">Harflarni tartibga keltiring</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-white dark:bg-gray-800 px-5 py-2.5 rounded-full shadow-lg border border-gray-100 dark:border-gray-700">
                            <span class="text-violet-600 dark:text-violet-400 font-bold">{{ level?.cefr_level }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level?.word_count }} ta so'z</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ formatTimeDisplay(level?.time_limit) }}</span>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-100 dark:border-gray-700 mb-6">
                        <div class="text-gray-600 dark:text-gray-400 space-y-3">
                            <p class="flex items-center gap-3">
                                <span class="text-2xl">📝</span>
                                <span>So'zlarni tartibga keltiring va to'g'ri javobni toping</span>
                            </p>
                            <p class="flex items-center gap-3">
                                <span class="text-2xl">📏</span>
                                <span>{{ level.min_word_length }}-{{ level.max_word_length }} harfli so'zlar</span>
                            </p>
                            <p v-if="level.max_mistakes" class="flex items-center gap-3">
                                <span class="text-2xl">❌</span>
                                <span>Maksimal {{ level.max_mistakes }} xatoga yo'l qo'yiladi</span>
                            </p>
                        </div>
                    </div>

                    <!-- Start Button -->
                    <button @click="startGame" class="w-full py-4 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-lg font-bold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                        Boshlash
                    </button>

                    <!-- Back Button -->
                    <div class="mt-4 text-center">
                        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all font-medium rounded-xl shadow-md hover:shadow-lg border border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Orqaga qaytish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Completed Screen -->
            <div v-else-if="gameCompleted && summary" class="flex items-center justify-center min-h-[80vh]">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                    <!-- Trophy/Emoji based on performance -->
                    <div class="mb-4">
                        <span class="text-7xl">{{ summary.stars === 3 ? '🏆' : summary.stars === 2 ? '🎉' : summary.stars === 1 ? '👍' : '💪' }}</span>
                    </div>

                    <!-- Stars -->
                    <div class="flex justify-center gap-4 mb-6">
                        <span v-for="i in 3" :key="i"
                              :class="i <= summary.stars ? 'text-yellow-400 scale-110' : 'text-gray-200 dark:text-gray-700'"
                              class="text-5xl transition-all duration-500 drop-shadow-md">⭐</span>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">
                        {{ summary.stars === 3 ? 'Mukammal!' : summary.stars === 2 ? 'Ajoyib!' : summary.stars === 1 ? 'Yaxshi!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ summary.accuracy }}% aniqlik bilan tugatdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">{{ summary.score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-2xl p-5 border border-purple-100 dark:border-purple-800/50">
                            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">+{{ summary.xp_earned }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                        </div>
                        <div class="bg-pink-50 dark:bg-pink-900/20 rounded-2xl p-5 border border-pink-100 dark:border-pink-800/50">
                            <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ summary.words_solved }}/{{ summary.total_words }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Yechilgan</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ summary.best_streak }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi streak</div>
                        </div>
                    </div>

                    <!-- Extra Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-fuchsia-50 dark:bg-fuchsia-900/20 rounded-2xl p-4 border border-fuchsia-100 dark:border-fuchsia-800/50">
                            <div class="text-2xl font-bold text-fuchsia-600 dark:text-fuchsia-400">{{ summary.fast_solves }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Tez yechimlar</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-4 border border-amber-100 dark:border-amber-800/50">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ summary.hints_used }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-medium mt-1">Maslahatlar</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                            Orqaga
                        </button>
                        <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>

            <!-- Game Playing Screen -->
            <div v-else class="max-w-5xl mx-auto">
                <!-- Game Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 mb-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-5">
                        <button @click="confirmExit" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Chiqish
                        </button>
                        <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 rounded-xl shadow-lg">
                            <span class="text-2xl">🔤</span>
                            <span class="font-bold text-white">{{ level?.name_uz }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">{{ level?.cefr_level }}</span>
                        </div>
                        <div class="w-[100px]"></div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4 border border-gray-100 dark:border-gray-600/50">
                            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ score }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800/50">
                            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ streak }}<span class="text-2xl ml-1">🔥</span></div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-violet-50 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/20 rounded-xl p-4 border border-violet-100 dark:border-violet-800/50">
                            <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">{{ wordsSolved }}/{{ totalWords }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Yechilgan</div>
                        </div>
                        <div :class="[
                            'rounded-xl p-4 border',
                            timeRemaining < 30
                                ? 'bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-900/30 dark:to-rose-900/20 border-red-100 dark:border-red-800/50'
                                : 'bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 border-green-100 dark:border-green-800/50'
                        ]">
                            <div :class="timeRemaining < 30 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="text-3xl font-bold">{{ formatTimeDisplay(timeRemaining) }}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Vaqt</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-2 font-medium">
                            <span>Jarayon</span>
                            <span>{{ wordsSolved }}/{{ totalWords }} so'z</span>
                        </div>
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full bg-gradient-to-r from-violet-500 via-purple-500 to-fuchsia-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Current Word Card -->
                <div v-if="currentWord" class="bg-white dark:bg-gray-800 rounded-2xl p-6 md:p-8 shadow-xl border border-gray-100 dark:border-gray-700 mb-5">
                    <!-- Translation Hint -->
                    <div class="text-center mb-6 pb-6 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">Tarjimasi:</p>
                        <p class="text-gray-900 dark:text-white text-2xl font-bold">{{ currentWord.translation }}</p>
                    </div>

                    <!-- Scrambled Letters -->
                    <div class="mb-6">
                        <p class="text-center text-gray-500 dark:text-gray-400 text-sm mb-3">Aralashtirilgan harflar:</p>
                        <div class="flex justify-center gap-2 flex-wrap">
                            <div v-for="(letter, index) in scrambledLetters" :key="index"
                                 @click="selectLetter(index)"
                                 :class="[
                                     'w-14 h-14 md:w-16 md:h-16 flex items-center justify-center text-2xl md:text-3xl font-bold rounded-xl cursor-pointer transition-all transform hover:scale-110 shadow-lg border-2',
                                     selectedLetters.includes(index)
                                         ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed border-gray-200 dark:border-gray-600 scale-95'
                                         : 'bg-gradient-to-br from-violet-500 to-purple-500 text-white hover:from-violet-600 hover:to-purple-600 border-transparent shadow-violet-500/30'
                                 ]">
                                {{ letter.toUpperCase() }}
                            </div>
                        </div>
                    </div>

                    <!-- Answer Input -->
                    <div class="mb-6">
                        <p class="text-center text-gray-500 dark:text-gray-400 text-sm mb-3">Javobingiz:</p>
                        <div class="flex justify-center gap-2 flex-wrap">
                            <div v-for="(letter, index) in answerLetters" :key="'ans-' + index"
                                 @click="removeLetter(index)"
                                 :class="[
                                     'w-14 h-14 md:w-16 md:h-16 flex items-center justify-center text-2xl md:text-3xl font-bold rounded-xl transition-all shadow-lg border-2',
                                     letter
                                         ? 'bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600'
                                         : 'bg-white dark:bg-gray-800 border-dashed border-violet-300 dark:border-violet-700'
                                 ]">
                                {{ letter ? letter.toUpperCase() : '' }}
                            </div>
                        </div>
                    </div>

                    <!-- Hint Letters (if any revealed) -->
                    <div v-if="revealedPositions.length > 0" class="text-center text-violet-600 dark:text-violet-400 text-sm mb-6 font-medium">
                        💡 Ko'rsatilgan harflar: {{ revealedPositions.map(p => `${p + 1}-pozitsiya`).join(', ') }}
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-center gap-3 mb-6">
                        <button @click="clearAnswer"
                                :disabled="currentWord?.solved"
                                class="px-6 py-3 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all border-2 border-gray-200 dark:border-gray-600 shadow-md hover:shadow-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                            Tozalash
                        </button>
                        <button @click="submitAnswer"
                                :disabled="!isAnswerComplete || currentWord?.solved"
                                class="px-8 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white font-bold rounded-xl hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl hover:scale-105">
                            Tekshirish
                        </button>
                    </div>

                    <!-- Feedback -->
                    <div v-if="feedback" :class="[
                        'p-4 rounded-xl text-center font-semibold',
                        feedback.correct
                            ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-2 border-green-300 dark:border-green-700'
                            : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-2 border-red-300 dark:border-red-700'
                    ]">
                        <p class="text-lg">{{ feedback.message }}</p>
                        <p v-if="!feedback.correct" class="text-sm mt-2">To'g'ri javob: <span class="font-bold">{{ feedback.correctAnswer }}</span></p>
                        <p v-if="feedback.correct" class="text-sm mt-2">+{{ feedback.points }} ball</p>
                    </div>
                </div>

                <!-- Word Navigation -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700 mb-5">
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <span class="text-xl">🎯</span>
                        <h3 class="text-gray-700 dark:text-gray-300 font-bold">So'zlar</h3>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-center">
                        <button v-for="(word, index) in words" :key="index"
                                @click="goToWord(index)"
                                :class="[
                                    'w-12 h-12 rounded-xl font-bold transition-all shadow-md hover:shadow-lg border-2',
                                    word.solved
                                        ? 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 border-green-400 dark:border-green-600'
                                        : currentWordIndex === index
                                            ? 'bg-gradient-to-br from-violet-500 to-purple-500 text-white border-transparent scale-110'
                                            : 'bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 border-gray-200 dark:border-gray-600 hover:border-violet-400 hover:scale-105'
                                ]">
                            {{ index + 1 }}
                        </button>
                    </div>
                </div>

                <!-- Powerups -->
                <div v-if="powerups && powerups.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-center gap-2 mb-4">
                        <span class="text-xl">⚡</span>
                        <h3 class="text-gray-700 dark:text-gray-300 font-bold">Qo'shimcha imkoniyatlar</h3>
                    </div>
                    <div class="flex justify-center gap-3 flex-wrap">
                        <button v-for="powerup in powerups" :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="getPowerupUsesRemaining(powerup.id) <= 0 || (currentWord?.solved)"
                                class="flex flex-col items-center p-4 bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-700/50 dark:to-gray-700/30 hover:from-violet-50 hover:to-purple-50 dark:hover:from-violet-900/20 dark:hover:to-purple-900/20 rounded-xl transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-md hover:shadow-lg border border-gray-200 dark:border-gray-600 hover:border-violet-400 dark:hover:border-violet-500 hover:scale-105 min-w-[100px]">
                            <span class="text-3xl mb-2">{{ powerup.icon }}</span>
                            <span class="text-gray-900 dark:text-white text-sm font-semibold mb-1">{{ powerup.name_uz }}</span>
                            <span class="text-violet-600 dark:text-violet-400 text-xs font-bold bg-violet-100 dark:bg-violet-900/30 px-2 py-0.5 rounded-full">
                                {{ getPowerupUsesRemaining(powerup.id) }} qoldi
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Exit Confirmation Modal -->
            <div v-if="showExitModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl">⚠️</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">O'yinni tark etish</h3>
                        <p class="text-gray-600 dark:text-gray-400">Haqiqatan ham chiqmoqchimisiz? Jarayon saqlanmaydi.</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showExitModal = false" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                            Yo'q
                        </button>
                        <button @click="goBack" class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white font-semibold rounded-xl hover:opacity-90 transition-all shadow-lg">
                            Ha, chiqish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reveal Word Modal -->
            <div v-if="showRevealModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl border border-gray-100 dark:border-gray-700">
                    <div class="w-20 h-20 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-5xl">👁️</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">So'z ochildi!</h3>
                    <p class="text-violet-600 dark:text-violet-400 text-4xl font-mono font-bold mb-2">{{ revealedWord }}</p>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-6">{{ revealedTranslation }}</p>
                    <button @click="closeRevealModal" class="px-8 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white font-semibold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl">
                        Davom etish
                    </button>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

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
