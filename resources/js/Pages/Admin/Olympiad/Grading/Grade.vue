<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, onMounted, onUnmounted } from 'vue';
import { Avatar, Badge, Button, Card, IconButton, PageHeader, ProgressBar, Textarea } from '@/Components/UI';
import {
    ChevronLeftIcon, ChevronRightIcon, MinusIcon, PlusIcon, PaperClipIcon, ClockIcon, CheckIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';
import { useSanitize } from '@/Composables/useSanitize';
import MathText from '@/Components/MathText.vue';

const props = defineProps({
    olympiad: Object,
    answer: Object,
    question: Object,
    user: Object,
    nextAnswerId: String,
    prevAnswerId: String,
    queuePosition: Object,
});

const { sanitize } = useSanitize();

// Minimal rich-text styling for sanitized question / rubric HTML (no typography plugin in this project)
const richText = 'max-w-none text-sm leading-relaxed [&_p]:mb-2 [&_p:last-child]:mb-0 [&_ul]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_img]:my-2 [&_img]:max-w-full [&_img]:rounded-md [&_a]:text-brand [&_a]:underline [&_pre]:overflow-x-auto [&_pre]:rounded-md [&_pre]:bg-surface-2 [&_pre]:p-3 [&_table]:w-full [&_td]:border [&_td]:border-border [&_td]:px-2 [&_td]:py-1 [&_th]:border [&_th]:border-border [&_th]:px-2 [&_th]:py-1';

// The controller nests question / participant data inside `answer`; older payloads sent them
// as separate props. Support both shapes.
const q = computed(() => props.question || {});
const aq = computed(() => props.answer?.question || {});
const participantName = computed(() => props.user?.name || props.answer?.user_name || 'Ishtirokchi');
const participantEmail = computed(() => props.user?.email || null);
const sectionTitle = computed(() => q.value.section?.title || props.answer?.section_title || 'Javobni baholash');
const maxPoints = computed(() => Number(q.value.max_points ?? props.answer?.max_points ?? 0) || 0);
const questionHtml = computed(() => sanitize(q.value.question_html || aq.value.html || ''));
const questionText = computed(() => q.value.question_text || aq.value.text || '');
const rubric = computed(() => q.value.grading_rubric ?? aq.value.rubric ?? null);
const rubricIsHtml = computed(() => typeof rubric.value === 'string');
const rubricItems = computed(() => (Array.isArray(rubric.value) ? rubric.value : rubric.value && typeof rubric.value === 'object' ? Object.values(rubric.value) : []));
const rubricText = (item) => (typeof item === 'string' ? item : item?.criterion || item?.title || item?.description || item?.name || JSON.stringify(item));
const rubricPoints = (item) => (item && typeof item === 'object' ? item.points ?? item.max_points ?? null : null);

// Participant answer — text / code / file, or the raw `user_answer` payload
const raw = computed(() => props.answer?.user_answer);
const textAnswer = computed(() => {
    if (props.answer?.text_answer) return props.answer.text_answer;
    const r = raw.value;
    if (typeof r === 'string' || typeof r === 'number') return String(r);
    if (Array.isArray(r) && r.every((x) => typeof x !== 'object')) return r.join(', ');
    if (r && typeof r === 'object') return r.text || r.answer || r.solution || null;
    return null;
});
const codeAnswer = computed(() => props.answer?.code_answer || (raw.value && typeof raw.value === 'object' && !Array.isArray(raw.value) ? raw.value.code : null) || null);
const fileUrl = computed(() => props.answer?.file_url || (raw.value && typeof raw.value === 'object' ? raw.value.file_url || raw.value.file : null) || null);
const rawFallback = computed(() => {
    if (textAnswer.value || codeAnswer.value || fileUrl.value) return null;
    if (raw.value === null || raw.value === undefined || raw.value === '') return null;
    return JSON.stringify(raw.value, null, 2);
});
const hasAnswer = computed(() => Boolean(textAnswer.value || codeAnswer.value || fileUrl.value || rawFallback.value));

const timeSpent = computed(() => {
    const t = Number(props.answer?.time_spent);
    if (!Number.isFinite(t) || t <= 0) return null;
    const m = Math.floor(t / 60);
    const s = Math.round(t % 60);
    return m ? `${m} daq ${s} s` : `${s} s`;
});

const isGraded = computed(() => Boolean(props.answer?.is_graded));

const form = useForm({
    score: props.answer.score ?? props.answer.points_earned ?? 0,
    feedback: props.answer.feedback ?? props.answer.grader_feedback ?? '',
});

const clamp = (v) => Math.min(maxPoints.value || Infinity, Math.max(0, Number(v) || 0));
const step = (dir) => {
    form.score = clamp(Math.round(((Number(form.score) || 0) + dir * 0.5) * 2) / 2);
};
const quick = computed(() => {
    const m = maxPoints.value;
    return [0, 0.25, 0.5, 0.75, 1].map((p, i) => ({
        key: i,
        percent: Math.round(p * 100),
        value: Math.round(m * p * 2) / 2,
    }));
});
const quickScore = (score) => {
    form.score = score;
};
const scorePercent = computed(() => (maxPoints.value ? Math.round((clamp(form.score) / maxPoints.value) * 100) : 0));

const submit = (goNext = true) => {
    form.score = clamp(form.score);
    form
        // The grading endpoint validates `points`; keep `score` for compatibility
        .transform((data) => ({ ...data, points: data.score }))
        .post(route('admin.olympiads.grading.submit', {
            olympiad: props.olympiad.id,
            answer: props.answer.id,
        }), {
            preserveScroll: true,
            onSuccess: () => {
                if (goNext && props.nextAnswerId) {
                    router.visit(route('admin.olympiads.grading.grade', {
                        olympiad: props.olympiad.id,
                        answer: props.nextAnswerId,
                    }));
                }
            },
        });
};

const goTo = (id) => {
    if (!id) return;
    router.visit(route('admin.olympiads.grading.grade', { olympiad: props.olympiad.id, answer: id }));
};

// Keyboard: Ctrl/⌘+Enter save & next · Alt+←/→ previous/next · 1–5 quick scores (outside text fields)
const onKey = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        if (!form.processing) submit(true);
        return;
    }
    if (e.altKey && e.key === 'ArrowLeft' && props.prevAnswerId) { e.preventDefault(); goTo(props.prevAnswerId); return; }
    if (e.altKey && e.key === 'ArrowRight' && props.nextAnswerId) { e.preventDefault(); goTo(props.nextAnswerId); return; }
    const tag = (e.target?.tagName || '').toLowerCase();
    if (['input', 'textarea', 'select'].includes(tag) || e.target?.isContentEditable) return;
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    const n = Number(e.key);
    if (n >= 1 && n <= 5) {
        e.preventDefault();
        quickScore(quick.value[n - 1].value);
    }
};
onMounted(() => {
    window.addEventListener('keydown', onKey);
    // `autofocus` only works on a full page load; focus the score on desktop after SPA navigation too
    if (window.matchMedia?.('(min-width: 1024px)').matches) {
        document.getElementById('grade-score')?.focus({ preventScroll: true });
    }
});
onUnmounted(() => window.removeEventListener('keydown', onKey));

