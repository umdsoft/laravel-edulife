<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

defineProps({
    course: Object,
    tests: Object,
    enrollment: Object
});

const getTestStatus = (test) => {
    if (test.is_passed) return { label: "O'tilgan", color: 'text-green-600 bg-green-100', icon: '✅' };
    if (test.has_active_attempt) return { label: "Jarayonda", color: 'text-blue-600 bg-blue-100', icon: '🔄' };
    if (!test.can_retake) return { label: "Urinishlar tugadi", color: 'text-red-600 bg-red-100', icon: '🔒' };
    return { label: "Mavjud", color: 'text-purple-600 bg-purple-100', icon: '🔓' };
};
</script>

<template>
    <StudentLayout>

        <Head title="Testlar" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <Link :href="route('student.learn.course', course.slug)"
                    class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">
                ← Kursga qaytish
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ course.title }} - Testlar
                </h1>
            </div>

            <div class="space-y-8">
                <!-- Lesson Tests -->
                <div v-if="tests.lesson_tests.length > 0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="p-1 bg-blue-100 text-blue-600 rounded">📝</span> Dars testlari
                    </h2>
                    <div class="grid gap-4">
                        <div v-for="test in tests.lesson_tests" :key="test.id"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all hover:shadow-md">
                            <div>
                                <h3 class="font-medium text-lg text-gray-900 dark:text-white">
                                    {{ test.title }}
                                </h3>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap gap-4">
                                    <span>⏱️ {{ test.time_limit }} daqiqa</span>
                                    <span>❓ {{ test.questions_count }} savol</span>
                                    <span>🎯 {{ test.pass_rate }}% o'tish</span>
                                    <span>🔄 {{ test.attempts_count }} / {{ test.max_attempts }} urinish</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div v-if="test.best_score > 0" class="text-right">
                                    <div class="text-sm font-bold"
                                        :class="test.is_passed ? 'text-green-600' : 'text-red-600'">
                                        {{ Math.round(test.best_score) }}%
                                    </div>
                                    <div class="text-xs text-gray-500">Eng yaxshi natija</div>
                                </div>

                                <Link :href="route('student.tests.start', test.id)"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                                    :class="test.has_active_attempt
                                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                                        : (test.can_retake ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-100 text-gray-500 cursor-not-allowed')">
                                {{ test.has_active_attempt ? 'Davom etish' : (test.is_passed ? 'Qayta topshirish' :
                                'Boshlash') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Module Tests -->
                <div v-if="tests.module_tests.length > 0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="p-1 bg-purple-100 text-purple-600 rounded">📋</span> Modul testlari
                    </h2>
                    <div class="grid gap-4">
                        <div v-for="test in tests.module_tests" :key="test.id"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-l-4 border-purple-500">
                            <div>
                                <h3 class="font-medium text-lg text-gray-900 dark:text-white">
                                    {{ test.title }}
                                </h3>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap gap-4">
                                    <span>⏱️ {{ test.time_limit }} daqiqa</span>
                                    <span>❓ {{ test.questions_count }} savol</span>
                                    <span>🎯 {{ test.pass_rate }}% o'tish</span>
                                </div>
                            </div>

                            <Link :href="route('student.tests.start', test.id)"
                                class="px-4 py-2 rounded-lg text-sm font-medium bg-purple-600 text-white hover:bg-purple-700">
                            Boshlash
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Final Test -->
                <div v-if="tests.final_test">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="p-1 bg-yellow-100 text-yellow-600 rounded">🏆</span> Yakuniy imtihon
                    </h2>
                    <div
                        class="bg-gradient-to-r from-purple-900 to-indigo-900 rounded-xl shadow-lg p-8 text-white relative overflow-hidden">
                        <div
                            class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl">
                        </div>

                        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div>
                                <h3 class="text-2xl font-bold mb-2">{{ tests.final_test.title }}</h3>
                                <p class="text-purple-200 mb-4">Kursni muvaffaqiyatli yakunlash va sertifikat olish
                                    uchun ushbu testdan o'ting.</p>
                                <div class="flex gap-6 text-sm font-medium text-purple-100">
                                    <span>⏱️ {{ tests.final_test.time_limit }} daqiqa</span>
                                    <span>❓ {{ tests.final_test.questions_count }} savol</span>
                                    <span>🎯 {{ tests.final_test.pass_rate }}% o'tish</span>
                                </div>
                            </div>

                            <Link :href="route('student.tests.start', tests.final_test.id)"
                                class="px-8 py-3 bg-white text-purple-900 rounded-lg font-bold hover:bg-gray-100 transition-colors shadow-lg">
                            Imtihonni Boshlash
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
