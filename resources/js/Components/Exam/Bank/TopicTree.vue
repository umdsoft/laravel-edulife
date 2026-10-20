<script setup>
/**
 * TopicTree — one level of the topic tree (recursive). Siblings are drag-sorted with the handle;
 * moving to another parent is the "Ko'chirish" action. Actions come from the page via
 * provide('topicActions', { add, rename, move, remove, toggle, reorder }).
 *   <TopicTree :nodes="tree" :parent-id="null" />
 */
import { inject, nextTick, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import {
    ChevronRightIcon, EllipsisVerticalIcon, PlusIcon, PencilSquareIcon, ArrowsRightLeftIcon, TrashIcon, QueueListIcon,
} from '@heroicons/vue/24/outline';
import { Bars2Icon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import Switch from '@/Components/UI/Switch.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';

defineOptions({ name: 'TopicTree' });

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    parentId: { type: String, default: null },
    depth: { type: Number, default: 0 },
});

const actions = inject('topicActions');
const list = ref([...props.nodes]);
watch(() => props.nodes, (n) => (list.value = [...n]));

const open = ref(new Set());
const toggleOpen = (id) => {
    const s = new Set(open.value);
    s.has(id) ? s.delete(id) : s.add(id);
    open.value = s;
};

const renaming = ref(null);
const draft = ref('');
const inputs = ref({});
const startRename = (node) => {
    renaming.value = node.id;
    draft.value = node.name_uz;
    nextTick(() => inputs.value[node.id]?.focus());
};
const commitRename = (node) => {
    const name = draft.value.trim();
    renaming.value = null;
    if (name && name !== node.name_uz) actions.rename(node, name);
};

const onEnd = () => actions.reorder(props.parentId, list.value.map((n) => n.id));
const grades = (n) => (n.grade_from || n.grade_to ? `${n.grade_from ?? '…'}–${n.grade_to ?? '…'}-sinf` : null);
</script>

<template>
    <draggable v-model="list" item-key="id" handle=".topic-handle" :animation="150" ghost-class="opacity-40" tag="ul"
        :class="depth ? 'ml-5 border-l border-border pl-3' : 'divide-y divide-border'" @end="onEnd">
        <template #item="{ element: node }">
            <li class="py-1">
                <div :class="['group flex items-center gap-2 rounded-md px-1.5 py-1.5 hover:bg-surface-2', node.is_active ? '' : 'opacity-60']">
                    <span class="topic-handle flex h-8 w-5 shrink-0 cursor-grab touch-none items-center justify-center text-fg-subtle hover:text-fg active:cursor-grabbing"
                        title="Sudrab tartiblang" aria-hidden="true">
                        <Bars2Icon class="h-4 w-4" />
                    </span>
                    <button v-if="node.children.length" type="button" class="focus-ring flex h-7 w-7 shrink-0 items-center justify-center rounded-sm text-fg-subtle hover:text-fg"
                        :aria-expanded="open.has(node.id)" :aria-label="open.has(node.id) ? 'Yopish' : 'Ochish'" @click="toggleOpen(node.id)">
                        <ChevronRightIcon :class="['h-4 w-4 transition-transform duration-150', open.has(node.id) ? 'rotate-90' : '']" aria-hidden="true" />
                    </button>
                    <span v-else class="w-7 shrink-0" />

                    <div class="min-w-0 flex-1">
                        <input v-if="renaming === node.id" :ref="(el) => (inputs[node.id] = el)" v-model="draft" type="text" maxlength="255"
                            class="focus-ring w-full rounded-sm border border-border-strong bg-surface px-2 py-1 text-sm text-fg"
                            :aria-label="`${node.name_uz} — yangi nom`" @keydown.enter.prevent="commitRename(node)" @keydown.esc="renaming = null"
                            @blur="commitRename(node)" />
                        <button v-else type="button" class="focus-ring block max-w-full truncate rounded-sm text-left text-sm font-medium text-fg"
                            title="Nomini o'zgartirish uchun bosing" @click="startRename(node)">{{ node.name_uz }}</button>
                        <p class="truncate font-mono text-xs text-fg-subtle">{{ node.code }}<span v-if="grades(node)" class="font-sans"> · {{ grades(node) }}</span></p>
                    </div>

                    <Badge :variant="node.questions_approved ? 'success' : 'neutral'" size="sm" class="shrink-0 tabular-nums"
                        :title="`Tasdiqlangan ${node.questions_approved} / jami ${node.questions_total}`">
                        {{ node.questions_approved }} / {{ node.questions_total }}
                    </Badge>
                    <Switch :model-value="node.is_active" size="sm" :aria-label="node.is_active ? 'Faol' : 'Nofaol'" class="shrink-0"
                        @update:model-value="actions.toggle(node, $event)" />
                    <Dropdown align="right">
                        <template #trigger><IconButton :icon="EllipsisVerticalIcon" label="Amallar" size="sm" /></template>
                        <DropdownItem :icon="PlusIcon" @click="actions.add(node)">Ichiga mavzu qo'shish</DropdownItem>
                        <DropdownItem :icon="PencilSquareIcon" @click="startRename(node)">Nomini o'zgartirish</DropdownItem>
                        <DropdownItem :icon="ArrowsRightLeftIcon" @click="actions.move(node)">Ko'chirish</DropdownItem>
                        <DropdownItem :icon="QueueListIcon" :href="route('teacher.exam.questions.index', { topic: node.id, subject: actions.subjectCode() })">Savollari</DropdownItem>
                        <DropdownItem divider />
                        <DropdownItem danger :icon="TrashIcon" @click="actions.remove(node)">O'chirish</DropdownItem>
                    </Dropdown>
                </div>
                <TopicTree v-if="node.children.length && open.has(node.id)" :nodes="node.children" :parent-id="node.id" :depth="depth + 1" />
            </li>
        </template>
    </draggable>
</template>
