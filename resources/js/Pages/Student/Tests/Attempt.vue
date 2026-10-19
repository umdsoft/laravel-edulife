<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { FlagIcon as FlagOutline, Squares2X2Icon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import { FlagIcon, CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/20/solid';
import { Button, Card, Modal, ProgressBar, Spinner } from '@/Components/UI';
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
const showNavigator = ref(false);
const warningsCount = ref(0);
const startTime = ref(Date.now());
const saveState = ref('idle'); // idle | saving | saved | error

// Initialize answers
props.questions.forEach(q => {
    answers.value[q.id] = q.user_answer;
});

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value]);
const isLastQuestion = computed(() => currentQuestionIndex.value === props.questions.length - 1);

// Presentation: "answered" follows what the student has selected right now (saved on navigation / submit)
const hasAnswer = (value) => {
    if (value === null || value === undefined || value === '') return false;
    if (Array.isArray(value)) return value.length > 0;
    if (typeof value === 'object') return Object.values(value).some((v) => v !== '' && v !== null && v !== undefined);
    return true;
};
const navQuestions = computed(() =>
    props.questions.map((q) => ({ ...q, is_answered: hasAnswer(answers.value[q.id]) || (q.is_answered && answers.value[q.id] === undefined) })),
);
const answeredCount = computed(() => navQuestions.value.filter((q) => q.is_answered).length);
const unansweredCount = computed(() => props.questions.length - answeredCount.value);
const answeredPct = computed(() => (props.questions.length ? (answeredCount.value / props.questions.length) * 100 : 0));

const typeLabels = {
    single_choice: 'Bitta javobni tanlang',
    multiple_choice: 'Bir nechta javob',
    true_false: "To'g'ri yoki noto'g'ri",
    fill_blank: 'Javobni yozing',
    matching: 'Moslashtiring',
    ordering: 'Tartiblang',
};

// Move focus to the new question so keyboard / screen-reader users land on it
const questionHeading = ref(null);
const focusQuestion = () => requestAnimationFrame(() => questionHeading.value?.focus({ preventScroll: false }));

// Navigation
const nextQuestion = () => {
    if (!isLastQuestion.value) {
        saveCurrentAnswer();
        currentQuestionIndex.value++;
        startTime.value = Date.now();
        focusQuestion();
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        saveCurrentAnswer();
        currentQuestionIndex.value--;
        startTime.value = Date.now();
        focusQuestion();
    }
};

const jumpToQuestion = (index) => {
    saveCurrentAnswer();
    currentQuestionIndex.value = index;
    startTime.value = Date.now();
    showNavigator.value = false;
    focusQuestion();
};

