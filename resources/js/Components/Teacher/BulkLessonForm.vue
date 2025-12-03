<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    course: Object,
    module: Object,
});

const form = useForm({
    lessons: [
        { title: '', description: '', type: 'video', is_free: false, is_preview: false }
    ],
});

const addLesson = () => {
    form.lessons.push({
        title: '',
        description: '',
        type: 'video',
        is_free: false,
        is_preview: false,
    });
};

const removeLesson = (index) => {
    if (form.lessons.length > 1) {
        form.lessons.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('teacher.courses.modules.bulk-lessons.store', [props.course.id, props.module.id]));
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div v-for="(lesson, index) in form.lessons" :key="index"
            class="bg-gray-50 rounded-lg p-4 border border-gray-200 relative">
            <button v-if="form.lessons.length > 1" type="button" @click="removeLesson(index)"
                class="absolute top-4 right-4 text-gray-400 hover:text-red-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h3 class="text-sm font-medium text-gray-900 mb-4">Dars {{ index + 1 }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Title -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dars nomi</label>
                    <input v-model="lesson.title" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required placeholder="Dars nomini kiriting" />
                    <div v-if="form.errors[`lessons.${index}.title`]" class="text-red-500 text-xs mt-1">
                        {{ form.errors[`lessons.${index}.title`] }}
                    </div>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dars turi</label>
                    <select v-model="lesson.type"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="video">Video dars</option>
                        <option value="text">Matnli dars</option>
                        <option value="quiz">Test sinovi</option>
                    </select>
                </div>

                <!-- Options -->
                <div class="flex items-center space-x-4 mt-6">
                    <label class="flex items-center">
                        <input v-model="lesson.is_free" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                        <span class="ml-2 text-sm text-gray-700">Bepul dars</span>
                    </label>

                    <label class="flex items-center">
                        <input v-model="lesson.is_preview" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                        <span class="ml-2 text-sm text-gray-700">Prevyu (ochiq)</span>
                    </label>
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qisqacha tavsif (ixtiyoriy)</label>
                    <textarea v-model="lesson.description" rows="2"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Dars haqida qisqacha ma'lumot"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-4">
            <button type="button" @click="addLesson"
                class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Yana dars qo'shish
            </button>

            <div class="flex space-x-3">
                <button type="button" @click="$emit('cancel')"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Bekor qilish
                </button>
                <button type="submit" :disabled="form.processing"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                    Saqlash
                </button>
            </div>
        </div>
    </form>
</template>
