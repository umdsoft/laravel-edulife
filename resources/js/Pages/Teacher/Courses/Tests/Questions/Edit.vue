<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { DocumentDuplicateIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import QuestionFormFields from '@/Components/Teacher/QuestionFormFields.vue';
import { editorShortcut } from '@/Components/Teacher/format';

const props = defineProps({
    course: Object,
    test: Object,
    question: Object,
    questionTypes: Object,
    calibration: { type: Object, default: null }, // observed difficulty (suggestion only)
});

const form = useForm({
    type: props.question.type,
    question_text: props.question.question_text,
    points: props.question.points,
    difficulty: props.question.difficulty || null,
    explanation: props.question.explanation,
    // option ids travel back so the server updates the rows in place (students' saved answers keep matching)
    options: props.question.options,
});

const submit = () => {
    if (form.processing) return;
    form.put(route('teacher.questions.update', props.question.id));
};

const duplicate = () => router.post(route('teacher.questions.duplicate', props.question.id));

const onKeydown = (e) => {
    if (!editorShortcut(e)) return;
    e.preventDefault();
    submit();
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head title="Savolni tahrirlash" />

    <TeacherLayout>
        <PageHeader title="Savolni tahrirlash" :description="`Test: ${test.title}`"
            :breadcrumbs="[
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Testlar', href: route('teacher.courses.tests.index', course.id) },
                { label: test.title, href: route('teacher.tests.questions.index', test.id) },
                { label: 'Tahrirlash' },
            ]">
            <template #actions>
                <Button variant="secondary" :icon-left="DocumentDuplicateIcon" @click="duplicate">Nusxa olish</Button>
            </template>
        </PageHeader>

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit">
            <QuestionFormFields :form="form" :question-types="questionTypes" type-locked :calibration="calibration" />

            <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <span v-if="form.isDirty" class="mr-auto text-13 text-fg-subtle">Saqlanmagan o'zgarishlar bor</span>
                <Button variant="secondary" :href="route('teacher.tests.questions.index', test.id)">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Saqlash</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
