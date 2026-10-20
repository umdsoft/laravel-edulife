<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import QuestionForm from '@/Components/Exam/Bank/QuestionForm.vue';
import { editorShortcut } from '@/Components/Teacher/format';

const props = defineProps({
    defaults: { type: Object, default: () => ({}) },
    subjects: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    vocabulary: { type: Object, default: () => ({}) },
});

const form = useForm({
    subject_id: props.defaults.subject_id ?? props.subjects[0]?.id ?? null,
    topic_id: props.defaults.topic_id ?? null,
    question_type: props.defaults.question_type || 'single_choice',
    question_text: '',
    difficulty_level: props.defaults.difficulty_level ?? null, // the teacher chooses; no silent default
    exam_formats: [],
    source_ref: '',
    options: [],
    answer: null,
    units_note: '',
    constants: [],
    solution_text: '',
    solution_video_url: '',
    intent: 'review',
    after: null,
});

const submit = (intent, after = null) => {
    if (form.processing) return;
    form.intent = intent;
    form.after = after;
    form.post(route('teacher.exam.questions.store'), { preserveScroll: true });
};

// Ctrl+S — tekshiruvga, Ctrl+Shift+Enter — saqlab, yana yozish
const onKeydown = (e) => {
    const action = editorShortcut(e);
    if (!action) return;
    e.preventDefault();
    submit('review', action === 'save-and-new' ? 'create' : null);
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head title="Yangi savol — Savollar banki" />

    <TeacherLayout>
        <PageHeader title="Yangi savol"
            :breadcrumbs="[{ label: 'Savollar banki', href: route('teacher.exam.questions.index') }, { label: 'Yangi savol' }]" />

        <form class="max-w-3xl" novalidate @submit.prevent="submit('review')">
            <QuestionForm :form="form" :subjects="subjects" :topics="topics" />

            <p class="mt-4 hidden text-xs text-fg-subtle sm:block">
                <kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Ctrl</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">S</kbd> — tekshiruvga yuborish,
                <kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Ctrl</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Shift</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Enter</kbd> — saqlab, keyingisini yozish.
            </p>

            <div class="sticky bottom-0 z-10 -mx-4 mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <Button variant="ghost" class="hidden sm:inline-flex" :href="route('teacher.exam.questions.index')">Bekor qilish</Button>
                <Button variant="secondary" :disabled="form.processing" @click="submit('draft')">Qoralama</Button>
                <Button variant="secondary" title="Ctrl+Shift+Enter" :disabled="form.processing" @click="submit('review', 'create')">
                    <span class="sm:hidden">Saqlab, yana</span><span class="hidden sm:inline">Saqlash va yana qo'shish</span>
                </Button>
                <Button variant="secondary" title="Ctrl+S" :disabled="form.processing" @click="submit('review')">Tekshiruvga</Button>
                <Button type="button" :loading="form.processing && form.intent === 'approve'" :disabled="form.processing" @click="submit('approve')">
                    Saqlash va tasdiqlash
                </Button>
            </div>
        </form>
    </TeacherLayout>
</template>
