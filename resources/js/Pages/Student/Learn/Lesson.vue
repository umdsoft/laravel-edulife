<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { PencilSquareIcon, DocumentIcon, PaperClipIcon, ArrowDownTrayIcon, FilmIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';
import { ChevronLeftIcon, QueueListIcon, CheckIcon, CheckCircleIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Card, Button, Badge, Tabs, EmptyState } from '@/Components/UI';
import LearnFrame from '@/Components/Student/LearnFrame.vue';
import VideoPlayer from '@/Components/Student/VideoPlayer.vue';
import VideoChapters from '@/Components/Student/VideoChapters.vue';
import LessonNavigation from '@/Components/Student/LessonNavigation.vue';
import NoteEditor from '@/Components/Student/NoteEditor.vue';
import NoteCard from '@/Components/Student/NoteCard.vue';
import RichContent from '@/Components/Student/RichContent.vue';

const props = defineProps({
    course: Object,
    lesson: Object,
    enrollment: Object,
    progress: Object,
    notes: Array,
    prevLesson: Object,
    nextLesson: Object,
    lessonProgresses: Object,
    moduleProgress: { type: Object, default: () => ({}) },
    lessonTests: { type: Array, default: () => [] },
});

const activeTab = ref('overview'); // overview, notes, files
const showNoteEditor = ref(false);
const editingNote = ref(null);
const videoRef = ref(null);
const playerWrap = ref(null);
const currentTime = ref(0);
const duration = ref(0);
const completing = ref(false);

const noteList = computed(() => props.notes || []);
const attachments = computed(() => props.lesson.attachments || []);
const isCompleted = computed(() => !!props.progress?.is_completed);
const quizHint = "Testdan o'tsangiz, dars avtomatik yakunlanadi.";

const tabs = computed(() => [
    { key: 'overview', label: 'Ma\'lumot' },
    { key: 'notes', label: 'Qaydlar', count: noteList.value.length || null },
    { key: 'files', label: 'Fayllar', count: attachments.value.length || null },
]);

const onVideoProgress = (data) => {
    // Background progress sync: the endpoint returns JSON, so use axios (not an Inertia visit)
    axios.post(route('student.learn.progress.update', props.lesson.id), {
        position: Math.floor(data.currentTime),
        duration: Math.floor(data.duration),
    }).catch(() => {
        // Ignore transient failures; the next tick will retry
    });
};

const onVideoEnded = () => {
    // Mark as completed, then refresh progress-related props
    completing.value = true;
    axios.post(route('student.learn.progress.complete', props.lesson.id))
        .then(() => {
            router.reload({ only: ['progress', 'lessonProgresses', 'enrollment'], preserveScroll: true });
        })
        .catch(() => {})
        .finally(() => (completing.value = false));
};

const onTimeUpdate = (time) => {
    currentTime.value = time;
};

const onLoadedMetadata = (d) => {
    duration.value = d;
};

// VideoPlayer exposes seek(seconds); bring the player into view so the jump is visible
const seekTo = (time) => {
    const player = videoRef.value;
    if (player && player.seek) {
        player.seek(time);
        playerWrap.value?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

const openNoteEditor = () => {
    editingNote.value = null;
    activeTab.value = 'notes';
    showNoteEditor.value = true;
};

const editNote = (note) => {
    editingNote.value = note;
    showNoteEditor.value = true;
};

const closeNoteEditor = () => {
    showNoteEditor.value = false;
    editingNote.value = null;
};
</script>

<template>

    <Head :title="lesson.title" />

    <StudentLayout :title="lesson.title">
        <LearnFrame :course="course" :current-lesson="lesson" :progress="lessonProgresses" :module-progress="moduleProgress" :show-nav="false">
            <template #header="{ openCurriculum }">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <Link :href="route('student.learn.course', course.slug)"
                        class="focus-ring inline-flex min-w-0 items-center gap-1.5 rounded-sm text-13 font-medium text-fg-subtle hover:text-fg">
                        <ChevronLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ course.title }}</span>
                    </Link>
                    <Button variant="secondary" size="sm" :icon-left="QueueListIcon" class="lg:hidden" @click="openCurriculum">
                        Dastur
                    </Button>
                </div>
            </template>

            <!-- Player / reading surface -->
            <div ref="playerWrap" class="scroll-mt-24">
                <VideoPlayer ref="videoRef" v-if="lesson.video" :src="lesson.video.url"
                    :poster="lesson.video.poster" :start-time="progress?.last_position || 0"
                    :chapters="lesson.video.chapters"
                    :watch-log-url="route('student.learn.progress.video-log', lesson.id)"
                    @progress="onVideoProgress" @ended="onVideoEnded"
                    @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMetadata" />

                <!-- Text lesson: a calm reading column -->
                <Card v-else-if="lesson.type === 'text'" padding="lg">
                    <article class="mx-auto max-w-[68ch]">
                        <RichContent :html="lesson.text_content" size="lg" />
                    </article>
                    <template #footer>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-13 text-fg-subtle">
                                {{ isCompleted ? 'Bu dars tugatilgan' : "O'qib bo'lgach, darsni yakunlang" }}
                            </span>
                            <Badge v-if="isCompleted" variant="success" :icon="CheckCircleIcon">Tugatilgan</Badge>
                            <Button v-else :loading="completing" :icon-left="CheckIcon" @click="onVideoEnded">Darsni yakunlash</Button>
                        </div>
                    </template>
                </Card>

                <!-- Quiz lesson: passing its test completes the lesson -->
                <Card v-else-if="lesson.type === 'quiz'" title="Dars testi"
                    :description="lessonTests.length ? quizHint : null">
                    <ul v-if="lessonTests.length" class="divide-y divide-border">
                        <li v-for="t in lessonTests" :key="t.id" class="flex flex-col gap-3 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-medium text-fg">{{ t.title }}</p>
                                <p class="text-13 tabular-nums text-fg-subtle">
                                    {{ t.questions_count }} savol · {{ t.time_limit }} daq · o'tish {{ t.passing_score }}%
                                    <template v-if="t.best_score !== null"> · eng yaxshi natija {{ t.best_score }}%</template>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge v-if="t.is_passed" variant="success" :icon="CheckCircleIcon">O'tilgan</Badge>
                                <Button :variant="t.is_passed ? 'secondary' : 'primary'" :icon-left="ClipboardDocumentCheckIcon" :href="route('student.tests.start', t.id)">
                                    {{ t.attempts ? 'Qayta topshirish' : 'Testni boshlash' }}
                                </Button>
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else size="sm" :icon="ClipboardDocumentCheckIcon" title="Bu darsga test hali qo'shilmagan"
                        description="Ustoz testni qo'shgach, shu yerda paydo bo'ladi." />
                    <template v-if="!lessonTests.length" #footer>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-13 text-fg-subtle">{{ isCompleted ? 'Bu dars tugatilgan' : "Kurs progressi to'xtab qolmasligi uchun darsni yakunlashingiz mumkin" }}</span>
                            <Badge v-if="isCompleted" variant="success" :icon="CheckCircleIcon">Tugatilgan</Badge>
                            <Button v-else :loading="completing" :icon-left="CheckIcon" @click="onVideoEnded">Darsni yakunlash</Button>
                        </div>
                    </template>
                </Card>

                <!-- No material yet: the lesson must not block course progress -->
                <Card v-else>
                    <EmptyState :icon="FilmIcon" title="Dars materiali hali yuklanmagan"
                        description="Ustoz materialni qo'shgach, shu yerda ko'rinadi." />
                    <template #footer>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-13 text-fg-subtle">{{ isCompleted ? 'Bu dars tugatilgan' : "Kurs progressi to'xtab qolmasligi uchun darsni yakunlashingiz mumkin" }}</span>
                            <Badge v-if="isCompleted" variant="success" :icon="CheckCircleIcon">Tugatilgan</Badge>
                            <Button v-else variant="secondary" :loading="completing" :icon-left="CheckIcon" @click="onVideoEnded">Darsni yakunlash</Button>
                        </div>
                    </template>
                </Card>

                <!-- Test that follows a video / text lesson -->
                <Card v-if="lesson.type !== 'quiz' && lessonTests.length" class="mt-6" title="Dars testi"
                    description="Darsni yakunlagach, bilimingizni tekshiring.">
                    <ul class="divide-y divide-border">
                        <li v-for="t in lessonTests" :key="t.id" class="flex flex-col gap-3 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-medium text-fg">{{ t.title }}</p>
                                <p class="text-13 tabular-nums text-fg-subtle">{{ t.questions_count }} savol · {{ t.time_limit }} daq · o'tish {{ t.passing_score }}%</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge v-if="t.is_passed" variant="success" :icon="CheckCircleIcon">O'tilgan</Badge>
                                <Button variant="secondary" :icon-left="ClipboardDocumentCheckIcon" :href="route('student.tests.start', t.id)">Testga o'tish</Button>
                            </div>
                        </li>
                    </ul>
                </Card>
            </div>

            <!-- Lesson title + tools -->
            <Card padding="none">
                <div class="flex flex-col gap-4 px-5 pt-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <p v-if="lesson.module?.title" class="text-13 font-medium text-fg-subtle">{{ lesson.module.title }}</p>
                        <h1 class="mt-0.5 text-xl font-semibold tracking-tight text-fg sm:text-2xl">{{ lesson.title }}</h1>
                        <Badge v-if="isCompleted" variant="success" size="sm" :icon="CheckCircleIcon" class="mt-2">Tugatilgan</Badge>
                    </div>
                    <Button variant="secondary" :icon-left="PencilSquareIcon" class="shrink-0" @click="openNoteEditor">
                        Qayd olish
                    </Button>
                </div>

                <div class="px-5 pb-5 sm:px-6">
                    <Tabs v-model="activeTab" :tabs="tabs" label="Dars bo'limlari" class="mt-3">
                        <template #overview>
                            <div class="space-y-6">
                                <RichContent v-if="lesson.description" :html="lesson.description" />
                                <p v-else class="text-sm text-fg-subtle">Dars tavsifi qo'shilmagan.</p>

                                <VideoChapters v-if="lesson.video?.chapters?.length > 0" :chapters="lesson.video.chapters"
                                    :current-time="currentTime" @seek="seekTo" />
                            </div>
                        </template>

                        <template #notes>
                            <div class="space-y-4">
                                <NoteEditor v-if="showNoteEditor" :key="editingNote?.id || 'new'" :lesson-id="lesson.id"
                                    :current-time="currentTime" :note="editingNote" @close="closeNoteEditor" @saved="closeNoteEditor" />

                                <div v-if="noteList.length > 0" class="grid gap-3 md:grid-cols-2">
                                    <NoteCard v-for="note in noteList" :key="note.id" :note="note" @edit="editNote" @seek="seekTo" />
                                </div>
                                <EmptyState v-else-if="!showNoteEditor" size="sm" :icon="PencilSquareIcon" title="Hozircha qaydlar yo'q"
                                    description="Dars davomida muhim joylarni belgilab oling — qayd video vaqti bilan saqlanadi.">
                                    <Button size="sm" variant="secondary" @click="openNoteEditor">Birinchi qaydni yozish</Button>
                                </EmptyState>
                            </div>
                        </template>

                        <template #files>
                            <ul v-if="attachments.length > 0" class="grid gap-3 md:grid-cols-2">
                                <li v-for="file in attachments" :key="file.id"
                                    class="flex items-center justify-between gap-3 rounded-md border border-border p-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-muted">
                                            <DocumentIcon class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-fg">{{ file.name }}</p>
                                            <p class="text-xs text-fg-subtle tabular-nums">{{ file.size }}</p>
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="sm" external :href="file.url" :icon-left="ArrowDownTrayIcon"
                                        :aria-label="`${file.name} — yuklab olish`">
                                        Yuklab olish
                                    </Button>
                                </li>
                            </ul>
                            <EmptyState v-else size="sm" :icon="PaperClipIcon" title="Fayllar yo'q"
                                description="Ushbu dars uchun qo'shimcha fayllar yuklanmagan." />
                        </template>
                    </Tabs>
                </div>
            </Card>

            <LessonNavigation :prev-lesson="prevLesson" :next-lesson="nextLesson" :course="course"
                :course-completed="Number(enrollment?.progress) >= 100" />
        </LearnFrame>
    </StudentLayout>
</template>
