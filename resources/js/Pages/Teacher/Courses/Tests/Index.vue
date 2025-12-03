<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const props = defineProps({
    course: Object,
    tests: Object, // Grouped by type
    modules: Array,
});

const deleteTest = (test) => {
    if (confirm('Testni o\'chirishni tasdiqlaysizmi?')) {
        useForm({}).delete(route('teacher.courses.tests.destroy', [props.course.id, test.id]));
    }
};

const toggleStatus = (test) => {
    useForm({}).post(route('teacher.courses.tests.toggle-status', [props.course.id, test.id]));
};

const getTestLabel = (type) => {
    const labels = {
        'lesson_test': 'Dars testi',
        'module_test': 'Modul testi',
        'final_test': 'Yakuniy test'
    };
    return labels[type] || type;
};
</script>

<template>

    <Head title="Testlar" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ course.title }} - Testlar
                </h2>
                <div class="flex gap-3">
                    <Link :href="route('teacher.courses.index')" class="btn-secondary">
                    Ortga
                    </Link>
                    <Link :href="route('teacher.courses.tests.create', course.id)" class="btn-primary">
                    Yangi test
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Test Types Loop -->
                <div v-for="(group, type) in tests" :key="type"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ getTestLabel(type) }}lar</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nomi</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Bog'langan</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Savollar</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Holati</th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amallar</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="test in group" :key="test.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ test.title }}</div>
                                            <div class="text-xs text-gray-500">{{ test.description }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="test.testable">{{ test.testable.title }}</span>
                                            <span v-else>Kurs yakuniy testi</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ test.questions_count }} / {{ test.questions_count_required }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button @click="toggleStatus(test)"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="test.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                                {{ test.is_active ? 'Faol' : 'Nofaol' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('teacher.tests.questions.index', test.id)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Savollar
                                            </Link>
                                            <Link :href="route('teacher.courses.tests.edit', [course.id, test.id])"
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            Tahrirlash
                                            </Link>
                                            <button @click="deleteTest(test)" class="text-red-600 hover:text-red-900">
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-if="Object.keys(tests).length === 0"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Hozircha testlar yo'q. Yangi test yarating.
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
