<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    olympiad: Object,
    attempt: Object,
    sections: Array,
});

const currentSectionIndex = ref(0);
const expandedQuestion = ref(null);

const currentSection = ref(props.sections[0]);

const selectSection = (index) => {
    currentSectionIndex.value = index;
    currentSection.value = props.sections[index];
    expandedQuestion.value = null;
};

const getAnswerStatus = (answer) => {
    if (answer.is_correct === true) return { icon: '✅', class: 'text-green-600 bg-green-50 border-green-200' };
    if (answer.is_correct === false) return { icon: '❌', class: 'text-red-600 bg-red-50 border-red-200' };
    if (answer.is_partially_correct) return { icon: '⚠️', class: 'text-amber-600 bg-amber-50 border-amber-200' };
    return { icon: '⏳', class: 'text-gray-600 bg-gray-50 border-gray-200' };
};
</script>

<template>
    <StudentLayout>
        <Head :title="`Javoblarni ko'rish - ${olympiad.title}`" />
        
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link 
                        :href="route('student.olympiads.results', olympiad.slug)"
                        class="text-purple-600 hover:underline text-sm mb-2 inline-block">
                        ← Natijalarga qaytish
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📝 Javoblarni ko'rish</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ olympiad.title }}</p>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Sections Sidebar -->
                    <div class="lg:w-64 flex-shrink-0">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sticky top-24">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Bo'limlar</h3>
                            <div class="space-y-2">
                                <button 
                                    v-for="(section, index) in sections" 
                                    :key="section.section_id"
                                    @click="selectSection(index)"
                                    :class="[
                                        'w-full text-left px-4 py-3 rounded-xl transition-colors',
                                        currentSectionIndex === index
                                            ? 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200'
                                            : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
                                    ]">
                                    <div class="font-medium">{{ section.section_title }}</div>
                                    <div class="text-sm mt-1">
                                        <span class="text-green-600">{{ section.correct_count }}</span> /
                                        <span>{{ section.total_questions }}</span>
                                        <span class="ml-2 text-gray-500">({{ section.score_percent }}%)</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Questions -->
                    <div class="flex-1 space-y-4">
                        <div 
                            v-for="(answer, index) in currentSection.answers" 
                            :key="answer.id"
                            class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                            <!-- Question Header -->
                            <button 
                                @click="expandedQuestion = expandedQuestion === index ? null : index"
                                :class="['w-full p-6 flex items-start gap-4 text-left transition-colors', getAnswerStatus(answer).class]">
                                <span class="text-2xl">{{ getAnswerStatus(answer).icon }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium text-gray-900 dark:text-white">Savol {{ index + 1 }}</span>
                                        <span class="text-sm font-medium">
                                            {{ answer.score || 0 }} / {{ answer.max_score }} ball
                                        </span>
                                    </div>
                                    <div class="text-sm line-clamp-2" v-html="answer.question_text"></div>
                                </div>
                                <span class="text-gray-400 text-xl transition-transform" :class="{ 'rotate-180': expandedQuestion === index }">
                                    ▼
                                </span>
                            </button>

                            <!-- Expanded Content -->
                            <div v-if="expandedQuestion === index" class="p-6 border-t border-gray-200 dark:border-gray-700 space-y-6">
                                <!-- Full Question -->
                                <div>
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Savol:</h4>
                                    <div class="prose dark:prose-invert max-w-none" v-html="answer.question_html || answer.question_text"></div>
                                </div>

                                <!-- User Answer -->
                                <div>
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Sizning javobingiz:</h4>
                                    <div v-if="answer.selected_option !== null" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                        <span class="font-semibold">{{ String.fromCharCode(65 + answer.selected_option) }}:</span>
                                        {{ answer.options?.[answer.selected_option] }}
                                    </div>
                                    <div v-else-if="answer.text_answer" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl whitespace-pre-wrap">
                                        {{ answer.text_answer }}
                                    </div>
                                    <div v-else class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl text-gray-500 italic">
                                        Javob berilmagan
                                    </div>
                                </div>

                                <!-- Correct Answer (if review allowed) -->
                                <div v-if="answer.correct_answer !== undefined && olympiad.show_correct_answers">
                                    <h4 class="font-medium text-green-700 dark:text-green-300 mb-2">To'g'ri javob:</h4>
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                                        <span v-if="answer.correct_option !== null" class="font-semibold">
                                            {{ String.fromCharCode(65 + answer.correct_option) }}:
                                        </span>
                                        {{ answer.correct_answer_text || answer.correct_answer }}
                                    </div>
                                </div>

                                <!-- Explanation -->
                                <div v-if="answer.explanation">
                                    <h4 class="font-medium text-blue-700 dark:text-blue-300 mb-2">Tushuntirish:</h4>
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 prose dark:prose-invert max-w-none" v-html="answer.explanation"></div>
                                </div>

                                <!-- Grader Feedback -->
                                <div v-if="answer.feedback">
                                    <h4 class="font-medium text-amber-700 dark:text-amber-300 mb-2">Baholovchi izohi:</h4>
                                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                                        {{ answer.feedback }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!currentSection.answers?.length" class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl">
                            <span class="text-4xl mb-4 block">📋</span>
                            <p class="text-gray-500">Bu bo'limda savollar yo'q</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
