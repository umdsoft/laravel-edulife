<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Alert, Button, EmptyState, Modal, ProgressBar, Skeleton, Spinner } from '@/Components/UI';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    CommandLineIcon,
    FlagIcon,
    QuestionMarkCircleIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon, CheckIcon, FlagIcon as FlagSolidIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    attempt: Object,
    sections: Array,
    progress: Object,
});

// State
const currentSectionId = ref(props.attempt.current_section_id);
const questions = ref([]);
const currentQuestionIndex = ref(0);
const isLoading = ref(false);
const isSaving = ref(false);
const remainingSeconds = ref(props.attempt.remaining_seconds);
const sectionRemainingSeconds = ref(0);
const showSidebar = ref(true);
const showSubmitModal = ref(false);
const sessionToken = props.attempt.session_token;

// Enhanced state for UX
const lastSaveTime = ref(null);
const showTimeWarning = ref(false);
const timeWarningMinutes = ref(0);
const violationCount = ref(0);
const showViolationAlert = ref(false);
const lastViolationType = ref('');
const showKeyboardHelp = ref(false);

// Timer intervals
let timerInterval = null;
let heartbeatInterval = null;
let autoSaveInterval = null;

// Computed
const currentSection = computed(() => props.sections.find(s => s.id === currentSectionId.value));
const currentQuestion = computed(() => questions.value[currentQuestionIndex.value]);
const answeredCount = computed(() => questions.value.filter(q => q.user_answer !== null).length);
const flaggedCount = computed(() => questions.value.filter(q => q.is_flagged).length);

