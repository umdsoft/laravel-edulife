<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import SingleChoice from '@/Components/Teacher/QuestionTypes/SingleChoice.vue';
import MultipleChoice from '@/Components/Teacher/QuestionTypes/MultipleChoice.vue';
import TrueFalse from '@/Components/Teacher/QuestionTypes/TrueFalse.vue';
import FillBlank from '@/Components/Teacher/QuestionTypes/FillBlank.vue';
import Matching from '@/Components/Teacher/QuestionTypes/Matching.vue';
import Ordering from '@/Components/Teacher/QuestionTypes/Ordering.vue';

const props = defineProps({
    course: Object,
    test: Object,
    question: Object,
    questionTypes: Object,
});

const form = useForm({
    type: props.question.type,
    question_text: props.question.question_text,
    points: props.question.points,
    explanation: props.question.explanation,
    options: props.question.options,
});

const submit = () => {
    form.put(route('teacher.tests.questions.update', [props.test.id, props.question.id]));
};
</script>

<template>

    <Head title="Savolni tahrirlash" />

    <TeacherLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Savolni tahrirlash
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Type (Disabled) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Savol turi</label>
                                <select v-model="form.type" disabled
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                                    <option v-for="(label, key) in questionTypes" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Savol turini o'zgartirib bo'lmaydi.</p>
                            </div>

                            <!-- Question Text -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Savol matni</label>
                                <textarea v-model="form.question_text" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required></textarea>
                            </div>

                            <!-- Points -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ball</label>
                                <input v-model="form.points" type="number" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <!-- Options Component -->
                            <div class="border-t border-b border-gray-200 py-6 my-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Javoblar</h3>

                                <SingleChoice v-if="form.type === 'single_choice'" v-model="form.options" />
                                <MultipleChoice v-if="form.type === 'multiple_choice'" v-model="form.options" />
                                <TrueFalse v-if="form.type === 'true_false'" v-model="form.options" />
                                <FillBlank v-if="form.type === 'fill_blank'" v-model="form.options" />
                                <Matching v-if="form.type === 'matching'" v-model="form.options" />
                                <Ordering v-if="form.type === 'ordering'" v-model="form.options" />
                            </div>

                            <!-- Explanation -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Izoh (ixtiyoriy)</label>
                                <textarea v-model="form.explanation" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="To'g'ri javob uchun izoh..."></textarea>
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t">
                                <Link :href="route('teacher.tests.questions.index', test.id)" class="btn-secondary">
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
