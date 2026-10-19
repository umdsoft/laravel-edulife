<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon, ListBulletIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import VideoChapterEditor from '@/Components/Teacher/VideoChapterEditor.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import { formatTimestamp } from '@/Components/Teacher/format';

const props = defineProps({
    lesson: Object,
    chapters: Array,
});

const showEditor = ref(false);
const editingChapter = ref(null);

const openEditor = (chapter = null) => {
    editingChapter.value = chapter;
    showEditor.value = true;
};

const chapterToDelete = ref(null);
const deleting = ref(false);
const deleteChapter = () => {
    router.delete(route('teacher.chapters.destroy', chapterToDelete.value.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            chapterToDelete.value = null;
        },
    });
};

// lesson.course is not always eager-loaded here — fall back to the foreign key
const courseId = computed(() => props.lesson.course?.id ?? props.lesson.course_id);
const sorted = computed(() => [...(props.chapters || [])].sort((a, b) => a.timestamp - b.timestamp));
const breadcrumbs = computed(() => {
    const items = [{ label: 'Kurslarim', href: route('teacher.courses.index') }];
    if (courseId.value) items.push({ label: 'Darslar rejasi', href: route('teacher.courses.modules.index', courseId.value) });
    items.push({ label: props.lesson.title, href: route('teacher.lessons.edit', props.lesson.id) });
    items.push({ label: 'Video bo\'limlari' });
    return items;
});
</script>

<template>
    <TeacherLayout>
        <Head :title="`${lesson.title} — Video bo'limlari`" />

        <PageHeader title="Video bo'limlari" description="O'quvchilar videoning kerakli qismiga bir bosishda o'ta oladi."
            :breadcrumbs="breadcrumbs">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openEditor()">Bo'lim qo'shish</Button>
            </template>
        </PageHeader>

        <Card class="max-w-3xl" padding="none">
            <ol v-if="sorted.length" class="divide-y divide-border">
                <li v-for="chapter in sorted" :key="chapter.id" class="flex items-center gap-4 px-4 py-3 hover:bg-surface-2/60 sm:px-6">
                    <span class="inline-flex h-7 min-w-[4rem] items-center justify-center rounded-sm bg-brand-50 px-2 text-13 font-medium tabular-nums text-brand">
                        {{ formatTimestamp(chapter.timestamp) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-medium text-fg">{{ chapter.title }}</h3>
                        <p v-if="chapter.description" class="truncate text-13 text-fg-subtle">{{ chapter.description }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <IconButton :icon="PencilSquareIcon" label="Tahrirlash" size="sm" @click="openEditor(chapter)" />
                        <IconButton :icon="TrashIcon" label="O'chirish" variant="danger" size="sm" @click="chapterToDelete = chapter" />
                    </div>
                </li>
            </ol>
            <EmptyState v-else :icon="ListBulletIcon" title="Bo'limlar yo'q"
                description="Uzun videoni mavzularga bo'ling — masalan «0:00 Kirish», «4:30 Masala yechish».">
                <Button :icon-left="PlusIcon" @click="openEditor()">Birinchi bo'limni qo'shish</Button>
            </EmptyState>
        </Card>

        <VideoChapterEditor v-if="showEditor" :lesson="lesson" :chapter="editingChapter" :show="showEditor"
            @close="showEditor = false" />

        <ConfirmDialog :show="!!chapterToDelete" title="Bo'limni o'chirasizmi?"
            :description="chapterToDelete ? `«${chapterToDelete.title}» bo'limi o'chiriladi.` : null"
            :loading="deleting" @confirm="deleteChapter" @close="chapterToDelete = null" />
    </TeacherLayout>
</template>