const formattedTime = computed(() => {
    const hours = Math.floor(remainingSeconds.value / 3600);
    const minutes = Math.floor((remainingSeconds.value % 3600) / 60);
    const seconds = remainingSeconds.value % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const isTimeUrgent = computed(() => remainingSeconds.value <= 300);
const isTimeCritical = computed(() => remainingSeconds.value <= 60);

const lastSaveFormatted = computed(() => {
    if (!lastSaveTime.value) return null;
    const diff = Math.floor((Date.now() - lastSaveTime.value.getTime()) / 1000);
    if (diff < 5) return 'hozirgina';
    if (diff < 60) return `${diff}s oldin`;
    return `${Math.floor(diff / 60)}m oldin`;
});

// Time warnings
watch(remainingSeconds, (newVal) => {
    const warningTimes = [600, 300, 60]; // 10, 5, 1 min
    warningTimes.forEach(time => {
        if (newVal === time) {
            timeWarningMinutes.value = Math.floor(time / 60);
            showTimeWarning.value = true;
            playWarningSound();
            setTimeout(() => showTimeWarning.value = false, 5000);
        }
    });
});

const playWarningSound = () => {
    try {
        const audio = new Audio('/sounds/warning.mp3');
        audio.volume = 0.5;
        audio.play().catch(() => { });
    } catch (e) { }
};

// API Functions
const loadSection = async (sectionId) => {
    if (isLoading.value) return;
    isLoading.value = true;

    try {
        const response = await fetch(route('student.olympiads.exam.section', { slug: props.olympiad.slug, section: sectionId }), {
            headers: { 'X-Session-Token': sessionToken }
        });
        const data = await response.json();
        questions.value = data.questions;
        sectionRemainingSeconds.value = data.remaining_time;
        currentSectionId.value = sectionId;
        currentQuestionIndex.value = 0;
    } catch (error) {
        console.error('Error loading section:', error);
    } finally {
        isLoading.value = false;
    }
};

const selectAnswer = async (answer) => {
    if (!currentQuestion.value) return;

    const questionId = currentQuestion.value.id;
    const oldAnswer = questions.value[currentQuestionIndex.value].user_answer;

    questions.value[currentQuestionIndex.value].user_answer = answer;
    isSaving.value = true;
    lastSaveTime.value = new Date();

    try {
        await fetch(route('student.olympiads.exam.answer', props.olympiad.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Session-Token': sessionToken,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ section_id: currentSectionId.value, question_id: questionId, answer, time_spent: 5 }),
        });
    } catch (error) {
        questions.value[currentQuestionIndex.value].user_answer = oldAnswer;
    } finally {
        isSaving.value = false;
    }
};

const toggleFlag = async () => {
    if (!currentQuestion.value) return;
    const questionId = currentQuestion.value.id;
    const oldValue = questions.value[currentQuestionIndex.value].is_flagged;

    questions.value[currentQuestionIndex.value].is_flagged = !oldValue;

    try {
        await fetch(route('student.olympiads.exam.flag', props.olympiad.slug), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Session-Token': sessionToken, 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ question_id: questionId }),
        });
    } catch (error) {
        questions.value[currentQuestionIndex.value].is_flagged = oldValue;
    }
};

const goToQuestion = (index) => { currentQuestionIndex.value = index; };
const nextQuestion = () => { if (currentQuestionIndex.value < questions.value.length - 1) currentQuestionIndex.value++; };
const prevQuestion = () => { if (currentQuestionIndex.value > 0) currentQuestionIndex.value--; };

const completeSection = async () => {
    try {
        const response = await fetch(route('student.olympiads.exam.section.complete', { slug: props.olympiad.slug, section: currentSectionId.value }), {
            method: 'POST',
            headers: { 'X-Session-Token': sessionToken, 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await response.json();
        if (data.next_section_id) await loadSection(data.next_section_id);
        else showSubmitModal.value = true;
    } catch (error) {
        console.error('Error completing section:', error);
    }
};

const submitExam = async () => {
    try {
        const response = await fetch(route('student.olympiads.exam.submit', props.olympiad.slug), {
            method: 'POST',
            headers: { 'X-Session-Token': sessionToken, 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await response.json();
        if (data.success) window.location.href = data.redirect;
    } catch (error) {
        console.error('Error submitting exam:', error);
    }
};

const sendHeartbeat = async () => {
    try {
        const response = await fetch(route('student.olympiads.exam.heartbeat', props.olympiad.slug), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Session-Token': sessionToken, 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ last_heartbeat: Math.floor(Date.now() / 1000) }),
        });
        const data = await response.json();
        if (data.is_disqualified) {
            alert('Siz imtihondan chetlashtirilgansiz');
            window.location.href = route('student.olympiads.results', props.olympiad.slug);
        }
    } catch (error) { }
};

// ========== ANTI-CHEAT SYSTEM ==========
const reportViolation = async (type, details = {}) => {
    violationCount.value++;
    lastViolationType.value = type;
    showViolationAlert.value = true;
    setTimeout(() => showViolationAlert.value = false, 3000);

    try {
        await fetch(route('student.olympiads.exam.violation', props.olympiad.slug), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Session-Token': sessionToken, 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ type, details, count: violationCount.value }),
        });
    } catch (e) { }
};

const handleVisibilityChange = () => { if (document.hidden) reportViolation('tab_switch', { timestamp: Date.now() }); };
const handleCopy = (e) => { e.preventDefault(); reportViolation('copy_attempt', {}); };
const handlePaste = (e) => { e.preventDefault(); reportViolation('paste_attempt', {}); };
const handleCut = (e) => { e.preventDefault(); reportViolation('cut_attempt', {}); };
const handleContextMenu = (e) => { e.preventDefault(); reportViolation('right_click', {}); };
const handleBlur = () => { reportViolation('window_blur', { timestamp: Date.now() }); };

const detectDevTools = () => {
    const threshold = 160;
    const devtoolsOpen = window.outerWidth - window.innerWidth > threshold || window.outerHeight - window.innerHeight > threshold;
    if (devtoolsOpen) reportViolation('devtools_open', {});
};

const handleKeyDown = (e) => {
    // Block dangerous shortcuts
    if (e.key === 'PrintScreen') { e.preventDefault(); reportViolation('screenshot_attempt', {}); }
    if (e.ctrlKey && e.shiftKey && e.key === 'I') { e.preventDefault(); reportViolation('devtools_shortcut', {}); }
    if (e.ctrlKey && e.key === 'u') { e.preventDefault(); reportViolation('view_source', {}); }
    if (e.key === 'F12') { e.preventDefault(); reportViolation('devtools_f12', {}); }

    // Exam keyboard shortcuts (only when not in input)
    if (!e.ctrlKey && !e.altKey && !e.metaKey && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        // A, B, C, D - select answer
        if (['a', 'b', 'c', 'd'].includes(e.key.toLowerCase())) {
            const index = e.key.toLowerCase().charCodeAt(0) - 97;
            if (currentQuestion.value?.options?.[index] !== undefined) selectAnswer(index);
        }
        // Arrow keys - navigate
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') nextQuestion();
        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') prevQuestion();
        // F - toggle flag
        if (e.key.toLowerCase() === 'f') toggleFlag();
        // 1-9 - jump to question
        if (/^[1-9]$/.test(e.key) && questions.value[parseInt(e.key) - 1]) goToQuestion(parseInt(e.key) - 1);
        // ? - show keyboard help
        if (e.key === '?') showKeyboardHelp.value = !showKeyboardHelp.value;
    }
};

// Lifecycle
onMounted(() => {
    // Load initial section
    if (currentSectionId.value) loadSection(currentSectionId.value);
    else if (props.sections.length > 0) loadSection(props.sections[0].id);

    // Timers
    timerInterval = setInterval(() => { if (remainingSeconds.value > 0) remainingSeconds.value--; else submitExam(); }, 1000);
    heartbeatInterval = setInterval(sendHeartbeat, 15000);
    setInterval(detectDevTools, 2000);

    // Anti-cheat listeners
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('copy', handleCopy);
    document.addEventListener('paste', handlePaste);
    document.addEventListener('cut', handleCut);
    document.addEventListener('contextmenu', handleContextMenu);
    document.addEventListener('keydown', handleKeyDown);
    window.addEventListener('blur', handleBlur);

    // Disable text selection
    document.body.style.userSelect = 'none';
    document.body.style.webkitUserSelect = 'none';
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (heartbeatInterval) clearInterval(heartbeatInterval);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('copy', handleCopy);
    document.removeEventListener('paste', handlePaste);
    document.removeEventListener('cut', handleCut);
    document.removeEventListener('contextmenu', handleContextMenu);
    document.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('blur', handleBlur);
    document.body.style.userSelect = '';
});

// ========== Presentation helpers (no behaviour) ==========
const progressValue = computed(() => (questions.value.length > 0 ? (answeredCount.value / questions.value.length) * 100 : 0));

const timerClass = computed(() => {
    if (isTimeCritical.value) return 'bg-danger-50 text-danger ring-danger-100 animate-pulse motion-reduce:animate-none';
    if (isTimeUrgent.value) return 'bg-warning-50 text-warning ring-warning-100';
    return 'bg-surface-2 text-fg ring-border';
});

const navButtonClass = (q, index) => [
    'focus-ring relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-sm font-medium tabular-nums ring-1 ring-inset transition-colors duration-150',
    q.is_flagged
        ? 'bg-warning-50 text-warning ring-warning-100'
        : q.user_answer !== null
            ? 'bg-brand-50 text-brand ring-brand-100'
            : 'bg-surface text-fg-muted ring-border hover:bg-surface-2',
    currentQuestionIndex.value === index ? '!ring-2 !ring-brand-500' : '',
];

const navButtonLabel = (q, index) => {
    const parts = [`Savol ${index + 1}`];
    parts.push(q.user_answer !== null ? 'javob berilgan' : 'javob berilmagan');
    if (q.is_flagged) parts.push('belgilangan');
    return parts.join(', ');
};

const optionClass = (optIndex) => [
    'focus-ring flex min-h-[56px] w-full items-center gap-4 rounded-md border px-4 py-3 text-left transition-colors duration-150',
    currentQuestion.value?.user_answer === optIndex
        ? 'border-brand-200 bg-brand-50'
        : 'border-border bg-surface hover:border-border-strong hover:bg-surface-2',
];

const letter = (i) => String.fromCharCode(65 + i);

const richText = 'text-base leading-relaxed text-fg [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-3 [&_ul]:list-disc [&_ul]:pl-5';

const shortcuts = [
    { label: 'Javob tanlash', keys: ['A', 'B', 'C', 'D'] },
    { label: 'Keyingi savol', keys: ['→', '↓'] },
    { label: 'Oldingi savol', keys: ['←', '↑'] },
    { label: 'Belgilash', keys: ['F'] },
    { label: "Savolga o'tish", keys: ['1–9'] },
    { label: 'Yorliqlar oynasi', keys: ['?'] },
];
</script>

<template>

    <Head :title="`Imtihon - ${olympiad.title}`" />

    <div class="flex min-h-screen select-none flex-col bg-bg text-fg">
        <!-- Time Warning -->
        <Transition name="slide-down">
            <div v-if="showTimeWarning" class="fixed inset-x-0 top-0 z-toast p-4">
                <div class="mx-auto max-w-md shadow-lg">
                    <Alert variant="danger" title="Vaqt tugamoqda!">
                        <span class="tabular-nums">{{ timeWarningMinutes }}</span> daqiqa qoldi
                    </Alert>
                </div>
            </div>
        </Transition>

        <!-- Violation Alert -->
        <Transition name="slide-down">
            <div v-if="showViolationAlert" class="fixed right-4 top-4 z-toast w-72 max-w-[calc(100vw-2rem)] shadow-lg">
                <Alert variant="warning" title="Ogohlantirish qayd etildi">
                    Jami: <span class="font-medium tabular-nums">{{ violationCount }}</span> ta
                </Alert>
            </div>
        </Transition>

        <!-- Top Bar -->
        <header class="sticky top-0 z-topbar border-b border-border bg-surface/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 lg:px-6">
                <button type="button"
                    class="focus-ring inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 hover:text-fg lg:hidden"
                    :aria-expanded="showSidebar" aria-controls="exam-mobile-nav"
                    :aria-label="showSidebar ? 'Savollar ro\'yxatini yashirish' : 'Savollar ro\'yxatini ko\'rsatish'"
                    @click="showSidebar = !showSidebar">
                    <Squares2X2Icon class="h-5 w-5" aria-hidden="true" />
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-fg">{{ olympiad.title }}</p>
                    <p class="truncate text-13 text-fg-subtle">
                        <span v-if="currentSection">{{ currentSection.title }} · </span>
                        <span class="tabular-nums">Savol {{ questions.length ? currentQuestionIndex + 1 : 0 }} / {{ questions.length }}</span>
                    </p>
                </div>

                <span class="hidden items-center gap-1.5 text-13 sm:inline-flex" aria-live="polite">
                    <template v-if="isSaving">
                        <Spinner size="h-3.5 w-3.5" label="Saqlanmoqda" class="text-fg-subtle" />
                        <span class="text-fg-subtle">Saqlanmoqda...</span>
                    </template>
                    <template v-else>
                        <CheckIcon class="h-4 w-4 text-success" aria-hidden="true" />
                        <span class="text-fg-muted">Saqlangan</span>
                    </template>
                </span>

                <button type="button"
                    class="focus-ring hidden h-10 w-10 shrink-0 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 hover:text-fg md:inline-flex"
                    aria-label="Klaviatura yorliqlari" title="Klaviatura yorliqlari" @click="showKeyboardHelp = true">
                    <CommandLineIcon class="h-5 w-5" aria-hidden="true" />
                </button>

                <div role="timer" aria-label="Qolgan vaqt"
                    :class="['inline-flex h-10 shrink-0 items-center gap-2 rounded-md px-3 text-base font-semibold tabular-nums ring-1 ring-inset', timerClass]">
                    <ClockIcon class="h-5 w-5" aria-hidden="true" />
                    {{ formattedTime }}
                </div>
            </div>

            <!-- Mobile navigator (scrolls horizontally) -->
            <div v-show="showSidebar" id="exam-mobile-nav" class="border-t border-border lg:hidden">
                <div class="flex gap-2 overflow-x-auto px-4 py-3 scrollbar-thin" role="group" aria-label="Savollar">
                    <button v-for="(q, index) in questions" :key="q.id" type="button" :class="navButtonClass(q, index)"
                        :aria-label="navButtonLabel(q, index)"
                        :aria-current="currentQuestionIndex === index ? 'step' : undefined" @click="goToQuestion(index)">
                        {{ index + 1 }}
                        <FlagSolidIcon v-if="q.is_flagged" class="absolute -right-1 -top-1 h-3.5 w-3.5 text-warning"
                            aria-hidden="true" />
                    </button>
                </div>
                <div v-if="sections.length > 1" class="flex gap-2 overflow-x-auto px-4 pb-3 scrollbar-thin">
                    <button v-for="section in sections" :key="section.id" type="button"
                        :disabled="section.status === 'completed'" :class="[
                            'focus-ring inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full px-3 text-13 font-medium ring-1 ring-inset disabled:cursor-not-allowed',
                            currentSectionId === section.id
                                ? 'bg-brand-50 text-brand ring-brand-100'
                                : section.status === 'completed'
                                    ? 'bg-success-50 text-success ring-success-100'
                                    : 'bg-surface text-fg-muted ring-border hover:bg-surface-2',
                        ]" @click="loadSection(section.id)">
                        <CheckCircleIcon v-if="section.status === 'completed'" class="h-4 w-4" aria-hidden="true" />
                        {{ section.title }}
                    </button>
                </div>
            </div>
        </header>

        <!-- Body -->
        <div class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 lg:px-6">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_288px]">
                <!-- Question Area -->
                <main class="min-w-0 space-y-4">
                    <div v-if="isLoading" class="space-y-4" aria-busy="true">
                        <div class="rounded-lg border border-border bg-surface p-6">
                            <Skeleton class="h-5 w-20" />
                            <Skeleton variant="text" :lines="3" class="mt-5" />
                        </div>
                        <Skeleton v-for="n in 4" :key="n" class="h-14 w-full" />
                    </div>

                    <template v-else-if="currentQuestion">
                        <!-- Question Card -->
                        <section class="rounded-lg border border-border bg-surface p-5 shadow-xs sm:p-6"
                            :aria-label="`Savol ${currentQuestionIndex + 1}`">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-13">
                                    <span class="font-semibold text-fg tabular-nums">Savol {{ currentQuestionIndex + 1 }}</span>
                                    <span class="text-fg-subtle" aria-hidden="true">·</span>
                                    <span class="text-fg-muted tabular-nums">{{ currentQuestion.points }} ball</span>
                                </div>
                                <button type="button" :aria-pressed="!!currentQuestion.is_flagged" :class="[
                                    'focus-ring inline-flex h-10 items-center gap-1.5 rounded-md px-3 text-13 font-medium ring-1 ring-inset transition-colors duration-150',
                                    currentQuestion.is_flagged
                                        ? 'bg-warning-50 text-warning ring-warning-100'
                                        : 'bg-surface text-fg-muted ring-border hover:bg-surface-2 hover:text-fg',
                                ]" @click="toggleFlag">
                                    <FlagSolidIcon v-if="currentQuestion.is_flagged" class="h-4 w-4" aria-hidden="true" />
                                    <FlagIcon v-else class="h-4 w-4" aria-hidden="true" />
                                    {{ currentQuestion.is_flagged ? 'Belgilangan' : 'Belgilash' }}
                                </button>
                            </div>

                            <div :class="richText" v-html="currentQuestion.question_html || currentQuestion.question_text">
                            </div>

                            <div v-if="currentQuestion.question_media" class="mt-4">
                                <img v-if="currentQuestion.question_media.type === 'image'"
                                    :src="currentQuestion.question_media.url" alt="Savol rasmi"
                                    class="max-w-full rounded-md border border-border">
                                <audio v-else-if="currentQuestion.question_media.type === 'audio'"
                                    :src="currentQuestion.question_media.url" controls class="w-full"></audio>
                            </div>
                        </section>

                        <!-- Options -->
                        <div class="space-y-2" role="group" aria-label="Javob variantlari">
                            <button v-for="(option, optIndex) in currentQuestion.options" :key="optIndex" type="button"
                                :class="optionClass(optIndex)" :aria-pressed="currentQuestion.user_answer === optIndex"
                                @click="selectAnswer(optIndex)">
                                <span :class="['inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                    currentQuestion.user_answer === optIndex
                                        ? 'bg-brand-600 text-white'
                                        : 'bg-surface-2 text-fg-muted']" aria-hidden="true">
                                    {{ letter(optIndex) }}
                                </span>
                                <span class="flex-1 text-sm text-fg sm:text-base">{{ option }}</span>
                                <CheckCircleIcon v-if="currentQuestion.user_answer === optIndex"
                                    class="h-5 w-5 shrink-0 text-brand" aria-hidden="true" />
                            </button>
                        </div>
                    </template>

                    <div v-else class="rounded-lg border border-border bg-surface">
                        <EmptyState :icon="QuestionMarkCircleIcon" title="Savollar topilmadi"
                            description="Bu bo'limda savollar yuklanmadi. Boshqa bo'limni tanlang yoki sahifani yangilang." />
                    </div>
                </main>

                <!-- Desktop navigator -->
                <aside class="hidden lg:block">
                    <div class="sticky top-24 space-y-4">
                        <div class="rounded-lg border border-border bg-surface p-5 shadow-xs">
                            <div class="flex items-center justify-between text-13">
                                <span class="text-fg-muted">Javob berilgan</span>
                                <span class="font-semibold text-fg tabular-nums">{{ answeredCount }} / {{ questions.length }}</span>
                            </div>
                            <ProgressBar :value="progressValue" size="sm" class="mt-2" />
                            <div class="mt-2 flex items-center justify-between text-xs text-fg-subtle">
                                <span class="inline-flex items-center gap-1 tabular-nums">
                                    <FlagSolidIcon class="h-3.5 w-3.5 text-warning" aria-hidden="true" />
                                    {{ flaggedCount }} belgilangan
                                </span>
                                <span v-if="lastSaveFormatted" class="text-success">Saqlandi: {{ lastSaveFormatted }}</span>
                            </div>

                            <div class="mt-5 flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-fg">Savollar</h2>
                                <button type="button"
                                    class="focus-ring rounded-sm text-13 font-medium text-brand underline-offset-4 hover:underline"
                                    @click="showKeyboardHelp = true">
                                    Yorliqlar
                                </button>
                            </div>
                            <div class="mt-3 grid grid-cols-5 gap-2" role="group" aria-label="Savollar">
                                <button v-for="(q, index) in questions" :key="q.id" type="button"
                                    :class="navButtonClass(q, index)" :aria-label="navButtonLabel(q, index)"
                                    :aria-current="currentQuestionIndex === index ? 'step' : undefined"
                                    @click="goToQuestion(index)">
                                    {{ index + 1 }}
                                    <FlagSolidIcon v-if="q.is_flagged"
                                        class="absolute -right-1 -top-1 h-3.5 w-3.5 text-warning" aria-hidden="true" />
                                </button>
                            </div>

                            <ul class="mt-4 space-y-1.5 text-xs text-fg-subtle">
                                <li class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-sm bg-brand-50 ring-1 ring-inset ring-brand-100" aria-hidden="true" />
                                    Javob berilgan
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-sm bg-warning-50 ring-1 ring-inset ring-warning-100" aria-hidden="true" />
                                    Belgilangan
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-sm bg-surface ring-2 ring-inset ring-brand-500" aria-hidden="true" />
                                    Joriy savol
                                </li>
                            </ul>
                        </div>

                        <div v-if="sections.length" class="rounded-lg border border-border bg-surface p-5 shadow-xs">
                            <h2 class="text-sm font-semibold text-fg">Bo'limlar</h2>
                            <div class="mt-3 space-y-1">
                                <button v-for="section in sections" :key="section.id" type="button"
                                    :disabled="section.status === 'completed'" :class="[
                                        'focus-ring flex min-h-10 w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-colors duration-150 disabled:cursor-not-allowed',
                                        currentSectionId === section.id
                                            ? 'bg-brand-50 font-medium text-brand'
                                            : section.status === 'completed'
                                                ? 'text-success'
                                                : 'text-fg-muted hover:bg-surface-2 hover:text-fg',
                                    ]" :aria-current="currentSectionId === section.id ? 'step' : undefined"
                                    @click="loadSection(section.id)">
                                    <CheckCircleIcon v-if="section.status === 'completed'" class="h-4 w-4 shrink-0"
                                        aria-hidden="true" />
                                    <span class="truncate">{{ section.title }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <footer class="sticky bottom-0 z-topbar border-t border-border bg-surface/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-2 px-4 lg:px-6">
                <Button variant="secondary" :icon-left="ChevronLeftIcon" :disabled="currentQuestionIndex === 0"
                    aria-label="Oldingi savol" @click="prevQuestion">
                    <span class="hidden sm:inline">Oldingi</span>
                </Button>

                <Button variant="ghost" @click="showSubmitModal = true">
                    Yakunlash
                </Button>

                <Button v-if="currentQuestionIndex < questions.length - 1" :icon-right="ChevronRightIcon"
                    aria-label="Keyingi savol" @click="nextQuestion">
                    <span class="hidden sm:inline">Keyingi</span>
                </Button>
                <Button v-else :icon-right="CheckIcon" @click="completeSection">
                    <span class="hidden sm:inline">Bo'limni&nbsp;</span>yakunlash
                </Button>
            </div>
        </footer>

        <!-- Keyboard Help Modal -->
        <Modal :show="showKeyboardHelp" title="Klaviatura yorliqlari" size="sm" @close="showKeyboardHelp = false">
            <dl class="divide-y divide-border text-sm">
                <div v-for="item in shortcuts" :key="item.label" class="flex items-center justify-between gap-3 py-2.5">
                    <dt class="text-fg-muted">{{ item.label }}</dt>
                    <dd class="flex gap-1">
                        <kbd v-for="k in item.keys" :key="k"
                            class="inline-flex h-6 min-w-6 items-center justify-center rounded-sm border border-border bg-surface-2 px-1.5 font-mono text-xs text-fg">{{ k }}</kbd>
                    </dd>
                </div>
            </dl>
            <template #footer>
                <Button variant="secondary" @click="showKeyboardHelp = false">Yopish</Button>
            </template>
        </Modal>

        <!-- Submit Modal -->
        <Modal :show="showSubmitModal" title="Imtihonni yakunlash"
            description="Haqiqatan ham imtihonni yakunlamoqchimisiz? Bu amalni ortga qaytarib bo'lmaydi." size="md"
            @close="showSubmitModal = false">
            <dl class="divide-y divide-border rounded-md bg-surface-2 px-4 text-sm">
                <div class="flex justify-between py-2.5">
                    <dt class="text-fg-muted">Javob berilgan</dt>
                    <dd class="font-medium text-fg tabular-nums">{{ answeredCount }} / {{ questions.length }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-fg-muted">Belgilangan</dt>
                    <dd class="font-medium text-fg tabular-nums">{{ flaggedCount }}</dd>
                </div>
                <div v-if="violationCount > 0" class="flex justify-between py-2.5">
                    <dt class="text-danger">Ogohlantirishlar</dt>
                    <dd class="font-medium text-danger tabular-nums">{{ violationCount }}</dd>
                </div>
            </dl>
            <Alert v-if="questions.length - answeredCount > 0" variant="warning" class="mt-4">
                <span class="tabular-nums">{{ questions.length - answeredCount }}</span> ta savolga javob berilmagan.
            </Alert>
            <template #footer>
                <Button variant="secondary" @click="showSubmitModal = false">Bekor qilish</Button>
                <Button :icon-left="CheckIcon" @click="submitExam">Yakunlash</Button>
            </template>
        </Modal>
    </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: transform 0.2s ease-out, opacity 0.2s ease-out;
}

.slide-down-enter-from,
.slide-down-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .slide-down-enter-active,
    .slide-down-leave-active {
        transition: none;
    }
}
</style>
