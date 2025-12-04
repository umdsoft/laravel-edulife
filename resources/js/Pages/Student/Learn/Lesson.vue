<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import VideoPlayer from '@/Components/Student/VideoPlayer.vue';
import VideoChapters from '@/Components/Student/VideoChapters.vue';
import LessonSidebar from '@/Components/Student/LessonSidebar.vue';
import LessonNavigation from '@/Components/Student/LessonNavigation.vue';
import NoteEditor from '@/Components/Student/NoteEditor.vue';
import NoteCard from '@/Components/Student/NoteCard.vue';
import { useSanitize } from '@/Composables/useSanitize.js';

const { sanitize } = useSanitize();

const props = defineProps({
    course: Object,
    lesson: Object,
    enrollment: Object,
    progress: Object,
    notes: Array,
    prevLesson: Object,
    nextLesson: Object,
    lessonProgresses: Object,
});

const activeTab = ref('overview'); // overview, notes, qna, files
const showNoteEditor = ref(false);
const editingNote = ref(null);
const videoRef = ref(null);
const currentTime = ref(0);
const duration = ref(0);

const tabs = [
    { id: 'overview', label: 'Ma\'lumot' },
    { id: 'notes', label: 'Qaydlar' },
    { id: 'files', label: 'Fayllar' },
];

const onVideoProgress = (data) => {
    // Send progress update to server
    router.post(route('student.learn.progress.update', props.lesson.id), {
        position: Math.floor(data.currentTime),
        duration: Math.floor(data.duration),
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const onVideoEnded = () => {
    // Mark as completed
    router.post(route('student.learn.progress.complete', props.lesson.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Show completion celebration or auto-advance
        },
    });
};

const onTimeUpdate = (time) => {
    currentTime.value = time;
};

const onLoadedMetadata = (d) => {
    duration.value = d;
};

const seekTo = (time) => {
    // Implementation depends on VideoPlayer exposing seek method or using a ref to call it
    // For now assuming VideoPlayer handles prop changes or we need to access video element
    // Ideally VideoPlayer should expose a method
    const player = videoRef.value;
    if (player && player.seek) {
        player.seek(time);
    }
};

const editNote = (note) => {
    editingNote.value = note;
    showNoteEditor.value = true;
};

const closeNoteEditor = () => {
    showNoteEditor.value = false;
    editingNote.value = null;
};

const downloadFile = (file) => {
    window.open(file.url, '_blank');
};
</script>

<template>

    <Head :title="lesson.title" />

    <StudentLayout>
        <div class="flex h-[calc(100vh-64px)] -m-6">
            <!-- Main Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Video Player -->
                <div class="mb-6">
                    <VideoPlayer ref="videoRef" v-if="lesson.video" :src="lesson.video.url"
                        :poster="lesson.video.poster" :start-time="progress?.last_position || 0"
                        :chapters="lesson.video.chapters" @progress="onVideoProgress" @ended="onVideoEnded"
                        @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMetadata" />

                    <!-- Text Lesson Fallback -->
                    <div v-else-if="lesson.type === 'text'"
                        class="bg-white p-8 rounded-2xl border border-gray-100 prose max-w-none">
                        <div v-html="sanitize(lesson.content)"></div>

                        <div class="mt-8 flex justify-center">
                            <button @click="onVideoEnded"
                                class="px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors">
                                Darsni yakunlash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lesson Info & Tabs -->
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden mb-6">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ lesson.title }}</h1>
                                <p class="text-gray-500">{{ lesson.module.title }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button @click="activeTab = 'notes'; showNoteEditor = true"
                                    class="flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors font-medium">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Qayd olish
                                </button>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="flex gap-6 border-b border-gray-100 -mb-6 pb-0">
                            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                                class="pb-4 border-b-2 font-medium transition-colors"
                                :class="activeTab === tab.id ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Overview Tab -->
                        <div v-if="activeTab === 'overview'" class="space-y-6">
                            <div class="prose max-w-none text-gray-600" v-html="sanitize(lesson.description)"></div>

                            <VideoChapters v-if="lesson.video?.chapters?.length > 0" :chapters="lesson.video.chapters"
                                :current-time="currentTime" @seek="seekTo" />
                        </div>

                        <!-- Notes Tab -->
                        <div v-if="activeTab === 'notes'" class="space-y-6">
                            <NoteEditor v-if="showNoteEditor" :lesson-id="lesson.id" :current-time="currentTime"
                                :note="editingNote" @close="closeNoteEditor" @saved="closeNoteEditor" />

                            <div v-if="notes.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <NoteCard v-for="note in notes" :key="note.id" :note="note" @edit="editNote"
                                    @seek="seekTo" />
                            </div>
                            <div v-else-if="!showNoteEditor" class="text-center py-8 text-gray-500">
                                Hozircha qaydlar yo'q. Dars davomida muhim joylarni belgilab oling.
                            </div>
                        </div>

                        <!-- Files Tab -->
                        <div v-if="activeTab === 'files'" class="space-y-4">
                            <div v-if="lesson.attachments?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="file in lesson.attachments" :key="file.id"
                                    class="flex items-center justify-between p-4 rounded-xl border border-gray-200 hover:border-purple-200 hover:bg-purple-50 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 group-hover:bg-white group-hover:text-purple-600 transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ file.name }}</p>
                                            <p class="text-xs text-gray-500">{{ file.size }}</p>
                                        </div>
                                    </div>
                                    <button @click="downloadFile(file)"
                                        class="p-2 text-gray-400 hover:text-purple-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                Ushbu dars uchun fayllar yuklanmagan.
                            </div>
                        </div>
                    </div>
                </div>

                <LessonNavigation :prev-lesson="prevLesson" :next-lesson="nextLesson" :course="course" />
            </div>

            <!-- Sidebar (Desktop) -->
            <LessonSidebar :course="course" :current-lesson="lesson" :progress="lessonProgresses"
                :module-progress="{}" />
        </div>
    </StudentLayout>
</template>
