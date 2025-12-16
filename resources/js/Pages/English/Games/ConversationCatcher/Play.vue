<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 flex flex-col p-4 md:p-6">
            <!-- Header -->
            <div class="max-w-4xl mx-auto mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('student.english.games.conversation-catcher.index')"
                            class="p-2 bg-white hover:bg-gray-50 rounded-xl transition-colors shadow-md border border-gray-200"
                        >
                            <ArrowLeftIcon class="w-6 h-6 text-gray-700" />
                        </Link>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">
                                Level {{ level.level_number }}: {{ level.name }}
                            </h1>
                            <p class="text-gray-600 text-sm">{{ level.name_uz }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100 px-4 py-2 flex items-center gap-2">
                            <span class="text-yellow-600 font-bold">{{ score }}</span>
                            <span class="text-gray-600 text-sm">ball</span>
                        </div>
                        <div v-if="gameState === 'playing' && totalTime" class="bg-white rounded-xl shadow-lg border border-gray-100 px-4 py-2">
                            <span class="text-gray-900 font-bold" :class="{ 'text-red-600': timeRemaining < 30 }">
                                {{ formatTime(timeRemaining) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Game States -->
            <div class="max-w-4xl mx-auto">
                <!-- Pre-game -->
                <div v-if="gameState === 'idle'" class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 text-center">
                    <div class="text-6xl mb-4">💬</div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ level.name }}</h2>
                    <p class="text-gray-600 mb-6">{{ level.description }}</p>

                    <div class="grid grid-cols-2 gap-4 max-w-md mx-auto mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                            <div class="text-2xl font-bold text-gray-900">{{ level.dialogues_count }}</div>
                            <div class="text-gray-600 text-sm">Dialog</div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                            <div class="text-2xl font-bold text-gray-900">{{ level.total_time ? level.total_time + 's' : '∞' }}</div>
                            <div class="text-gray-600 text-sm">Vaqt</div>
                        </div>
                    </div>

                    <!-- Game Mode Selection -->
                    <div class="mb-6">
                        <p class="text-gray-700 mb-3">Rejimni tanlang:</p>
                        <div class="flex flex-wrap justify-center gap-3">
                            <button
                                v-for="mode in gameModes"
                                :key="mode.id"
                                class="px-4 py-2 rounded-xl transition-all"
                                :class="[
                                    selectedMode === mode.id
                                        ? 'bg-pink-500 text-white shadow-md'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200'
                                ]"
                                @click="selectedMode = mode.id"
                            >
                                {{ mode.icon }} {{ mode.name }}
                            </button>
                        </div>
                    </div>

                    <button
                        @click="startGame"
                        class="px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-bold rounded-xl hover:from-pink-600 hover:to-purple-600 transition-all transform hover:scale-105 shadow-lg"
                    >
                        O'yinni boshlash
                    </button>
                </div>

                <!-- Playing -->
                <div v-else-if="gameState === 'playing'" class="space-y-6">
                    <!-- Progress -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700">Dialog {{ currentIndex + 1 }}/{{ dialogues.length }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-700">Streak:</span>
                                <span class="text-yellow-600 font-bold">{{ streak }} 🔥</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-gradient-to-r from-pink-500 to-purple-500 h-2 rounded-full transition-all duration-300"
                                :style="{ width: `${((currentIndex) / dialogues.length) * 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Dialogue Card -->
                    <div v-if="currentDialogue" class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <!-- Context -->
                        <div class="mb-6 text-center">
                            <div class="inline-block bg-gradient-to-r from-purple-100 to-pink-100 px-4 py-2 rounded-xl border border-purple-200">
                                <span class="text-gray-900 font-medium">{{ currentDialogue.context }}</span>
                                <span v-if="showTranslation" class="text-gray-600 ml-2">({{ currentDialogue.context_uz }})</span>
                            </div>
                        </div>

                        <!-- Conversation Lines -->
                        <div class="space-y-4 mb-6">
                            <div
                                v-for="(line, lineIndex) in currentDialogue.lines"
                                :key="lineIndex"
                                class="flex gap-4"
                                :class="[
                                    line.speaker === 'You' || line.speaker === 'Patient' || line.speaker === 'Customer'
                                        ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <div
                                    class="max-w-[80%] rounded-2xl p-4 shadow-md"
                                    :class="[
                                        line.speaker === 'You' || line.speaker === 'Patient' || line.speaker === 'Customer'
                                            ? 'bg-gradient-to-br from-pink-100 to-pink-50 rounded-br-none border border-pink-200'
                                            : 'bg-gradient-to-br from-blue-50 to-indigo-50 rounded-bl-none border border-blue-200'
                                    ]"
                                >
                                    <div class="text-xs text-gray-600 mb-1 font-medium">{{ line.speaker }}</div>
                                    <div v-if="!line.is_question" class="text-gray-900">
                                        {{ line.text }}
                                    </div>
                                    <div v-else class="text-gray-900">
                                        <span v-if="!answered" class="text-pink-600 font-bold">{{ line.text }}</span>
                                        <span v-else>
                                            {{ selectedAnswer }}
                                            <span v-if="lastResult?.correct" class="text-green-600 ml-2">✓</span>
                                            <span v-else class="text-red-600 ml-2">✗</span>
                                        </span>
                                    </div>
                                    <div v-if="showTranslation && line.text_uz" class="text-gray-600 text-sm mt-1">
                                        {{ line.text_uz }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Answer Options -->
                        <div v-if="!answered" class="space-y-3">
                            <p class="text-gray-700 text-center mb-4 font-medium">Qaysi javob to'g'ri?</p>
                            <button
                                v-for="(option, optionIndex) in currentOptions"
                                :key="optionIndex"
                                @click="selectAnswer(option)"
                                class="w-full p-4 bg-gray-50 hover:bg-gray-100 rounded-xl text-gray-900 text-left transition-all hover:scale-[1.02] border border-gray-200 shadow-sm"
                                :class="{ 'ring-2 ring-pink-500 bg-pink-50 border-pink-300': selectedAnswer === option }"
                            >
                                <span class="font-medium mr-2">{{ String.fromCharCode(65 + optionIndex) }}.</span>
                                {{ option }}
                            </button>

                            <button
                                v-if="selectedAnswer"
                                @click="submitAnswer"
                                :disabled="isSubmitting"
                                class="w-full mt-4 py-4 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-bold rounded-xl hover:from-pink-600 hover:to-purple-600 transition-all disabled:opacity-50 shadow-lg"
                            >
                                {{ isSubmitting ? 'Tekshirilmoqda...' : 'Javobni tekshirish' }}
                            </button>
                        </div>

                        <!-- Result -->
                        <div v-else class="text-center space-y-4">
                            <div
                                v-if="lastResult?.correct"
                                class="bg-green-50 border border-green-300 rounded-xl p-4 shadow-md"
                            >
                                <div class="text-4xl mb-2">🎉</div>
                                <div class="text-green-700 font-bold text-xl">To'g'ri!</div>
                                <div class="text-green-600">+{{ lastResult.points_earned }} ball</div>
                            </div>
                            <div
                                v-else
                                class="bg-red-50 border border-red-300 rounded-xl p-4 shadow-md"
                            >
                                <div class="text-4xl mb-2">😔</div>
                                <div class="text-red-700 font-bold text-xl">Noto'g'ri</div>
                                <div class="text-gray-900 mt-2">To'g'ri javob:</div>
                                <div class="text-green-700 font-medium">{{ lastResult.correct_answer }}</div>
                            </div>

                            <button
                                @click="nextDialogue"
                                class="px-8 py-3 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-bold rounded-xl hover:from-pink-600 hover:to-purple-600 transition-all shadow-lg"
                            >
                                {{ currentIndex < dialogues.length - 1 ? 'Keyingi dialog' : 'Yakunlash' }}
                            </button>
                        </div>
                    </div>

                    <!-- Powerups -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
                        <div class="flex flex-wrap justify-center gap-3">
                            <button
                                v-for="powerup in powerups"
                                :key="powerup.id"
                                @click="usePowerup(powerup.id)"
                                :disabled="getPowerupUsesRemaining(powerup.id) <= 0"
                                class="px-4 py-2 bg-gray-50 rounded-xl text-gray-900 transition-all hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 border border-gray-200 shadow-sm"
                            >
                                <span>{{ powerup.icon }}</span>
                                <span class="text-sm">{{ powerup.name }}</span>
                                <span class="text-xs text-gray-600">({{ getPowerupUsesRemaining(powerup.id) }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Game Complete -->
                <div v-else-if="gameState === 'complete'" class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 text-center">
                    <div class="text-6xl mb-4">
                        {{ summary.stars === 3 ? '🏆' : summary.stars === 2 ? '🎉' : '👍' }}
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">O'yin yakunlandi!</h2>

                    <!-- Stars -->
                    <div class="flex justify-center gap-2 mb-6">
                        <StarIcon
                            v-for="i in 3"
                            :key="i"
                            class="w-12 h-12"
                            :class="i <= summary.stars ? 'text-yellow-400' : 'text-gray-300'"
                        />
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                            <div class="text-2xl font-bold text-gray-900">{{ summary.score }}</div>
                            <div class="text-gray-600 text-sm">Ball</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
                            <div class="text-2xl font-bold text-green-600">{{ summary.accuracy }}%</div>
                            <div class="text-gray-600 text-sm">Aniqlik</div>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-4 border border-yellow-100">
                            <div class="text-2xl font-bold text-yellow-600">{{ summary.streak }}</div>
                            <div class="text-gray-600 text-sm">Eng yaxshi streak</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-100">
                            <div class="text-2xl font-bold text-purple-600">{{ summary.correct_answers }}/{{ summary.total_answers }}</div>
                            <div class="text-gray-600 text-sm">To'g'ri</div>
                        </div>
                    </div>

                    <!-- Rewards -->
                    <div class="flex justify-center gap-6 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">⭐</span>
                            <span class="text-gray-900 font-bold">+{{ summary.xp_earned }} XP</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🪙</span>
                            <span class="text-yellow-600 font-bold">+{{ summary.coins_earned }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <button
                            @click="restartGame"
                            class="px-6 py-3 bg-gray-100 text-gray-900 font-bold rounded-xl hover:bg-gray-200 transition-all border border-gray-200 shadow-md"
                        >
                            🔄 Qayta o'ynash
                        </button>
                        <Link
                            :href="route('student.english.games.conversation-catcher.index')"
                            class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-bold rounded-xl hover:from-pink-600 hover:to-purple-600 transition-all shadow-lg"
                        >
                            📋 Darajalarga qaytish
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Hint Modal -->
            <div v-if="showHintModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 max-w-md w-full">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">💡 Maslahat</h3>
                    <p class="text-gray-700 mb-4">{{ hintText }}</p>
                    <button
                        @click="showHintModal = false"
                        class="w-full py-3 bg-gradient-to-r from-pink-500 to-purple-500 text-white font-bold rounded-xl hover:from-pink-600 hover:to-purple-600 transition-all shadow-lg"
                    >
                        Tushundim
                    </button>
                </div>
            </div>
        </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, StarIcon } from '@heroicons/vue/24/solid';
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
    }
});

