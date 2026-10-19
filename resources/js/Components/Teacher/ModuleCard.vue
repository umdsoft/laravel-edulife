<script setup>
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import {
    EllipsisVerticalIcon, PencilSquareIcon, TrashIcon, PlusIcon, QueueListIcon, ClipboardDocumentCheckIcon,
    ArrowUpIcon, ArrowDownIcon,
} from '@heroicons/vue/24/outline';
import { ChevronDownIcon, Bars2Icon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import LessonCard from './LessonCard.vue';

const props = defineProps({
    module: Object,
    course: Object,
    // position in the course (0-based) and total, for "move up/down" menu items
    index: { type: Number, default: null },
    total: { type: Number, default: null },
});

const emit = defineEmits(['edit', 'delete', 'reorder-lessons', 'add-lesson', 'move']);

const isExpanded = ref(true);
const lessonsList = ref([...(props.module.lessons || [])]);

// Keep the local (draggable) copy in sync with fresh server props
watch(() => props.module.lessons, (lessons) => {
    lessonsList.value = [...(lessons || [])];
});

const onLessonsReorder = () => {
    emit('reorder-lessons', lessonsList.value);
};

const moveLesson = (from, to) => {
    if (to < 0 || to >= lessonsList.value.length) return;
    const next = [...lessonsList.value];
    const [item] = next.splice(from, 1);
    next.splice(to, 0, item);
    lessonsList.value = next;
    onLessonsReorder();
};

const contentId = computed(() => `module-${props.module.id}-lessons`);
const bulkHref = computed(() => route('teacher.courses.modules.bulk-lessons.create', [props.course.id, props.module.id]));
const testHref = computed(() => route('teacher.courses.tests.create', { course: props.course.id, module_id: props.module.id, type: 'module_test' }));
</script>

<template>
    <section class="rounded-lg border border-border bg-surface shadow-xs">
        <!-- Header -->
        <div class="flex items-center gap-2 px-3 py-3 sm:gap-3 sm:px-4">
            <span class="module-handle flex h-8 w-6 shrink-0 cursor-grab touch-none items-center justify-center rounded-sm text-fg-subtle hover:bg-surface-2 hover:text-fg active:cursor-grabbing"
                title="Sudrab tartiblang" aria-hidden="true">
                <Bars2Icon class="h-5 w-5" />
            </span>

            <button type="button" class="focus-ring group flex min-w-0 flex-1 items-center gap-2 rounded-sm text-left"
                :aria-expanded="isExpanded" :aria-controls="contentId" @click="isExpanded = !isExpanded">
                <ChevronDownIcon :class="['h-5 w-5 shrink-0 text-fg-subtle transition-transform duration-150', isExpanded ? '' : '-rotate-90']"
                    aria-hidden="true" />
                <span class="min-w-0">
                    <span class="flex items-center gap-2">
                        <span v-if="index !== null" class="text-13 font-medium tabular-nums text-fg-subtle">{{ index + 1 }}.</span>
                        <span class="truncate text-sm font-semibold text-fg group-hover:text-brand">{{ module.title }}</span>
                    </span>
                    <span class="mt-0.5 flex items-center gap-2 text-13 text-fg-subtle">
                        <span class="tabular-nums">{{ lessonsList.length }} ta dars</span>
                        <Badge v-if="module.is_free" variant="success" size="sm">Bepul</Badge>
                    </span>
                </span>
            </button>

            <Button variant="soft" size="sm" :icon-left="PlusIcon" :aria-label="`${module.title} moduliga dars qo'shish`"
                @click="emit('add-lesson')">Dars</Button>

            <Dropdown align="right">
                <template #trigger>
                    <IconButton :icon="EllipsisVerticalIcon" :label="`${module.title} — amallar`" size="sm" />
                </template>
                <DropdownItem :href="bulkHref" :icon="QueueListIcon">Bir nechta dars qo'shish</DropdownItem>
                <DropdownItem :href="testHref" :icon="ClipboardDocumentCheckIcon">Modul testi yaratish</DropdownItem>
                <DropdownItem :icon="PencilSquareIcon" @click="emit('edit')">Tahrirlash</DropdownItem>
                <template v-if="total > 1">
                    <DropdownItem divider />
                    <DropdownItem :icon="ArrowUpIcon" :disabled="index === 0" @click="emit('move', -1)">Yuqoriga surish</DropdownItem>
                    <DropdownItem :icon="ArrowDownIcon" :disabled="index === total - 1" @click="emit('move', 1)">Pastga surish</DropdownItem>
                </template>
                <DropdownItem divider />
                <DropdownItem danger :icon="TrashIcon" @click="emit('delete')">O'chirish</DropdownItem>
            </Dropdown>
        </div>

        <!-- Lessons -->
        <div v-show="isExpanded" :id="contentId" class="border-t border-border bg-surface-2/40 p-2 sm:p-3">
            <draggable v-model="lessonsList" item-key="id" handle=".lesson-handle" :group="`lessons-${module.id}`"
                :animation="150" ghost-class="opacity-40" class="space-y-1.5" @end="onLessonsReorder">
                <template #item="{ element: lesson, index: i }">
                    <LessonCard :lesson="lesson" :module="module" :course="course" :index="i" :total="lessonsList.length"
                        @move="(dir) => moveLesson(i, i + dir)" />
                </template>
            </draggable>

            <div v-if="!lessonsList.length" class="flex flex-col items-center gap-2 py-5 text-center">
                <p class="text-13 text-fg-subtle">Bu modulda hali dars yo'q</p>
                <Button variant="secondary" size="sm" :icon-left="PlusIcon" @click="emit('add-lesson')">Birinchi darsni qo'shish</Button>
            </div>
            <button v-else type="button"
                class="focus-ring mt-1.5 flex h-10 w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-border-strong text-13 font-medium text-fg-muted transition-colors hover:border-brand-500 hover:bg-surface hover:text-brand"
                @click="emit('add-lesson')">
                <PlusIcon class="h-4 w-4" aria-hidden="true" />
                Dars qo'shish
            </button>
        </div>
    </section>
</template>
