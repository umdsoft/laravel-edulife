<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import LessonSidebar from '@/Components/Student/LessonSidebar.vue';
import NoteCard from '@/Components/Student/NoteCard.vue';
import NoteEditor from '@/Components/Student/NoteEditor.vue';

const props = defineProps({
    course: Object,
    notes: Object, // Grouped by lesson_id
});

const editingNote = ref(null);
const showEditor = ref(false);
const activeLessonId = ref(null);

const editNote = (note) => {
    editingNote.value = note;
    activeLessonId.value = note.lesson_id;
    showEditor.value = true;
};

const closeEditor = () => {
    showEditor.value = false;
    editingNote.value = null;
    activeLessonId.value = null;
};

const seekTo = (timestamp, lessonId) => {
    router.visit(route('student.learn.lesson', {
        course: props.course.slug,
        lesson: lessonId
    }));
};
</script>

<template>

    <Head :title="`${course.title} - Qaydlar`" />

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
                        <h1 class="text-2xl font-bold text-gray-900">Mening qaydlarim</h1>
                    </div>
                </div>

                <!-- Editor Modal/Overlay -->
                <div v-if="showEditor" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                    <div class="w-full max-w-lg">
                        <NoteEditor :lesson-id="activeLessonId" :note="editingNote" @close="closeEditor"
                            @saved="closeEditor" />
                    </div>
                </div>

                <!-- Notes List -->
                <div class="space-y-8">
                    <div v-for="(lessonNotes, lessonId) in notes" :key="lessonId"
                        class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900">{{ lessonNotes[0].lesson.title }}</h3>
                            <Link :href="route('student.learn.lesson', { course: course.slug, lesson: lessonId })"
                                class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                            Darsga o'tish &rarr;
                            </Link>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <NoteCard v-for="note in lessonNotes" :key="note.id" :note="note" @edit="editNote"
                                @seek="(time) => seekTo(time, lessonId)" />
                        </div>
                    </div>

                    <div v-if="Object.keys(notes).length === 0"
                        class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Qaydlar yo'q</h3>
                        <p class="text-gray-500 mt-2">Darslarni ko'rish jarayonida muhim joylarni belgilab oling.</p>
                    </div>
                </div>
            </div>

            <!-- Sidebar (Desktop) -->
            <LessonSidebar :course="course" :progress="{}" :module-progress="{}" />
        </div>
    </StudentLayout>
</template>
