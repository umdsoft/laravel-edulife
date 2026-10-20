<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowUpTrayIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Alert from '@/Components/UI/Alert.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import Select from '@/Components/UI/Select.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import { OPTION_LABELS, typeLabel } from '@/Components/Exam/Bank/bank';
import { LEVEL_OPTIONS, levelLabel } from '@/Utils/difficulty';

const props = defineProps({
    token: { type: String, required: true },
    fileName: { type: String, default: '' },
    fileErrors: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
});

const importable = (r) => !r.errors.length && !r.duplicate;
const excluded = ref(new Set());
const toggleRow = (r, on) => {
    const s = new Set(excluded.value);
    on ? s.delete(r.row) : s.add(r.row);
    excluded.value = s;
};
const selected = computed(() => props.rows.filter((r) => importable(r) && !excluded.value.has(r.row)));
const selectedWithAnswers = computed(() => selected.value.filter((r) => r.has_file_answer).length);
const acceptAnswers = ref(false);

// difficulty: a row without a valid level in the file gets one here (never a silent default)
const levels = ref({}); // file row number -> 1..5
const setLevel = (r, level) => (levels.value = { ...levels.value, [r.row]: level });
const lacksLevel = (r) => !!r.needs_difficulty && !levels.value[r.row];
const pendingLevel = computed(() => props.rows.filter((r) => importable(r) && lacksLevel(r)));
const missingLevel = computed(() => selected.value.filter(lacksLevel));
const bulkLevel = ref(null);
const applyToAll = () => {
    if (!bulkLevel.value) return;
    const next = { ...levels.value };
    props.rows.filter((r) => importable(r) && r.needs_difficulty && !next[r.row]).forEach((r) => (next[r.row] = bulkLevel.value));
    levels.value = next;
};
const difficultyError = computed(() => usePage().props.errors?.difficulties || null);

const view = ref('all');
const tabs = computed(() => [
    { key: 'all', label: 'Hammasi', count: props.summary.total },
    { key: 'ok', label: 'Tayyor', count: props.summary.valid },
    { key: 'dup', label: 'Dublikat', count: props.summary.duplicates },
    { key: 'err', label: 'Xato', count: props.summary.errors },
    ...(props.summary.needs_difficulty ? [{ key: 'level', label: 'Qiyinliksiz', count: pendingLevel.value.length }] : []),
]);
const visible = computed(() => props.rows.filter((r) => ({
    all: true,
    ok: importable(r),
    dup: !r.errors.length && !!r.duplicate,
    err: r.errors.length > 0,
    level: importable(r) && !!r.needs_difficulty,
})[view.value]));

const sending = ref(false);
const confirm = () => router.post(route('teacher.exam.import.confirm'), {
    token: props.token,
    accept_answers: acceptAnswers.value,
    exclude_rows: [...excluded.value],
    difficulties: Object.fromEntries(selected.value.filter((r) => levels.value[r.row]).map((r) => [r.row, levels.value[r.row]])),
}, { preserveScroll: true, onStart: () => (sending.value = true), onFinish: () => (sending.value = false) });

const state = (r) => {
    if (r.errors.length) return { label: 'Xato', variant: 'danger' };
    if (r.duplicate === 'bank') return { label: 'Bankda bor', variant: 'warning' };
    if (r.duplicate === 'file') return { label: `Dublikat (${r.duplicate_of}-qator)`, variant: 'warning' };
    return { label: 'Tayyor', variant: 'success' };
};
</script>

