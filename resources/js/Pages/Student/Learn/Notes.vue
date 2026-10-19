<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { PencilSquareIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Card, Button, EmptyState, Modal } from '@/Components/UI';
import LearnFrame from '@/Components/Student/LearnFrame.vue';
import NoteCard from '@/Components/Student/NoteCard.vue';
import NoteEditor from '@/Components/Student/NoteEditor.vue';

const props = defineProps({
    course: Object,
    notes: Object, // Grouped by lesson_id
});

const editingNote = ref(null);
const showEditor = ref(false);
const activeLessonId = ref(null);

const groups = computed(() => Object.entries(props.notes || {}));
const total = computed(() => groups.value.reduce((n, [, list]) => n + (list?.length || 0), 0));

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
        lesson: lessonId,
    }));
};
</script>

<template>

    <Head :title="`${course.title} - Qaydlar`" />

    <StudentLayout title="Qaydlar">
        <LearnFrame :course="course">
            <template #header>
                <PageHeader title="Mening qaydlarim" :description="`${course.title} · ${total} ta qayd`"
                    :back="route('student.learn.course', course.slug)" back-label="Kursga qaytish" />
            </template>

            <template v-if="groups.length">
                <Card v-for="[lessonId, lessonNotes] in groups" :key="lessonId" padding="sm">
                    <template #header>
                        <div class="flex w-full min-w-0 items-center justify-between gap-3">
                            <h2 class="truncate text-sm font-semibold text-fg">{{ lessonNotes[0]?.lesson?.title }}</h2>
                            <Button variant="ghost" size="sm" :icon-right="ArrowRightIcon"
                                :href="route('student.learn.lesson', { course: course.slug, lesson: lessonId })">
                                Darsga o'tish
                            </Button>
                        </div>
                    </template>
                    <div class="grid gap-3 md:grid-cols-2">
                        <NoteCard v-for="note in lessonNotes" :key="note.id" :note="note" @edit="editNote"
                            @seek="(time) => seekTo(time, lessonId)" />
                    </div>
                </Card>
            </template>

            <EmptyState v-else bordered size="lg" :icon="PencilSquareIcon" title="Qaydlar yo'q"
                description="Darslarni ko'rish jarayonida muhim joylarni belgilab oling.">
                <Button variant="secondary" :href="route('student.learn.course', course.slug)">Darslarga o'tish</Button>
            </EmptyState>
        </LearnFrame>

        <!-- Edit note -->
        <Modal :show="showEditor" size="lg" @close="closeEditor">
            <NoteEditor v-if="showEditor" :key="editingNote?.id || 'new'" :lesson-id="activeLessonId" :note="editingNote"
                class="!rounded-none !border-0 !shadow-none sm:!p-6" @close="closeEditor" @saved="closeEditor" />
        </Modal>
    </StudentLayout>
</template>
