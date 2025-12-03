<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import VideoChapterEditor from '@/Components/Teacher/VideoChapterEditor.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    lesson: Object,
    chapters: Array,
});

const showEditor = ref(false);
const editingChapter = ref(null);
const chaptersList = ref([...props.chapters]);

const openEditor = (chapter = null) => {
    editingChapter.value = chapter;
    showEditor.value = true;
};

const deleteChapter = (chapter) => {
    if (confirm('Rostdan ham bu bo\'limni o\'chirmoqchimisiz?')) {
        router.delete(route('teacher.lessons.chapters.destroy', [props.lesson.id, chapter.id]));
    }
};

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
};
</script>

<template>
    <TeacherLayout>

        <Head :title="`${lesson.title} - Video Bo'limlari`" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <div class="flex items-center text-sm text-gray-500 mb-1">
                        <Link :route="route('teacher.courses.edit', [lesson.course.id, { tab: 'curriculum' }])"
                            class="hover:text-indigo-600">
                        {{ lesson.course.title }}
                        </Link>
                        <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span>{{ lesson.title }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Video Bo'limlari</h1>
                </div>

                <button @click="openEditor()"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Bo'lim qo'shish
                </button>
            </div>

            <div v-if="chapters.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Bo'limlar yo'q</h3>
                <p class="mt-1 text-sm text-gray-500">Video navigatsiyasini osonlashtirish uchun bo'limlar qo'shing.</p>
                <div class="mt-6">
                    <button @click="openEditor()"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Bo'lim qo'shish
                    </button>
                </div>
            </div>

            <div v-else class="bg-white rounded-lg shadow overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <div v-for="chapter in chapters" :key="chapter.id"
                        class="p-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center">
                            <span
                                class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 w-16">
                                {{ formatTime(chapter.timestamp) }}
                            </span>

                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-900">{{ chapter.title }}</h3>
                                <p v-if="chapter.description" class="text-xs text-gray-500">{{ chapter.description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <button @click="openEditor(chapter)" class="text-gray-400 hover:text-indigo-600"
                                title="Tahrirlash">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <button @click="deleteChapter(chapter)" class="text-gray-400 hover:text-red-600"
                                title="O'chirish">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <VideoChapterEditor v-if="showEditor" :lesson="lesson" :chapter="editingChapter" :show="showEditor"
            @close="showEditor = false" />
    </TeacherLayout>
</template>