<template>
    <Head title="Import — oldindan ko'rish" />

    <TeacherLayout>
        <PageHeader title="Oldindan ko'rish" :description="fileName"
            :breadcrumbs="[
                { label: 'Savollar banki', href: route('teacher.exam.questions.index') },
                { label: 'Import', href: route('teacher.exam.import.index') },
                { label: 'Oldindan ko\'rish' },
            ]">
            <template #meta>
                <span class="text-13 tabular-nums text-fg-muted">
                    {{ summary.total }} qator · {{ summary.valid }} tayyor · {{ summary.duplicates }} dublikat · {{ summary.errors }} xato
                </span>
            </template>
            <template #actions>
                <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :href="route('teacher.exam.import.index')">Boshqa fayl</Button>
            </template>
        </PageHeader>

        <div class="space-y-4">
            <Alert v-for="e in fileErrors" :key="e" variant="warning">{{ e }}</Alert>

            <Alert v-if="difficultyError" variant="danger">{{ difficultyError }}</Alert>
            <div v-if="summary.needs_difficulty" class="flex flex-col gap-3 rounded-lg border border-warning-100 bg-warning-50 p-4 sm:flex-row sm:items-end">
                <p class="flex-1 text-13 text-fg">
                    <span class="font-semibold">{{ summary.needs_difficulty }} ta qatorda qiyinlik yo'q yoki noto'g'ri.</span>
                    Har qatorda darajani tanlang yoki bittasini hammasiga qo'llang — qiyinliksiz savol bankka tushmaydi.
                </p>
                <div class="flex items-end gap-2">
                    <Select v-model="bulkLevel" :options="LEVEL_OPTIONS" placeholder="Darajani tanlang" size="sm" class="w-44" aria-label="Hammasi uchun qiyinlik" />
                    <Button size="sm" variant="secondary" :disabled="!bulkLevel || !pendingLevel.length" @click="applyToAll">Hammasiga qo'llash</Button>
                </div>
            </div>

            <Tabs v-model="view" :tabs="tabs" label="Qatorlar" />

            <ol class="space-y-3">
                <li v-for="r in visible" :key="r.row" :class="['rounded-lg border bg-surface p-4 shadow-xs', importable(r) ? 'border-border' : 'border-border opacity-80']">
                    <div class="flex flex-wrap items-center gap-2">
                        <Checkbox v-if="importable(r)" :model-value="!excluded.has(r.row)" :aria-label="`${r.row}-qatorni import qilish`"
                            @update:model-value="toggleRow(r, $event)" />
                        <span class="text-13 font-semibold tabular-nums text-fg-subtle">{{ r.row }}-qator</span>
                        <Badge :variant="state(r).variant" size="sm" dot>{{ state(r).label }}</Badge>
                        <Badge v-if="r.subject" :variant="r.subject === 'Fizika' ? 'physics' : 'math'" size="sm">{{ r.subject }}</Badge>
                        <Badge v-if="r.topic" variant="neutral" size="sm">{{ r.topic }}</Badge>
                        <Badge v-if="r.type" variant="neutral" size="sm">{{ typeLabel(r.type) }}</Badge>
                        <Badge v-if="r.difficulty" variant="neutral" size="sm">Qiyinlik {{ levelLabel(r.difficulty) }}</Badge>
                        <Select v-else-if="importable(r) && r.needs_difficulty" :model-value="levels[r.row] ?? null" :options="LEVEL_OPTIONS"
                            placeholder="Qiyinlikni tanlang" size="sm" class="w-44"
                            :aria-label="`${r.row}-qator qiyinligi`" @update:model-value="setLevel(r, $event)" />
                        <span v-if="r.answer_label" class="ml-auto text-13 text-fg-muted">Fayldagi javob: <span class="font-medium text-fg">{{ r.answer_label }}</span></span>
                    </div>
                    <div v-if="r.question_text" class="mt-2 text-fg"><RichContent :html="r.question_text" size="sm" /></div>
                    <ul v-if="r.options.length" class="mt-2 grid gap-x-6 gap-y-1 text-13 text-fg-muted sm:grid-cols-2">
                        <li v-for="(o, i) in r.options" :key="i" class="flex items-baseline gap-1">
                            <span class="font-semibold">{{ OPTION_LABELS[i] }})</span><RichContent :html="o" size="sm" class="inline-block" />
                        </li>
                    </ul>
                    <p v-if="r.source_ref" class="mt-2 text-xs text-fg-subtle">Manba: {{ r.source_ref }}</p>
                    <ul v-if="r.errors.length || r.warnings.length" class="mt-2 space-y-0.5 text-13">
                        <li v-for="e in r.errors" :key="e" class="text-danger">{{ e }}</li>
                        <li v-for="w in r.warnings" :key="w" class="flex items-center gap-1 text-warning">
                            <ExclamationTriangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />{{ w }}
                        </li>
                    </ul>
                </li>
            </ol>
            <p v-if="!visible.length" class="py-6 text-center text-13 text-fg-subtle">Bu bo'limda qator yo'q.</p>
        </div>

        <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex flex-col gap-3 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:flex-row sm:items-center sm:px-0">
            <Checkbox v-if="selectedWithAnswers" v-model="acceptAnswers" class="flex-1"
                :label="`Fayldagi to'g'ri javoblarni qabul qilaman (${selectedWithAnswers} ta savol)`"
                description="Belgilanmasa, javob tekshiruv navbatida qo'lda belgilanadi. Har holda savollar tasdiqlanmagan holda tushadi." />
            <span v-else class="flex-1 text-13 text-fg-subtle">Javoblar tekshiruv navbatida belgilanadi.</span>
            <span v-if="missingLevel.length" class="text-13 font-medium text-warning">{{ missingLevel.length }} ta qatorda qiyinlik tanlanmagan</span>
            <Button :disabled="!selected.length || missingLevel.length > 0" :loading="sending" @click="confirm">{{ selected.length }} ta savolni import qilish</Button>
        </div>
    </TeacherLayout>
</template>
