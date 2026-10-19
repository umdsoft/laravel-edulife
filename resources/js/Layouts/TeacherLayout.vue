<script setup>
/**
 * TeacherLayout — app shell for the teacher (ustoz) panel.
 * Usage unchanged:
 *   <TeacherLayout>
 *     <template #header>…title / actions…</template>   (rendered above the page content)
 *     …page…
 *   </TeacherLayout>
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    HomeIcon, BookOpenIcon, UsersIcon, QueueListIcon, ClipboardDocumentCheckIcon, ChatBubbleLeftRightIcon,
    ChartBarIcon, UserCircleIcon, PencilSquareIcon, BellIcon, PlusIcon,
} from '@heroicons/vue/24/outline';
import AppShell from '@/Components/Layout/AppShell.vue';
import UserMenu from '@/Components/Layout/UserMenu.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { resolveNavItems, resolveNavSections, routeHas, routePath } from '@/Utils/nav';
import { useFlashToasts } from '@/Composables/useFlashToasts';

defineProps({
    title: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const unread = computed(() => Number(page.props.auth?.unread_notifications_count || 0));

useFlashToasts();

const BANK_ROUTES = ['teacher.questions.index', 'teacher.question-bank.index'];

const sections = computed(() => {
    const hasBank = BANK_ROUTES.some((r) => routeHas(r));
    // Course sub-pages (tests, lessons, modules…) keep "Kurslar" active
    const courseMatch = ['/teacher/tests', '/teacher/lessons', '/teacher/modules', '/teacher/chapters', '/teacher/announcements', '/teacher/attachments'];
    if (!hasBank) courseMatch.push('/teacher/questions');

    return resolveNavSections([
        {
            items: [
                { label: 'Dashboard', route: 'teacher.dashboard', icon: HomeIcon, exact: true },
                { label: 'Kurslar', route: 'teacher.courses.index', icon: BookOpenIcon, match: courseMatch },
                { label: 'O\'quvchilar', route: 'teacher.students.index', icon: UsersIcon },
                { label: 'Imtihonlar', route: ['teacher.exams.index', 'teacher.saturday-exams.index'], icon: ClipboardDocumentCheckIcon },
                { label: 'Savollar banki', route: BANK_ROUTES, icon: QueueListIcon, match: ['/teacher/questions'] },
                { label: 'Sharhlar', route: 'teacher.reviews.index', icon: ChatBubbleLeftRightIcon },
                { label: 'Statistika', route: 'teacher.analytics.index', icon: ChartBarIcon },
            ],
        },
        {
            title: 'Hisob',
            items: [{ label: 'Profil', route: 'teacher.profile.index', icon: UserCircleIcon }],
        },
    ]);
});

const menuItems = computed(() =>
    resolveNavItems([
        { label: 'Profil', route: 'teacher.profile.index', icon: UserCircleIcon },
        { label: 'Profilni tahrirlash', route: 'teacher.profile.edit', icon: PencilSquareIcon },
    ]),
);

const homeHref = computed(() => routePath('teacher.dashboard') || '/teacher/dashboard');
const notificationsHref = computed(() => routePath('teacher.notifications.index'));
const createCourseHref = computed(() => routePath('teacher.courses.create'));
</script>

<template>
    <AppShell :sections="sections" role-tag="Ustoz" :home-href="homeHref" :title="title">
        <template #actions>
            <Button v-if="createCourseHref" :href="createCourseHref" size="sm" :icon-left="PlusIcon" class="hidden sm:inline-flex">
                Yangi kurs
            </Button>
            <IconButton v-if="notificationsHref" :href="notificationsHref" :icon="BellIcon" label="Bildirishnomalar"
                :badge="unread || null" />
        </template>

        <template #user>
            <UserMenu :user="user" :items="menuItems" role-label="O'qituvchi" />
        </template>

        <template v-if="$slots.header" #header>
            <div class="mb-6">
                <slot name="header" />
            </div>
        </template>

        <slot />
    </AppShell>
</template>
