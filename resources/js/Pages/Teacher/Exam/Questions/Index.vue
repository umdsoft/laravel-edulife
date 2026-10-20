<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    PlusIcon, MagnifyingGlassIcon, ArrowUpTrayIcon, ClipboardDocumentCheckIcon, FunnelIcon, QueueListIcon, XMarkIcon,
    FolderIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import { formatDate, formatNumber } from '@/Components/Teacher/format';
import {
    DIFFICULTIES, FORMATS, ORIGINS, STATUSES, TYPES, difficultyVariant, isChoiceType, originLabel, statusMeta, topicOptions, typeLabel,
} from '@/Components/Exam/Bank/bank';

const props = defineProps({
    questions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusCounts: { type: Object, default: () => ({}) },
    subjects: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    vocabulary: { type: Object, default: () => ({}) },
    reviewPending: { type: Number, default: 0 },
});

const f = reactive({
    subject: props.filters.subject || null,
    status: props.filters.status || null,
    topic: props.filters.topic || null,
    difficulty: props.filters.difficulty ? Number(props.filters.difficulty) : null,
    type: props.filters.type || null,
    origin: props.filters.origin || null,
    format: props.filters.format || null,
    source: props.filters.source || '',
    q: props.filters.q || '',
});
const showMore = ref(!!(f.type || f.origin || f.format || f.source));

const apply = () => {
    const query = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== null && v !== ''));
    router.get(route('teacher.exam.questions.index'), query, { preserveState: true, preserveScroll: true, replace: true });
};
const applyDebounced = debounce(apply, 350);
watch(() => [f.subject, f.status, f.topic, f.difficulty, f.type, f.origin, f.format], apply);
watch(() => [f.q, f.source], applyDebounced);

const subject = computed(() => props.subjects.find((s) => s.code === f.subject) || null);
watch(() => f.subject, () => {
    if (f.topic && !props.topics.some((t) => t.id === f.topic && t.subject_id === subject.value?.id)) f.topic = null;
});
const topicChoices = computed(() => (subject.value ? topicOptions(props.topics, subject.value.id, { includeInactive: true }) : []));

const subjectTabs = computed(() => [
    { key: 'all', label: 'Hammasi' },
    ...props.subjects.map((s) => ({ key: s.code, label: s.name_uz })),
]);
const subjectTab = computed({
    get: () => f.subject || 'all',
    set: (v) => (f.subject = v === 'all' ? null : v),
});

const totalCount = computed(() => Object.values(props.statusCounts || {}).reduce((s, n) => s + Number(n), 0));
const statusTabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: totalCount.value },
    ...Object.entries(STATUSES).map(([key, s]) => ({ key, label: s.label, count: Number(props.statusCounts?.[key] || 0) })),
]);
const statusTab = computed({
    get: () => f.status || 'all',
    set: (v) => (f.status = v === 'all' ? null : v),
});

const hasFilters = computed(() => !!(f.topic || f.difficulty || f.type || f.origin || f.format || f.source || f.q));
const reset = () => Object.assign(f, { topic: null, difficulty: null, type: null, origin: null, format: null, source: '', q: '' });

const typeOptions = Object.entries(TYPES).map(([value, label]) => ({ value, label }));
const originOptions = Object.entries(ORIGINS).map(([value, label]) => ({ value, label }));
const formatOptions = Object.entries(FORMATS).map(([value, label]) => ({ value, label }));

const answerKey = (q) => {
    if (isChoiceType(q.question_type)) {
        const marked = (q.options || []).filter((o) => o.is_correct === true).map((o) => o.label);
        return marked.length ? { ok: true, text: marked.join(', ') } : { ok: false, text: 'belgilanmagan' };
    }
    if (q.answer?.value !== null && q.answer?.value !== undefined) {
        return { ok: true, text: `${q.answer.value}${q.answer.unit ? ` ${q.answer.unit}` : ''}` };
    }
    return { ok: false, text: 'kiritilmagan' };
};
</script>

