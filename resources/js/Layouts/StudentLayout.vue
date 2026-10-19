<script setup>
/**
 * StudentLayout — app shell for the student panel.
 * Usage unchanged: <StudentLayout> …page… </StudentLayout>  (optional `title` shown in the mobile top bar).
 * Nav items only render when their Ziggy route exists.
 */
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon, ClipboardDocumentCheckIcon, BookOpenIcon, AcademicCapIcon, BeakerIcon, TrophyIcon, BoltIcon,
    FlagIcon, ChartBarIcon, CheckBadgeIcon, StarIcon, ShoppingBagIcon, DocumentCheckIcon, UserCircleIcon,
    HeartIcon, UsersIcon, Cog6ToothIcon, LifebuoyIcon, SparklesIcon, BellIcon, MagnifyingGlassIcon, FireIcon,
    CircleStackIcon,
} from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeSolid, BookOpenIcon as BookOpenSolid, BeakerIcon as BeakerSolid,
    ChartBarIcon as ChartBarSolid, UserCircleIcon as UserCircleSolid, ClipboardDocumentCheckIcon as ClipboardSolid,
} from '@heroicons/vue/24/solid';
import AppShell from '@/Components/Layout/AppShell.vue';
import UserMenu from '@/Components/Layout/UserMenu.vue';
import TopbarSearch from '@/Components/Layout/TopbarSearch.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import { resolveNavItems, resolveNavSections, routePath } from '@/Utils/nav';
import { useFlashToasts } from '@/Composables/useFlashToasts';

