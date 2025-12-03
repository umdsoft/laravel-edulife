<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import VideoUploader from '@/Components/Teacher/VideoUploader.vue';

const props = defineProps({
    course: Object,
    module: Object,
    lesson: Object,
});

const form = useForm({
    title: props.lesson?.title || '',
    description: props.lesson?.description || '',
    type: props.lesson?.type || 'video',
    content: props.lesson?.content || '',
    is_free: !!props.lesson?.is_free,
    is_preview: !!props.lesson?.is_preview,
});

const submit = () => {
    if (props.lesson) {
        form.put(route('teacher.courses.modules.lessons.update', [props.course.id, props.module.id, props.lesson.id]));
    } else {
        form.post(route('teacher.courses.modules.lessons.store', [props.course.id, props.module.id]));
    }
};
</script>

<template>

    <Head :title="lesson ? 'Darsni tahrirlash' : 'Yangi dars'" />

    <TeacherLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ lesson ? 'Darsni tahrirlash' : 'Yangi dars' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nomi</label>
                                        <input v-model="form.title" type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                        <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{
                                            form.errors.title }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tavsif</label>
                                        <textarea v-model="form.description" rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Turi</label>
                                        <select v-model="form.type"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="video">Video dars</option>
                                            <option value="text">Matnli dars</option>
                                            <option value="quiz">Test</option>
                                        </select>
                                    </div>

                                    <div class="flex gap-6">
                                        <div class="flex items-center">
                                            <input v-model="form.is_free" type="checkbox"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label class="ml-2 block text-sm text-gray-900">Bepul dars</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input v-model="form.is_preview" type="checkbox"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label class="ml-2 block text-sm text-gray-900">Preview (sotib olishdan
                                                oldin
                                                ko'rish)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Video Upload -->
                                    <div v-if="form.type === 'video' && lesson">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Video fayl</label>
                                        <VideoUploader :lesson="lesson" />
                                    </div>

                                    <!-- Text Content -->
                                    <div v-if="form.type === 'text'">
                                        <label class="block text-sm font-medium text-gray-700">Matn</label>
                                        <textarea v-model="form.content" rows="10"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">Markdown formatini qo'llab-quvvatlaydi</p>
                                    </div>

                                    <div v-if="form.type === 'video' && !lesson"
                                        class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                                        <p class="text-sm text-yellow-700">
                                            Video yuklash uchun avval darsni saqlang.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t">
                                <Link :href="route('teacher.courses.modules.index', course.id)" class="btn-secondary">
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