// Saving answers
const saveCurrentAnswer = async () => {
    const q = currentQuestion.value;
    const answer = answers.value[q.id];

    // Only save if changed or not saved yet (optimization can be added here)
    // For now, save on navigation

    try {
        const timeSpent = Math.round((Date.now() - startTime.value) / 1000);
        saveState.value = 'saving';

        await axios.post(route('student.tests.save-answer', props.attempt.id), {
            question_id: q.id,
            answer: answer,
            time_on_question: timeSpent
        });

        // Update local state
        q.is_answered = !!answer && (Array.isArray(answer) ? answer.length > 0 : true);
        q.user_answer = answer;
        saveState.value = 'saved';

    } catch (error) {
        saveState.value = 'error';
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
    <div class="min-h-screen bg-bg text-fg">

        <Head :title="test.title" />

        <a href="#question-area"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-toast focus:rounded-md focus:bg-surface focus:px-4 focus:py-2 focus:shadow-lg">
            Savolga o'tish
        </a>

        <!-- Focused exam bar: title · timer · submit -->
        <header class="sticky top-0 z-topbar border-b border-border bg-surface/95 backdrop-blur">
            <div class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4 sm:h-16 sm:px-6">
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-sm font-semibold text-fg sm:text-base">{{ test.title }}</h1>
                    <p v-if="course?.title" class="hidden truncate text-xs text-fg-subtle sm:block">{{ course.title }}</p>
                </div>

                <TestTimer :expires-at="attempt.expires_at" @expired="submitTest" />

                <Button variant="secondary" size="sm" :icon-left="Squares2X2Icon" class="lg:hidden" :aria-label="`Savollar: ${answeredCount}/${questions.length}`"
                    @click="showNavigator = true">
                    <span class="tabular-nums">{{ answeredCount }}/{{ questions.length }}</span>
                </Button>
                <Button size="sm" class="hidden sm:inline-flex" :loading="isSubmitting" @click="showSubmitConfirm = true">
                    Topshirish
                </Button>
            </div>
            <ProgressBar :value="answeredPct" size="xs" class="[&>div]:rounded-none" />
        </header>

        <main id="question-area" class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:py-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_288px] lg:items-start">
                <!-- Question -->
                <Card padding="md" class="min-w-0">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <h2 ref="questionHeading" tabindex="-1" class="text-13 font-medium text-fg-subtle focus:outline-none">
                                Savol <span class="text-fg tabular-nums">{{ currentQuestionIndex + 1 }}</span>
                                <span class="tabular-nums"> / {{ questions.length }}</span>
                            </h2>
                            <p v-if="typeLabels[currentQuestion.type]" class="mt-0.5 text-xs text-fg-subtle">
                                {{ typeLabels[currentQuestion.type] }}
                            </p>
                        </div>
                        <Button :variant="currentQuestion.is_flagged ? 'soft' : 'ghost'" size="sm"
                            :aria-pressed="!!currentQuestion.is_flagged" @click="toggleFlag">
                            <template #leading>
                                <FlagIcon v-if="currentQuestion.is_flagged" class="h-4 w-4 text-warning-600" />
                                <FlagOutline v-else class="h-4 w-4" />
                            </template>
                            {{ currentQuestion.is_flagged ? 'Belgilangan' : 'Keyinroq qaytish' }}
                        </Button>
                    </div>

                    <!-- Question text: the hero of the page -->
                    <p class="mb-6 max-w-[70ch] whitespace-pre-wrap text-lg leading-relaxed text-fg sm:text-xl">{{ currentQuestion.content }}</p>

                    <div :key="currentQuestion.id">
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

                    <!-- Sticky on phones so the next step is always in reach -->
                    <div class="pb-safe sticky bottom-0 z-10 -mx-5 -mb-5 mt-6 rounded-b-lg bg-surface/95 px-5 pb-4 backdrop-blur sm:static sm:mx-0 sm:mb-0 sm:bg-transparent sm:px-0 sm:pb-0 sm:backdrop-blur-none">
                        <QuestionNavigation :has-previous="currentQuestionIndex > 0" :has-next="!isLastQuestion"
                            :is-last="isLastQuestion" :is-submitting="isSubmitting" @previous="prevQuestion"
                            @next="nextQuestion" @submit="showSubmitConfirm = true" />
                        <p class="mt-3 flex min-h-[1.25rem] items-center justify-center gap-1.5 text-xs text-fg-subtle sm:justify-end" aria-live="polite">
                            <template v-if="saveState === 'saving'"><Spinner size="h-3.5 w-3.5" /> Saqlanmoqda…</template>
                            <template v-else-if="saveState === 'saved'"><CheckCircleIcon class="h-3.5 w-3.5 text-success" aria-hidden="true" /> Javob saqlandi</template>
                            <template v-else-if="saveState === 'error'">
                                <ExclamationCircleIcon class="h-3.5 w-3.5 text-danger" aria-hidden="true" />
                                <span class="text-danger">Saqlanmadi — keyingi o'tishda qayta urinamiz</span>
                            </template>
                        </p>
                    </div>
                </Card>

                <!-- Navigator (desktop) -->
                <aside class="hidden space-y-4 lg:sticky lg:top-24 lg:block" aria-label="Savollar navigatori">
                    <Card padding="sm">
                        <TestProgress :questions="navQuestions" :current-question-index="currentQuestionIndex"
                            @navigate="jumpToQuestion" />
                    </Card>
                    <p class="flex gap-2 px-1 text-xs text-fg-subtle">
                        <InformationCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Javob har bir savoldan o'tganda avtomatik saqlanadi.
                    </p>
                    <Button block variant="secondary" :loading="isSubmitting" @click="showSubmitConfirm = true">
                        Testni topshirish
                    </Button>
                </aside>
            </div>
        </main>

        <!-- Navigator (mobile sheet) -->
        <Modal :show="showNavigator" size="md" title="Savollar" @close="showNavigator = false">
            <div class="pb-2">
                <TestProgress :questions="navQuestions" :current-question-index="currentQuestionIndex"
                    @navigate="jumpToQuestion" />
            </div>
            <template #footer>
                <Button variant="secondary" @click="showNavigator = false">Yopish</Button>
                <Button :loading="isSubmitting" @click="showNavigator = false; showSubmitConfirm = true">Testni topshirish</Button>
            </template>
        </Modal>

        <!-- Modals -->
        <TestSubmitConfirm :show="showSubmitConfirm" :unanswered-count="unansweredCount" :loading="isSubmitting"
            @close="showSubmitConfirm = false" @confirm="submitTest" />

        <AntiCheatWarning :show="showAntiCheatWarning" :warnings-count="warningsCount"
            @close="showAntiCheatWarning = false" />
    </div>
</template>
