<template>
    <StudentLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-purple-900 py-4 sm:py-6">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Header with Progress -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <button
                            @click="exitReview"
                            class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="font-medium">Chiqish</span>
                        </button>

                        <div class="flex items-center gap-4">
                            <!-- Streak -->
                            <div class="flex items-center gap-1 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-3 py-1 rounded-full">
                                <span class="text-lg">{{ currentStreak }}</span>
                            </div>

                            <!-- Score -->
                            <div class="flex items-center gap-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 px-3 py-1 rounded-full">
                                <span class="font-semibold">{{ correctCount }}/{{ totalItems }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            class="absolute inset-y-0 left-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500"
                            :style="{ width: `${progressPercent}%` }"
                        ></div>
                    </div>
                    <div class="flex justify-between mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ currentIndex + 1 }} / {{ totalItems }}</span>
                        <span>{{ Math.round(progressPercent) }}% tugallandi</span>
                    </div>
                </div>

                <!-- Review Card -->
                <div v-if="currentItem && !isCompleted" class="relative">
                    <!-- Flashcard Mode -->
                    <div v-if="currentItem.review_type === 'flashcard'" class="perspective-1000">
                        <div
                            class="relative w-full h-80 sm:h-96 cursor-pointer transition-transform duration-500 transform-style-3d"
                            :class="{ 'rotate-y-180': isFlipped }"
                            @click="flipCard"
                        >
                            <!-- Front -->
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-3xl shadow-xl p-8 flex flex-col items-center justify-center backface-hidden">
                                <div class="text-center text-white">
                                    <p class="text-sm uppercase tracking-wider opacity-80 mb-4">Inglizcha</p>
                                    <h2 class="text-4xl sm:text-5xl font-bold mb-4">{{ currentItem.word }}</h2>
                                    <p v-if="currentItem.pronunciation" class="text-xl opacity-80">{{ currentItem.pronunciation }}</p>
                                    <button
                                        v-if="currentItem.audio_url"
                                        @click.stop="playAudio"
                                        class="mt-6 p-4 bg-white/20 hover:bg-white/30 rounded-full transition-colors"
                                    >
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="absolute bottom-4 text-sm text-white/60">Kartani aylantirish uchun bosing</p>
                            </div>

                            <!-- Back -->
                            <div class="absolute inset-0 bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 flex flex-col items-center justify-center backface-hidden rotate-y-180">
                                <div class="text-center">
                                    <p class="text-sm uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">O'zbekcha</p>
                                    <h2 class="text-4xl sm:text-5xl font-bold text-gray-800 dark:text-white mb-4">{{ currentItem.translation }}</h2>
                                    <p v-if="currentItem.definition" class="text-lg text-gray-600 dark:text-gray-300 mt-4">{{ currentItem.definition }}</p>
                                    <p v-if="currentItem.example" class="text-base text-gray-500 dark:text-gray-400 mt-4 italic">"{{ currentItem.example }}"</p>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Buttons -->
                        <div v-if="isFlipped" class="flex justify-center gap-4 mt-8">
                            <button
                                @click="rateWord('again')"
                                class="px-6 py-3 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl font-semibold hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                            >
                                Yana
                            </button>
                            <button
                                @click="rateWord('hard')"
                                class="px-6 py-3 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-xl font-semibold hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"
                            >
                                Qiyin
                            </button>
                            <button
                                @click="rateWord('good')"
                                class="px-6 py-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl font-semibold hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                            >
                                Yaxshi
                            </button>
                            <button
                                @click="rateWord('easy')"
                                class="px-6 py-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl font-semibold hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                            >
                                Oson
                            </button>
                        </div>
                    </div>

                    <!-- Multiple Choice Mode -->
                    <div v-else-if="currentItem.review_type === 'multiple_choice' || currentItem.review_type === 'reverse'" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8">
                        <div class="text-center mb-8">
                            <p class="text-sm uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Savol</p>
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">{{ currentItem.question }}</h2>
                            <button
                                v-if="currentItem.audio_url && currentItem.review_type !== 'reverse'"
                                @click="playAudio"
                                class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-full text-purple-600 dark:text-purple-400 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="grid gap-4">
                            <button
                                v-for="(option, index) in currentItem.options"
                                :key="index"
                                @click="selectOption(option)"
                                :disabled="showResult"
                                class="p-4 rounded-2xl border-2 text-left font-medium transition-all transform hover:scale-[1.02]"
                                :class="getOptionClass(option)"
                            >
                                <div class="flex items-center gap-4">
                                    <span class="w-10 h-10 flex items-center justify-center rounded-xl text-lg font-bold"
                                        :class="showResult && option.is_correct ? 'bg-green-500 text-white' : showResult && selectedOption === option && !option.is_correct ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                    >
                                        {{ String.fromCharCode(65 + index) }}
                                    </span>
                                    <span class="flex-1 text-lg">{{ option.value }}</span>
                                    <span v-if="showResult && option.is_correct" class="text-green-500 text-2xl">&#10003;</span>
                                    <span v-if="showResult && selectedOption === option && !option.is_correct" class="text-red-500 text-2xl">&#10007;</span>
                                </div>
                            </button>
                        </div>

                        <!-- Next Button -->
                        <div v-if="showResult" class="mt-8 text-center">
                            <button
                                @click="nextItem"
                                class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity"
                            >
                                Keyingisi
                            </button>
                        </div>
                    </div>

                    <!-- Typing Mode -->
                    <div v-else-if="currentItem.review_type === 'typing'" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8">
                        <div class="text-center mb-8">
                            <p class="text-sm uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Savol</p>
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">{{ currentItem.question }}</h2>
                            <p v-if="currentItem.hint" class="text-lg text-gray-400 dark:text-gray-500 mt-4 font-mono">Maslahat: {{ currentItem.hint }}</p>
                        </div>

                        <div class="max-w-md mx-auto">
                            <input
                                v-model="typedAnswer"
                                @keyup.enter="checkTypedAnswer"
                                type="text"
                                :disabled="showResult"
                                class="w-full px-6 py-4 text-xl text-center border-2 rounded-2xl focus:outline-none focus:ring-4 transition-all"
                                :class="showResult ? (isCorrect ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20') : 'border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:border-purple-500 focus:ring-purple-500/20'"
                                placeholder="Javobingizni yozing..."
                                autocomplete="off"
                            />

                            <div v-if="showResult" class="mt-4 text-center">
                                <p v-if="isCorrect" class="text-green-600 dark:text-green-400 font-semibold text-lg">To'g'ri!</p>
                                <p v-else class="text-red-600 dark:text-red-400 font-semibold text-lg">
                                    Noto'g'ri. To'g'ri javob: <span class="font-bold">{{ currentItem.word }}</span>
                                </p>
                            </div>

                            <div class="mt-6 flex justify-center gap-4">
                                <button
                                    v-if="!showResult"
                                    @click="checkTypedAnswer"
                                    class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity"
                                >
                                    Tekshirish
                                </button>
                                <button
                                    v-if="showResult"
                                    @click="nextItem"
                                    class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity"
                                >
                                    Keyingisi
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Listening Mode -->
                    <div v-else-if="currentItem.review_type === 'listening'" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8">
                        <div class="text-center mb-8">
                            <p class="text-sm uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Tinglang va tanlang</p>
                            <button
                                @click="playAudio"
                                class="w-24 h-24 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white shadow-lg hover:opacity-90 transition-opacity mx-auto"
                            >
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                            <p class="text-gray-500 dark:text-gray-400 mt-4">So'zni eshiting va to'g'ri javobni tanlang</p>
                        </div>

                        <div class="grid gap-4">
                            <button
                                v-for="(option, index) in currentItem.options"
                                :key="index"
                                @click="selectOption(option)"
                                :disabled="showResult"
                                class="p-4 rounded-2xl border-2 text-left font-medium transition-all transform hover:scale-[1.02]"
                                :class="getOptionClass(option)"
                            >
                                <div class="flex items-center gap-4">
                                    <span class="w-10 h-10 flex items-center justify-center rounded-xl text-lg font-bold"
                                        :class="showResult && option.is_correct ? 'bg-green-500 text-white' : showResult && selectedOption === option && !option.is_correct ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                    >
                                        {{ String.fromCharCode(65 + index) }}
                                    </span>
                                    <span class="flex-1 text-lg">{{ option.value }}</span>
                                </div>
                            </button>
                        </div>

                        <div v-if="showResult" class="mt-8 text-center">
                            <button
                                @click="nextItem"
                                class="px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity"
                            >
                                Keyingisi
                            </button>
                        </div>
                    </div>

                    <!-- Word Status Badge -->
                    <div class="absolute -top-3 right-4">
                        <span
                            class="px-3 py-1 rounded-full text-sm font-medium"
                            :class="getStatusClass(currentItem.status)"
                        >
                            {{ getStatusText(currentItem.status) }}
                        </span>
                    </div>
                </div>

                <!-- Completion Screen -->
                <div v-if="isCompleted" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 text-center">
                    <div class="mb-8">
                        <div class="w-24 h-24 mx-auto bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Tabriklaymiz!</h2>
                        <p class="text-gray-600 dark:text-gray-300">Ko'rib chiqish sessiyasi yakunlandi</p>
                    </div>

                    <!-- Stars Display -->
                    <div v-if="completionStats.stars > 0" class="flex justify-center gap-1 mb-6">
                        <span v-for="n in 3" :key="n" class="text-4xl">
                            {{ n <= completionStats.stars ? '⭐' : '☆' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-8">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-4">
                            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ completionStats.correct }}/{{ completionStats.total }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">To'g'ri javoblar</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-4">
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ completionStats.accuracy }}%</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Aniqlik</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-4 sm:col-span-1 col-span-2">
                            <div class="flex justify-center gap-6">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">+{{ completionStats.xp_earned }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">XP</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-amber-500 dark:text-amber-400">+{{ completionStats.coins_earned || 0 }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Coin</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perfect Session Badge -->
                    <div v-if="completionStats.is_perfect" class="mb-6">
                        <div class="inline-flex items-center gap-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-3 rounded-full font-bold">
                            <span class="text-2xl">🏆</span>
                            <span>Mukammal natija!</span>
                        </div>
                    </div>

                    <!-- Achievements -->
                    <div v-if="completionStats.new_achievements?.length > 0" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Yangi yutuqlar!</h3>
                        <div class="flex justify-center gap-4">
                            <div
                                v-for="achievement in completionStats.new_achievements"
                                :key="achievement.id"
                                class="bg-gradient-to-r from-yellow-400 to-orange-500 rounded-2xl p-4 text-white"
                            >
                                <span class="text-3xl">{{ achievement.icon }}</span>
                                <p class="font-semibold mt-2">{{ achievement.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center gap-4">
                        <button
                            @click="goToReview"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            Orqaga
                        </button>
                        <button
                            @click="startNewSession"
                            class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:opacity-90 transition-opacity"
                        >
                            Yana boshlash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    session: Object,
    config: Object,
    scoringConfig: Object,
});

