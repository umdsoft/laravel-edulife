<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import LessonSidebar from '@/Components/Student/LessonSidebar.vue';
import QnaQuestion from '@/Components/Student/QnaQuestion.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    course: Object,
    questions: Object,
});

const showAskModal = ref(false);
const form = useForm({
    content: '',
    lesson_id: null,
});

const submitQuestion = () => {
    form.post(route('student.learn.qna.store', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAskModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>

    <Head :title="`${course.title} - Savol-javoblar`" />

    <StudentLayout>
        <div class="flex h-[calc(100vh-64px)] -m-6">
            <!-- Main Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <Link :href="route('student.learn.course', course.slug)"
                            class="text-sm text-purple-600 hover:underline mb-1 inline-block">
                        &larr; Kursga qaytish
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">Savol-javoblar</h1>
                    </div>
                    <button @click="showAskModal = true"
                        class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors font-medium">
                        Savol berish
                    </button>
                </div>

                <!-- Ask Modal -->
                <div v-if="showAskModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl w-full max-w-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Savol berish</h3>
                        <form @submit.prevent="submitQuestion">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Darsni tanlang
                                    (ixtiyoriy)</label>
                                <select v-model="form.lesson_id"
                                    class="w-full border-gray-200 rounded-xl focus:ring-purple-500 focus:border-purple-500">
                                    <option :value="null">Umumiy savol</option>
                                    <optgroup v-for="module in course.modules" :key="module.id" :label="module.title">
                                        <option v-for="lesson in module.lessons" :key="lesson.id" :value="lesson.id">
                                            {{ lesson.title }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Savolingiz</label>
                                <textarea v-model="form.content" rows="4"
                                    class="w-full border-gray-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                                    placeholder="Savolingizni batafsil yozing..."></textarea>
                                <p v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content
                                    }}</p>
                            </div>
                            <div class="flex justify-end gap-3">
                                <button type="button" @click="showAskModal = false"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl font-medium">
                                    Bekor qilish
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-medium disabled:opacity-50">
                                    Yuborish
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Questions List -->
                <div class="space-y-4">
                    <QnaQuestion v-for="question in questions.data" :key="question.id" :question="question" />

                    <div v-if="questions.data.length === 0"
                        class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Savollar yo'q</h3>
                        <p class="text-gray-500 mt-2">Birinchi bo'lib savol bering!</p>
                        <button @click="showAskModal = true"
                            class="mt-4 px-6 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors font-medium">
                            Savol berish
                        </button>
                    </div>

                    <Pagination :data="questions" />
                </div>
            </div>

            <!-- Sidebar (Desktop) -->
            <LessonSidebar :course="course" :progress="{}" :module-progress="{}" />
        </div>
    </StudentLayout>
</template>