const gameState = ref('idle');
const selectedMode = ref('classic');
const sessionId = ref(null);
const dialogues = ref([]);
const currentIndex = ref(0);
const score = ref(0);
const streak = ref(0);
const totalTime = ref(null);
const timeRemaining = ref(null);
const timerInterval = ref(null);

const selectedAnswer = ref(null);
const answered = ref(false);
const isSubmitting = ref(false);
const lastResult = ref(null);
const summary = ref(null);

const powerupsUsed = ref({});
const showTranslation = ref(false);
const showHintModal = ref(false);
const hintText = ref('');
const dialogueStartTime = ref(null);

const currentDialogue = computed(() => {
    if (currentIndex.value < dialogues.value.length) {
        return dialogues.value[currentIndex.value];
    }
    return null;
});

const currentOptions = computed(() => {
    if (!currentDialogue.value) return [];

    for (const line of currentDialogue.value.lines) {
        if (line.is_question && line.options) {
            return line.options;
        }
    }
    return [];
});

const startGame = async () => {
    try {
        const response = await axios.post(
            route('student.english.games.conversation-catcher.start-session', { level: props.level.level_number }),
            { mode: selectedMode.value }
        );

        if (response.data.success) {
            const data = response.data.data;
            sessionId.value = data.session_id;
            dialogues.value = data.dialogues;
            totalTime.value = data.total_time;
            timeRemaining.value = data.total_time;

            gameState.value = 'playing';
            dialogueStartTime.value = Date.now();

            if (totalTime.value) {
                startTimer();
            }
        }
    } catch (error) {
        console.error('Failed to start game:', error);
        alert('O\'yinni boshlashda xatolik yuz berdi');
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

const selectAnswer = (answer) => {
    if (!answered.value) {
        selectedAnswer.value = answer;
    }
};

const submitAnswer = async () => {
    if (!selectedAnswer.value || isSubmitting.value) return;

    isSubmitting.value = true;
    const timeSpent = (Date.now() - dialogueStartTime.value) / 1000;

    try {
        const response = await axios.post(
            route('student.english.games.conversation-catcher.submit-answer'),
            {
                session_id: sessionId.value,
                dialogue_index: currentIndex.value,
                answer: selectedAnswer.value,
                time_spent: timeSpent
            }
        );

        if (response.data.success) {
            const data = response.data.data;
            lastResult.value = data;
            answered.value = true;
            score.value = data.score;
            streak.value = data.streak;

            if (data.is_complete && data.summary) {
                summary.value = data.summary;
            }
        }
    } catch (error) {
        console.error('Failed to submit answer:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const nextDialogue = () => {
    if (lastResult.value?.is_complete) {
        endGame();
        return;
    }

    currentIndex.value++;
    selectedAnswer.value = null;
    answered.value = false;
    lastResult.value = null;
    dialogueStartTime.value = Date.now();
};

const endGame = async () => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }

    if (!summary.value) {
        try {
            const response = await axios.post(
                route('student.english.games.conversation-catcher.complete-session'),
                { session_id: sessionId.value }
            );

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
    dialogues.value = [];
    currentIndex.value = 0;
    score.value = 0;
    streak.value = 0;
    timeRemaining.value = null;
    selectedAnswer.value = null;
    answered.value = false;
    lastResult.value = null;
    summary.value = null;
    powerupsUsed.value = {};
    showTranslation.value = false;
};

const usePowerup = async (powerupId) => {
    if (getPowerupUsesRemaining(powerupId) <= 0) return;

    try {
        const response = await axios.post(
            route('student.english.games.conversation-catcher.use-powerup'),
            {
                session_id: sessionId.value,
                powerup_id: powerupId
            }
        );

        if (response.data.success) {
            const data = response.data.data;
            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1;

            switch (powerupId) {
                case 'hint':
                    hintText.value = data.hint;
                    showHintModal.value = true;
                    break;
                case 'skip':
                    if (data.is_complete) {
                        summary.value = data.summary;
                        endGame();
                    } else {
                        currentIndex.value = data.current_index;
                        selectedAnswer.value = null;
                        answered.value = false;
                        dialogueStartTime.value = Date.now();
                    }
                    break;
                case 'extra_time':
                    if (data.time_remaining !== undefined) {
                        timeRemaining.value = data.time_remaining;
                    }
                    break;
                case 'translation':
                    showTranslation.value = true;
                    break;
                case 'reveal_word':
                    hintText.value = `Javobda "${data.revealed_word}" so'zi bor`;
                    showHintModal.value = true;
                    break;
            }
        }
    } catch (error) {
        console.error('Failed to use powerup:', error);
    }
};

const getPowerupUsesRemaining = (powerupId) => {
    const powerup = props.powerups.find(p => p.id === powerupId);
    if (!powerup) return 0;

    const used = powerupsUsed.value[powerupId] || 0;
    return (powerup.uses_per_game || 1) - used;
};

const formatTime = (seconds) => {
    if (seconds === null) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
});
</script>
