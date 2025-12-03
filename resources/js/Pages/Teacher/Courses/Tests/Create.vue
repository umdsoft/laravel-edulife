<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

const props = defineProps({
    course: Object,
    modules: Array,
    testTypes: Object,
});

const form = useForm({
    title: '',
    description: '',
    type: 'lesson_test',
    module_id: '',
    lesson_id: '',
    max_attempts: 3,
    shuffle_questions: true,
    shuffle_options: true,
    show_correct_answers: false,
});

const selectedModule = computed(() => {
    return props.modules.find(m => m.id === form.module_id);
});

const availableLessons = computed(() => {
    return selectedModule.value ? selectedModule.value.lessons : [];
});

const updateDefaults = () => {
    // Optional: Set defaults based on type
};

const submit = () => {
    form.post(route('teacher.courses.tests.store', props.course.id));
};
</script>

<template>

    <Head title="Yangi test" />

    <TeacherLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Yangi test yaratish
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Test turi</label>
                                <select v-model="form.type" @change="updateDefaults"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="lesson_test">Dars testi</option>
                                    <option value="module_test">Modul testi</option>
                                    <option value="final_test">Yakuniy test</option>
                                </select>
                            </div>

                            <!-- Context Selection -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-if="form.type !== 'final_test'">
                                    <label class="block text-sm font-medium text-gray-700">Modul</label>
                                    <select v-model="form.module_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Tanlang</option>
                                        <option v-for="module in modules" :key="module.id" :value="module.id">
                                            {{ module.title }}
                                        </option>
                                    </select>
                                </div>

                                <div v-if="form.type === 'lesson_test'">
                                    <label class="block text-sm font-medium text-gray-700">Dars</label>
                                    <select v-model="form.lesson_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :disabled="!form.module_id" required>
                                        <option value="">Tanlang</option>
                                        <option v-for="lesson in availableLessons" :key="lesson.id" :value="lesson.id">
                                            {{ lesson.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>

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
