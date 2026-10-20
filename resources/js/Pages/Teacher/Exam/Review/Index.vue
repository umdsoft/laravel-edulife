<script setup>
/**
 * Tekshiruv navbati — one question at a time, built for speed:
 *   1–5 mark option A–E · Enter approve · R reject · N / → next · E edit text.
 * The server (QuestionApprovalGuard) decides whether approval is possible; the
 * disabled button only mirrors it.
 */
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CheckIcon, XMarkIcon, ArrowRightIcon, PencilSquareIcon, ClipboardDocumentCheckIcon, ArrowUpTrayIcon, QueueListIcon,
    ExclamationTriangleIcon, VideoCameraIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon as CheckSolid } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Select from '@/Components/UI/Select.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Alert from '@/Components/UI/Alert.vue';
import Modal from '@/Components/UI/Modal.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import MarkdownEditor from '@/Components/Teacher/MarkdownEditor.vue';
import OptionsEditor from '@/Components/Exam/Bank/OptionsEditor.vue';
import NumericAnswerFields from '@/Components/Exam/Bank/NumericAnswerFields.vue';
import {
    DIFFICULTIES, ORIGINS, OPTION_LABELS, difficultyVariant, hasCorrectAnswer, isChoiceType, isNumericType, originLabel,
    reviewKeyAction, topicOptions, typeLabel,
} from '@/Components/Exam/Bank/bank';

const props = defineProps({
    question: { type: Object, default: null },
    queue: { type: Object, default: () => ({ remaining: 0, position: 0, next_id: null }) },
    progress: { type: Object, default: () => ({ approved: 0, target: 100 }) },
    filters: { type: Object, default: () => ({}) },
    subjects: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    vocabulary: { type: Object, default: () => ({}) },
});

const page = usePage();
const errors = computed(() => page.props.errors || {});

// ---- local copy for instant feedback (server answer replaces it) -------------------
const options = ref([]);
const answer = ref(null);
const syncLocal = () => {
    options.value = (props.question?.options || []).map((o) => ({ ...o }));
    answer.value = props.question?.answer
        ? { ...props.question.answer, accepted_units: [...(props.question.answer.accepted_units || [])] }
        : { value: '', tolerance_type: 'abs', tolerance: '0', unit: '', accepted_units: [], note: '' };
};
watch(() => props.question, syncLocal, { immediate: true });

const q = computed(() => props.question);
const isChoice = computed(() => isChoiceType(q.value?.question_type));
const isNumeric = computed(() => isNumericType(q.value?.question_type));
const ready = computed(() => q.value && hasCorrectAnswer({ ...q.value, options: options.value }));
const blockers = computed(() => Object.entries(q.value?.violations || {})
    // the correct-answer item is shown by the answer area itself
    .filter(([k]) => !['correct_count', 'correct_missing', 'answer_missing'].includes(k))
    .map(([, v]) => v));
const busy = ref(false);
const navFilters = computed(() => ({ ...(props.filters || {}) }));

const visit = (method, url, data = {}, opts = {}) => router[method](url, data, {
    preserveScroll: true,
    onStart: () => (busy.value = true),
    onFinish: () => (busy.value = false),
    ...opts,
});

// ---- marking -----------------------------------------------------------------------
const mark = (index) => {
    const opt = options.value[index];
    if (!opt || !q.value) return;
    if (q.value.question_type === 'multiple_choice') {
        opt.is_correct = opt.is_correct !== true;
    } else {
        options.value.forEach((o, i) => (o.is_correct = i === index));
    }
    router.post(route('teacher.exam.review.mark', q.value.id), { option_id: opt.id }, {
        preserveScroll: true, preserveState: true, async: true, only: ['question', 'errors', 'flash'],
    });
};

const saveAnswer = () => visit('post', route('teacher.exam.review.answer', q.value.id), { answer: answer.value }, {
    preserveState: true, only: ['question', 'errors', 'flash'],
});

// ---- approve / reject / next -------------------------------------------------------
const approve = () => {
    if (!q.value || busy.value) return;
    const picked = q.value.question_type === 'single_choice' ? options.value.find((o) => o.is_correct === true) : null;
    visit('post', route('teacher.exam.review.approve', q.value.id), { option_id: picked?.id || null, ...navFilters.value });
};
const next = () => {
    if (!q.value || busy.value) return;
    visit('get', route('teacher.exam.review'), { ...navFilters.value, after: q.value.id }, { preserveState: false });
};

