<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import QuestionReview from '@/Components/Student/QuestionReview.vue';

defineProps({
    attempt: Object,
    test: Object,
    course: Object,
    answers: Array
});
</script>

<template>
    <StudentLayout>

        <Head :title="test.title + ' - Tahlil'" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <Link :href="route('student.tests.result', attempt.id)"
                        class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">
                    ← Natijaga qaytish
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Javoblar tahlili
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ test.title }}
                    </p>
                </div>

                <div class="text-right">
                    <div class="text-2xl font-bold" :class="attempt.is_passed ? 'text-green-600' : 'text-red-600'">
                        {{ Math.round(attempt.score) }}%
                    </div>
                    <div class="text-sm text-gray-500">Umumiy natija</div>
                </div>
            </div>

            <div class="space-y-6">
                <QuestionReview v-for="(answer, index) in answers" :key="answer.id" :answer="answer" :index="index" />
            </div>

            <div class="mt-8 text-center">
                <Link :href="route('student.tests.index', course.id)"
                    class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                Testlar ro'yxatiga qaytish
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>