defineProps({
    // Page title (shown in the mobile top bar). Previously fell through as an HTML attribute.
    title: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const studentProfile = computed(() => page.props.auth?.studentProfile);
const unread = computed(() => Number(page.props.auth?.unread_notifications_count || 0));

useFlashToasts();

const EXAM_ROUTES = ['student.exams.index', 'student.exam.index', 'student.saturday-exams.index'];

const sections = computed(() =>
    resolveNavSections([
        {
            title: 'O\'qish',
            items: [
                { label: 'Bosh sahifa', route: 'student.dashboard', icon: HomeIcon, exact: true },
                { label: 'Imtihonlar', route: EXAM_ROUTES, icon: ClipboardDocumentCheckIcon },
                { label: 'Kurslarim', route: 'student.my-courses.index', icon: BookOpenIcon, match: ['/student/learn'] },
                { label: 'Kurslar', route: 'student.courses.index', icon: AcademicCapIcon },
                { label: 'Laboratoriya', route: 'student.lab.index', icon: BeakerIcon },
            ],
        },
        {
            title: 'Musobaqalar',
            items: [
                { label: 'Olimpiada', route: 'student.olympiads.index', icon: TrophyIcon, match: ['/student/olympiad-history', '/student/olympiad-certificates'] },
                { label: 'Battle', route: 'student.battles.index', icon: BoltIcon },
                { label: 'Turnirlar', route: 'student.tournaments.index', icon: FlagIcon },
                { label: 'Reyting', route: 'student.leaderboard.index', icon: ChartBarIcon },
            ],
        },
        {
            title: 'Yutuqlar',
            items: [
                { label: 'Topshiriqlar', route: 'student.missions.index', icon: CheckBadgeIcon },
                { label: 'Yutuqlar', route: 'student.achievements.index', icon: StarIcon },
                { label: 'Coin do\'kon', route: 'student.shop.index', icon: ShoppingBagIcon },
                { label: 'Sertifikatlar', route: 'student.certificates.index', icon: DocumentCheckIcon },
            ],
        },
    ]),
);

// Bottom tab bar (mobile): 4 key items + Profil
const tabs = computed(() => {
    const main = resolveNavItems([
        { label: 'Bosh sahifa', short: 'Bosh', route: 'student.dashboard', icon: HomeIcon, activeIcon: HomeSolid, exact: true },
        { label: 'Kurslarim', short: 'Kurslar', route: 'student.my-courses.index', icon: BookOpenIcon, activeIcon: BookOpenSolid, match: ['/student/learn'] },
        { label: 'Imtihonlar', short: 'Imtihon', route: EXAM_ROUTES, icon: ClipboardDocumentCheckIcon, activeIcon: ClipboardSolid },
        { label: 'Laboratoriya', short: 'Lab', route: 'student.lab.index', icon: BeakerIcon, activeIcon: BeakerSolid },
        { label: 'Reyting', route: 'student.leaderboard.index', icon: ChartBarIcon, activeIcon: ChartBarSolid },
    ]).slice(0, 4);
    const profile = resolveNavItems([
        { label: 'Profil', route: 'student.profile.show', icon: UserCircleIcon, activeIcon: UserCircleSolid },
    ]);
    return [...main, ...profile];
});

const menuItems = computed(() =>
    resolveNavItems([
        { label: 'Profil', route: 'student.profile.show', icon: UserCircleIcon },
        { label: 'XP va daraja', route: 'student.xp.index', icon: SparklesIcon },
        { label: 'Istaklar ro\'yxati', route: 'student.wishlist.index', icon: HeartIcon },
        { label: 'Do\'stlar', route: 'student.friends.index', icon: UsersIcon },
        { label: 'Sozlamalar', route: 'student.settings.index', icon: Cog6ToothIcon },
        { label: 'Yordam', route: 'student.support.index', icon: LifebuoyIcon },
    ]),
);

const homeHref = computed(() => routePath('student.dashboard') || '/student/dashboard');
const searchHref = computed(() => routePath('student.search.index'));
const notificationsHref = computed(() => routePath('student.notifications.index'));
const xpHref = computed(() => routePath('student.xp.index'));
const coinsHref = computed(() => routePath('student.coins.index') || routePath('student.shop.index'));

const fmt = (n) => new Intl.NumberFormat('uz-UZ').format(Number(n) || 0);
const stats = computed(() => ({
    level: studentProfile.value?.level ?? user.value?.level ?? 1,
    xp: studentProfile.value?.xp ?? user.value?.xp_total ?? 0,
    coins: studentProfile.value?.coins ?? user.value?.coin_balance ?? 0,
    streak: studentProfile.value?.streak_days ?? 0,
    progress: Number(studentProfile.value?.level_progress ?? 0),
}));
const chip = 'focus-ring inline-flex h-8 items-center gap-1.5 rounded-full border border-border bg-surface px-2.5 text-13 font-medium text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg tabular-nums';
</script>

<template>
    <AppShell :sections="sections" :tabs="tabs" :home-href="homeHref" :title="title">
        <template #topbar>
            <TopbarSearch v-if="searchHref" :action="searchHref" placeholder="Kurslarni izlash…" class="hidden md:block" />
        </template>

        <template #actions>
            <!-- Compact gamification stats -->
            <div class="mr-1 hidden items-center gap-1.5 sm:flex">
                <component :is="xpHref ? Link : 'span'" :href="xpHref || undefined" :class="chip"
                    :title="`Daraja ${stats.level} · ${fmt(stats.xp)} XP`">
                    <SparklesIcon class="h-4 w-4 text-brand" aria-hidden="true" />
                    <span class="text-fg">{{ stats.level }}</span>
                    <span class="hidden text-fg-subtle xl:inline">· {{ fmt(stats.xp) }} XP</span>
                    <span class="sr-only">daraja</span>
                </component>
                <component :is="xpHref ? Link : 'span'" :href="xpHref || undefined" :class="chip"
                    :title="`${stats.streak} kunlik seriya`">
                    <FireIcon class="h-4 w-4 text-warning" aria-hidden="true" />
                    <span class="text-fg">{{ stats.streak }}</span>
                    <span class="sr-only">kunlik seriya</span>
                </component>
                <component :is="coinsHref ? Link : 'span'" :href="coinsHref || undefined" :class="chip"
                    :title="`${fmt(stats.coins)} coin`">
                    <CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />
                    <span class="text-fg">{{ fmt(stats.coins) }}</span>
                    <span class="sr-only">coin</span>
                </component>
            </div>

            <IconButton v-if="searchHref" :href="searchHref" :icon="MagnifyingGlassIcon" label="Qidirish" class="md:hidden" />
            <IconButton v-if="notificationsHref" :href="notificationsHref" :icon="BellIcon" label="Bildirishnomalar"
                :badge="unread || null" />
        </template>

        <template #user>
            <UserMenu :user="user" :items="menuItems" role-label="O'quvchi">
                <template #extra>
                    <div class="mt-3">
                        <ProgressBar :value="stats.progress" size="xs" :label="`Daraja ${stats.level}`" show-value />
                        <p class="mt-2 flex gap-3 text-xs text-fg-subtle sm:hidden">
                            <span class="tabular-nums">{{ fmt(stats.xp) }} XP</span>
                            <span class="tabular-nums">{{ stats.streak }} kun seriya</span>
                            <span class="tabular-nums">{{ fmt(stats.coins) }} coin</span>
                        </p>
                    </div>
                </template>
            </UserMenu>
        </template>

        <template #drawer-top>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-md bg-surface-2 px-2 py-2">
                    <p class="text-sm font-semibold text-fg tabular-nums">{{ stats.level }}</p>
                    <p class="text-[11px] text-fg-subtle">Daraja</p>
                </div>
                <div class="rounded-md bg-surface-2 px-2 py-2">
                    <p class="text-sm font-semibold text-fg tabular-nums">{{ stats.streak }}</p>
                    <p class="text-[11px] text-fg-subtle">Seriya</p>
                </div>
                <div class="rounded-md bg-surface-2 px-2 py-2">
                    <p class="text-sm font-semibold text-fg tabular-nums">{{ fmt(stats.coins) }}</p>
                    <p class="text-[11px] text-fg-subtle">Coin</p>
                </div>
            </div>
        </template>

        <slot />
    </AppShell>
</template>
