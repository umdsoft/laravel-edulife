<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import TestTimer from '@/Components/Student/TestTimer.vue';
import TestProgress from '@/Components/Student/TestProgress.vue';
import QuestionNavigation from '@/Components/Student/QuestionNavigation.vue';
import AntiCheatWarning from '@/Components/Student/AntiCheatWarning.vue';
import TestSubmitConfirm from '@/Components/Student/TestSubmitConfirm.vue';
import QuestionSingleChoice from '@/Components/Student/QuestionSingleChoice.vue';
import QuestionMultipleChoice from '@/Components/Student/QuestionMultipleChoice.vue';
import QuestionTrueFalse from '@/Components/Student/QuestionTrueFalse.vue';
import QuestionFillBlank from '@/Components/Student/QuestionFillBlank.vue';
import QuestionMatching from '@/Components/Student/QuestionMatching.vue';
import QuestionOrdering from '@/Components/Student/QuestionOrdering.vue';

const props = defineProps({
    attempt: Object,
    test: Object,
    course: Object,
    questions: Array,
    timeRemaining: Number
});

const currentQuestionIndex = ref(0);
const answers = ref({});
const isSubmitting = ref(false);
const showSubmitConfirm = ref(false);
const showAntiCheatWarning = ref(false);
const warningsCount = ref(0);
const startTime = ref(Date.now());

// Initialize answers
props.questions.forEach(q => {
    answers.value[q.id] = q.user_answer;
});

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value]);
const isLastQuestion = computed(() => currentQuestionIndex.value === props.questions.length - 1);
const unansweredCount = computed(() => props.questions.filter(q => !q.is_answered).length);

// Navigation
const nextQuestion = () => {
    if (!isLastQuestion.value) {
        saveCurrentAnswer();
        currentQuestionIndex.value++;
        startTime.value = Date.now();
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        saveCurrentAnswer();
        currentQuestionIndex.value--;
        startTime.value = Date.now();
    }
};

const jumpToQuestion = (index) => {
    saveCurrentAnswer();
    currentQuestionIndex.value = index;
    startTime.value = Date.now();
};

// Saving answers
const saveCurrentAnswer = async () => {
    const q = currentQuestion.value;
    const answer = answers.value[q.id];

    // Only save if changed or not saved yet (optimization can be added here)
    // For now, save on navigation

    try {
        const timeSpent = Math.round((Date.now() - startTime.value) / 1000);

        await axios.post(route('student.tests.save-answer', props.attempt.id), {
            question_id: q.id,
            answer: answer,
            time_on_question: timeSpent
        });

        // Update local state
        q.is_answered = !!answer && (Array.isArray(answer) ? answer.length > 0 : true);
        q.user_answer = answer;

    } catch (error) {
        console.error('Failed to save answer', error);
    }
};

// Submission
const resultUrl = () => route('student.tests.result', props.attempt.id);

const submitTest = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    await saveCurrentAnswer();

    try {
        // The submit endpoint returns JSON ({ success, redirect }), so use axios and then navigate
        const { data } = await axios.post(route('student.tests.submit', props.attempt.id), {
            confirm: true
        });
        router.visit(data?.redirect || resultUrl());
    } catch (error) {
        if (error.response?.status === 400) {
            // Already submitted (e.g. timer expiry raced a manual submit) - show the result
            router.visit(resultUrl());
            return;
        }
        console.error('Failed to submit test', error);
        isSubmitting.value = false;
    }
};

// Anti-cheat
const handleVisibilityChange = () => {
    if (document.hidden) {
        logEvent('tab_switch');
        warningsCount.value++;
        showAntiCheatWarning.value = true;
    }
};

const logEvent = (type) => {
    axios.post(route('student.tests.log-event', props.attempt.id), {
        event_type: type
    });
};

// Prevent right click
const handleContextMenu = (e) => {
    e.preventDefault();
    logEvent('right_click');
};

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('contextmenu', handleContextMenu);
});

onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('contextmenu', handleContextMenu);
});

// Flagging
const toggleFlag = async () => {
    const q = currentQuestion.value;
    try {
        const response = await axios.post(route('student.tests.flag-question', [props.attempt.id, q.id]));
        q.is_flagged = response.data.is_flagged;
    } catch (error) {
        console.error('Failed to flag question', error);
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">

        <Head :title="test.title" />

        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white truncate max-w-md">
                    {{ test.title }}
                </h1>

                <div class="flex items-center gap-4">
                    <TestTimer :expires-at="attempt.expires_at" @expired="submitTest" />
                    <button @click="showSubmitConfirm = true"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Topshirish
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Question Area -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 min-h-[400px] flex flex-col">
                        <div class="flex justify-between items-start mb-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                Savol {{ currentQuestionIndex + 1 }}
                            </h2>
                            <button @click="toggleFlag" class="text-sm flex items-center gap-1 transition-colors"
                                :class="currentQuestion.is_flagged ? 'text-red-600' : 'text-gray-400 hover:text-gray-600'">
                                <span v-if="currentQuestion.is_flagged">🚩 Belgilangan</span>
                                <span v-else>🏳️ Belgilash</span>
                            </button>
                        </div>

                        <div class="prose dark:prose-invert max-w-none mb-8">
                            <p class="text-lg">{{ currentQuestion.content }}</p>
                        </div>

                        <div class="flex-1">
                            <QuestionSingleChoice v-if="currentQuestion.type === 'single_choice'"
                                :question="currentQuestion" v-model="answers[currentQuestion.id]" />
                            <QuestionMultipleChoice v-else-if="currentQuestion.type === 'multiple_choice'"
                                :question="currentQuestion" v-model="answers[currentQuestion.id]" />
                            <QuestionTrueFalse v-else-if="currentQuestion.type === 'true_false'"
                                :question="currentQuestion" v-model="answers[currentQuestion.id]" />
                            <QuestionFillBlank v-else-if="currentQuestion.type === 'fill_blank'"
                                :question="currentQuestion" v-model="answers[currentQuestion.id]" />
                            <QuestionMatching v-else-if="currentQuestion.type === 'matching'"
                                :question="currentQuestion" v-model="answers[currentQuestion.id]" />
                            <QuestionOrdering v-else-if="currentQuestion.type === 'ordering'"
                                :question="currentQuestion" v-model="answers[currentQuestion.id]" />
                        </div>

                        <QuestionNavigation :has-previous="currentQuestionIndex > 0" :has-next="!isLastQuestion"
                            :is-last="isLastQuestion" :is-submitting="isSubmitting" @previous="prevQuestion"
                            @next="nextQuestion" @submit="showSubmitConfirm = true" />
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <TestProgress :questions="questions" :current-question-index="currentQuestionIndex"
                        @navigate="jumpToQuestion" />

                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-sm text-blue-800 dark:text-blue-300">
                        <p class="font-bold mb-1">Eslatma:</p>
                        <p>Har bir savolga javob bergach, "Keyingi" tugmasini bosing. Javoblar avtomatik saqlanadi.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modals -->
        <TestSubmitConfirm :show="showSubmitConfirm" :unanswered-count="unansweredCount"
            @close="showSubmitConfirm = false" @confirm="submitTest" />

        <AntiCheatWarning :show="showAntiCheatWarning" :warnings-count="warningsCount"
            @close="showAntiCheatWarning = false" />
    </div>
</template>
