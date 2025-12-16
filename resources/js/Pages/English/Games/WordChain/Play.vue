<template>
    <Head :title="`So'z Zanjiri - ${level?.name_uz || 'O\'yin'}`" />

    <StudentLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 -m-6 p-4 md:p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Start Screen -->
                <div v-if="!gameStarted" class="flex items-center justify-center min-h-[80vh]">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 md:p-12 max-w-lg w-full text-center shadow-xl border border-gray-100 dark:border-gray-700">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 rounded-3xl mb-6 shadow-2xl shadow-emerald-500/30">
                            <span class="text-5xl filter drop-shadow-lg">🔗</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white mb-2">{{ level.name_uz }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-lg mb-6">{{ level.name }}</p>

                        <!-- Level Info Badge -->
                        <div class="mt-4 inline-flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 px-5 py-2.5 rounded-full shadow-lg border border-gray-200 dark:border-gray-600">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ level.cefr_level }}</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ level.target_chain_length }} so'z</span>
                            <span class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ formatTimeDisplay(level.time_limit) }}</span>
                        </div>

                        <!-- Game Rules -->
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-6 my-8 border border-emerald-100 dark:border-emerald-800/50 text-left">
                            <h3 class="text-gray-800 dark:text-white font-bold mb-3 text-center">O'yin qoidalari</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-400 text-sm">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5">✓</span>
                                    <span>So'zlar zanjirini tuzing</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5">✓</span>
                                    <span>Har bir so'z oldingi so'zning oxirgi harfi bilan boshlanishi kerak</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5">✓</span>
                                    <span>Maqsad: <strong class="text-emerald-600 dark:text-emerald-400">{{ level.target_chain_length }} so'z</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5">✓</span>
                                    <span>Boshlang'ich harf: <strong class="text-2xl text-emerald-600 dark:text-emerald-400">{{ level.starting_letter }}</strong></span>
                                </li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                                Orqaga
                            </button>
                            <button @click="startGame" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                Boshlash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Game -->
                <div v-else-if="!gameCompleted" class="space-y-5">
                    <!-- Game Header -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-5">
                            <button @click="confirmExit" class="flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all font-medium">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Chiqish
                            </button>
                            <div class="flex items-center gap-3 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-lg">
                                <span class="text-2xl">🔗</span>
                                <span class="font-bold text-white">{{ level.name_uz }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-sm font-semibold">{{ level.cefr_level }}</span>
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
                                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ combo }}<span class="text-2xl ml-1">🔥</span></div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">Combo</div>
                            </div>
                            <div class="bg-gradient-to-br from-emerald-50 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/50">
                                <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ chain.length }}/{{ targetChainLength }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-semibold mt-1">So'zlar</div>
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
                                <span>Progress</span>
                                <span v-if="availableWordsCount !== null" class="text-emerald-600 dark:text-emerald-400">Mavjud: {{ availableWordsCount }}</span>
                            </div>
                            <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                                <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 transition-all duration-500 ease-out rounded-full shadow-lg"
                                     :style="{ width: `${(chain.length / targetChainLength) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Letter Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-3 text-lg">Keyingi so'z shu harf bilan boshlansin:</p>
                        <div class="text-7xl font-bold bg-gradient-to-r from-emerald-500 to-teal-600 bg-clip-text text-transparent animate-bounce">{{ currentLetter }}</div>
                    </div>

                    <!-- Word Input Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700">
                        <div class="flex gap-3">
                            <input
                                v-model="wordInput"
                                @keyup.enter="submitWord"
                                type="text"
                                :placeholder="`'${currentLetter}' harfi bilan so'z yozing...`"
                                class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-2 border-gray-200 dark:border-gray-600 focus:border-emerald-500 dark:focus:border-emerald-500 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 text-lg outline-none transition-colors"
                                :disabled="isSubmitting"
                                ref="wordInputRef"
                            />
                            <button
                                @click="submitWord"
                                :disabled="!wordInput.trim() || isSubmitting"
                                class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold rounded-xl hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl hover:scale-105"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>

                        <!-- Error Message -->
                        <p v-if="errorMessage" class="text-red-500 dark:text-red-400 text-sm mt-3 text-center font-medium">{{ errorMessage }}</p>

                        <!-- Last Word Info -->
                        <div v-if="lastWordInfo" class="mt-3 p-4 rounded-xl border-2" :class="lastWordInfo.valid ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800/50' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/50'">
                            <div class="flex items-center justify-between">
                                <span class="font-bold" :class="lastWordInfo.valid ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">{{ lastWordInfo.word }}</span>
                                <span v-if="lastWordInfo.valid" class="text-green-600 dark:text-green-400 font-bold">+{{ lastWordInfo.points }} ball</span>
                            </div>
                            <p v-if="lastWordInfo.translation" class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ lastWordInfo.translation }}</p>
                        </div>
                    </div>

                    <!-- Chain Display Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-xl border border-gray-100 dark:border-gray-700">
                        <h3 class="text-gray-800 dark:text-white font-bold mb-3 flex items-center gap-2">
                            <span class="text-xl">🔗</span>
                            So'zlar zanjiri
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="(item, index) in chain" :key="index" class="flex items-center">
                                <span class="px-4 py-2 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/30 dark:to-teal-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50 rounded-xl text-sm font-semibold shadow-sm">
                                    {{ item.word }}
                                </span>
                                <span v-if="index < chain.length - 1" class="mx-2 text-emerald-600 dark:text-emerald-400 text-xl font-bold">→</span>
                            </div>
                            <span v-if="chain.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">Zanjir bo'sh...</span>
                        </div>
                    </div>

                    <!-- Powerups -->
                    <div v-if="powerups && powerups.length > 0" class="flex justify-center gap-3 flex-wrap">
                        <button
                            v-for="powerup in powerups"
                            :key="powerup.id"
                            @click="usePowerup(powerup.id)"
                            :disabled="getPowerupUsesRemaining(powerup.id) <= 0"
                            class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-all disabled:opacity-30 disabled:cursor-not-allowed border border-gray-200 dark:border-gray-700 shadow-lg hover:shadow-xl hover:scale-105"
                        >
                            <span class="text-3xl mb-1">{{ powerup.icon }}</span>
                            <span class="text-gray-800 dark:text-white text-xs font-semibold">{{ powerup.name_uz }}</span>
                            <span class="text-emerald-600 dark:text-emerald-400 text-xs font-bold mt-1">{{ getPowerupUsesRemaining(powerup.id) }} qoldi</span>
                        </button>
                    </div>
                </div>

                <!-- Game Complete Screen -->
                <div v-else class="flex items-center justify-center min-h-[80vh]">
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
                        <p class="text-gray-500 dark:text-gray-400 mb-8 text-lg">{{ summary.chain_length }} so'z bilan tugatdingiz</p>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-5 border border-emerald-100 dark:border-emerald-800/50">
                                <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ summary.score }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Ball</div>
                            </div>
                            <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-5 border border-violet-100 dark:border-violet-800/50">
                                <div class="text-3xl font-bold text-violet-600 dark:text-violet-400">+{{ summary.xp_earned }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">XP</div>
                            </div>
                            <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-2xl p-5 border border-cyan-100 dark:border-cyan-800/50">
                                <div class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ summary.chain_length }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Zanjir uzunligi</div>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-2xl p-5 border border-orange-100 dark:border-orange-800/50">
                                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ summary.max_combo }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">🔥 Eng yaxshi combo</div>
                            </div>
                        </div>

                        <!-- Chain Words -->
                        <div v-if="summary.chain && summary.chain.length > 0" class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-4 mb-6 border border-gray-200 dark:border-gray-600">
                            <h3 class="text-gray-800 dark:text-white font-bold mb-2">Sizning zanjiringiz</h3>
                            <div class="flex flex-wrap justify-center gap-1">
                                <span v-for="(item, index) in summary.chain" :key="index" class="text-gray-700 dark:text-gray-300 text-sm">
                                    {{ item.word }}<span v-if="index < summary.chain.length - 1"> → </span>
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button @click="goBack" class="flex-1 py-3.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all hover:scale-105">
                                Orqaga
                            </button>
                            <button @click="restartGame" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                Qayta o'ynash
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exit Confirmation Modal -->
            <div v-if="showExitModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 max-w-sm w-full">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">O'yinni tark etish</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Haqiqatan ham chiqmoqchimisiz? Jarayon saqlanmaydi.</p>
                    <div class="flex gap-3">
                        <button @click="showExitModal = false" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                            Yo'q
                        </button>
                        <button @click="goBack" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all text-center">
                            Ha, chiqish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hint Modal -->
            <div v-if="showHintModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 max-w-sm w-full text-center">
                    <div class="text-4xl mb-4">💡</div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Maslahat</h3>
                    <p class="text-emerald-600 dark:text-emerald-400 text-2xl font-mono mb-2">{{ hintText }}</p>
                    <p v-if="hintTranslation" class="text-gray-600 dark:text-gray-400 mb-4">{{ hintTranslation }}</p>
                    <button @click="showHintModal = false" class="px-6 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl hover:opacity-90 transition-all shadow-lg">
                        Tushundim
                    </button>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import axios from 'axios';

