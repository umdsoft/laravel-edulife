<script setup>
import { Head, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import BulkLessonForm from '@/Components/Teacher/BulkLessonForm.vue';

const props = defineProps({
    course: Object,
    module: Object,
});

const backToCurriculum = () => router.visit(route('teacher.courses.modules.index', props.course.id));
</script>

<template>
    <TeacherLayout>
        <Head :title="`${course.title} — Bir nechta dars qo'shish`" />

        <PageHeader title="Bir nechta dars qo'shish"
            :description="`«${module.title}» moduliga bir vaqtda bir nechta dars yarating. Video va fayllarni keyin har bir darsga alohida yuklaysiz. Nomdan keyin Enter — keyingi dars.`"
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Darslar rejasi', href: route('teacher.courses.modules.index', course.id) },
                { label: module.title },
            ]" />

        <div class="max-w-[40rem]">
            <BulkLessonForm :course="course" :module="module" @cancel="backToCurriculum" />
        </div>
    </TeacherLayout>
</template>