// State
const currentIndex = ref(0);
const correctCount = ref(0);
const currentStreak = ref(0);
const bestStreak = ref(0);
const isFlipped = ref(false);
const showResult = ref(false);
const isCorrect = ref(false);
const selectedOption = ref(null);
const typedAnswer = ref('');
const isCompleted = ref(false);
const completionStats = ref({});
const startTime = ref(Date.now());

// Computed
const reviewItems = computed(() => props.session?.review_items || []);
const totalItems = computed(() => reviewItems.value.length);
const currentItem = computed(() => reviewItems.value[currentIndex.value]);
const progressPercent = computed(() => totalItems.value > 0 ? ((currentIndex.value) / totalItems.value) * 100 : 0);

// Methods
const flipCard = () => {
    isFlipped.value = !isFlipped.value;
};

const playAudio = () => {
    if (currentItem.value?.audio_url) {
        const audio = new Audio(currentItem.value.audio_url);
        audio.play().catch(err => {
            // TTS fallback
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(currentItem.value.word);
                utterance.lang = 'en-US';
                speechSynthesis.speak(utterance);
            }
        });
    } else if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(currentItem.value.word);
        utterance.lang = 'en-US';
        speechSynthesis.speak(utterance);
    }
};

const rateWord = async (rating) => {
    const correct = rating === 'good' || rating === 'easy';

    // Update counts first
    if (correct) {
        correctCount.value++;
        currentStreak.value++;
        if (currentStreak.value > bestStreak.value) {
            bestStreak.value = currentStreak.value;
        }
    } else {
        currentStreak.value = 0;
    }

    // Send to server (don't wait for response to advance)
    axios.post('/student/english/vocabulary/review/check', {
        word_id: currentItem.value.word_id,
        answer: rating,
        review_type: 'flashcard',
    }).catch(e => {
        console.error('Error checking answer:', e);
    });

    // Small delay to ensure state updates before transitioning
    await new Promise(resolve => setTimeout(resolve, 100));

    // Move to next item
    nextItem();
};

