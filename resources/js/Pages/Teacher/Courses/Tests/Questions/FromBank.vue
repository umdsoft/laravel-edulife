<script setup>
/**
 * "Bankdan qo'shish": exam bank questions into a course test (docs/exam/03 §2.2).
 * Pick subject / topic / difficulty, see how many approved questions each level has, then either
 * tick questions by hand or ask for a distribution ("oson 5 · o'rta 10 · qiyin 5") drawn at random.
 * The questions are copied with the level mapped to the course band (1–2 oson, 3 o'rta, 4–5 qiyin).
 */
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ArchiveBoxArrowDownIcon, ArrowPathIcon, CheckIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Alert, Badge, Button, Card, Checkbox, EmptyState, Input, PageHeader, Select, Spinner, Tabs } from '@/Components/UI';
import RichContent from '@/Components/Student/RichContent.vue';
import { topicOptions } from '@/Components/Exam/Bank/bank';
import { BAND_LABELS, BANDS, LEVELS, LEVEL_LABELS, bandLabel, difficultyVariant, distributionSummary, levelLabel } from '@/Utils/difficulty';

const props = defineProps({
    course: { type: Object, required: true },
    test: { type: Object, required: true },
    subjects: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    bands: { type: Array, default: () => [] },
    inTest: { type: Array, default: () => [] },
    maxPerAdd: { type: Number, default: 100 },
});

const f = reactive({ subject_id: props.subjects[0]?.id ?? null, topic_id: null, difficulty: null, q: '' });
const subjectOptions = computed(() => props.subjects.map((s) => ({ value: s.id, label: s.name_uz })));
const topicChoices = computed(() => topicOptions(props.topics, f.subject_id));
const difficultyChoices = [
    ...BANDS.map((b) => ({ value: b, label: `${BAND_LABELS[b]} (${b === 'easy' ? '1–2' : b === 'medium' ? '3' : '4–5'})` })),
    ...LEVELS.map((l) => ({ value: String(l), label: LEVEL_LABELS[l] })),
];
watch(() => f.subject_id, () => (f.topic_id = null));

// ---- counts + list (JSON) ----------------------------------------------------------------
const state = reactive({ loading: false, error: false, levels: {}, bands: {}, items: [], total: 0, page: 1, lastPage: 1 });
let timer = null;
let seq = 0;
const load = (page = 1) => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        if (!f.subject_id) return;
        const mine = ++seq;
        state.loading = true;
        state.error = false;
        try {
            const { data } = await axios.get(route('teacher.tests.questions.bank.search', props.test.id), {
                params: { subject_id: f.subject_id, topic_id: f.topic_id || undefined, difficulty: f.difficulty || undefined, q: f.q || undefined, page },
            });
            if (mine !== seq) return;
            Object.assign(state, { levels: data.levels, bands: data.bands, total: data.total, page: data.current_page, lastPage: data.last_page });
            state.items = page > 1 ? [...state.items, ...data.items] : data.items;
        } catch {
            if (mine === seq) state.error = true;
        } finally {
            if (mine === seq) state.loading = false;
        }
    }, page > 1 ? 0 : 300);
};
watch(() => [f.subject_id, f.topic_id, f.difficulty, f.q], () => load(1), { immediate: true });
onBeforeUnmount(() => clearTimeout(timer));

const totalAvailable = computed(() => LEVELS.reduce((s, l) => s + Number(state.levels[l] || 0), 0));

// ---- mode ----------------------------------------------------------------------------------
const mode = ref('distribution');
const tabs = computed(() => [
    { key: 'distribution', label: "Taqsimot bo'yicha" },
    { key: 'pick', label: "Qo'lda tanlash", count: picked.value.length || undefined },
]);

// hand-pick
const picked = ref([]);
watch(() => [f.subject_id, f.topic_id], () => (picked.value = []));
const isPicked = (id) => picked.value.includes(id);
const togglePick = (id, on) => {
    picked.value = on ? [...new Set([...picked.value, id])] : picked.value.filter((x) => x !== id);
};

