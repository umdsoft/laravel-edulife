<script setup>
/**
 * AppShell — shared app frame for Student / Teacher / Admin layouts.
 *
 *  ≥1024px  : sidebar 240px (user can collapse to a 64px icon rail; remembered)
 *  768–1023 : icon rail
 *  <768     : off-canvas drawer (menu button in the top bar) + optional bottom tab bar
 *
 * Props: sections (resolved nav), tabs (resolved bottom-tab items, mobile only),
 *        roleTag, homeHref, title (top-bar title on small screens), fluid (no max width).
 * Slots: topbar (left side, after menu button) · actions (right side, before user menu) ·
 *        user (avatar menu) · drawer-top (extra block on top of the mobile drawer) ·
 *        sidebar-footer · header (page header region above content) · default (page).
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon, ChevronDoubleLeftIcon, ChevronDoubleRightIcon } from '@heroicons/vue/24/outline';
import BrandMark from './BrandMark.vue';
import SidebarNav from './SidebarNav.vue';
import ToastViewport from '@/Components/UI/ToastViewport.vue';
import { useMediaQuery } from '@/Composables/useMediaQuery';
import { isItemActive } from '@/Utils/nav';

const props = defineProps({
    sections: { type: Array, required: true },
    tabs: { type: Array, default: () => [] },
    roleTag: { type: String, default: null },
    homeHref: { type: String, default: '/' },
    title: { type: String, default: null },
    fluid: Boolean,
});

const COLLAPSE_KEY = 'edulife_sidebar_collapsed';
const page = usePage();

const isTablet = useMediaQuery('(min-width: 768px)');
const isDesktop = useMediaQuery('(min-width: 1024px)');

const readCollapsed = () => {
    try {
        return localStorage.getItem(COLLAPSE_KEY) === 'true';
    } catch {
        return false;
    }
};
const collapsed = ref(readCollapsed());
const toggleCollapsed = () => {
    collapsed.value = !collapsed.value;
    try {
        localStorage.setItem(COLLAPSE_KEY, String(collapsed.value));
    } catch {
        /* ignore */
    }
};

const rail = computed(() => isTablet.value && (!isDesktop.value || collapsed.value));
const sidebarWidth = computed(() => (rail.value ? 'w-rail' : 'w-sidebar'));
const contentPad = computed(() => (rail.value ? 'md:pl-rail' : 'md:pl-sidebar'));
const hasTabs = computed(() => props.tabs.length > 0);

/* ---------- Mobile drawer ---------- */
const drawerOpen = ref(false);
const drawerPanel = ref(null);
const menuButton = ref(null);

const openDrawer = () => {
    drawerOpen.value = true;
    nextTick(() => drawerPanel.value?.querySelector('a,button')?.focus());
};
const closeDrawer = (restore = true) => {
    if (!drawerOpen.value) return;
    drawerOpen.value = false;
    if (restore) nextTick(() => menuButton.value?.focus());
};

watch(drawerOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});
watch(isTablet, (wide) => {
    if (wide) closeDrawer(false);
});

const onKeydown = (e) => {
    if (e.key === 'Escape' && drawerOpen.value) closeDrawer();
};