const props = defineProps({
    level: { type: Object, required: true },
    config: { type: Object, required: true },
    powerups: { type: Array, default: () => [] },
});

// Game state
const gameStarted = ref(false);
const gameCompleted = ref(false);
const sessionId = ref(null);
const score = ref(0);
const combo = ref(0);
const chain = ref([]);
const currentLetter = ref(props.level.starting_letter);
const targetChainLength = ref(props.level.target_chain_length);
const timeLimit = ref(props.level.time_limit);
const timeRemaining = ref(props.level.time_limit);
const summary = ref(null);
const powerupsUsed = ref({});
const availableWordsCount = ref(null);

// Input state
const wordInput = ref('');
const wordInputRef = ref(null);
const isSubmitting = ref(false);
const errorMessage = ref('');
const lastWordInfo = ref(null);
const wordStartTime = ref(null);

// Modals
const showExitModal = ref(false);
const showHintModal = ref(false);
const hintText = ref('');
const hintTranslation = ref('');

let timerInterval = null;

const startGame = async () => {
    try {
        const response = await axios.post(route('student.english.games.word-chain.start', { level: props.level.level_number }));

        if (response.data.success) {
            sessionId.value = response.data.data.session_id;
            gameStarted.value = true;
            wordStartTime.value = Date.now();

            if (timeLimit.value) {
                startTimer();
            }

            await nextTick();
            wordInputRef.value?.focus();
            updateAvailableWords();
        }
    } catch (error) {
        console.error('Failed to start game:', error);
    }
};

