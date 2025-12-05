<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    olympiad: Object,
    answer: Object,
    question: Object,
    user: Object,
    nextAnswerId: String,
    prevAnswerId: String,
    queuePosition: Object,
});

const form = useForm({
    score: props.answer.score || 0,
    feedback: props.answer.feedback || '',
});

const submit = (goNext = true) => {
    form.post(route('admin.olympiads.grading.submit', { 
        olympiad: props.olympiad.id, 
        answer: props.answer.id 
    }), {
        onSuccess: () => {
            if (goNext && props.nextAnswerId) {
                router.visit(route('admin.olympiads.grading.grade', {
                    olympiad: props.olympiad.id,
                    answer: props.nextAnswerId
                }));
            }
        }
    });
};

const quickScore = (score) => {
    form.score = score;
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Baholash - ${user.name}`" />
        
        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <Link 
                            :href="route('admin.olympiads.grading.index', olympiad.id)" 
                            class="text-purple-600 hover:underline text-sm mb-2 inline-block">
                            ← Baholash paneliga qaytish
                        </Link>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ question.section?.title }}</h1>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ queuePosition.current }} / {{ queuePosition.total }}
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Question & Answer -->
                    <div class="space-y-6">
                        <!-- Question -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-lg">❓</span> Savol
                                <span class="ml-auto text-sm text-gray-500">{{ question.max_points }} ball</span>
                            </h3>
                            <div class="prose dark:prose-invert max-w-none" v-html="question.question_html || question.question_text"></div>
                            
                            <!-- Rubric if available -->
                            <div v-if="question.grading_rubric" class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                                <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-2">📋 Baholash mezonlari</h4>
                                <div class="text-sm text-amber-700 dark:text-amber-300" v-html="question.grading_rubric"></div>
                            </div>
                        </div>

                        <!-- User Answer -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <span class="text-lg">✍️</span> Foydalanuvchi javobi
                            </h3>
                            
                            <!-- Text answer -->
                            <div v-if="answer.text_answer" class="prose dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-700 p-4 rounded-xl">
                                {{ answer.text_answer }}
                            </div>
                            
                            <!-- Code answer -->
                            <div v-if="answer.code_answer" class="bg-gray-900 rounded-xl p-4 overflow-x-auto">
                                <pre class="text-green-400 text-sm font-mono">{{ answer.code_answer }}</pre>
                            </div>
                            
                            <!-- File attachment -->
                            <div v-if="answer.file_path" class="mt-4">
                                <a 
                                    :href="answer.file_url" 
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100">
                                    📎 Yuklangan faylni ko'rish
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Grading Panel -->
                    <div class="space-y-6">
                        <!-- User Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">👤 Ishtirokchi</h3>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-xl font-bold text-purple-600">
                                    {{ user.name?.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Grading Form -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">📝 Baholash</h3>
                            
                            <form @submit.prevent="submit(true)" class="space-y-6">
                                <!-- Score -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Ball (0 - {{ question.max_points }})
                                    </label>
                                    <div class="flex items-center gap-4">
                                        <input 
                                            v-model.number="form.score"
                                            type="number"
                                            :min="0"
                                            :max="question.max_points"
                                            step="0.5"
                                            class="w-32 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-center text-2xl font-bold">
                                        <span class="text-gray-500">/ {{ question.max_points }}</span>
                                    </div>
                                    
                                    <!-- Quick score buttons -->
                                    <div class="flex gap-2 mt-3">
                                        <button 
                                            type="button"
                                            @click="quickScore(0)"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-sm hover:bg-red-200">
                                            0
                                        </button>
                                        <button 
                                            type="button"
                                            @click="quickScore(Math.floor(question.max_points * 0.25))"
                                            class="px-3 py-1 bg-orange-100 text-orange-700 rounded-lg text-sm hover:bg-orange-200">
                                            25%
                                        </button>
                                        <button 
                                            type="button"
                                            @click="quickScore(Math.floor(question.max_points * 0.5))"
                                            class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg text-sm hover:bg-yellow-200">
                                            50%
                                        </button>
                                        <button 
                                            type="button"
                                            @click="quickScore(Math.floor(question.max_points * 0.75))"
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200">
                                            75%
                                        </button>
                                        <button 
                                            type="button"
                                            @click="quickScore(question.max_points)"
                                            class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-sm hover:bg-green-200">
                                            100%
                                        </button>
                                    </div>
                                </div>

                                <!-- Feedback -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Izoh (ixtiyoriy)
                                    </label>
                                    <textarea 
                                        v-model="form.feedback"
                                        rows="4"
                                        placeholder="Foydalanuvchiga ko'rsatiladigan izoh..."
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3">
                                    <button 
                                        type="button"
                                        @click="submit(false)"
                                        :disabled="form.processing"
                                        class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 disabled:opacity-50">
                                        Saqlash
                                    </button>
                                    <button 
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1 py-3 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 disabled:opacity-50">
                                        {{ form.processing ? '...' : 'Saqlash va keyingi →' }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Navigation -->
                        <div class="flex gap-3">
                            <Link 
                                v-if="prevAnswerId"
                                :href="route('admin.olympiads.grading.grade', { olympiad: olympiad.id, answer: prevAnswerId })"
                                class="flex-1 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-medium text-center hover:bg-gray-50 border border-gray-200 dark:border-gray-700">
                                ← Oldingi
                            </Link>
                            <Link 
                                v-if="nextAnswerId"
                                :href="route('admin.olympiads.grading.grade', { olympiad: olympiad.id, answer: nextAnswerId })"
                                class="flex-1 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-medium text-center hover:bg-gray-50 border border-gray-200 dark:border-gray-700">
                                Keyingi →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
