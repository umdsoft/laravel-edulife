<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    PlayCircleIcon, DocumentTextIcon, ClipboardDocumentCheckIcon, PencilSquareIcon, TrashIcon, EllipsisVerticalIcon,
    PaperClipIcon, ListBulletIcon, ArrowUpIcon, ArrowDownIcon, PlusIcon,
} from '@heroicons/vue/24/outline';
import { Bars2Icon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { LESSON_TYPES } from './format';

const props = defineProps({
    lesson: Object,
    module: Object,
    course: Object,
    index: { type: Number, default: null },
    total: { type: Number, default: null },
});

const emit = defineEmits(['move']);

const confirming = ref(false);
const deleting = ref(false);

const deleteLesson = () => {
    router.delete(route('teacher.lessons.destroy', props.lesson.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirming.value = false;
        },
    });
};

const TYPE_ICONS = { video: PlayCircleIcon, text: DocumentTextIcon, quiz: ClipboardDocumentCheckIcon };
const typeIcon = computed(() => TYPE_ICONS[props.lesson.type] || DocumentTextIcon);
const typeLabel = computed(() => LESSON_TYPES[props.lesson.type] || 'Dars');
const editHref = computed(() => route('teacher.lessons.edit', props.lesson.id));
</script>

<template>
    <div class="group flex items-center gap-2 rounded-md border border-border bg-surface px-2 py-2 transition-shadow duration-150 hover:shadow-xs sm:gap-3 sm:px-3">
        <span class="lesson-handle flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded-sm text-fg-subtle/70 hover:bg-surface-2 hover:text-fg active:cursor-grabbing"
            title="Sudrab tartiblang" aria-hidden="true">
            <Bars2Icon class="h-4 w-4" />
        </span>

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-muted" :title="typeLabel">
            <component :is="typeIcon" class="h-[18px] w-[18px]" aria-hidden="true" />
        </span>

        <div class="min-w-0 flex-1">
            <Link :href="editHref" class="focus-ring block truncate rounded-sm text-sm font-medium text-fg hover:text-brand">
                {{ lesson.title }}
            </Link>
            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-fg-subtle">
                <span>{{ typeLabel }}</span>
                <Badge v-if="lesson.is_free" variant="success" size="sm">Bepul</Badge>
                <Badge v-if="lesson.is_preview" variant="info" size="sm">Ochiq ko'rish</Badge>
            </div>
        </div>

        <IconButton :href="editHref" :icon="PencilSquareIcon" label="Tahrirlash" size="sm" class="hidden sm:inline-flex" />

        <Dropdown align="right">
            <template #trigger>
                <IconButton :icon="EllipsisVerticalIcon" :label="`${lesson.title} — amallar`" size="sm" />
            </template>
            <DropdownItem :href="editHref" :icon="PencilSquareIcon">Tahrirlash</DropdownItem>
            <DropdownItem :href="route('teacher.lessons.attachments.index', lesson.id)" :icon="PaperClipIcon">Fayllar</DropdownItem>
            <DropdownItem v-if="lesson.type === 'video'" :href="route('teacher.lessons.chapters.index', lesson.id)" :icon="ListBulletIcon">
                Video bo'limlari
            </DropdownItem>
            <DropdownItem v-if="lesson.type === 'quiz'"
                :href="route('teacher.courses.tests.create', { course: course.id, lesson_id: lesson.id, module_id: module.id, type: 'lesson_test' })"
                :icon="PlusIcon">
                Dars testi yaratish
            </DropdownItem>
            <template v-if="total > 1">
                <DropdownItem divider />
                <DropdownItem :icon="ArrowUpIcon" :disabled="index === 0" @click="emit('move', -1)">Yuqoriga surish</DropdownItem>
                <DropdownItem :icon="ArrowDownIcon" :disabled="index === total - 1" @click="emit('move', 1)">Pastga surish</DropdownItem>
            </template>
            <DropdownItem divider />
            <DropdownItem danger :icon="TrashIcon" @click="confirming = true">O'chirish</DropdownItem>
        </Dropdown>

        <ConfirmDialog :show="confirming" title="Darsni o'chirasizmi?"
            :description="`«${lesson.title}» va uning video/fayllari o'chiriladi. O'quvchilar progressi bor darsni o'chirib bo'lmaydi.`"
            :loading="deleting" @confirm="deleteLesson" @close="confirming = false" />
    </div>
</template>
