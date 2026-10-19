<script setup>
/**
 * CourseNav — section tabs shared by every course workspace page
 * (details, curriculum, tests, announcements, Q&A). Put it in PageHeader #tabs.
 *   <CourseNav :course="course" active="curriculum" />
 */
import { computed } from 'vue';
import {
    Cog6ToothIcon, QueueListIcon, ClipboardDocumentCheckIcon, MegaphoneIcon, ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline';
import Tabs from '@/Components/UI/Tabs.vue';
import { routeHas } from '@/Utils/nav';

const props = defineProps({
    course: { type: Object, required: true },
    // details | curriculum | tests | announcements | qna (falls back to URL matching)
    active: { type: String, default: null },
});

const tabs = computed(() => {
    const id = props.course.id;
    return [
        { key: 'details', label: 'Ma\'lumotlar', route: 'teacher.courses.edit', icon: Cog6ToothIcon },
        { key: 'curriculum', label: 'Darslar rejasi', route: 'teacher.courses.modules.index', icon: QueueListIcon },
        { key: 'tests', label: 'Testlar', route: 'teacher.courses.tests.index', icon: ClipboardDocumentCheckIcon },
        { key: 'announcements', label: 'E\'lonlar', route: 'teacher.courses.announcements.index', icon: MegaphoneIcon },
        { key: 'qna', label: 'Savol-javob', route: 'teacher.courses.qna.index', icon: ChatBubbleLeftRightIcon },
    ]
        .filter((t) => routeHas(t.route))
        .map((t) => ({
            key: t.key,
            label: t.label,
            icon: t.icon,
            href: route(t.route, id),
            active: props.active ? props.active === t.key : undefined,
        }));
});
</script>

<template>
    <Tabs :tabs="tabs" label="Kurs bo'limlari" />
</template>
