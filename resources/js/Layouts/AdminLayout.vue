<script setup>
/**
 * AdminLayout — app shell for the admin panel with a grouped sidebar.
 * Usage unchanged: <AdminLayout> …page… </AdminLayout>. Flash messages → toasts.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    HomeIcon, DocumentChartBarIcon, BookOpenIcon, FolderIcon, QuestionMarkCircleIcon, TagIcon,
    ChatBubbleLeftRightIcon, TrophyIcon, DocumentCheckIcon, RectangleGroupIcon, UsersIcon, AcademicCapIcon,
    StarIcon, CheckBadgeIcon, ChartBarIcon, CircleStackIcon, CreditCardIcon, ArrowPathRoundedSquareIcon,
    Squares2X2Icon, TicketIcon, BellIcon, ServerStackIcon, Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
import AppShell from '@/Components/Layout/AppShell.vue';
import UserMenu from '@/Components/Layout/UserMenu.vue';
import { resolveNavItems, resolveNavSections, routePath } from '@/Utils/nav';
import { useFlashToasts } from '@/Composables/useFlashToasts';

defineProps({
    title: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

useFlashToasts();

const sections = computed(() =>
    resolveNavSections([
        {
            title: 'Asosiy',
            items: [
                { label: 'Dashboard', route: 'admin.dashboard', icon: HomeIcon, exact: true },
                { label: 'Hisobotlar', route: 'admin.reports.index', icon: DocumentChartBarIcon },
            ],
        },
        {
            title: 'Ta\'lim',
            items: [
                { label: 'Kurslar', route: 'admin.courses.index', icon: BookOpenIcon },
                { label: 'Yo\'nalishlar', route: 'admin.directions.index', icon: FolderIcon },
                { label: 'Savollar', route: 'admin.questions.index', icon: QuestionMarkCircleIcon },
                { label: 'Teglar', route: 'admin.tags.index', icon: TagIcon },
                { label: 'Sharhlar', route: 'admin.reviews.index', icon: ChatBubbleLeftRightIcon },
                { label: 'Olimpiadalar', route: 'admin.olympiads.index', icon: TrophyIcon },
                { label: 'Sertifikatlar', route: 'admin.certificates.index', icon: DocumentCheckIcon },
                { label: 'Sertifikat shablonlari', route: 'admin.certificate-templates.index', icon: RectangleGroupIcon },
            ],
        },
        {
            title: 'Foydalanuvchilar',
            items: [
                { label: 'Foydalanuvchilar', route: 'admin.users.index', icon: UsersIcon },
                { label: 'O\'qituvchilar', route: 'admin.teachers.index', icon: AcademicCapIcon },
            ],
        },
        {
            title: 'Gamifikatsiya',
            items: [
                { label: 'Yutuqlar', route: 'admin.achievements.index', icon: StarIcon },
                { label: 'Kunlik vazifalar', route: 'admin.daily-missions.index', icon: CheckBadgeIcon },
                { label: 'Reyting', route: 'admin.leaderboard.index', icon: ChartBarIcon },
                { label: 'COIN paketlari', route: 'admin.coin-packages.index', icon: CircleStackIcon },
            ],
        },
        {
            title: 'Moliya',
            items: [
                { label: 'To\'lovlar', route: 'admin.payments.index', icon: CreditCardIcon },
                { label: 'Obunalar', route: 'admin.subscriptions.index', icon: ArrowPathRoundedSquareIcon },
                { label: 'Tarif rejalari', route: 'admin.subscriptions.plans', icon: Squares2X2Icon },
                { label: 'Promo kodlar', route: 'admin.promo-codes.index', icon: TicketIcon },
            ],
        },
        {
            title: 'Tizim',
            items: [
                { label: 'Xabarnomalar', route: 'admin.notifications.index', icon: BellIcon },
                { label: 'Tizim loglari', route: 'admin.system-logs.index', icon: ServerStackIcon },
                { label: 'Sozlamalar', route: 'admin.settings.index', icon: Cog6ToothIcon },
            ],
        },
    ]),
);

const menuItems = computed(() =>
    resolveNavItems([{ label: 'Sozlamalar', route: 'admin.settings.index', icon: Cog6ToothIcon }]),
);

const homeHref = computed(() => routePath('admin.dashboard') || '/admin/dashboard');
const roleLabel = computed(() => (user.value?.role === 'super_admin' ? 'Super admin' : 'Admin'));
</script>

<template>
    <AppShell :sections="sections" role-tag="Admin" :home-href="homeHref" :title="title">
        <template #user>
            <UserMenu :user="user" :items="menuItems" :role-label="roleLabel" show-name />
        </template>

        <slot />
    </AppShell>
</template>
