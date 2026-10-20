<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import QuestionFormFields from '@/Components/Teacher/QuestionFormFields.vue';
import { QUESTION_TYPES, editorShortcut } from '@/Components/Teacher/format';

const props = defineProps({
    course: Object,
    test: Object,
    questionTypes: Object,
});

// "Saqlash va yana qo'shish" comes back with ?type=…&difficulty=… — keep them for the next question
const query = new URLSearchParams(String(usePage().url || '').split('?')[1] || '');
const initialType = Object.keys(QUESTION_TYPES).includes(query.get('type')) ? query.get('type') : 'single_choice';
const initialDifficulty = ['easy', 'medium', 'hard'].includes(query.get('difficulty')) ? query.get('difficulty') : 'medium';

const form = useForm({
    type: initialType,
    question_text: '',
    points: 1,
    difficulty: initialDifficulty,
    explanation: '',
    options: [],
    after: null,
});

// Each type has its own option shape — start fresh so the new editor seeds its defaults
watch(() => form.type, () => {
    form.options = [];
    form.clearErrors('options');
});

const submit = (after = null) => {
    if (form.processing) return;
    form.after = after;
    form.post(route('teacher.tests.questions.store', props.test.id));
};

// Ctrl+S / Ctrl+Enter — saqlash, Ctrl+Shift+Enter — saqlash va yangisini yozish
const onKeydown = (e) => {
    const action = editorShortcut(e);
    if (!action) return;
    e.preventDefault();
    submit(action === 'save-and-new' ? 'create' : null);
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head title="Yangi savol" />

    <TeacherLayout>
        <PageHeader title="Yangi savol" :description="`Test: ${test.title}`"
            :breadcrumbs="[
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Testlar', href: route('teacher.courses.tests.index', course.id) },
                { label: test.title, href: route('teacher.tests.questions.index', test.id) },
                { label: 'Yangi savol' },
            ]" />

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit()">
            <QuestionFormFields :form="form" :question-types="questionTypes" />

            <p class="mt-4 hidden text-xs text-fg-subtle sm:block">
                Tezkor tugmalar: <kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Ctrl</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">S</kbd> — saqlash,
                <kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Ctrl</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Shift</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Enter</kbd> — saqlab, keyingi savolni yozish.
            </p>

            <div class="sticky bottom-0 z-10 -mx-4 mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <Button variant="secondary" class="hidden sm:inline-flex" :href="route('teacher.tests.questions.index', test.id)">Bekor qilish</Button>
                <Button variant="secondary" title="Ctrl+Shift+Enter" :loading="form.processing && form.after === 'create'" :disabled="form.processing"
                    @click="submit('create')">
                    <span class="sm:hidden">Saqlab, yana</span><span class="hidden sm:inline">Saqlash va yana qo'shish</span>
                </Button>
                <Button type="submit" title="Ctrl+S" :loading="form.processing && form.after !== 'create'" :disabled="form.processing">Saqlash</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
