<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader } from '@/Components/UI';
import TestResultCard from '@/Components/Student/TestResultCard.vue';

defineProps({
    attempt: Object,
    test: Object,
    course: Object,
    passRate: Number
});
</script>

<template>
    <StudentLayout title="Natija">

        <Head :title="test.title + ' - Natija'" />

        <div class="mx-auto max-w-2xl">
            <PageHeader title="Test yakunlandi" :description="test.title"
                :back="course ? route('student.tests.index', course.id) : null" back-label="Testlar ro'yxati" />

            <TestResultCard :attempt="attempt" :pass-rate="passRate" />
        </div>
    </StudentLayout>
</template>
