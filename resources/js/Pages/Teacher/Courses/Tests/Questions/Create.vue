<script setup>
import { watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import QuestionFormFields from '@/Components/Teacher/QuestionFormFields.vue';

const props = defineProps({
    course: Object,
    test: Object,
    questionTypes: Object,
});

const form = useForm({
    type: 'single_choice',
    question_text: '',
    points: 1,
    explanation: '',
    options: [],
});

// Each type has its own option shape — start fresh so the new editor seeds its defaults
watch(() => form.type, () => {
    form.options = [];
    form.clearErrors('options');
});

const submit = () => {
    form.post(route('teacher.tests.questions.store', props.test.id));
};
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

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit">
            <QuestionFormFields :form="form" :question-types="questionTypes" />

            <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <Button variant="secondary" :href="route('teacher.tests.questions.index', test.id)">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Saqlash</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