const selectOption = async (option) => {
    if (showResult.value) return;

    selectedOption.value = option;
    isCorrect.value = option.is_correct;
    showResult.value = true;

    try {
        await axios.post('/student/english/vocabulary/review/check', {
            word_id: currentItem.value.word_id,
            answer: option.value,
            review_type: currentItem.value.review_type,
        });
    } catch (e) {
        console.error('Error checking answer:', e);
    }

    if (option.is_correct) {
        correctCount.value++;
        currentStreak.value++;
        if (currentStreak.value > bestStreak.value) {
            bestStreak.value = currentStreak.value;
        }
    } else {
        currentStreak.value = 0;
    }
};

const checkTypedAnswer = async () => {
    if (showResult.value || !typedAnswer.value.trim()) return;

    try {
        const response = await axios.post('/student/english/vocabulary/review/check', {
            word_id: currentItem.value.word_id,
            answer: typedAnswer.value.trim(),
            review_type: 'typing',
        });

        isCorrect.value = response.data.result?.correct || false;
    } catch (e) {
        console.error('Error checking answer:', e);
        isCorrect.value = typedAnswer.value.trim().toLowerCase() === currentItem.value.word.toLowerCase();
    }

    showResult.value = true;

    if (isCorrect.value) {
        correctCount.value++;
        currentStreak.value++;
        if (currentStreak.value > bestStreak.value) {
            bestStreak.value = currentStreak.value;
        }
    } else {
        currentStreak.value = 0;
    }
};

