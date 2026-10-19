<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PlusIcon, ClipboardDocumentCheckIcon, EllipsisVerticalIcon, PencilSquareIcon, TrashIcon, QueueListIcon,
} from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Table from '@/Components/UI/Table.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import CourseNav from '@/Components/Teacher/CourseNav.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';

const props = defineProps({
    course: Object,
    tests: Object, // Grouped by type
    modules: Array,
});

// Groups arrive keyed by the DB enum (lesson / module / final); older data may use *_test
const GROUPS = {
    lesson: { label: 'Dars testlari', order: 1 },
    lesson_test: { label: 'Dars testlari', order: 1 },
    module: { label: 'Modul testlari', order: 2 },
    module_test: { label: 'Modul testlari', order: 2 },
    final: { label: 'Yakuniy testlar', order: 3 },
    final_test: { label: 'Yakuniy testlar', order: 3 },
};
const groups = computed(() => Object.entries(props.tests || {})
    .map(([type, items]) => ({ type, items, ...(GROUPS[type] || { label: type, order: 9 }) }))
    .sort((a, b) => a.order - b.order));

const columns = [
    { key: 'title', label: 'Nomi' },
    { key: 'testable', label: 'Bog\'langan', hideBelow: 'md' },
    { key: 'questions_count', label: 'Savollar', numeric: true },
    { key: 'is_active', label: 'Faol', width: '1%' },
    { key: 'actions', label: '', align: 'right', width: '1%' },
];

const toggling = ref(null);
const toggleStatus = (test) => {
    router.post(route('teacher.courses.tests.toggle-status', [props.course.id, test.id]), {}, {
        preserveScroll: true,
        onStart: () => (toggling.value = test.id),
        onFinish: () => (toggling.value = null),
    });
};

const toDelete = ref(null);
const deleting = ref(false);
const deleteTest = () => {
    router.delete(route('teacher.courses.tests.destroy', [props.course.id, toDelete.value.id]), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`${course.title} — Testlar`" />

    <TeacherLayout>
        <PageHeader title="Testlar" description="Dars, modul va yakuniy testlar. Savolsiz test o'quvchilarga ko'rinmaydi — avval savollar qo'shing, so'ng faollashtiring."
            :breadcrumbs="[{ label: 'Kurslarim', href: route('teacher.courses.index') }, { label: course.title, href: route('teacher.courses.edit', course.id) }, { label: 'Testlar' }]">
            <template #actions>
                <Button :href="route('teacher.courses.tests.create', course.id)" :icon-left="PlusIcon">Yangi test</Button>
            </template>
            <template #tabs>
                <CourseNav :course="course" active="tests" />
            </template>
        </PageHeader>

        <div v-if="groups.length" class="space-y-6">
            <Card v-for="group in groups" :key="group.type" :title="group.label" :description="`${group.items.length} ta test`" padding="none">
                <Table :columns="columns" :rows="group.items" :bordered="false">
                    <template #cell-title="{ row }">
                        <Link :href="route('teacher.tests.questions.index', row.id)" class="focus-ring rounded-sm font-medium text-fg hover:text-brand">
                            {{ row.title }}
                        </Link>
                        <p v-if="row.description" class="mt-0.5 line-clamp-1 max-w-md text-13 text-fg-subtle">{{ row.description }}</p>
                    </template>
                    <template #cell-testable="{ row }">
                        <span class="text-fg-muted">{{ row.testable?.title || 'Butun kurs' }}</span>
                    </template>
                    <template #cell-questions_count="{ value }">
                        <span :class="value ? 'text-fg' : 'text-warning'">{{ value || 0 }}</span>
                    </template>
                    <template #cell-is_active="{ row }">
                        <button type="button" role="switch" :aria-checked="!!row.is_active" :aria-label="`${row.title} — faol`"
                            :disabled="toggling === row.id" :class="[
                                'focus-ring relative inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5 transition-colors duration-150 disabled:opacity-50',
                                row.is_active ? 'bg-brand-600' : 'bg-surface-3',
                            ]" @click="toggleStatus(row)">
                            <span aria-hidden="true" :class="['inline-block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-150',
                                row.is_active ? 'translate-x-4' : 'translate-x-0']" />
                        </button>
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <Button :href="route('teacher.tests.questions.index', row.id)" variant="secondary" size="sm" :icon-left="QueueListIcon"
                                class="hidden sm:inline-flex">Savollar</Button>
                            <Dropdown align="right">
                                <template #trigger>
                                    <IconButton :icon="EllipsisVerticalIcon" :label="`${row.title} — amallar`" size="sm" />
                                </template>
                                <DropdownItem :href="route('teacher.tests.questions.index', row.id)" :icon="QueueListIcon">Savollar</DropdownItem>
                                <DropdownItem :href="route('teacher.courses.tests.edit', [course.id, row.id])" :icon="PencilSquareIcon">Sozlamalar</DropdownItem>
                                <DropdownItem divider />
                                <DropdownItem danger :icon="TrashIcon" @click="toDelete = row">O'chirish</DropdownItem>
                            </Dropdown>
                        </div>
                    </template>
                </Table>
            </Card>
        </div>

        <Card v-else>
            <EmptyState :icon="ClipboardDocumentCheckIcon" size="lg" title="Hali test yo'q"
                description="Dars yoki modul oxirida bilimni tekshirish uchun test yarating.">
                <Button :href="route('teacher.courses.tests.create', course.id)" :icon-left="PlusIcon">Birinchi testni yaratish</Button>
            </EmptyState>
        </Card>

        <ConfirmDialog :show="!!toDelete" title="Testni o'chirasizmi?"
            :description="toDelete ? `«${toDelete.title}» va uning barcha savollari o'chiriladi.` : null"
            :loading="deleting" @confirm="deleteTest" @close="toDelete = null" />
    </TeacherLayout>
</template>
