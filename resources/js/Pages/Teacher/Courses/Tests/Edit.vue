<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { QueueListIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import TestSettingsFields from '@/Components/Teacher/TestSettingsFields.vue';

const props = defineProps({
    course: Object,
    test: Object,
    modules: Array,
    testTypes: Object,
});

const form = useForm({
    title: props.test.title,
    description: props.test.description,
    max_attempts: props.test.max_attempts,
    shuffle_questions: !!props.test.shuffle_questions,
    shuffle_options: !!props.test.shuffle_options,
    show_correct_answers: !!props.test.show_correct_answers,
});

const submit = () => {
    form.put(route('teacher.courses.tests.update', [props.course.id, props.test.id]), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
};
</script>

<template>
    <Head title="Testni tahrirlash" />

    <TeacherLayout>
        <PageHeader :title="test.title"
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Testlar', href: route('teacher.courses.tests.index', course.id) },
                { label: 'Sozlamalar' },
            ]">
            <template #meta>
                <Badge :variant="test.is_active ? 'success' : 'neutral'" dot>{{ test.is_active ? 'Faol' : 'Nofaol' }}</Badge>
            </template>
            <template #actions>
                <Button variant="secondary" :href="route('teacher.tests.questions.index', test.id)" :icon-left="QueueListIcon">Savollar</Button>
            </template>
        </PageHeader>

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit">
            <div class="space-y-6">
                <TestSettingsFields :form="form" />
            </div>

            <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <span v-if="form.isDirty" class="mr-auto text-13 text-fg-subtle">Saqlanmagan o'zgarishlar bor</span>
                <Button variant="secondary" :href="route('teacher.courses.tests.index', course.id)">Testlarga qaytish</Button>
                <Button type="submit" :loading="form.processing">Saqlash</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
