<script setup>
/**
 * Shanba imtihoni — taking an exam attempt.
 * One question per screen (or the whole list), KaTeX via MathText, server-authoritative timer,
 * batched autosave (dirty answers every few seconds, on navigation, when the tab hides, before submit),
 * numeric input with a unit picker, submit confirmation. Mobile first (375px).
 */
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { FlagIcon as FlagOutline, Squares2X2Icon, QueueListIcon, DocumentTextIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import { FlagIcon, CheckCircleIcon, ExclamationCircleIcon, EyeSlashIcon } from '@heroicons/vue/20/solid';
import MathText from '@/Components/MathText.vue';
import { Badge, Button, Card, Input, Modal, ProgressBar, Select, Spinner } from '@/Components/UI';
import TestTimer from '@/Components/Student/TestTimer.vue';
import TestProgress from '@/Components/Student/TestProgress.vue';
import QuestionNavigation from '@/Components/Student/QuestionNavigation.vue';
import TestSubmitConfirm from '@/Components/Student/TestSubmitConfirm.vue';
import QuestionSingleChoice from '@/Components/Student/QuestionSingleChoice.vue';
import QuestionMultipleChoice from '@/Components/Student/QuestionMultipleChoice.vue';

const props = defineProps({
    attempt: { type: Object, required: true },
    session: { type: Object, required: true },
    questions: { type: Array, required: true },
    answers: { type: Object, default: () => ({}) },
    autosaveSeconds: { type: Number, default: 15 },
    serverNow: { type: String, default: null },
});

// --- state -------------------------------------------------------------------------------
const state = reactive({});
props.questions.forEach((q) => {
    const saved = props.answers?.[q.id] || {};
    state[q.id] = {
        selected_option_id: saved.selected_option_id ?? null,
        selected_option_ids: Array.isArray(saved.selected_option_ids) ? [...saved.selected_option_ids] : [],
        answer_text: saved.answer_text ?? '',
        unit: saved.unit ?? null,
        is_flagged: !!saved.is_flagged,
    };
});

const LIST_KEY = 'edulife_exam_list_view';
const readListPref = () => { try { return localStorage.getItem(LIST_KEY) === '1'; } catch { return false; } };
const listView = ref(readListPref());
const toggleView = () => {
    listView.value = !listView.value;
    try { localStorage.setItem(LIST_KEY, listView.value ? '1' : '0'); } catch { /* private mode */ }
};

const current = ref(0);
const showNavigator = ref(false);
const showSubmitConfirm = ref(false);
const isSubmitting = ref(false);
const saveState = ref('idle'); // idle | saving | saved | error
const violations = ref(props.attempt.violations_count || 0);

// Timer from the server's remaining seconds, so a wrong device clock does not matter
const expiresAt = new Date(Date.now() + (props.attempt.remaining_seconds || 0) * 1000).toISOString();

const currentQuestion = computed(() => props.questions[current.value]);
const isLast = computed(() => current.value === props.questions.length - 1);

const isAnswered = (q) => {
    const a = state[q.id];
    if (!a) return false;
    if (q.type === 'multiple_choice') return a.selected_option_ids.length > 0;
    if (q.options?.length) return !!a.selected_option_id;
    return String(a.answer_text || '').trim() !== '';
};
const navQuestions = computed(() => props.questions.map((q) => ({ id: q.id, is_answered: isAnswered(q), is_flagged: state[q.id].is_flagged })));
const answeredCount = computed(() => navQuestions.value.filter((q) => q.is_answered).length);
const unansweredCount = computed(() => props.questions.length - answeredCount.value);
const answeredPct = computed(() => (props.questions.length ? (answeredCount.value / props.questions.length) * 100 : 0));

const typeHint = (q) => ({
    single_choice: 'Bitta javobni tanlang',
    true_false: "To'g'ri yoki noto'g'ri",
    multiple_choice: "Bir nechta to'g'ri javob bo'lishi mumkin",
    numeric: 'Javobni son bilan yozing',
    numeric_with_unit: 'Javobni son bilan yozing va birlikni tanlang',
}[q.type] || '');
const pointsLabel = (p) => `${String(p).replace('.', ',')} ball`;

// --- autosave ----------------------------------------------------------------------------
const dirty = new Set();
const timeSpent = {}; // seconds per question since the last save
let shownAt = Date.now();
let saving = null;
let closed = false;

const markDirty = (id) => {
    dirty.add(id);
    saveState.value = 'idle';
};

const trackTime = () => {
    const q = currentQuestion.value;
    if (!q || listView.value) return;
    const seconds = Math.round((Date.now() - shownAt) / 1000);
    if (seconds > 0) {
        timeSpent[q.id] = (timeSpent[q.id] || 0) + seconds;
        dirty.add(q.id);
    }
    shownAt = Date.now();
};

const payloadFor = (q) => {
    const a = state[q.id];
    const item = { question_id: q.id, is_flagged: a.is_flagged };
    if (q.type === 'multiple_choice') item.selected_option_ids = a.selected_option_ids;
    else if (q.options?.length) item.selected_option_id = a.selected_option_id;
    else {
        item.answer_text = String(a.answer_text ?? '').trim() || null;
        if (q.type === 'numeric_with_unit') item.unit = a.unit || null;
    }
    if (timeSpent[q.id]) item.time_spent_seconds = Math.min(3600, timeSpent[q.id]);
    return item;
};

const headers = { 'X-Exam-Token': props.attempt.token };

const flush = async () => {
    if (closed) return true;
    if (saving) await saving;
    if (!dirty.size) return true;

    const ids = [...dirty];
    dirty.clear();
    const items = props.questions.filter((q) => ids.includes(q.id)).map(payloadFor);
    ids.forEach((id) => delete timeSpent[id]);

    saveState.value = 'saving';
    saving = axios
        .post(route('student.exam.answers', props.attempt.id), { answers: items }, { headers })
        .then(() => {
            saveState.value = 'saved';
            return true;
        })
        .catch((error) => {
            if (error.response?.status === 409) {
                closed = true;
                router.visit(error.response.data?.redirect || route('student.exam.result', props.attempt.id));
                return false;
            }
            ids.forEach((id) => dirty.add(id)); // try again next time
            saveState.value = 'error';
            return false;
        })
        .finally(() => {
            saving = null;
        });
    return saving;
};

// --- navigation --------------------------------------------------------------------------
const questionHeading = ref(null);
const focusQuestion = () => requestAnimationFrame(() => questionHeading.value?.focus({ preventScroll: false }));

const go = (index) => {
    if (index < 0 || index >= props.questions.length) return;
    trackTime();
    current.value = index;
    showNavigator.value = false;
    flush();
    focusQuestion();
};

const jumpInList = (index) => {
    showNavigator.value = false;
    document.getElementById(`exam-q-${props.questions[index].id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const toggleFlag = (q) => {
    state[q.id].is_flagged = !state[q.id].is_flagged;
    markDirty(q.id);
};

const setChoice = (q, value) => {
    if (q.type === 'multiple_choice') state[q.id].selected_option_ids = value || [];
    else state[q.id].selected_option_id = value;
    markDirty(q.id);
};
const setText = (q, value) => {
    state[q.id].answer_text = value;
    markDirty(q.id);
};
const setUnit = (q, value) => {
    state[q.id].unit = value;
    markDirty(q.id);
};

const unitOptions = (q) => (q.unit_choices || []).map((u) => ({ value: u, label: u }));

// --- submit ------------------------------------------------------------------------------
const submit = async () => {
    if (isSubmitting.value || closed) return;
    isSubmitting.value = true;
    trackTime();
    await flush();
    if (closed) return;
    closed = true;
    router.post(route('student.exam.submit', props.attempt.id), {}, {
        onError: () => { isSubmitting.value = false; closed = false; },
    });
};

const onExpired = () => submit();

// --- light anti-cheat: tab switches are logged, never block ------------------------------
let hiddenAt = null;
const onVisibility = () => {
    if (document.hidden) {
        hiddenAt = Date.now();
        trackTime();
        flush();
        return;
    }
    if (hiddenAt === null || closed) return;
    const hidden = Date.now() - hiddenAt;
    hiddenAt = null;
    shownAt = Date.now();
    if (hidden < 1500) return;
    violations.value++;
    axios.post(route('student.exam.events', props.attempt.id), { type: 'tab_switch', hidden_ms: hidden }, { headers }).catch(() => {});
};

const onBeforeUnload = (e) => {
    if (dirty.size && !closed) {
        flush();
        e.preventDefault();
        e.returnValue = '';
    }
};

let interval = null;
onMounted(() => {
    interval = setInterval(() => { trackTime(); flush(); }, Math.max(5, props.autosaveSeconds) * 1000);
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('beforeunload', onBeforeUnload);
});
onBeforeUnmount(() => {
    clearInterval(interval);
    document.removeEventListener('visibilitychange', onVisibility);
    window.removeEventListener('beforeunload', onBeforeUnload);
});
</script>

<template>
    <div class="min-h-screen bg-bg text-fg">
        <Head :title="session.title" />

        <a href="#question-area"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-toast focus:rounded-md focus:bg-surface focus:px-4 focus:py-2 focus:shadow-lg">
            Savolga o'tish
        </a>

        <!-- Focused exam bar: title · timer · submit -->
        <header class="sticky top-0 z-topbar border-b border-border bg-surface/95 backdrop-blur">
            <div class="mx-auto flex h-14 max-w-6xl items-center gap-2 px-4 sm:h-16 sm:gap-3 sm:px-6">
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-sm font-semibold text-fg sm:text-base">{{ session.title }}</h1>
                    <p v-if="session.subject" class="hidden truncate text-xs text-fg-subtle sm:block">{{ session.subject.name }}</p>
                </div>

                <TestTimer :expires-at="expiresAt" @expired="onExpired" />

                <Button variant="secondary" size="sm" :icon-left="Squares2X2Icon" class="lg:hidden"
                    :aria-label="`Savollar: ${answeredCount}/${questions.length}`" @click="showNavigator = true">
                    <span class="tabular-nums">{{ answeredCount }}/{{ questions.length }}</span>
                </Button>
                <Button size="sm" class="hidden sm:inline-flex" :loading="isSubmitting" @click="showSubmitConfirm = true">
                    Topshirish
                </Button>
            </div>
            <ProgressBar :value="answeredPct" size="xs" class="[&>div]:rounded-none" />
        </header>

        <main id="question-area" class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:py-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_288px] lg:items-start">
                <div class="min-w-0 space-y-4">
                    <template v-for="(q, index) in (listView ? questions : [currentQuestion])" :key="q.id">
                        <Card :id="`exam-q-${q.id}`" padding="md" class="min-w-0 scroll-mt-24">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 :ref="listView ? undefined : (el) => (questionHeading = el)" tabindex="-1"
                                        class="text-13 font-medium text-fg-subtle focus:outline-none">
                                        Savol <span class="text-fg tabular-nums">{{ q.position }}</span>
                                        <span class="tabular-nums"> / {{ questions.length }}</span>
                                        <span class="mx-1.5 text-border-strong" aria-hidden="true">·</span>
                                        <span class="tabular-nums">{{ pointsLabel(q.max_points) }}</span>
                                    </h2>
                                    <p class="mt-0.5 text-xs text-fg-subtle">{{ typeHint(q) }}</p>
                                </div>
                                <Button :variant="state[q.id].is_flagged ? 'soft' : 'ghost'" size="sm"
                                    :aria-pressed="state[q.id].is_flagged" @click="toggleFlag(q)">
                                    <template #leading>
                                        <FlagIcon v-if="state[q.id].is_flagged" class="h-4 w-4 text-warning-600" />
                                        <FlagOutline v-else class="h-4 w-4" />
                                    </template>
                                    <span class="hidden sm:inline">{{ state[q.id].is_flagged ? 'Belgilangan' : 'Keyinroq qaytish' }}</span>
                                    <span class="sm:hidden">{{ state[q.id].is_flagged ? 'Belgili' : 'Belgilash' }}</span>
                                </Button>
                            </div>

                            <!-- Question text: the hero of the page -->
                            <MathText :text="q.text" tag="div" class="mb-4 block max-w-[70ch] text-base leading-relaxed text-fg sm:text-lg" />
                            <img v-if="q.image" :src="q.image" alt="Savol rasmi" class="mb-4 max-h-72 rounded-md border border-border" loading="lazy">
                            <p v-if="q.units_note" class="mb-4 inline-flex items-center gap-1.5 rounded-sm bg-surface-2 px-2 py-1 text-13 text-fg-muted">
                                <InformationCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                                <MathText :text="q.units_note" />
                            </p>

                            <div>
                                <QuestionMultipleChoice v-if="q.type === 'multiple_choice'" :question="q"
                                    :model-value="state[q.id].selected_option_ids" @update:model-value="(v) => setChoice(q, v)" />
                                <QuestionSingleChoice v-else-if="q.options.length" :question="q"
                                    :model-value="state[q.id].selected_option_id" @update:model-value="(v) => setChoice(q, v)" />
                                <div v-else class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-start">
                                    <Input :id="`answer-${q.id}`" :model-value="state[q.id].answer_text" label="Javob" inputmode="decimal"
                                        autocomplete="off" maxlength="64" size="lg" placeholder="Masalan: 2,5 yoki 1/2 yoki 3·10^8"
                                        hint="Kasr: vergul yoki nuqta. Daraja: 3·10^8 yoki 3e8."
                                        @update:model-value="(v) => setText(q, v)" />
                                    <Select v-if="q.type === 'numeric_with_unit'" :id="`unit-${q.id}`" :model-value="state[q.id].unit"
                                        label="Birlik" placeholder="Tanlang" size="lg" :options="unitOptions(q)"
                                        @update:model-value="(v) => setUnit(q, v)" />
                                </div>
                            </div>

                            <div v-if="!listView"
                                class="pb-safe sticky bottom-0 z-10 -mx-5 -mb-5 mt-6 rounded-b-lg bg-surface/95 px-5 pb-4 pt-2 backdrop-blur sm:static sm:mx-0 sm:mb-0 sm:bg-transparent sm:px-0 sm:pb-0 sm:pt-0 sm:backdrop-blur-none">
                                <QuestionNavigation :has-previous="current > 0" :has-next="!isLast" :is-last="isLast"
                                    :is-submitting="isSubmitting" @previous="go(current - 1)" @next="go(current + 1)"
                                    @submit="showSubmitConfirm = true" />
                            </div>
                        </Card>
                    </template>

                    <div v-if="listView" class="flex justify-end">
                        <Button :loading="isSubmitting" @click="showSubmitConfirm = true">Imtihonni topshirish</Button>
                    </div>

                    <p class="flex min-h-[1.25rem] flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-fg-subtle sm:justify-end" aria-live="polite">
                        <span v-if="saveState === 'saving'" class="inline-flex items-center gap-1.5"><Spinner size="h-3.5 w-3.5" /> Saqlanmoqda…</span>
                        <span v-else-if="saveState === 'saved'" class="inline-flex items-center gap-1.5">
                            <CheckCircleIcon class="h-3.5 w-3.5 text-success" aria-hidden="true" /> Javoblar saqlandi
                        </span>
                        <span v-else-if="saveState === 'error'" class="inline-flex items-center gap-1.5 text-danger">
                            <ExclamationCircleIcon class="h-3.5 w-3.5" aria-hidden="true" /> Saqlanmadi — qayta urinamiz
                        </span>
                        <span v-if="violations > 0" class="inline-flex items-center gap-1.5 text-warning">
                            <EyeSlashIcon class="h-3.5 w-3.5" aria-hidden="true" /> Boshqa oynaga o'tish: {{ violations }} (ustozga ko'rinadi)
                        </span>
                    </p>
                </div>

                <!-- Navigator (desktop) -->
                <aside class="hidden space-y-4 lg:sticky lg:top-24 lg:block" aria-label="Savollar navigatori">
                    <Card padding="sm">
                        <TestProgress :questions="navQuestions" :current-question-index="listView ? -1 : current"
                            @navigate="(i) => (listView ? jumpInList(i) : go(i))" />
                    </Card>
                    <Button block variant="ghost" size="sm" :icon-left="listView ? DocumentTextIcon : QueueListIcon" @click="toggleView">
                        {{ listView ? 'Bittadan ko\'rsatish' : 'Hammasini ro\'yxatda ko\'rsatish' }}
                    </Button>
                    <p class="flex gap-2 px-1 text-xs text-fg-subtle">
                        <InformationCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                        Javoblar har {{ autosaveSeconds }} soniyada va savoldan o'tganda avtomatik saqlanadi. Vaqt tugasa, imtihon o'zi topshiriladi.
                    </p>
                    <Button block variant="secondary" :loading="isSubmitting" @click="showSubmitConfirm = true">
                        Imtihonni topshirish
                    </Button>
                </aside>
            </div>
        </main>

        <!-- Navigator (mobile sheet) -->
        <Modal :show="showNavigator" size="md" title="Savollar" @close="showNavigator = false">
            <div class="space-y-3 pb-2">
                <TestProgress :questions="navQuestions" :current-question-index="listView ? -1 : current"
                    @navigate="(i) => (listView ? jumpInList(i) : go(i))" />
                <Badge v-if="unansweredCount" variant="warning">Javobsiz: {{ unansweredCount }}</Badge>
            </div>
            <template #footer>
                <Button variant="ghost" @click="toggleView(); showNavigator = false">
                    {{ listView ? 'Bittadan' : 'Ro\'yxat' }}
                </Button>
                <Button :loading="isSubmitting" @click="showNavigator = false; showSubmitConfirm = true">Topshirish</Button>
            </template>
        </Modal>

        <TestSubmitConfirm :show="showSubmitConfirm" :unanswered-count="unansweredCount" :loading="isSubmitting"
            @close="showSubmitConfirm = false" @confirm="submit" />
    </div>
</template>
