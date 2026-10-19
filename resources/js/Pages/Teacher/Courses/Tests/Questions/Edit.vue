<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import QuestionFormFields from '@/Components/Teacher/QuestionFormFields.vue';

const props = defineProps({
    course: Object,
    test: Object,
    question: Object,
    questionTypes: Object,
});

const form = useForm({
    type: props.question.type,
    question_text: props.question.question_text,
    points: props.question.points,
    explanation: props.question.explanation,
    options: props.question.options,
});

const submit = () => {
    form.put(route('teacher.questions.update', props.question.id));
};
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
            ]" />

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit">
            <QuestionFormFields :form="form" :question-types="questionTypes" type-locked />

            <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <Button variant="secondary" :href="route('teacher.tests.questions.index', test.id)">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Saqlash</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