const position = computed(() => props.queuePosition || null);
const breadcrumbs = computed(() => [
    { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
    { label: props.olympiad.title, href: route('admin.olympiads.show', props.olympiad.id) },
    { label: 'Baholash', href: route('admin.olympiads.grading.index', props.olympiad.id) },
    { label: participantName.value },
]);
</script>

<template>
    <AdminLayout>
        <Head :title="`Baholash — ${participantName}`" />

        <PageHeader :title="sectionTitle" :breadcrumbs="breadcrumbs">
            <template #meta>
                <Badge v-if="position" variant="neutral">
                    <span class="tabular-nums">{{ position.current }} / {{ position.total }}</span>
                </Badge>
                <Badge v-if="isGraded" variant="success" dot>Baholangan</Badge>
                <Badge v-else variant="warning" dot>Baholanmagan</Badge>
                <span v-if="timeSpent" class="inline-flex items-center gap-1 text-13 text-fg-subtle">
                    <ClockIcon class="h-4 w-4" aria-hidden="true" /> {{ timeSpent }}
                </span>
            </template>
            <template #actions>
                <IconButton :icon="ChevronLeftIcon" label="Oldingi javob (Alt+←)" variant="secondary" :disabled="!prevAnswerId"
                    @click="goTo(prevAnswerId)" />
                <IconButton :icon="ChevronRightIcon" label="Keyingi javob (Alt+→)" variant="secondary" :disabled="!nextAnswerId"
                    @click="goTo(nextAnswerId)" />
            </template>
        </PageHeader>

        <ProgressBar v-if="position && position.total" :value="Number(position.current) || 0" :max="Number(position.total) || 1"
            size="xs" class="-mt-2 mb-6" />

        <div class="grid gap-6 lg:grid-cols-5">
            <!-- Question & answer -->
            <div class="space-y-6 lg:col-span-3">
                <Card title="Savol">
                    <template #actions>
                        <Badge variant="neutral"><span class="tabular-nums">{{ maxPoints }}</span> ball</Badge>
                    </template>
                    <div v-if="questionHtml" :class="[richText, 'text-fg']" v-html="questionHtml" />
                    <MathText v-else :text="questionText || '—'" tag="div" class="block text-sm leading-relaxed text-fg" />

                    <div v-if="rubric && (rubricIsHtml || rubricItems.length)" class="mt-5 rounded-md border border-border bg-surface-2 p-4">
                        <h4 class="mb-2 flex items-center gap-2 text-13 font-semibold text-fg">
                            <ClipboardDocumentListIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                            Baholash mezonlari
                        </h4>
                        <div v-if="rubricIsHtml" :class="[richText, 'text-fg-muted']" v-html="sanitize(rubric)" />
                        <ul v-else class="space-y-1.5 text-sm text-fg-muted">
                            <li v-for="(item, i) in rubricItems" :key="i" class="flex justify-between gap-3">
                                <span>{{ rubricText(item) }}</span>
                                <span v-if="rubricPoints(item) !== null" class="shrink-0 font-medium tabular-nums text-fg">{{ rubricPoints(item) }} ball</span>
                            </li>
                        </ul>
                    </div>
                </Card>

                <Card title="Ishtirokchi javobi">
                    <div v-if="hasAnswer" class="space-y-4">
                        <div v-if="textAnswer" class="whitespace-pre-line rounded-md bg-surface-2 p-4 text-sm leading-relaxed text-fg">
                            {{ textAnswer }}
                        </div>
                        <pre v-if="codeAnswer" class="overflow-x-auto rounded-md border border-border bg-surface-2 p-4 font-mono text-13 text-fg">{{ codeAnswer }}</pre>
                        <pre v-if="rawFallback" class="overflow-x-auto rounded-md bg-surface-2 p-4 font-mono text-13 text-fg-muted">{{ rawFallback }}</pre>
                        <Button v-if="fileUrl" variant="secondary" size="sm" :icon-left="PaperClipIcon" external :href="fileUrl">
                            Yuklangan faylni ochish
                        </Button>
                    </div>
                    <p v-else class="text-sm text-fg-subtle">Ishtirokchi javob qoldirmagan.</p>
                </Card>
            </div>

            <!-- Grading panel -->
            <aside class="lg:col-span-2">
                <div class="space-y-4 lg:sticky lg:top-24">
                    <Card padding="sm">
                        <div class="flex items-center gap-3">
                            <Avatar :name="participantName" size="md" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-fg">{{ participantName }}</p>
                                <p v-if="participantEmail" class="truncate text-13 text-fg-subtle">{{ participantEmail }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card title="Baho">
                        <form class="space-y-5" @submit.prevent="submit(true)">
                            <div>
                                <label for="grade-score" class="mb-1.5 block text-sm font-medium text-fg">
                                    Ball <span class="font-normal text-fg-subtle">(0 – {{ maxPoints }})</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <IconButton :icon="MinusIcon" label="0.5 kamaytirish" variant="secondary"
                                        :disabled="Number(form.score) <= 0" @click="step(-1)" />
                                    <input id="grade-score" v-model.number="form.score" type="number" inputmode="decimal" min="0"
                                        :max="maxPoints" step="0.5"
                                        :aria-invalid="Boolean(form.errors.points || form.errors.score) || undefined"
                                        aria-describedby="grade-score-hint"
                                        class="focus-ring h-12 w-24 rounded-md border border-border-strong bg-surface text-center text-2xl font-semibold tabular-nums text-fg shadow-xs focus:border-brand-500"
                                        @blur="form.score = clamp(form.score)" />
                                    <IconButton :icon="PlusIcon" label="0.5 oshirish" variant="secondary"
                                        :disabled="Number(form.score) >= maxPoints" @click="step(1)" />
                                    <span class="ml-1 text-sm tabular-nums text-fg-subtle">/ {{ maxPoints }}</span>
                                    <span class="ml-auto text-sm font-medium tabular-nums text-fg-muted">{{ scorePercent }}%</span>
                                </div>
                                <p v-if="form.errors.points || form.errors.score" class="mt-1.5 text-13 text-danger" role="alert">
                                    {{ form.errors.points || form.errors.score }}
                                </p>

                                <div class="mt-3 grid grid-cols-5 gap-1.5" role="group" aria-label="Tezkor ball">
                                    <button v-for="(qs, i) in quick" :key="qs.key" type="button" :aria-pressed="Number(form.score) === qs.value"
                                        :class="[
                                            'focus-ring flex h-10 flex-col items-center justify-center rounded-md border text-xs font-medium tabular-nums transition-colors',
                                            Number(form.score) === qs.value
                                                ? 'border-brand-500 bg-brand-50 text-brand'
                                                : 'border-border bg-surface text-fg-muted hover:bg-surface-2 hover:text-fg',
                                        ]" @click="quickScore(qs.value)">
                                        <span>{{ qs.percent }}%</span>
                                        <span class="text-[10px] text-fg-subtle">{{ i + 1 }}</span>
                                    </button>
                                </div>
                                <p id="grade-score-hint" class="mt-1.5 text-xs text-fg-subtle">Klaviatura: 1–5 tezkor ball</p>
                            </div>

                            <Textarea v-model="form.feedback" label="Izoh" optional :rows="4" :maxlength="1000" show-count autoresize
                                placeholder="Ishtirokchiga ko'rsatiladigan izoh" :error="form.errors.feedback" />

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <Button variant="secondary" class="sm:flex-1" :disabled="form.processing" @click="submit(false)">
                                    Saqlash
                                </Button>
                                <Button type="submit" class="sm:flex-1" :icon-right="CheckIcon" :loading="form.processing">
                                    Saqlash va keyingi
                                </Button>
                            </div>
                            <p class="text-center text-xs text-fg-subtle">
                                <kbd class="rounded-sm border border-border bg-surface-2 px-1.5 py-0.5 font-mono text-[11px]">Ctrl</kbd>
                                +
                                <kbd class="rounded-sm border border-border bg-surface-2 px-1.5 py-0.5 font-mono text-[11px]">Enter</kbd>
                                — saqlash va keyingisiga o'tish
                            </p>
                        </form>
                    </Card>
                </div>
            </aside>
        </div>
    </AdminLayout>
</template>
