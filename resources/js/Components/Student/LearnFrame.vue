<script setup>
/**
 * LearnFrame — shared frame for the course learning area (Learn/Course, Lesson, Notes, Qna, Announcements).
 *   <LearnFrame :course="course" :current-lesson="lesson" :progress="lessonProgresses" :module-progress="moduleProgress">
 *     <template #header="{ openCurriculum }"><PageHeader … /></template>
 *     …main column…
 *   </LearnFrame>
 * Desktop: main column + sticky curriculum sidebar. Mobile: curriculum opens in a bottom sheet.
 * `showNav` renders the course section tabs (Umumiy / Qaydlar / Savol-javob / E'lonlar / Testlar).
 */
import { computed, ref } from 'vue';
import { QueueListIcon } from '@heroicons/vue/20/solid';
import Tabs from '@/Components/UI/Tabs.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';
import LessonSidebar from './LessonSidebar.vue';
import { routeHas } from '@/Utils/nav';

const props = defineProps({
    course: { type: Object, required: true },
    currentLesson: { type: Object, default: null },
    progress: { type: Object, default: () => ({}) },
    moduleProgress: { type: Object, default: () => ({}) },
    showNav: { type: Boolean, default: true },
    counts: { type: Object, default: () => ({}) }, // { qna, announcements }
});

const curriculumOpen = ref(false);
// Some pages (e.g. announcements) don't load modules — then the frame is a single column
const hasCurriculum = computed(() => Array.isArray(props.course?.modules));
const openCurriculum = () => {
    if (hasCurriculum.value) curriculumOpen.value = true;
};

const safeRoute = (name, params) => {
    if (!routeHas(name)) return null;
    try {
        return route(name, params);
    } catch {
        return null;
    }
};

const navTabs = computed(() => [
    { key: 'overview', label: 'Umumiy', href: safeRoute('student.learn.course', props.course.slug) },
    { key: 'notes', label: 'Qaydlar', href: safeRoute('student.learn.notes.index', props.course.id) },
    { key: 'qna', label: 'Savol-javob', href: safeRoute('student.learn.qna.index', props.course.id), count: props.counts.qna || null },
    { key: 'announcements', label: "E'lonlar", href: safeRoute('student.learn.announcements.index', props.course.id), count: props.counts.announcements || null },
    { key: 'tests', label: 'Testlar', href: safeRoute('student.tests.index', props.course.id) },
].filter((t) => t.href));
</script>

<template>
    <div>
        <slot name="header" :openCurriculum="openCurriculum" />

        <div v-if="showNav" class="mb-6 flex items-end gap-3">
            <Tabs :tabs="navTabs" label="Kurs bo'limlari" class="min-w-0 flex-1" />
            <Button v-if="hasCurriculum" variant="secondary" size="sm" :icon-left="QueueListIcon" class="mb-1.5 lg:hidden"
                @click="openCurriculum">
                Dastur
            </Button>
        </div>

        <div :class="['grid gap-6', hasCurriculum ? 'lg:grid-cols-[minmax(0,1fr)_300px] xl:grid-cols-[minmax(0,1fr)_320px]' : '']">
            <div class="min-w-0 space-y-6">
                <slot :openCurriculum="openCurriculum" />
            </div>

            <aside v-if="hasCurriculum" class="hidden lg:block" aria-label="Kurs dasturi">
                <div class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-hidden rounded-lg border border-border bg-surface shadow-xs">
                    <LessonSidebar :course="course" :current-lesson="currentLesson" :progress="progress"
                        :module-progress="moduleProgress" class="max-h-[calc(100vh-6rem)]" />
                </div>
            </aside>
        </div>

        <!-- Mobile curriculum sheet -->
        <Modal :show="curriculumOpen" size="md" @close="curriculumOpen = false">
            <LessonSidebar :course="course" :current-lesson="currentLesson" :progress="progress"
                :module-progress="moduleProgress" @navigate="curriculumOpen = false" />
            <template #footer>
                <Button variant="secondary" block @click="curriculumOpen = false">Yopish</Button>
            </template>
        </Modal>
    </div>
</template>