const nextItem = () => {
    if (currentIndex.value + 1 >= totalItems.value) {
        completeSession();
    } else {
        currentIndex.value++;
        isFlipped.value = false;
        showResult.value = false;
        selectedOption.value = null;
        typedAnswer.value = '';
        isCorrect.value = false;
    }
};

const completeSession = async () => {
    const timeTaken = Math.round((Date.now() - startTime.value) / 1000);

    try {
        const response = await axios.post('/student/english/vocabulary/review/complete', {
            correct: correctCount.value,
            total: totalItems.value,
            streak: bestStreak.value,
            time_taken: timeTaken,
        });

        completionStats.value = response.data.result || {
            correct: correctCount.value,
            total: totalItems.value,
            accuracy: Math.round((correctCount.value / totalItems.value) * 100),
            xp_earned: correctCount.value * 10,
        };
    } catch (e) {
        console.error('Error completing session:', e);
        completionStats.value = {
            correct: correctCount.value,
            total: totalItems.value,
            accuracy: Math.round((correctCount.value / totalItems.value) * 100),
            xp_earned: correctCount.value * 10,
        };
    }

    isCompleted.value = true;
};

const getOptionClass = (option) => {
    if (!showResult.value) {
        return 'border-gray-200 dark:border-gray-600 hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20';
    }
    if (option.is_correct) {
        return 'border-green-500 bg-green-50 dark:bg-green-900/20';
    }
    if (selectedOption.value === option && !option.is_correct) {
        return 'border-red-500 bg-red-50 dark:bg-red-900/20';
    }
    return 'border-gray-200 dark:border-gray-600 opacity-50';
};

const getStatusClass = (status) => {
    const classes = {
        'new': 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        'learning': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
        'mastered': 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
        'difficult': 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    };
    return classes[status] || classes['new'];
};

const getStatusText = (status) => {
    const texts = {
        'new': 'Yangi',
        'learning': "O'rganilmoqda",
        'mastered': "O'zlashtirilgan",
        'difficult': 'Qiyin',
    };
    return texts[status] || 'Yangi';
};

const exitReview = () => {
    if (currentIndex.value > 0 && !isCompleted.value) {
        if (confirm("Sessiyani to'xtatmoqchimisiz? Natijalaringiz saqlanmaydi.")) {
            router.visit('/student/english/vocabulary/review');
        }
    } else {
        router.visit('/student/english/vocabulary/review');
    }
};

const goToReview = () => {
    router.visit('/student/english/vocabulary/review');
};

const startNewSession = () => {
    router.reload();
};

onMounted(() => {
    // Auto-play audio for listening mode
    if (currentItem.value?.review_type === 'listening') {
        setTimeout(playAudio, 500);
    }
});
</script>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}

.transform-style-3d {
    transform-style: preserve-3d;
}

.backface-hidden {
    backface-visibility: hidden;
}

.rotate-y-180 {
    transform: rotateY(180deg);
}
</style>