const submitWord = async () => {
    if (!wordInput.value.trim() || isSubmitting.value) return;

    const word = wordInput.value.trim();
    const timeSpent = (Date.now() - wordStartTime.value) / 1000;

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('student.english.games.word-chain.submit'), {
            session_id: sessionId.value,
            word: word,
            time_spent: timeSpent,
        });

        if (response.data.success) {
            const data = response.data.data;

            if (data.valid) {
                // Success
                chain.value.push({
                    word: data.word,
                    translation: data.translation,
                    points: data.points_earned,
                });
                score.value = data.score;
                combo.value = data.combo;
                currentLetter.value = data.next_letter;

                lastWordInfo.value = {
                    valid: true,
                    word: data.word,
                    translation: data.translation,
                    points: data.points_earned,
                };

                wordInput.value = '';
                wordStartTime.value = Date.now();
                updateAvailableWords();

                if (data.target_reached) {
                    gameCompleted.value = true;
                    summary.value = data.summary;
                    stopTimer();
                }
            } else {
                // Invalid word
                errorMessage.value = data.message;
                lastWordInfo.value = {
                    valid: false,
                    word: word,
                };
            }
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Xatolik yuz berdi";
    } finally {
        isSubmitting.value = false;
        wordInputRef.value?.focus();
    }
};

const usePowerup = async (powerupId) => {
    if (getPowerupUsesRemaining(powerupId) <= 0) return;

    try {
        let response;

        if (powerupId === 'hint') {
            response = await axios.post(route('student.english.games.word-chain.hint'), {
                session_id: sessionId.value,
            });
        } else if (powerupId === 'skip_letter') {
            response = await axios.post(route('student.english.games.word-chain.skip'), {
                session_id: sessionId.value,
            });
        } else {
            response = await axios.post(route('student.english.games.word-chain.powerup'), {
                session_id: sessionId.value,
                powerup_id: powerupId,
            });
        }

        if (response.data.success) {
            const data = response.data.data;

            powerupsUsed.value[powerupId] = (powerupsUsed.value[powerupId] || 0) + 1;

            if (powerupId === 'hint') {
                hintText.value = data.hint;
                hintTranslation.value = data.translation;
                showHintModal.value = true;
                combo.value = 0;
            } else if (powerupId === 'skip_letter') {
                currentLetter.value = data.new_letter;
                combo.value = 0;
                updateAvailableWords();
            } else if (powerupId === 'extra_time') {
                timeRemaining.value = data.time_remaining;
            } else if (powerupId === 'word_reveal') {
                if (data.revealed_word) {
                    hintText.value = data.revealed_word;
                    hintTranslation.value = data.translation;
                    showHintModal.value = true;
                }
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

const updateAvailableWords = async () => {
    try {
        const response = await axios.post(route('student.english.games.word-chain.available'), {
            session_id: sessionId.value,
        });

        if (response.data.success) {
            availableWordsCount.value = response.data.data.available_count;
        }
    } catch (error) {
        console.error('Failed to get available words:', error);
    }
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
        await axios.post(route('student.english.games.word-chain.time'), {
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
        const response = await axios.post(route('student.english.games.word-chain.complete'), {
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
    score.value = 0;
    combo.value = 0;
    chain.value = [];
    currentLetter.value = props.level.starting_letter;
    timeRemaining.value = props.level.time_limit;
    summary.value = null;
    powerupsUsed.value = {};
    wordInput.value = '';
    errorMessage.value = '';
    lastWordInfo.value = null;
    availableWordsCount.value = null;
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
    router.visit(route('student.english.games.word-chain.index'));
};

const formatTimeDisplay = (seconds) => {
    if (seconds === null) return '--:--';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

onMounted(() => {
    // Keyboard shortcut for submit
    const handleKeydown = (e) => {
        if (e.key === 'Enter' && gameStarted.value && !gameCompleted.value) {
            submitWord();
        }
    };
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    stopTimer();
});
</script>