const rejectOpen = ref(false);
const reject = reactive({ reason: '', error: null });
const QUICK_REASONS = ['Shart noaniq yoki to\'liq emas', 'Variantlarda xato', 'To\'g\'ri javob yo\'q', 'Dublikat', 'Mavzu yoki daraja mos emas'];
const openReject = () => {
    reject.reason = '';
    reject.error = null;
    rejectOpen.value = true;
};
const sendReject = () => {
    if (!reject.reason.trim()) {
        reject.error = 'Rad etish sababini yozing.';
        return;
    }
    visit('post', route('teacher.exam.review.reject', q.value.id), { reason: reject.reason, ...navFilters.value }, {
        onSuccess: () => (rejectOpen.value = false),
        onError: (e) => (reject.error = e.reason || null),
    });
};

// ---- meta (one click saves) and inline edit ----------------------------------------
const patch = (data) => visit('patch', route('teacher.exam.review.update', q.value.id), { subject_id: q.value.subject_id, ...data }, {
    preserveState: true, only: ['question', 'errors', 'flash'],
});
const setDifficulty = (level) => level !== q.value.difficulty_level && patch({ difficulty_level: level });
const setTopic = (id) => id !== q.value.topic_id && patch({ topic_id: id });
const setSubject = (id) => id !== q.value.subject_id && patch({ subject_id: id, topic_id: null });
const topicChoices = computed(() => (q.value ? topicOptions(props.topics, q.value.subject_id, { keep: q.value.topic_id }) : []));
const subjectChoices = computed(() => props.subjects.map((s) => ({ value: s.id, label: s.name_uz })));

const editing = ref(false);
const edit = reactive({ question_text: '', options: [], source_ref: '', solution_text: '', solution_video_url: '', units_note: '' });
const startEdit = () => {
    if (!q.value) return;
    Object.assign(edit, {
        question_text: q.value.question_text || '',
        options: (q.value.options || []).map((o) => ({ text: o.text })),
        source_ref: q.value.source_ref || '',
        solution_text: q.value.solution_text || '',
        solution_video_url: q.value.solution_video_url || '',
        units_note: q.value.units_note || '',
    });
    editing.value = true;
};
const saveEdit = () => {
    const data = { ...edit };
    if (!isChoice.value) delete data.options;
    patch(data);
};
watch(() => q.value?.id, () => (editing.value = false));
watch(() => page.props.flash?.success, (msg) => {
    if (msg === 'Saqlandi.') editing.value = false;
});

// ---- keyboard ----------------------------------------------------------------------
const onKeydown = (e) => {
    if (rejectOpen.value) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            sendReject();
        }
        return;
    }
    if (editing.value) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'Enter')) {
            e.preventDefault();
            saveEdit();
        } else if (e.key === 'Escape') {
            editing.value = false;
        }
        return;
    }
    const hit = reviewKeyAction(e);
    if (!hit || !q.value) return;
    e.preventDefault();
    if (hit.action === 'mark' && isChoice.value) mark(hit.index);
    else if (hit.action === 'approve') approve();
    else if (hit.action === 'reject') openReject();
    else if (hit.action === 'next') next();
    else if (hit.action === 'edit') startEdit();
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

// ---- filters -----------------------------------------------------------------------
const filterSubject = ref(props.filters.subject || null);
const filterOrigin = ref(props.filters.origin || null);
watch([filterSubject, filterOrigin], ([subject, origin]) => {
    router.get(route('teacher.exam.review'), Object.fromEntries(Object.entries({ subject, origin }).filter(([, v]) => v)), { replace: true });
});
const subjectFilterOptions = computed(() => props.subjects.map((s) => ({ value: s.code, label: s.name_uz })));
const originFilterOptions = Object.entries(ORIGINS).map(([value, label]) => ({ value, label }));

const progressPct = computed(() => Math.round((props.progress.approved / Math.max(1, props.progress.target)) * 100));
const videoHost = computed(() => {
    try {
        return q.value?.solution_video_url ? new URL(q.value.solution_video_url).host : null;
    } catch {
        return null;
    }
});
</script>

