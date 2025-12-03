<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import AttachmentUploader from '@/Components/Teacher/AttachmentUploader.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    lesson: Object,
    attachments: Array,
});

const showUploader = ref(false);
const attachmentsList = ref([...props.attachments]);

const updateOrder = () => {
    const items = attachmentsList.value.map((item, index) => ({
        id: item.id,
        sort_order: index + 1,
    }));

    router.post(route('teacher.lessons.attachments.reorder', props.lesson.id), {
        attachments: items,
    }, {
        preserveScroll: true,
    });
};

const deleteAttachment = (attachment) => {
    if (confirm('Rostdan ham bu faylni o\'chirmoqchimisiz?')) {
        router.delete(route('teacher.lessons.attachments.destroy', [props.lesson.id, attachment.id]));
    }
};

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
    <TeacherLayout>

        <Head :title="`${lesson.title} - Fayllar`" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <h1 class="text-2xl font-bold text-gray-900">Dars fayllari</h1>
                </div>

                <button @click="showUploader = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Fayl qo'shish
                </button>
            </div>

            <div v-if="attachments.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Fayllar yo'q</h3>
                <p class="mt-1 text-sm text-gray-500">Bu dars uchun hali hech qanday fayl yuklanmagan.</p>
                <div class="mt-6">
                    <button @click="showUploader = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Fayl yuklash
                    </button>
                </div>
            </div>

            <div v-else class="bg-white rounded-lg shadow overflow-hidden">
                <draggable v-model="attachmentsList" item-key="id" handle=".drag-handle" @end="updateOrder"
                    class="divide-y divide-gray-200">
                    <template #item="{ element }">
                        <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center flex-1">
                                <div class="drag-handle cursor-move text-gray-400 mr-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </div>

                                <div
                                    class="flex-shrink-0 h-10 w-10 rounded bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <span class="uppercase text-xs font-bold">{{ element.file_type }}</span>
                                </div>

                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900">{{ element.title }}</h3>
                                    <p class="text-xs text-gray-500">
                                        {{ formatSize(element.file_size) }} • {{ element.download_count }} marta
                                        yuklangan
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <a :href="element.file_url" target="_blank" class="text-gray-400 hover:text-indigo-600"
                                    title="Yuklab olish">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>

                                <button @click="deleteAttachment(element)" class="text-gray-400 hover:text-red-600"
                                    title="O'chirish">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>

        <AttachmentUploader v-if="showUploader" :lesson="lesson" @close="showUploader = false" />
    </TeacherLayout>
</template>
