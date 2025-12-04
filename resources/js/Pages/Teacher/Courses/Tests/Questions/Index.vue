<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import draggable from 'vuedraggable';
import { useSanitize } from '@/Composables/useSanitize.js';

const { sanitize } = useSanitize();

const props = defineProps({
    course: Object,
    test: Object,
    questions: Array,
});

const questionsList = ref(props.questions);

const deleteQuestion = (question) => {
    if (confirm('Savolni o\'chirishni tasdiqlaysizmi?')) {
        useForm({}).delete(route('teacher.tests.questions.destroy', [props.test.id, question.id]));
    }
};

const onReorder = () => {
    useForm({
        questions: questionsList.value.map((q, index) => ({
            id: q.id,
            sort_order: index + 1,
        })),
    }).post(route('teacher.tests.questions.reorder', props.test.id), {
        preserveScroll: true,
    });
};

const getTypeLabel = (type) => {
    const labels = {
        'single_choice': 'Bitta to\'g\'ri',
        'multiple_choice': 'Ko\'p to\'g\'ri',
        'true_false': 'Rost/Yolg\'on',
        'fill_blank': 'Bo\'sh joy',
        'matching': 'Moslashtirish',
        'ordering': 'Tartiblash',
    };
    return labels[type] || type;
};
</script>

<template>

    <Head title="Savollar" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ test.title }} - Savollar
                </h2>
                <div class="flex gap-3">
                    <Link :href="route('teacher.courses.tests.index', course.id)" class="btn-secondary">
                    Ortga
                    </Link>
                    <Link :href="route('teacher.tests.questions.create', test.id)" class="btn-primary">
                    Yangi savol
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="questions.length === 0"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Hozircha savollar yo'q. Yangi savol qo'shing.
                </div>

                <draggable v-model="questionsList" item-key="id" handle=".drag-handle" @end="onReorder"
                    class="space-y-4">
                    <template #item="{ element: question, index }">
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex items-start gap-4">
                            <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 8h16M4 16h16" />
                                </svg>
                            </div>

                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mb-2">
                                            {{ getTypeLabel(question.type) }}
                                        </span>
                                        <div class="text-gray-900 font-medium" v-html="sanitize(question.question_text)"></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Link :href="route('teacher.tests.questions.edit', [test.id, question.id])"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                        Tahrirlash
                                        </Link>
                                        <button @click="deleteQuestion(question)"
                                            class="text-red-600 hover:text-red-800 text-sm">
                                            O'chirish
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-2 text-sm text-gray-500">
                                    {{ question.points }} ball
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>
    </TeacherLayout>
</template>