// distribution
const dist = reactive({ easy: 0, medium: 0, hard: 0 });
const distTotal = computed(() => BANDS.reduce((s, b) => s + (Number(dist[b]) || 0), 0));
const distShort = computed(() => BANDS.filter((b) => Number(dist[b] || 0) > Number(state.bands[b] || 0)));
const distText = computed(() => distributionSummary(dist));

// ---- submit --------------------------------------------------------------------------------
const sending = ref(false);
const errors = computed(() => usePage().props.errors || {});
const canSubmit = computed(() => !!f.subject_id && !sending.value && (mode.value === 'pick'
    ? picked.value.length > 0 && picked.value.length <= props.maxPerAdd
    : distTotal.value > 0 && distTotal.value <= props.maxPerAdd));
const submit = () => {
    if (!canSubmit.value) return;
    const base = { subject_id: f.subject_id, topic_id: f.topic_id || null, mode: mode.value };
    const payload = mode.value === 'pick'
        ? { ...base, question_ids: picked.value }
        : { ...base, distribution: { easy: Number(dist.easy) || 0, medium: Number(dist.medium) || 0, hard: Number(dist.hard) || 0 } };
    router.post(route('teacher.tests.questions.bank.store', props.test.id), payload, {
        preserveScroll: true,
        onStart: () => (sending.value = true),
        onFinish: () => (sending.value = false),
    });
};
</script>