let offNavigate = null;
onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    offNavigate = router.on('navigate', () => closeDrawer(false));
});
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    if (typeof offNavigate === 'function') offNavigate();
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="min-h-screen bg-bg text-fg">
        <a href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-toast focus:rounded-md focus:bg-surface focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-md">
            Asosiy kontentga o'tish
        </a>

        <!-- ============ Sidebar (tablet / desktop) ============ -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-sidebar hidden flex-col border-r border-border bg-surface transition-[width] duration-200 ease-out md:flex',
            sidebarWidth,
        ]">
            <div :class="['flex h-topbar shrink-0 items-center border-b border-border', rail ? 'justify-center px-2' : 'px-5']">
                <Link :href="homeHref" class="focus-ring rounded-md" aria-label="EDULIFE bosh sahifa">
                    <BrandMark :compact="rail" :tag="roleTag" />
                </Link>
            </div>

            <div :class="['scrollbar-thin flex-1 overflow-y-auto py-4', rail ? 'px-2' : 'px-3']">
                <SidebarNav :sections="sections" :rail="rail" />
            </div>

            <div :class="['shrink-0 border-t border-border py-3', rail ? 'px-2' : 'px-3']">
                <slot v-if="!rail" name="sidebar-footer" />
                <button v-if="isDesktop" type="button" :class="[
                    'focus-ring flex h-9 items-center rounded-md text-sm font-medium text-fg-subtle transition-colors hover:bg-surface-2 hover:text-fg',
                    rail ? 'mx-auto w-10 justify-center' : 'w-full gap-3 px-3',
                ]" :aria-label="collapsed ? 'Menyuni kengaytirish' : 'Menyuni yig\'ish'" :aria-expanded="!collapsed"
                    :title="rail ? 'Menyuni kengaytirish' : undefined" @click="toggleCollapsed">
                    <ChevronDoubleRightIcon v-if="collapsed" class="h-5 w-5" aria-hidden="true" />
                    <template v-else>
                        <ChevronDoubleLeftIcon class="h-5 w-5" aria-hidden="true" />
                        <span>Yig'ish</span>
                    </template>
                </button>
            </div>
        </aside>

        <!-- ============ Mobile drawer ============ -->
        <Teleport to="body">
            <transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150 ease-in" leave-to-class="opacity-0">
                <div v-if="drawerOpen" class="fixed inset-0 z-drawer bg-overlay/40 md:hidden" aria-hidden="true"
                    @click="closeDrawer()" />
            </transition>
            <transition enter-active-class="transition-transform duration-200 ease-out" enter-from-class="-translate-x-full"
                leave-active-class="transition-transform duration-150 ease-in" leave-to-class="-translate-x-full">
                <div v-if="drawerOpen" ref="drawerPanel" role="dialog" aria-modal="true" aria-label="Menyu"
                    class="fixed inset-y-0 left-0 z-drawer flex w-[min(20rem,86vw)] flex-col bg-surface shadow-lg md:hidden">
                    <div class="flex h-topbar shrink-0 items-center justify-between border-b border-border px-4">
                        <BrandMark :tag="roleTag" />
                        <button type="button"
                            class="focus-ring inline-flex h-10 w-10 items-center justify-center rounded-md text-fg-subtle hover:bg-surface-2 hover:text-fg"
                            aria-label="Menyuni yopish" @click="closeDrawer()">
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                    <div v-if="$slots['drawer-top']" class="border-b border-border px-4 py-3">
                        <slot name="drawer-top" />
                    </div>
                    <div class="scrollbar-thin flex-1 overflow-y-auto px-3 py-4">
                        <SidebarNav :sections="sections" @navigate="closeDrawer(false)" />
                    </div>
                    <div v-if="$slots['sidebar-footer']" class="border-t border-border px-3 py-3">
                        <slot name="sidebar-footer" />
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- ============ Main column ============ -->
        <div :class="['flex min-h-screen flex-col transition-[padding] duration-200 ease-out', contentPad]">
            <header
                class="sticky top-0 z-topbar flex h-topbar shrink-0 items-center gap-2 border-b border-border bg-surface/90 px-3 backdrop-blur supports-[backdrop-filter]:bg-surface/80 sm:gap-3 sm:px-6">
                <button ref="menuButton" type="button"
                    class="focus-ring inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-fg-muted hover:bg-surface-2 hover:text-fg md:hidden"
                    aria-label="Menyuni ochish" :aria-expanded="drawerOpen" @click="openDrawer">
                    <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                </button>

                <Link :href="homeHref" class="focus-ring shrink-0 rounded-md md:hidden" aria-label="EDULIFE bosh sahifa">
                    <BrandMark compact />
                </Link>
                <p v-if="title" class="min-w-0 truncate text-sm font-semibold text-fg md:hidden">{{ title }}</p>

                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <slot name="topbar" />
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                    <slot name="actions" />
                    <slot name="user" />
                </div>
            </header>

            <main id="main-content" tabindex="-1" :class="['flex-1 focus:outline-none', hasTabs ? 'pb-20 md:pb-0' : '']">
                <div :class="fluid ? 'w-full px-4 py-6 sm:px-6 lg:py-8' : 'page-container'">
                    <slot name="header" />
                    <slot />
                </div>
            </main>
        </div>

        <!-- ============ Mobile bottom tab bar ============ -->
        <nav v-if="hasTabs" aria-label="Tezkor navigatsiya"
            class="pb-safe fixed inset-x-0 bottom-0 z-topbar border-t border-border bg-surface/95 backdrop-blur md:hidden">
            <ul class="grid h-16" :style="{ gridTemplateColumns: `repeat(${tabs.length}, minmax(0, 1fr))` }">
                <li v-for="tab in tabs" :key="tab.href" class="h-full">
                    <Link :href="tab.href" :aria-current="isItemActive(page.url, tab) ? 'page' : undefined" :class="[
                        'focus-ring flex h-full flex-col items-center justify-center gap-1 rounded-md text-[11px] font-medium transition-colors',
                        isItemActive(page.url, tab) ? 'text-brand' : 'text-fg-subtle hover:text-fg',
                    ]">
                        <component :is="isItemActive(page.url, tab) && tab.activeIcon ? tab.activeIcon : tab.icon"
                            class="h-6 w-6" aria-hidden="true" />
                        <span class="max-w-full truncate px-1">{{ tab.short || tab.label }}</span>
                    </Link>
                </li>
            </ul>
        </nav>

        <ToastViewport />
    </div>
</template>
