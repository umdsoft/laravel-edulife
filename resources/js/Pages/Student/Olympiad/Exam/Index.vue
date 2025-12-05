<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

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
</script>

<template>

    <Head :title="`Imtihon - ${olympiad.title}`" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex select-none">
        <!-- Time Warning Alert -->
        <Transition name="slide-down">
            <div v-if="showTimeWarning" class="fixed top-0 inset-x-0 z-50 p-4">
                <div
                    class="max-w-md mx-auto bg-red-500 text-white rounded-xl p-4 shadow-2xl flex items-center gap-3 animate-pulse">
                    <span class="text-2xl">⏰</span>
                    <div>
                        <div class="font-bold">Vaqt tugamoqda!</div>
                        <div class="text-sm opacity-90">{{ timeWarningMinutes }} daqiqa qoldi</div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Violation Alert -->
        <Transition name="slide-down">
            <div v-if="showViolationAlert" class="fixed top-4 right-4 z-50">
                <div class="bg-red-600 text-white rounded-xl p-4 shadow-2xl">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⚠️</span>
                        <span class="font-medium">Ogohlantirish qayd etildi!</span>
                    </div>
                    <div class="text-sm opacity-90 mt-1">Jami: {{ violationCount }} ta</div>
                </div>
            </div>
        </Transition>

        <!-- Sidebar -->
        <aside
            :class="['w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-all fixed lg:relative h-full z-40', showSidebar ? 'translate-x-0' : '-translate-x-full']">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-bold text-gray-900 dark:text-white truncate">{{ olympiad.title }}</h2>
                <p class="text-sm text-gray-500">{{ currentSection?.title }}</p>
            </div>

            <!-- Timer -->
            <div
                :class="['p-4 text-white text-center', isTimeCritical ? 'bg-red-600 animate-pulse' : isTimeUrgent ? 'bg-orange-500' : 'bg-gradient-to-r from-purple-600 to-indigo-600']">
                <div class="text-3xl font-mono font-bold">{{ formattedTime }}</div>
                <div class="text-sm opacity-80">Qolgan vaqt</div>
            </div>

            <!-- Progress -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Javob berilgan:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ answeredCount }}/{{ questions.length
                    }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all"
                        :style="{ width: `${questions.length > 0 ? (answeredCount / questions.length) * 100 : 0}%` }">
                    </div>
                </div>
                <div class="flex justify-between text-xs mt-2 text-gray-500">
                    <span>🚩 {{ flaggedCount }} belgilangan</span>
                    <span v-if="lastSaveFormatted" class="text-green-500">✓ {{ lastSaveFormatted }}</span>
                </div>
            </div>

            <!-- Question Navigator -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Savollar</h3>
                    <button @click="showKeyboardHelp = true" class="text-xs text-purple-600 hover:underline">⌨️
                        Yorliqlar</button>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <button v-for="(q, index) in questions" :key="q.id" @click="goToQuestion(index)"
                        :class="['w-10 h-10 rounded-lg text-sm font-medium relative transition-all',
                            currentQuestionIndex === index ? 'ring-2 ring-purple-500 ring-offset-2' : '',
                            q.user_answer !== null ? 'bg-green-500 text-white' : q.is_flagged ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300']">
                        {{ index + 1 }}
                        <span v-if="q.is_flagged" class="absolute -top-1 -right-1 text-xs">🚩</span>
                    </button>
                </div>
            </div>

            <!-- Sections -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Bo'limlar</h3>
                <div class="space-y-2">
                    <button v-for="section in sections" :key="section.id" @click="loadSection(section.id)"
                        :disabled="section.status === 'completed'"
                        :class="['w-full text-left px-3 py-2 rounded-lg text-sm transition-colors',
                            currentSectionId === section.id ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200' : section.status === 'completed' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300']">
                        <span v-if="section.status === 'completed'">✅</span> {{ section.title }}
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Top Bar -->
            <header
                class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-30">
                <div class="flex items-center gap-4">
                    <button @click="showSidebar = !showSidebar"
                        class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">☰</button>
                    <span class="text-gray-600 dark:text-gray-400">Savol {{ currentQuestionIndex + 1 }} / {{
                        questions.length }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span v-if="isSaving" class="text-sm text-gray-500 flex items-center gap-1">
                        <span
                            class="w-3 h-3 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></span>
                        Saqlanmoqda...
                    </span>
                    <span v-else class="text-sm text-green-500">✓ Saqlangan</span>
                    <div class="lg:hidden font-mono font-bold"
                        :class="isTimeCritical ? 'text-red-500' : isTimeUrgent ? 'text-orange-500' : 'text-purple-600'">
                        {{ formattedTime }}</div>
                </div>
            </header>

            <!-- Question Area -->
            <div class="flex-1 overflow-y-auto p-4 lg:p-8">
                <div v-if="isLoading" class="flex items-center justify-center h-64">
                    <div class="animate-spin w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full">
                    </div>
                </div>

                <div v-else-if="currentQuestion" class="max-w-4xl mx-auto">
                    <!-- Question Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5 lg:p-8 mb-6">
                        <div class="flex items-start justify-between mb-4">
                            <span
                                class="px-3 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200 rounded-full text-sm font-medium">
                                {{ currentQuestion.points }} ball
                            </span>
                            <button @click="toggleFlag"
                                :class="['p-2 rounded-lg transition-colors', currentQuestion.is_flagged ? 'bg-amber-100 text-amber-700' : 'hover:bg-gray-100 text-gray-400']">🚩</button>
                        </div>

                        <div class="prose dark:prose-invert max-w-none"
                            v-html="currentQuestion.question_html || currentQuestion.question_text"></div>

                        <div v-if="currentQuestion.question_media" class="mt-4">
                            <img v-if="currentQuestion.question_media.type === 'image'"
                                :src="currentQuestion.question_media.url" class="max-w-full rounded-lg">
                            <audio v-else-if="currentQuestion.question_media.type === 'audio'"
                                :src="currentQuestion.question_media.url" controls class="w-full"></audio>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="space-y-3">
                        <button v-for="(option, optIndex) in currentQuestion.options" :key="optIndex"
                            @click="selectAnswer(optIndex)"
                            :class="['w-full text-left p-4 rounded-xl border-2 transition-all',
                                currentQuestion.user_answer === optIndex ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/30' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 bg-white dark:bg-gray-800']">
                            <div class="flex items-center gap-4">
                                <span
                                    :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold', currentQuestion.user_answer === optIndex ? 'bg-purple-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300']">
                                    {{ String.fromCharCode(65 + optIndex) }}
                                </span>
                                <span class="text-gray-900 dark:text-white flex-1">{{ option }}</span>
                                <span class="text-xs text-gray-400 hidden sm:block">{{ String.fromCharCode(65 +
                                    optIndex).toLowerCase() }} tugmasi</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation -->
            <footer
                class="h-20 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 lg:px-6 sticky bottom-0">
                <button @click="prevQuestion" :disabled="currentQuestionIndex === 0"
                    class="px-4 lg:px-6 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                    ← <span class="hidden sm:inline">Oldingi</span>
                </button>

                <button @click="showSubmitModal = true"
                    class="px-4 lg:px-6 py-2 rounded-lg font-medium bg-red-500 text-white hover:bg-red-600 transition-colors">
                    Yakunlash
                </button>

                <button v-if="currentQuestionIndex < questions.length - 1" @click="nextQuestion"
                    class="px-4 lg:px-6 py-2 rounded-lg font-medium bg-purple-600 text-white hover:bg-purple-700 transition-colors">
                    <span class="hidden sm:inline">Keyingi</span> →
                </button>
                <button v-else @click="completeSection"
                    class="px-4 lg:px-6 py-2 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700 transition-colors">
                    <span class="hidden sm:inline">Bo'limni</span> yakunlash ✓
                </button>
            </footer>
        </main>

        <!-- Keyboard Help Modal -->
        <div v-if="showKeyboardHelp"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            @click.self="showKeyboardHelp = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-sm w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⌨️ Klaviatura yorliqlari</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Javob
                            tanlash</span><span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">A B C
                            D</span></div>
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Keyingi
                            savol</span><span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">→
                            ↓</span></div>
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Oldingi
                            savol</span><span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">←
                            ↑</span></div>
                    <div class="flex justify-between"><span
                            class="text-gray-600 dark:text-gray-400">Belgilash</span><span
                            class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">F</span></div>
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Savolga
                            o'tish</span><span
                            class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">1-9</span></div>
                </div>
                <button @click="showKeyboardHelp = false"
                    class="w-full mt-4 py-2 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700">Yopish</button>
            </div>
        </div>

        <!-- Submit Modal -->
        <div v-if="showSubmitModal"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Imtihonni yakunlash</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Haqiqatan ham imtihonni yakunlamoqchimisiz? Bu amalni
                    ortga qaytarib bo'lmaydi.</p>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
                    <div class="flex justify-between text-sm mb-2"><span>Javob berilgan:</span><span
                            class="font-medium">{{ answeredCount }}/{{ questions.length }}</span></div>
                    <div class="flex justify-between text-sm"><span>Belgilangan:</span><span class="font-medium">{{
                        flaggedCount }}</span></div>
                    <div v-if="violationCount > 0" class="flex justify-between text-sm text-red-500 mt-2">
                        <span>Ogohlantirishlar:</span><span class="font-medium">{{ violationCount }}</span>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button @click="showSubmitModal = false"
                        class="flex-1 py-3 rounded-xl font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">Bekor
                        qilish</button>
                    <button @click="submitExam"
                        class="flex-1 py-3 rounded-xl font-medium bg-green-600 text-white hover:bg-green-700">Yakunlash
                        ✓</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}
</style>
