<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import TestResultCard from '@/Components/Student/TestResultCard.vue';

defineProps({
    attempt: Object,
    test: Object,
    course: Object,
    passRate: Number
});
</script>

<template>
    <StudentLayout>

        <Head :title="test.title + ' - Natija'" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    Test Yakunlandi
                </h1>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ test.title }}
                </p>
            </div>

            <TestResultCard :attempt="attempt" :pass-rate="passRate" />
        </div>
    </StudentLayout>
</template>
