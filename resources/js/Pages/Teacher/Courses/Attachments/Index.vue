<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { PlusIcon, PaperClipIcon, ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { Bars2Icon, LockClosedIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import AttachmentUploader from '@/Components/Teacher/AttachmentUploader.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import { formatNumber, formatSize } from '@/Components/Teacher/format';

const props = defineProps({
    lesson: Object,
    attachments: Array,
});

const showUploader = ref(false);
const attachmentsList = ref([...props.attachments]);
watch(() => props.attachments, (list) => {
    attachmentsList.value = [...(list || [])];
});

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

const toDelete = ref(null);
const deleting = ref(false);
const deleteAttachment = () => {
    router.delete(route('teacher.attachments.destroy', toDelete.value.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
};

const TYPE_TONES = {
    pdf: 'bg-danger-50 text-danger',
    document: 'bg-info-50 text-info',
    spreadsheet: 'bg-success-50 text-success',
    presentation: 'bg-warning-50 text-warning',
    image: 'bg-physics-50 text-physics',
};
const typeTone = (type) => TYPE_TONES[type] || 'bg-surface-2 text-fg-muted';
const typeLabel = (a) => String(a.file_type || a.extension || 'fayl').slice(0, 4);

const courseId = computed(() => props.lesson.course?.id ?? props.lesson.course_id);
const breadcrumbs = computed(() => [
    { label: 'Kurslarim', href: route('teacher.courses.index') },
    ...(props.lesson.course ? [{ label: props.lesson.course.title, href: route('teacher.courses.edit', courseId.value) }] : []),
    ...(courseId.value ? [{ label: 'Darslar rejasi', href: route('teacher.courses.modules.index', courseId.value) }] : []),
    { label: props.lesson.title, href: route('teacher.lessons.edit', props.lesson.id) },
    { label: 'Fayllar' },
]);
</script>

<template>
    <TeacherLayout>
        <Head :title="`${lesson.title} — Fayllar`" />

        <PageHeader title="Dars fayllari" :description="`«${lesson.title}» darsi uchun qo'shimcha materiallar.`" :breadcrumbs="breadcrumbs">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="showUploader = true">Fayl qo'shish</Button>
            </template>
        </PageHeader>

        <Card class="max-w-3xl" padding="none">
            <draggable v-if="attachmentsList.length" v-model="attachmentsList" item-key="id" handle=".drag-handle" tag="ul"
                :animation="150" ghost-class="opacity-40" class="divide-y divide-border" @end="updateOrder">
                <template #item="{ element }">
                    <li class="flex items-center gap-3 bg-surface px-3 py-3 hover:bg-surface-2/60 sm:px-5">
                        <span class="drag-handle flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded-sm text-fg-subtle hover:bg-surface-2 hover:text-fg active:cursor-grabbing"
                            title="Sudrab tartiblang" aria-hidden="true">
                            <Bars2Icon class="h-4 w-4" />
                        </span>
                        <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-[11px] font-semibold uppercase', typeTone(element.file_type)]">
                            {{ typeLabel(element) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-medium text-fg">{{ element.title }}</h3>
                            <p class="flex items-center gap-1.5 text-13 tabular-nums text-fg-subtle">
                                {{ formatSize(element.file_size) }} · {{ formatNumber(element.download_count || 0) }} marta yuklangan
                                <span v-if="element.is_downloadable === false || element.is_downloadable === 0" class="inline-flex items-center gap-0.5">
                                    · <LockClosedIcon class="h-3.5 w-3.5" aria-hidden="true" /> yuklab bo'lmaydi
                                </span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <a :href="element.file_url" target="_blank" rel="noopener"
                                class="focus-ring inline-flex h-8 w-8 items-center justify-center rounded-md text-fg-subtle hover:bg-surface-2 hover:text-fg"
                                :aria-label="`${element.title} — ochish`" title="Ochish">
                                <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                            </a>
                            <IconButton :icon="TrashIcon" label="O'chirish" variant="danger" size="sm" @click="toDelete = element" />
                        </div>
                    </li>
                </template>
            </draggable>

            <EmptyState v-else :icon="PaperClipIcon" title="Fayllar yo'q" description="Konspekt, masalalar to'plami yoki taqdimotni yuklang.">
                <Button :icon-left="PlusIcon" @click="showUploader = true">Fayl yuklash</Button>
            </EmptyState>
        </Card>

        <AttachmentUploader v-if="showUploader" :lesson="lesson" @close="showUploader = false" />

        <ConfirmDialog :show="!!toDelete" title="Faylni o'chirasizmi?"
            :description="toDelete ? `«${toDelete.title}» o'chiriladi.` : null"
            :loading="deleting" @confirm="deleteAttachment" @close="toDelete = null" />
    </TeacherLayout>
</template>
