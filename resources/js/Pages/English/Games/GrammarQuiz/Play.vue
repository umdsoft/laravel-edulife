<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 flex flex-col p-4 sm:p-6">
        <!-- Pre-game Screen -->
        <div v-if="gameState === 'pregame'" class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 max-w-md w-full">
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">📝</div>
                    <h2 class="text-gray-900 text-2xl font-bold mb-2">{{ level.name }}</h2>
                    <p class="text-emerald-600">{{ level.name_uz }}</p>
                    <div class="mt-3 inline-block bg-emerald-100 text-emerald-700 px-4 py-1 rounded-full">
                        {{ level.cefr_level }} - {{ level.difficulty }}
                    </div>
                </div>

                <!-- Quiz Type Selection -->
                <div class="mb-6">
                    <label class="text-gray-600 text-sm mb-2 block">Savol turi:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button v-for="(type, key) in availableQuizTypes" :key="key"
                                @click="selectedQuizType = key"
                                class="p-3 rounded-xl border-2 transition-all text-left"
                                :class="selectedQuizType === key
                                    ? 'border-emerald-500 bg-emerald-50'
                                    : 'border-gray-200 bg-gray-50 hover:bg-gray-100'">
                            <div class="text-2xl mb-1">{{ type.icon }}</div>
                            <div class="text-gray-900 text-sm font-medium">{{ type.name }}</div>
                            <div class="text-emerald-600 text-xs">{{ type.point_multiplier }}x ball</div>
                        </button>
                    </div>
                </div>

                <!-- Level Info -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Savollar</span>
                        <span class="text-gray-900">{{ level.questions_count }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Vaqt</span>
                        <span class="text-gray-900">{{ Math.floor(level.time_limit / 60) }} daqiqa</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Mukofot</span>
                        <span class="text-emerald-600">+{{ level.xp_reward }} XP</span>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <Link :href="route('student.english.games.grammar-quiz.index')"
                          class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-center">
                        Orqaga
                    </Link>
                    <button @click="startGame"
                            :disabled="isLoading"
                            class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl transition-colors font-semibold disabled:opacity-50">
                        <span v-if="isLoading">Yuklanmoqda...</span>
                        <span v-else>Boshlash</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Game Screen -->
        <div v-else-if="gameState === 'playing'" class="min-h-screen flex flex-col">
            <!-- Game Header -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-4">
                <div class="max-w-4xl mx-auto px-4 py-3">
                    <div class="flex items-center justify-between">
                        <!-- Progress -->
                        <div class="flex items-center space-x-4">
                            <div class="text-gray-900">
                                <span class="font-bold">{{ currentIndex + 1 }}</span>
                                <span class="text-gray-500">/{{ totalQuestions }}</span>
                            </div>
                            <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300"
                                     :style="{ width: `${((currentIndex + 1) / totalQuestions) * 100}%` }"></div>
                            </div>
                        </div>

                        <!-- Score & Streak -->
                        <div class="flex items-center space-x-6">
                            <div class="text-center">
                                <div class="text-emerald-600 text-xs">Ball</div>
                                <div class="text-gray-900 font-bold">{{ score }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-orange-500 text-xs">Streak</div>
                                <div class="text-gray-900 font-bold flex items-center">
                                    <span>{{ streak }}</span>
                                    <span v-if="streak >= 3" class="ml-1">🔥</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-red-500 text-xs">Vaqt</div>
                                <div class="text-gray-900 font-bold" :class="{ 'text-red-500': timeRemaining < 30 }">
                                    {{ formatTime(timeRemaining) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Area -->
            <div class="flex-1 flex items-center justify-center p-4">
                <div class="max-w-2xl w-full">
                    <!-- Question Card -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
                        <!-- Question Type Badge -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-sm">
                                {{ getQuestionTypeName(currentQuestion?.type) }}
                            </span>
                            <span class="text-emerald-600 text-sm">
                                +{{ currentQuestion?.points || 100 }} ball
                            </span>
                        </div>

                        <!-- Fill in the Blank -->
                        <div v-if="currentQuestion?.type === 'fill_blank'" class="space-y-6">
                            <p class="text-gray-900 text-xl leading-relaxed">
                                <template v-for="(part, index) in getSentenceParts(currentQuestion.sentence)" :key="index">
                                    <span v-if="part === '___'"
                                          class="inline-block px-4 py-1 mx-1 bg-emerald-100 border-b-2 border-emerald-500 rounded min-w-[80px] text-center">
                                        {{ selectedAnswer || '___' }}
                                    </span>
                                    <span v-else>{{ part }}</span>
                                </template>
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <button v-for="option in currentQuestion.options" :key="option"
                                        @click="selectOption(option)"
                                        :disabled="answerSubmitted"
                                        class="p-4 rounded-xl border-2 transition-all text-gray-900 font-medium"
                                        :class="getOptionClass(option)">
                                    {{ option }}
                                </button>
                            </div>
                        </div>

                        <!-- Multiple Choice -->
                        <div v-else-if="currentQuestion?.type === 'multiple_choice'" class="space-y-6">
                            <p class="text-gray-900 text-xl mb-6">{{ currentQuestion.sentence }}</p>
                            <div class="space-y-3">
                                <button v-for="option in currentQuestion.options" :key="option"
                                        @click="selectOption(option)"
                                        :disabled="answerSubmitted"
                                        class="w-full p-4 rounded-xl border-2 transition-all text-gray-900 text-left"
                                        :class="getOptionClass(option)">
                                    {{ option }}
                                </button>
                            </div>
                        </div>

                        <!-- Error Correction -->
                        <div v-else-if="currentQuestion?.type === 'error_correction'" class="space-y-6">
                            <p class="text-gray-600 text-sm mb-2">Xatoni toping va tuzating:</p>
                            <p class="text-gray-900 text-xl italic bg-gray-50 p-4 rounded-xl">
                                "{{ currentQuestion.sentence }}"
                            </p>
                            <div v-if="currentQuestion.is_correct_sentence" class="space-y-3">
                                <button @click="selectOption('correct')"
                                        :disabled="answerSubmitted"
                                        class="w-full p-4 rounded-xl border-2 transition-all text-gray-900"
                                        :class="selectedAnswer === 'correct' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:bg-gray-50'">
                                    ✓ Gap to'g'ri
                                </button>
                            </div>
                            <div v-else>
                                <input v-model="typedAnswer"
                                       type="text"
                                       placeholder="To'g'rilangan gapni yozing..."
                                       :disabled="answerSubmitted"
                                       class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-emerald-500 focus:outline-none"
                                       @keyup.enter="submitTypedAnswer" />
                                <button @click="submitTypedAnswer"
                                        :disabled="answerSubmitted || !typedAnswer.trim()"
                                        class="mt-3 w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-colors disabled:opacity-50">
                                    Tekshirish
                                </button>
                            </div>
                        </div>

                        <!-- Sentence Transformation -->
                        <div v-else-if="currentQuestion?.type === 'sentence_transformation'" class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-xl">
                                <p class="text-gray-600 text-sm mb-2">{{ currentQuestion.instruction }}:</p>
                                <p class="text-gray-900 text-lg italic">"{{ currentQuestion.original_sentence }}"</p>
                            </div>
                            <div v-if="currentQuestion.hints?.length" class="text-emerald-600 text-sm">
                                <span class="font-medium">Yordam:</span>
                                {{ currentQuestion.hints.join(' | ') }}
                            </div>
                            <input v-model="typedAnswer"
                                   type="text"
                                   placeholder="Yangi gapni yozing..."
                                   :disabled="answerSubmitted"
                                   class="w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-emerald-500 focus:outline-none"
                                   @keyup.enter="submitTypedAnswer" />
                            <button @click="submitTypedAnswer"
                                    :disabled="answerSubmitted || !typedAnswer.trim()"
                                    class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-colors disabled:opacity-50">
                                Tekshirish
                            </button>
                        </div>
                    </div>

                    <!-- Feedback -->
                    <Transition name="fade">
                        <div v-if="answerSubmitted && feedback"
                             class="rounded-2xl p-6 mb-6"
                             :class="feedback.is_correct ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">{{ feedback.is_correct ? '✅' : '❌' }}</span>
                                <span class="text-gray-900 text-xl font-bold">
                                    {{ feedback.is_correct ? 'To\'g\'ri!' : 'Noto\'g\'ri' }}
                                </span>
                                <span v-if="feedback.is_correct" class="ml-auto text-emerald-600 font-bold">
                                    +{{ feedback.points_earned }} ball
                                </span>
                            </div>
                            <div v-if="!feedback.is_correct" class="text-gray-900 mb-2">
                                <span class="text-emerald-600">To'g'ri javob:</span>
                                {{ feedback.correct_answer }}
                            </div>
                            <div v-if="feedback.explanation" class="text-gray-700 text-sm bg-white p-3 rounded-lg">
                                <span class="font-medium">Izoh:</span> {{ feedback.explanation }}
                            </div>
                            <div v-if="feedback.grammar_rule_uz" class="text-teal-700 text-sm mt-2">
                                <span class="font-medium">Qoida:</span> {{ feedback.grammar_rule_uz }}
                            </div>
                            <button @click="nextQuestion"
                                    class="mt-4 w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl transition-colors">
                                {{ feedback.has_next_question ? 'Keyingi savol' : 'Natijalarni ko\'rish' }}
                            </button>
                        </div>
                    </Transition>

                    <!-- Powerups -->
                    <div class="flex justify-center space-x-4">
                        <button @click="useHint"
                                :disabled="hintsUsed >= maxHints || answerSubmitted"
                                class="flex items-center space-x-2 px-4 py-2 bg-white rounded-xl shadow border border-gray-100 text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50">
                            <span class="text-xl">💡</span>
                            <span>Yordam ({{ maxHints - hintsUsed }})</span>
                        </button>
                        <button @click="useFiftyFifty"
                                :disabled="fiftyFiftyUsed || answerSubmitted || !hasFiftyFiftyOptions"
                                class="flex items-center space-x-2 px-4 py-2 bg-white rounded-xl shadow border border-gray-100 text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50">
                            <span class="text-xl">✂️</span>
                            <span>50/50</span>
                        </button>
                        <button @click="skipQuestion"
                                :disabled="answerSubmitted"
                                class="flex items-center space-x-2 px-4 py-2 bg-white rounded-xl shadow border border-gray-100 text-gray-900 hover:bg-gray-50 transition-colors disabled:opacity-50">
                            <span class="text-xl">⏭️</span>
                            <span>O'tkazish</span>
                        </button>
                    </div>

                    <!-- Hint Display -->
                    <div v-if="currentHint" class="mt-4 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <p class="text-yellow-800 text-sm">
                            <span class="font-bold">Qoida:</span> {{ currentHint.grammar_rule_uz || currentHint.grammar_rule }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Screen -->
        <div v-else-if="gameState === 'results'" class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 max-w-md w-full">
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">
                        {{ results.stars === 3 ? '🏆' : results.stars === 2 ? '⭐' : results.stars === 1 ? '👍' : '📚' }}
                    </div>
                    <h2 class="text-gray-900 text-2xl font-bold mb-2">
                        {{ results.stars === 3 ? 'Mukammal!' : results.stars === 2 ? 'Yaxshi!' : results.stars >= 1 ? 'Yomon emas!' : 'Qayta urinib ko\'ring' }}
                    </h2>
                    <p class="text-emerald-600">{{ level.name }}</p>

                    <!-- Stars -->
                    <div class="flex justify-center space-x-2 mt-4">
                        <StarIcon v-for="star in 3" :key="star"
                                 class="w-10 h-10 transition-all duration-500"
                                 :class="star <= results.stars
                                     ? 'text-yellow-400 fill-yellow-400 scale-110'
                                     : 'text-gray-300'"
                                 :style="{ transitionDelay: `${star * 200}ms` }" />
                    </div>
                </div>

                <!-- Results Stats -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ball</span>
                        <span class="text-gray-900 font-bold">{{ results.score }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Aniqlik</span>
                        <span class="text-gray-900 font-bold">{{ results.accuracy }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">To'g'ri javoblar</span>
                        <span class="text-gray-900 font-bold">{{ results.correct_answers }}/{{ results.total_questions }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Eng yaxshi streak</span>
                        <span class="text-gray-900 font-bold">{{ results.max_streak }} 🔥</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Vaqt</span>
                        <span class="text-gray-900 font-bold">{{ formatTime(results.time_taken) }}</span>
                    </div>
                </div>

                <!-- Rewards -->
                <div class="flex justify-center space-x-8 mb-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-emerald-600">+{{ results.xp_earned }}</div>
                        <div class="text-gray-600 text-sm">XP</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-500">+{{ results.coins_earned }}</div>
                        <div class="text-gray-600 text-sm">Tangalar</div>
                    </div>
                </div>

                <!-- Combo Bonuses -->
                <div v-if="results.combo_bonuses?.length" class="bg-orange-50 rounded-xl p-4 mb-6 border border-orange-100">
                    <p class="text-orange-700 text-sm font-medium mb-2">Combo bonuslari:</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="bonus in results.combo_bonuses" :key="bonus.streak"
                              class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-sm">
                            {{ bonus.streak }}x streak: +{{ bonus.bonus }}
                        </span>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <Link :href="route('student.english.games.grammar-quiz.index')"
                          class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors text-center">
                        Bosqichlarga
                    </Link>
                    <button @click="restartGame"
                            class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl transition-colors font-semibold">
                        Qayta o'ynash
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    level: Object,
    config: Object,
    quizTypes: Object,
    powerups: Array,
    difficultyLevels: Object,
});

// Game state
const gameState = ref('pregame');
const sessionId = ref(null);
const isLoading = ref(false);
const selectedQuizType = ref('mixed');

// Question state
const currentQuestion = ref(null);
const currentIndex = ref(0);
const totalQuestions = ref(0);
const selectedAnswer = ref(null);
const typedAnswer = ref('');
const answerSubmitted = ref(false);
const feedback = ref(null);

// Score state
const score = ref(0);
const streak = ref(0);

// Timer
const timeRemaining = ref(0);
const timerInterval = ref(null);

// Powerups
const hintsUsed = ref(0);
const maxHints = ref(3);
const fiftyFiftyUsed = ref(false);
const currentHint = ref(null);
const reducedOptions = ref(null);

// Results
const results = ref(null);

// Computed
const availableQuizTypes = computed(() => {
    const types = { mixed: { icon: '🎲', name: 'Aralash', point_multiplier: 1.0 }, ...props.quizTypes };
    return types;
});

const hasFiftyFiftyOptions = computed(() => {
    return currentQuestion.value?.options?.length >= 4;
});

// Methods
const startGame = async () => {
    isLoading.value = true;
    try {
        const levelNum = props.level?.level_number;
        const response = await axios.post(route('student.english.games.grammar-quiz.start', { level: levelNum }), {
            quiz_type: selectedQuizType.value,
        });

        if (response.data.success) {
            sessionId.value = response.data.session_id;
            currentQuestion.value = response.data.current_question;
            currentIndex.value = response.data.current_index;
            totalQuestions.value = response.data.total_questions;
            timeRemaining.value = response.data.time_limit;

            // Set max hints based on difficulty
            const difficulty = props.difficultyLevels?.[props.level.difficulty];
            maxHints.value = difficulty?.hints_allowed ?? 3;

            gameState.value = 'playing';
            startTimer();
        }
    } catch (error) {
        console.error('Failed to start game:', error);
    } finally {
        isLoading.value = false;
    }
};


const startTimer = () => {
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

const selectOption = (option) => {
    if (answerSubmitted.value) return;
    selectedAnswer.value = option;
    submitAnswer(option);
};

const submitTypedAnswer = () => {
    if (answerSubmitted.value || !typedAnswer.value.trim()) return;
    submitAnswer(typedAnswer.value.trim());
};

const submitAnswer = async (answer) => {
    answerSubmitted.value = true;
    const startTime = Date.now() - (timeRemaining.value * 1000);
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);

    try {
        const response = await axios.post(route('student.english.games.grammar-quiz.check'), {
            session_id: sessionId.value,
            answer: answer,
            time_spent: Math.min(timeSpent, 60),
        });

        if (response.data.success) {
            feedback.value = response.data;
            score.value = response.data.current_score;
            streak.value = response.data.current_streak;

            if (response.data.has_next_question) {
                // Prepare next question
            } else {
                // Game will end
            }
        }
    } catch (error) {
        console.error('Failed to check answer:', error);
    }
};

const nextQuestion = () => {
    if (!feedback.value?.has_next_question) {
        endGame();
        return;
    }

    currentQuestion.value = feedback.value.next_question;
    currentIndex.value = feedback.value.next_index;
    selectedAnswer.value = null;
    typedAnswer.value = '';
    answerSubmitted.value = false;
    feedback.value = null;
    currentHint.value = null;
    reducedOptions.value = null;
};

const useHint = async () => {
    try {
        const response = await axios.post(route('student.english.games.grammar-quiz.hint'), {
            session_id: sessionId.value,
        });

        if (response.data.success) {
            currentHint.value = response.data.hint;
            hintsUsed.value = maxHints.value - response.data.hints_remaining;
        }
    } catch (error) {
        console.error('Failed to use hint:', error);
    }
};

const useFiftyFifty = async () => {
    try {
        const response = await axios.post(route('student.english.games.grammar-quiz.fifty-fifty'), {
            session_id: sessionId.value,
        });

        if (response.data.success) {
            fiftyFiftyUsed.value = true;
            currentQuestion.value.options = response.data.remaining_options;
        }
    } catch (error) {
        console.error('Failed to use 50/50:', error);
    }
};

const skipQuestion = async () => {
    try {
        const response = await axios.post(route('student.english.games.grammar-quiz.skip'), {
            session_id: sessionId.value,
        });

        if (response.data.success) {
            streak.value = 0;
            if (response.data.has_next_question) {
                currentQuestion.value = response.data.next_question;
                currentIndex.value = response.data.next_index;
                selectedAnswer.value = null;
                typedAnswer.value = '';
                answerSubmitted.value = false;
                feedback.value = null;
                currentHint.value = null;
            } else {
                endGame();
            }
        }
    } catch (error) {
        console.error('Failed to skip:', error);
    }
};

const endGame = async () => {
    stopTimer();
    try {
        const response = await axios.post(route('student.english.games.grammar-quiz.complete'), {
            session_id: sessionId.value,
        });

        if (response.data.success) {
            results.value = response.data;
            gameState.value = 'results';
        }
    } catch (error) {
        console.error('Failed to complete game:', error);
    }
};

const restartGame = () => {
    // Reset all state
    sessionId.value = null;
    currentQuestion.value = null;
    currentIndex.value = 0;
    totalQuestions.value = 0;
    selectedAnswer.value = null;
    typedAnswer.value = '';
    answerSubmitted.value = false;
    feedback.value = null;
    score.value = 0;
    streak.value = 0;
    hintsUsed.value = 0;
    fiftyFiftyUsed.value = false;
    currentHint.value = null;
    reducedOptions.value = null;
    results.value = null;
    gameState.value = 'pregame';
};

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const getSentenceParts = (sentence) => {
    if (!sentence) return [];
    return sentence.split(/(_+)/);
};

const getQuestionTypeName = (type) => {
    const names = {
        fill_blank: 'Bo\'sh joyni to\'ldiring',
        multiple_choice: 'Ko\'p tanlov',
        error_correction: 'Xatoni tuzating',
        sentence_transformation: 'Gap o\'zgartirish',
    };
    return names[type] || type;
};

const getOptionClass = (option) => {
    if (!answerSubmitted.value) {
        return selectedAnswer.value === option
            ? 'border-emerald-500 bg-emerald-50'
            : 'border-gray-200 hover:bg-gray-50';
    }

    const normalizedOption = option.toLowerCase().trim();
    const normalizedCorrect = feedback.value?.correct_answer?.toLowerCase().trim();
    const normalizedSelected = selectedAnswer.value?.toLowerCase().trim();

    if (normalizedOption === normalizedCorrect) {
        return 'border-green-500 bg-green-50';
    }
    if (normalizedOption === normalizedSelected && !feedback.value?.is_correct) {
        return 'border-red-500 bg-red-50';
    }
    return 'border-gray-200 opacity-50';
};

// Lifecycle
onMounted(() => {
    // Any initialization
});

onUnmounted(() => {
    stopTimer();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