<template>
    <Head title="Tekshiruv navbati" />

    <TeacherLayout>
        <PageHeader title="Tekshiruv navbati" description="To'g'ri javobni belgilang va tasdiqlang — imtihonga faqat tasdiqlangan savol tushadi.">
            <template #meta>
                <div class="flex min-w-[14rem] items-center gap-3">
                    <span class="text-13 text-fg-muted">Bu hafta tasdiqlandi:</span>
                    <span class="text-13 font-semibold tabular-nums text-fg">{{ progress.approved }} / {{ progress.target }}</span>
                    <ProgressBar :value="progress.approved" :max="progress.target" size="sm" class="w-28"
                        :tone="progressPct >= 100 ? 'success' : 'brand'" :label="`Haftalik reja: ${progressPct}%`" />
                </div>
            </template>
            <template #actions>
                <Select v-model="filterSubject" :options="subjectFilterOptions" placeholder="Barcha fanlar" size="sm" class="w-40" aria-label="Fan" />
                <Select v-model="filterOrigin" :options="originFilterOptions" placeholder="Har qanday manba" size="sm" class="w-44" aria-label="Manba turi" />
            </template>
        </PageHeader>

        <template v-if="q">
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- the question, large -->
                <div class="space-y-4 lg:col-span-2">
                    <Card padding="lg">
                        <template #header>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="mr-1 text-13 font-semibold tabular-nums text-fg-subtle">{{ queue.position }} / {{ queue.remaining }}</span>
                                <Badge v-if="q.subject" :variant="q.subject.code === 'physics' ? 'physics' : 'math'" size="sm">{{ q.subject.name_uz }}</Badge>
                                <Badge v-if="q.topic" variant="neutral" size="sm">{{ q.topic.name_uz }}</Badge>
                                <Badge v-if="q.difficulty_level" :variant="difficultyVariant(q.difficulty_level)" size="sm">Qiyinlik {{ q.difficulty_level }}</Badge>
                                <Badge variant="neutral" size="sm">{{ typeLabel(q.question_type) }}</Badge>
                                <Badge variant="info" size="sm">{{ originLabel(q.origin) }}</Badge>
                            </div>
                        </template>

                        <!-- stem -->
                        <div v-if="!editing">
                            <RichContent :html="q.question_text" size="lg" class="text-fg" />
                            <p v-if="q.units_note" class="mt-3 text-13 text-fg-muted">{{ q.units_note }}</p>
                            <p v-if="q.source_ref" class="mt-3 text-13 text-fg-subtle">Manba: {{ q.source_ref }}</p>
                        </div>
                        <div v-else class="space-y-4">
                            <MarkdownEditor v-model="edit.question_text" label="Shart" :rows="5" :error="errors.question_text" />
                            <OptionsEditor v-if="isChoice" v-model="edit.options" hide-marks :errors="errors" />
                            <Input v-model="edit.source_ref" label="Manba" optional :error="errors.source_ref" />
                            <Input v-model="edit.units_note" label="Birliklar haqida izoh" optional :error="errors.units_note" />
                            <MarkdownEditor v-model="edit.solution_text" label="Yechim matni (ixtiyoriy)" :rows="4" :error="errors.solution_text" />
                            <Input v-model="edit.solution_video_url" label="Yechim videosi" type="url" optional placeholder="https://youtu.be/…"
                                :error="errors.solution_video_url" />
                            <div class="flex justify-end gap-2">
                                <Button variant="secondary" @click="editing = false">Bekor qilish <kbd class="ml-1 text-xs text-fg-subtle">Esc</kbd></Button>
                                <Button :loading="busy" @click="saveEdit">Saqlash <kbd class="ml-1 text-xs opacity-75">Ctrl+S</kbd></Button>
                            </div>
                        </div>

                        <!-- options: one click / key marks the correct one -->
                        <div v-if="isChoice && !editing" class="mt-6 space-y-2" role="group" aria-label="Variantlar — to'g'risini belgilang">
                            <button v-for="(o, i) in options" :key="o.id" type="button" :aria-pressed="o.is_correct === true"
                                :class="[
                                    'focus-ring group flex w-full items-start gap-3 rounded-md border px-3.5 py-3 text-left transition-colors duration-150',
                                    o.is_correct === true ? 'border-success-600 bg-success-50' : 'border-border hover:border-border-strong hover:bg-surface-2',
                                ]" @mousedown.prevent @click="mark(i)">
                                <span :class="[
                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-13 font-semibold',
                                    o.is_correct === true ? 'border-success-600 bg-success-600 text-white' : 'border-border-strong text-fg-muted group-hover:text-fg',
                                ]">
                                    <CheckSolid v-if="o.is_correct === true" class="h-4 w-4" aria-hidden="true" />
                                    <template v-else>{{ OPTION_LABELS[i] }}</template>
                                </span>
                                <RichContent :html="o.text" size="md" class="min-w-0 flex-1 pt-1 text-fg" />
                                <kbd class="hidden shrink-0 rounded-sm border border-border bg-surface px-1.5 text-xs text-fg-subtle sm:inline">{{ i + 1 }}</kbd>
                            </button>
                            <p v-if="q.question_type === 'multiple_choice'" class="text-13 text-fg-subtle">Bir nechta to'g'ri variant bo'lishi mumkin — har birini bosing.</p>
                        </div>

                        <!-- numeric answer -->
                        <div v-if="isNumeric && !editing" class="mt-6 rounded-md border border-border p-4">
                            <p class="mb-3 text-13 font-medium text-fg">To'g'ri javob</p>
                            <NumericAnswerFields v-model="answer" :with-unit="q.question_type === 'numeric_with_unit'" :errors="errors" compact />
                            <div class="mt-3 flex justify-end">
                                <Button size="sm" variant="secondary" :loading="busy" @click="saveAnswer">Javobni saqlash</Button>
                            </div>
                        </div>

                        <template #footer>
                            <div class="flex flex-wrap items-center gap-2">
                                <Button variant="danger" :icon-left="XMarkIcon" :disabled="busy" title="R" @click="openReject">
                                    Rad etish <kbd class="ml-1 hidden text-xs opacity-75 sm:inline">R</kbd>
                                </Button>
                                <Button variant="ghost" :icon-left="PencilSquareIcon" :disabled="busy || editing" title="E" @click="startEdit">
                                    Tahrirlash <kbd class="ml-1 hidden text-xs text-fg-subtle sm:inline">E</kbd>
                                </Button>
                                <span class="flex-1" />
                                <Button variant="secondary" :icon-right="ArrowRightIcon" :disabled="busy || queue.remaining < 2" title="N" @click="next">
                                    Keyingi <kbd class="ml-1 hidden text-xs text-fg-subtle sm:inline">N</kbd>
                                </Button>
                                <Button :icon-left="CheckIcon" :disabled="!ready || busy" :loading="busy" title="Enter" @click="approve">
                                    Tasdiqlash <kbd class="ml-1 hidden text-xs opacity-75 sm:inline">Enter</kbd>
                                </Button>
                            </div>
                        </template>
                    </Card>

                    <Alert v-if="errors.approve" variant="danger" title="Tasdiqlanmadi">{{ errors.approve }}</Alert>
                    <Alert v-else-if="!ready" variant="warning" :icon="ExclamationTriangleIcon">
                        {{ isChoice ? 'To\'g\'ri variantni belgilang (sichqoncha yoki 1–5 tugmalari).' : 'To\'g\'ri javobni kiriting va saqlang.' }}
                    </Alert>
                    <Alert v-if="blockers.length" variant="warning" title="Tasdiqlash uchun yana kerak">
                        <ul class="list-disc pl-5"><li v-for="b in blockers" :key="b">{{ b }}</li></ul>
                    </Alert>
                </div>

                <!-- classification, one click saves -->
                <aside class="space-y-6">
                    <Card title="Tasnif">
                        <div class="space-y-4">
                            <Select :model-value="q.subject_id" label="Fan" :options="subjectChoices" size="sm" @update:model-value="setSubject" />
                            <Select :model-value="q.topic_id" label="Mavzu" placeholder="Mavzuni tanlang" :options="topicChoices" size="sm"
                                :error="errors.topic_id" @update:model-value="setTopic" />
                            <div>
                                <p class="mb-1.5 text-13 font-medium text-fg">Qiyinlik</p>
                                <div class="grid grid-cols-5 gap-1" role="radiogroup" aria-label="Qiyinlik">
                                    <button v-for="d in DIFFICULTIES" :key="d.value" type="button" role="radio" :aria-checked="q.difficulty_level === d.value"
                                        :title="d.label" :class="[
                                            'focus-ring h-9 rounded-md border text-13 font-semibold tabular-nums transition-colors duration-150',
                                            q.difficulty_level === d.value ? 'border-brand-500 bg-brand-50 text-brand' : 'border-border-strong text-fg-muted hover:bg-surface-2',
                                        ]" @mousedown.prevent @click="setDifficulty(d.value)">{{ d.value }}</button>
                                </div>
                                <p v-if="errors.difficulty_level" class="mt-1 text-13 text-danger">{{ errors.difficulty_level }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card title="Yechim">
                        <div v-if="q.solution_text" class="max-h-64 overflow-y-auto">
                            <RichContent :html="q.solution_text" size="sm" />
                        </div>
                        <p v-else class="text-13 text-fg-subtle">Yechim yozilmagan (ixtiyoriy).</p>
                        <a v-if="q.solution_video_url" :href="q.solution_video_url" target="_blank" rel="noopener noreferrer"
                            class="focus-ring mt-3 inline-flex items-center gap-1.5 rounded-sm text-13 text-brand underline-offset-2 hover:underline">
                            <VideoCameraIcon class="h-4 w-4" aria-hidden="true" />{{ videoHost || 'Video' }}
                        </a>
                        <Button size="sm" variant="ghost" class="mt-3" :icon-left="PencilSquareIcon" @click="startEdit">Qo'shish / tahrirlash</Button>
                    </Card>

                    <div class="rounded-lg border border-border bg-surface-2/60 p-4 text-13 text-fg-muted">
                        <p class="mb-2 font-medium text-fg">Tezkor tugmalar</p>
                        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                            <dt><kbd class="rounded-sm border border-border bg-surface px-1">1</kbd>–<kbd class="rounded-sm border border-border bg-surface px-1">5</kbd></dt><dd>A–E variantni belgilash</dd>
                            <dt><kbd class="rounded-sm border border-border bg-surface px-1">Enter</kbd></dt><dd>Tasdiqlash</dd>
                            <dt><kbd class="rounded-sm border border-border bg-surface px-1">R</kbd></dt><dd>Rad etish</dd>
                            <dt><kbd class="rounded-sm border border-border bg-surface px-1">N</kbd></dt><dd>Keyingi savol</dd>
                            <dt><kbd class="rounded-sm border border-border bg-surface px-1">E</kbd></dt><dd>Matnni tahrirlash</dd>
                        </dl>
                    </div>
                </aside>
            </div>
        </template>

        <EmptyState v-else :icon="ClipboardDocumentCheckIcon" bordered size="lg" title="Navbat bo'sh"
            :description="filters.subject || filters.origin ? 'Bu filtr bo\'yicha tekshiriladigan savol yo\'q.' : 'Tekshiriladigan savol qolmadi. Yangi savol qo\'shing yoki import qiling.'">
            <div class="flex flex-wrap justify-center gap-2">
                <Button variant="secondary" :icon-left="QueueListIcon" :href="route('teacher.exam.questions.index')">Savollar banki</Button>
                <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :href="route('teacher.exam.import.index')">Import</Button>
            </div>
        </EmptyState>

        <Modal :show="rejectOpen" title="Rad etish" description="Sabab savol kartasida saqlanadi." size="md" @close="rejectOpen = false">
            <div class="space-y-3">
                <div class="flex flex-wrap gap-1.5">
                    <button v-for="r in QUICK_REASONS" :key="r" type="button"
                        class="focus-ring rounded-full border border-border-strong px-2.5 py-1 text-13 text-fg-muted hover:bg-surface-2 hover:text-fg"
                        @click="reject.reason = r">{{ r }}</button>
                </div>
                <Textarea v-model="reject.reason" label="Sabab" required :rows="3" autofocus :error="reject.error" />
            </div>
            <template #footer>
                <Button variant="secondary" @click="rejectOpen = false">Bekor qilish</Button>
                <Button variant="danger" :loading="busy" title="Ctrl+Enter" @click="sendReject">Rad etish</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
