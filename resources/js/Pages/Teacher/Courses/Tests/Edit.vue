<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

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
    form.put(route('teacher.courses.tests.update', [props.course.id, props.test.id]));
};
</script>

<template>

    <Head title="Testni tahrirlash" />

    <TeacherLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Testni tahrirlash: {{ test.title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Basic Info -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nomi</label>
                                <input v-model="form.title" type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tavsif</label>
                                <textarea v-model="form.description" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>

                            <!-- Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Urinishlar soni</label>
                                    <input v-model="form.max_attempts" type="number" min="1"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input v-model="form.shuffle_questions" type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-2 block text-sm text-gray-900">Savollarni aralashtirish</label>
                                </div>
                                <div class="flex items-center">
                                    <input v-model="form.shuffle_options" type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-2 block text-sm text-gray-900">Javoblarni aralashtirish</label>
                                </div>
                                <div class="flex items-center">
                                    <input v-model="form.show_correct_answers" type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label class="ml-2 block text-sm text-gray-900">Test tugagach to'g'ri javoblarni
                                        ko'rsatish</label>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t">
                                <Link :href="route('teacher.courses.tests.index', course.id)" class="btn-secondary">
                                Bekor qilish
                                </Link>
                                <button type="submit" class="btn-primary" :disabled="form.processing">
                                    Saqlash
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