<template>
    <Head title="Savollar banki" />

    <TeacherLayout>
        <PageHeader title="Savollar banki" description="Imtihonlarga faqat tasdiqlangan savollar tushadi.">
            <template #actions>
                <Button variant="secondary" :icon-left="FolderIcon" :href="route('teacher.exam.topics.index', subject ? { subject: subject.code } : {})">Mavzular</Button>
                <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :href="route('teacher.exam.import.index')">Import</Button>
                <Button variant="secondary" :icon-left="ClipboardDocumentCheckIcon" :href="route('teacher.exam.review', subject ? { subject: subject.code } : {})">
                    Tekshiruv<span v-if="reviewPending" class="ml-1 tabular-nums text-fg-subtle">{{ formatNumber(reviewPending) }}</span>
                </Button>
                <Button :icon-left="PlusIcon" :href="route('teacher.exam.questions.create', subject ? { subject: subject.code } : {})">Yangi savol</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <Tabs v-model="subjectTab" :tabs="subjectTabs" variant="pills" label="Fan" />

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <Input v-model="f.q" placeholder="Shart yoki manba bo'yicha qidirish" :leading-icon="MagnifyingGlassIcon" class="lg:w-80"
                    aria-label="Qidirish" />
                <Select v-if="subject" v-model="f.topic" :options="topicChoices" placeholder="Barcha mavzular" class="lg:w-72" aria-label="Mavzu" />
                <Select v-model="f.difficulty" :options="DIFFICULTIES" placeholder="Har qanday qiyinlik" class="lg:w-48" aria-label="Qiyinlik" />
                <div class="flex gap-2">
                    <Button variant="ghost" :icon-left="FunnelIcon" :aria-expanded="showMore" @click="showMore = !showMore">Yana</Button>
                    <Button v-if="hasFilters" variant="ghost" :icon-left="XMarkIcon" @click="reset">Tozalash</Button>
                </div>
            </div>
            <div v-if="showMore" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Select v-model="f.type" :options="typeOptions" placeholder="Barcha turlar" aria-label="Savol turi" />
                <Select v-model="f.origin" :options="originOptions" placeholder="Har qanday manba turi" aria-label="Kelib chiqishi" />
                <Select v-model="f.format" :options="formatOptions" placeholder="Barcha formatlar" aria-label="Format" />
                <Input v-model="f.source" placeholder="Manba: to'plam nomi, bet…" aria-label="Manba" />
            </div>

            <Tabs v-model="statusTab" :tabs="statusTabs" label="Holat" />

            <ol v-if="questions.data.length" class="space-y-3">
                <li v-for="q in questions.data" :key="q.id" class="rounded-lg border border-border bg-surface p-4 shadow-xs">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <Badge :variant="statusMeta(q.review_status).variant" size="sm" dot>{{ statusMeta(q.review_status).label }}</Badge>
                        <Badge v-if="q.subject" :variant="q.subject.code === 'physics' ? 'physics' : 'math'" size="sm">{{ q.subject.name_uz }}</Badge>
                        <Badge v-if="q.difficulty_level" :variant="difficultyVariant(q.difficulty_level)" size="sm">Qiyinlik {{ q.difficulty_level }}</Badge>
                        <Badge variant="neutral" size="sm">{{ typeLabel(q.question_type) }}</Badge>
                        <Badge v-if="q.origin !== 'manual'" variant="info" size="sm">{{ originLabel(q.origin) }}</Badge>
                        <Badge v-if="q.is_demo" variant="neutral" size="sm">Namuna</Badge>
                        <Badge v-if="q.error_reports_count" variant="danger" size="sm">Xato haqida: {{ q.error_reports_count }}</Badge>
                        <span class="ml-auto text-xs tabular-nums text-fg-subtle">{{ formatDate(q.created_at) }}</span>
                    </div>

                    <Link :href="route('teacher.exam.questions.edit', q.id)" class="focus-ring mt-2 block rounded-sm">
                        <div class="line-clamp-4 text-fg">
                            <RichContent :html="q.question_text" size="sm" />
                        </div>
                    </Link>

                    <ul v-if="q.options.length" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-13">
                        <li v-for="o in q.options" :key="o.label" :class="['flex items-baseline gap-1', o.is_correct === true ? 'font-medium text-success' : 'text-fg-muted']">
                            <span class="font-semibold">{{ o.label }})</span>
                            <RichContent :html="o.text" size="sm" class="inline-block" />
                            <CheckIcon v-if="o.is_correct === true" class="h-4 w-4 shrink-0 self-center" aria-label="to'g'ri" />
                        </li>
                    </ul>

                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-13 text-fg-subtle">
                        <span v-if="q.topic">Mavzu: <span class="text-fg-muted">{{ q.topic }}</span></span>
                        <span v-else class="text-warning">Mavzu tanlanmagan</span>
                        <span>Javob: <span :class="answerKey(q).ok ? 'text-fg-muted' : 'text-warning'">{{ answerKey(q).text }}</span></span>
                        <span v-if="q.source_ref" class="truncate">Manba: {{ q.source_ref }}</span>
                        <span v-if="q.review_status === 'rejected' && q.reject_reason" class="text-danger">Sabab: {{ q.reject_reason }}</span>
                        <span class="ml-auto flex gap-2">
                            <Button v-if="q.review_status === 'in_review'" size="sm" variant="soft"
                                :href="route('teacher.exam.review', { question: q.id })">Tekshirish</Button>
                            <Button size="sm" variant="ghost" :href="route('teacher.exam.questions.edit', q.id)">Tahrirlash</Button>
                        </span>
                    </div>
                </li>
            </ol>

            <EmptyState v-else :icon="QueueListIcon" bordered :title="hasFilters || f.status ? 'Savol topilmadi' : 'Bank hali bo\'sh'"
                :description="hasFilters || f.status ? 'Filtrlarni o\'zgartirib ko\'ring.' : 'Savolni qo\'lda yozing yoki fayldan import qiling.'">
                <div class="flex gap-2">
                    <Button v-if="hasFilters" variant="secondary" @click="reset">Filtrlarni tozalash</Button>
                    <Button v-else variant="secondary" :icon-left="ArrowUpTrayIcon" :href="route('teacher.exam.import.index')">Import</Button>
                    <Button :icon-left="PlusIcon" :href="route('teacher.exam.questions.create')">Yangi savol</Button>
                </div>
            </EmptyState>

            <Pagination v-if="questions.data.length" :data="questions" />
        </div>
    </TeacherLayout>
</template>
