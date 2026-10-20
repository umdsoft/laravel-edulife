<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import {
    PlusIcon, PencilSquareIcon, TrashIcon, EllipsisVerticalIcon, Cog6ToothIcon, QuestionMarkCircleIcon,
    ArrowUpIcon, ArrowDownIcon, InformationCircleIcon, DocumentDuplicateIcon, ArchiveBoxArrowDownIcon,
} from '@heroicons/vue/24/outline';
import { Bars2Icon, CheckIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import MathText from '@/Components/MathText.vue';
import { mediaPlaceholderText } from '@/Utils/richText';
import { QUESTION_TYPES, questionDifficulty } from '@/Components/Teacher/format';
import { bandLabel, calibrationText } from '@/Utils/difficulty';

const props = defineProps({
    course: Object,
    test: Object,
    questions: Array,
    calibration: { type: Object, default: () => ({}) }, // question id => observed difficulty (battle p̂), suggestion only
});

const questionsList = ref([...(props.questions || [])]);
// Fresh props after delete / reorder → refresh the draggable copy
watch(() => props.questions, (list) => {
    questionsList.value = [...(list || [])];
});

const totalPoints = computed(() => questionsList.value.reduce((s, q) => s + (Number(q.points) || 0), 0));

const toDelete = ref(null);
const deleting = ref(false);
const deleteQuestion = () => {
    router.delete(route('teacher.questions.destroy', toDelete.value.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
};

const onReorder = () => {
    useForm({
        questions: questionsList.value.map((q, index) => ({
            id: q.id,
            sort_order: index + 1,
        })),
    }).post(route('teacher.tests.questions.reorder', props.test.id), {
        preserveScroll: true,
    });
};

const move = (from, dir) => {
    const to = from + dir;
    if (to < 0 || to >= questionsList.value.length) return;
    const next = [...questionsList.value];
    const [item] = next.splice(from, 1);
    next.splice(to, 0, item);
    questionsList.value = next;
    onReorder();
};

const getTypeLabel = (type) => QUESTION_TYPES[type] || type;

// Copy right after the original and open it — for a series of similar problems
const duplicate = (question) => router.post(route('teacher.questions.duplicate', question.id));

const difficultyCounts = computed(() => questionsList.value.reduce((acc, q) => {
    const key = questionDifficulty(q.difficulty).value;
    acc[key] = (acc[key] || 0) + 1;
    return acc;
}, {}));

// Short, readable answer key under each question
const answerSummary = (q) => {
    const opts = q.options || [];
    if (!opts.length) return [];
    switch (q.type) {
        case 'single_choice':
        case 'multiple_choice':
        case 'true_false':
            return opts.map((o) => ({ text: o.option_text, correct: !!o.is_correct }));
        case 'fill_blank':
            return opts.map((o) => ({ text: o.option_text, correct: true }));
        case 'matching':
            return opts.map((o) => ({ text: `${o.option_text} → ${o.match_text}`, correct: false }));
        case 'ordering':
            return [...opts].sort((a, b) => (a.correct_position || 0) - (b.correct_position || 0))
                .map((o, i) => ({ text: `${i + 1}. ${o.option_text}`, correct: false }));
        default:
            return [];
    }
};
</script>

<template>
    <Head :title="`${test.title} — Savollar`" />

    <TeacherLayout>
        <PageHeader :title="test.title"
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Testlar', href: route('teacher.courses.tests.index', course.id) },
                { label: 'Savollar' },
            ]">
            <template #meta>
                <Badge :variant="test.is_active ? 'success' : 'neutral'" dot>{{ test.is_active ? 'Faol' : 'Nofaol' }}</Badge>
                <span class="text-13 tabular-nums text-fg-muted">{{ questionsList.length }} ta savol · {{ totalPoints }} ball</span>
                <span v-if="questionsList.length" class="text-13 tabular-nums text-fg-subtle">
                    Oson {{ difficultyCounts.easy || 0 }} · O'rta {{ difficultyCounts.medium || 0 }} · Qiyin {{ difficultyCounts.hard || 0 }}
                </span>
            </template>
            <template #actions>
                <Button variant="secondary" :href="route('teacher.courses.tests.edit', [course.id, test.id])" :icon-left="Cog6ToothIcon">Sozlamalar</Button>
                <Button variant="secondary" :href="route('teacher.tests.questions.bank', test.id)" :icon-left="ArchiveBoxArrowDownIcon">Bankdan qo'shish</Button>
                <Button :href="route('teacher.tests.questions.create', test.id)" :icon-left="PlusIcon">Yangi savol</Button>
            </template>
        </PageHeader>

        <div v-if="questionsList.length" class="max-w-3xl space-y-4">
            <p class="flex items-center gap-2 text-13 text-fg-subtle">
                <InformationCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                Tartibni tutqich bilan sudrab o'zgartiring — avtomatik saqlanadi.
            </p>

            <draggable v-model="questionsList" item-key="id" handle=".drag-handle" :animation="150" ghost-class="opacity-40"
                tag="ol" class="space-y-3" @end="onReorder">
                <template #item="{ element: question, index }">
                    <li class="flex items-start gap-2 rounded-lg border border-border bg-surface p-3 shadow-xs sm:gap-3 sm:p-4">
                        <span class="drag-handle mt-0.5 flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded-sm text-fg-subtle hover:bg-surface-2 hover:text-fg active:cursor-grabbing"
                            title="Sudrab tartiblang" aria-hidden="true">
                            <Bars2Icon class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-13 font-semibold tabular-nums text-fg-subtle">{{ index + 1 }}.</span>
                                <Badge variant="brand" size="sm">{{ getTypeLabel(question.type) }}</Badge>
                                <Badge :variant="questionDifficulty(question.difficulty).variant" size="sm">{{ questionDifficulty(question.difficulty).label }}</Badge>
                                <span class="text-13 tabular-nums text-fg-subtle">{{ question.points }} ball</span>
                                <Badge v-if="question.bank_question_id" variant="neutral" size="sm" title="Imtihon savollar bankidan nusxa">Bankdan</Badge>
                                <Badge v-if="calibration[question.id]" :variant="calibration[question.id].mismatch ? 'warning' : 'neutral'" size="sm"
                                    :title="calibration[question.id].mismatch ? `Battle'da ${bandLabel(calibration[question.id].band).toLowerCase()} deb hisoblanadi` : null">
                                    {{ calibrationText(calibration[question.id]) }}
                                </Badge>
                            </div>
                            <Link :href="route('teacher.questions.edit', question.id)" class="focus-ring mt-1.5 block rounded-sm">
                                <MathText :text="question.question_text" tag="div" class="text-sm font-medium leading-6 text-fg hover:text-brand" />
                            </Link>
                            <ul v-if="answerSummary(question).length" class="mt-2 flex flex-wrap gap-1.5">
                                <li v-for="(a, i) in answerSummary(question)" :key="i" :class="[
                                    'inline-flex max-w-full items-center gap-1 rounded-sm px-2 py-0.5 text-xs',
                                    a.correct ? 'bg-success-50 text-success ring-1 ring-inset ring-success-100' : 'bg-surface-2 text-fg-muted',
                                ]">
                                    <CheckIcon v-if="a.correct" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                    <MathText :text="mediaPlaceholderText(a.text)" class="truncate" />
                                    <span v-if="a.correct" class="sr-only">(to'g'ri)</span>
                                </li>
                            </ul>
                        </div>

                        <div class="flex shrink-0 items-center">
                            <IconButton :href="route('teacher.questions.edit', question.id)" :icon="PencilSquareIcon" label="Tahrirlash" size="sm"
                                class="hidden sm:inline-flex" />
                            <Dropdown align="right">
                                <template #trigger>
                                    <IconButton :icon="EllipsisVerticalIcon" :label="`${index + 1}-savol — amallar`" size="sm" />
                                </template>
                                <DropdownItem :href="route('teacher.questions.edit', question.id)" :icon="PencilSquareIcon">Tahrirlash</DropdownItem>
                                <DropdownItem :icon="DocumentDuplicateIcon" @click="duplicate(question)">Nusxa olish</DropdownItem>
                                <template v-if="questionsList.length > 1">
                                    <DropdownItem :icon="ArrowUpIcon" :disabled="index === 0" @click="move(index, -1)">Yuqoriga surish</DropdownItem>
                                    <DropdownItem :icon="ArrowDownIcon" :disabled="index === questionsList.length - 1" @click="move(index, 1)">Pastga surish</DropdownItem>
                                </template>
                                <DropdownItem divider />
                                <DropdownItem danger :icon="TrashIcon" @click="toDelete = question">O'chirish</DropdownItem>
                            </Dropdown>
                        </div>
                    </li>
                </template>
            </draggable>

            <Link :href="route('teacher.tests.questions.create', test.id)"
                class="focus-ring flex h-12 w-full items-center justify-center gap-2 rounded-lg border border-dashed border-border-strong text-sm font-medium text-fg-muted transition-colors hover:border-brand-500 hover:bg-surface hover:text-brand">
                <PlusIcon class="h-5 w-5" aria-hidden="true" />
                Savol qo'shish
            </Link>
        </div>

        <Card v-else class="max-w-3xl">
            <EmptyState :icon="QuestionMarkCircleIcon" size="lg" title="Hali savol yo'q"
                description="Birinchi savolni qo'shing. Test faqat savollar bilan o'quvchilarga ochiladi.">
                <div class="flex flex-wrap justify-center gap-2">
                    <Button :href="route('teacher.tests.questions.create', test.id)" :icon-left="PlusIcon">Savol qo'shish</Button>
                    <Button variant="secondary" :href="route('teacher.tests.questions.bank', test.id)" :icon-left="ArchiveBoxArrowDownIcon">Bankdan qo'shish</Button>
                </div>
            </EmptyState>
        </Card>

        <ConfirmDialog :show="!!toDelete" title="Savolni o'chirasizmi?" description="Savol va uning javob variantlari o'chiriladi."
            :loading="deleting" @confirm="deleteQuestion" @close="toDelete = null" />
    </TeacherLayout>
</template>