<template>
    <Head :title="`Bankdan qo'shish — ${test.title}`" />

    <TeacherLayout>
        <PageHeader title="Bankdan qo'shish" :description="test.title"
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: test.title, href: route('teacher.tests.questions.index', test.id) },
                { label: 'Bankdan qo\'shish' },
            ]" />

        <div class="grid max-w-5xl gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <Card title="Qayerdan" description="Faqat tasdiqlangan, variantli savollar olinadi. Testda bor savollar qayta qo'shilmaydi.">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Select v-model="f.subject_id" label="Fan" required :options="subjectOptions" :error="errors.subject_id" />
                        <Select v-model="f.topic_id" label="Mavzu" placeholder="Fanning hamma mavzulari" :options="topicChoices" :error="errors.topic_id"
                            hint="Bo'lim tanlansa, ichidagi mavzular ham kiradi" />
                    </div>
                </Card>

                <Card>
                    <Tabs v-model="mode" :tabs="tabs" label="Qo'shish usuli" class="-mt-1 mb-5" />

                    <!-- distribution -->
                    <div v-if="mode === 'distribution'" class="space-y-4">
                        <p class="text-13 text-fg-muted">Har darajadan nechta savol kerakligini yozing — tizim bankdan tasodifiy tanlaydi.</p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div v-for="b in BANDS" :key="b">
                                <Input v-model.number="dist[b]" type="number" min="0" :max="maxPerAdd" inputmode="numeric" :label="BAND_LABELS[b]"
                                    :hint="`Bankda: ${state.bands[b] ?? 0} ta`" :error="distShort.includes(b) ? `Bankda faqat ${state.bands[b] ?? 0} ta` : null" />
                            </div>
                        </div>
                        <Alert v-if="errors.distribution" variant="danger">{{ errors.distribution }}</Alert>
                        <Alert v-else-if="distShort.length" variant="warning">
                            Ba'zi darajada savol yetmaydi — borlari qo'shiladi, yetmagani xabarda ko'rsatiladi.
                        </Alert>
                    </div>

                    <!-- hand pick -->
                    <div v-else class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <Select v-model="f.difficulty" :options="difficultyChoices" placeholder="Har qanday qiyinlik" label="Qiyinlik" size="sm" />
                            <Input v-model="f.q" type="search" label="Qidirish" size="sm" placeholder="Shart matnidan so'z" />
                        </div>
                        <Alert v-if="errors.question_ids" variant="danger">{{ errors.question_ids }}</Alert>

                        <div v-if="state.loading && !state.items.length" class="flex justify-center py-8"><Spinner /></div>
                        <EmptyState v-else-if="!state.items.length" :icon="ArchiveBoxArrowDownIcon" size="sm" title="Mos savol yo'q"
                            description="Boshqa mavzu yoki qiyinlikni tanlang." />
                        <ol v-else class="space-y-3">
                            <li v-for="q in state.items" :key="q.id"
                                :class="['rounded-lg border bg-surface p-3 transition-colors sm:p-4', isPicked(q.id) ? 'border-brand-500 ring-1 ring-brand-500' : 'border-border']">
                                <div class="flex items-start gap-3">
                                    <Checkbox :model-value="isPicked(q.id)" :aria-label="`Savolni tanlash`" class="mt-0.5"
                                        @update:model-value="togglePick(q.id, $event)" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <Badge :variant="difficultyVariant(q.difficulty_level)" size="sm">{{ levelLabel(q.difficulty_level) }}</Badge>
                                            <Badge variant="neutral" size="sm">→ {{ bandLabel(q.band) }}</Badge>
                                            <Badge v-if="q.topic" variant="neutral" size="sm">{{ q.topic }}</Badge>
                                            <Badge v-if="q.question_type === 'multiple_choice'" variant="neutral" size="sm">Bir nechta javob</Badge>
                                        </div>
                                        <RichContent :html="q.question_text" size="sm" class="mt-2 text-fg" />
                                        <ul class="mt-2 grid gap-x-6 gap-y-1 text-13 sm:grid-cols-2">
                                            <li v-for="o in q.options" :key="o.label" :class="['flex items-baseline gap-1', o.is_correct ? 'text-success' : 'text-fg-muted']">
                                                <span class="font-semibold">{{ o.label }})</span>
                                                <RichContent :html="o.text" size="sm" class="inline-block" />
                                                <CheckIcon v-if="o.is_correct" class="h-3.5 w-3.5 shrink-0" aria-label="to'g'ri" />
                                            </li>
                                        </ul>
                                        <p v-if="q.source_ref" class="mt-1 text-xs text-fg-subtle">Manba: {{ q.source_ref }}</p>
                                    </div>
                                </div>
                            </li>
                        </ol>
                        <div v-if="state.page < state.lastPage" class="flex justify-center">
                            <Button variant="secondary" size="sm" :icon-left="ArrowPathIcon" :loading="state.loading" @click="load(state.page + 1)">
                                Yana ko'rsatish ({{ state.items.length }} / {{ state.total }})
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Bankda bor" :description="f.topic_id ? 'Tanlangan mavzu bo\'yicha' : 'Fanning hamma mavzulari'">
                    <div v-if="state.error" class="text-13 text-danger">Hisoblab bo'lmadi. Sahifani yangilang.</div>
                    <dl v-else class="space-y-1.5 text-13">
                        <div v-for="l in LEVELS" :key="l" class="flex items-center justify-between gap-2">
                            <dt class="text-fg-muted">{{ LEVEL_LABELS[l] }}</dt>
                            <dd class="font-semibold tabular-nums text-fg">{{ state.levels[l] ?? '—' }}</dd>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2 border-t border-border pt-2">
                            <dt class="text-fg-muted">Jami</dt>
                            <dd class="font-semibold tabular-nums text-fg">{{ totalAvailable }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-fg-subtle">
                        Kurs testida: oson = 1–2, o'rta = 3, qiyin = 4–5. <span v-if="inTest.length">Testda bankdan {{ inTest.length }} ta savol bor.</span>
                    </p>
                </Card>

                <div class="sticky top-4 rounded-lg border border-border bg-surface p-4 shadow-xs">
                    <p class="text-13 text-fg-muted">
                        <template v-if="mode === 'pick'">Tanlangan: <span class="font-semibold text-fg">{{ picked.length }} ta</span></template>
                        <template v-else>Taqsimot: <span class="font-semibold text-fg">{{ distText || '—' }}</span></template>
                    </p>
                    <Button class="mt-3 w-full" :icon-left="mode === 'pick' ? CheckIcon : SparklesIcon" :disabled="!canSubmit" :loading="sending" @click="submit">
                        {{ mode === 'pick' ? `${picked.length} ta savolni qo'shish` : `${distTotal} ta savolni tanlab qo'shish` }}
                    </Button>
                    <p class="mt-2 text-xs text-fg-subtle">Savollar testga nusxa bo'lib tushadi — keyin tahrirlash mumkin, bank o'zgarmaydi.</p>
                </div>
            </aside>
        </div>
    </TeacherLayout>
</template>
